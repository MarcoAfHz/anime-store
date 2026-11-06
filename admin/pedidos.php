<?php
$titulo_pagina = 'Pedidos — Admin';
$css_extra     = 'admin';
require_once '../includes/header.php';
requiere_admin();

$conn = conectar();

// Filtros
$estado   = sanitizar($_GET['estado'] ?? '');
$q        = sanitizar($_GET['q']      ?? '');
$id_ver   = (int) ($_GET['id']        ?? 0);

$where = ['1=1'];
if (!empty($estado)) $where[] = "p.estado = '" . escapar($estado) . "'";
if (!empty($q))      $where[] = "(u.nombre LIKE '%" . escapar($q) . "%' OR c.codigo LIKE '%" . escapar($q) . "%')";
$where_sql = implode(' AND ', $where);

$pedidos = $conn->query("
    SELECT p.*, u.nombre AS nombre_usuario, u.telefono, u.email,
           c.codigo AS comprobante
    FROM pedidos p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    LEFT JOIN comprobantes c ON c.id_pedido = p.id_pedido
    WHERE $where_sql
    ORDER BY p.fecha DESC
");

// Ver pedido específico
$pedido_detalle = null;
if ($id_ver > 0) {
    $pedido_detalle = $conn->query("
        SELECT p.*, u.nombre AS nombre_usuario, u.telefono, u.email,
               c.codigo AS comprobante, pg.estado AS pago_estado, pg.yape_qr
        FROM pedidos p
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        LEFT JOIN comprobantes c ON c.id_pedido = p.id_pedido
        LEFT JOIN pagos pg ON pg.id_pedido = p.id_pedido
        WHERE p.id_pedido = $id_ver
    ")->fetch_assoc();

    $items_detalle = $conn->query("
        SELECT dp.*, pr.nombre,
               (SELECT url FROM imagenes_producto WHERE id_producto = dp.id_producto AND principal = 1 LIMIT 1) AS imagen
        FROM detalle_pedido dp
        JOIN productos pr ON dp.id_producto = pr.id_producto
        WHERE dp.id_pedido = $id_ver
    ");
}

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    csrf_validar($_POST['csrf_token'] ?? '');
    $accion    = $_POST['accion'];
    $id_pedido = (int) ($_POST['id_pedido'] ?? 0);

    if ($accion === 'cambiar_estado' && $id_pedido > 0) {
        $nuevo_estado = sanitizar($_POST['nuevo_estado'] ?? '');
        $estados_validos = ['pendiente','confirmado','enviado','entregado','cancelado'];
        $estado_actual   = $conn->query("SELECT estado FROM pedidos WHERE id_pedido = $id_pedido")->fetch_assoc()['estado'] ?? null;
        if (in_array($nuevo_estado, $estados_validos) && $estado_actual !== null && $estado_actual !== $nuevo_estado) {
            $conn->begin_transaction();
            $conn->query("UPDATE pedidos SET estado = '$nuevo_estado' WHERE id_pedido = $id_pedido");

            // Cancelar devuelve el stock; reactivar un cancelado lo vuelve a descontar
            if ($nuevo_estado === 'cancelado' || $estado_actual === 'cancelado') {
                $signo = $nuevo_estado === 'cancelado' ? '+' : '-';
                $lineas = $conn->query("SELECT id_producto, cantidad, talla FROM detalle_pedido WHERE id_pedido = $id_pedido");
                while ($l = $lineas->fetch_assoc()) {
                    $conn->query("UPDATE productos SET stock = GREATEST(0, stock $signo {$l['cantidad']}) WHERE id_producto = {$l['id_producto']}");
                    if (!empty($l['talla'])) {
                        $talla_esc = escapar($l['talla']);
                        $conn->query("UPDATE producto_tallas SET stock = GREATEST(0, CAST(stock AS SIGNED) $signo {$l['cantidad']}) WHERE id_producto = {$l['id_producto']} AND talla = '$talla_esc'");
                    }
                }
                if ($nuevo_estado === 'cancelado') {
                    $conn->query("UPDATE pagos SET estado = 'rechazado' WHERE id_pedido = $id_pedido AND estado = 'pendiente'");
                }
            }
            // Si confirma el pedido, confirmar también el pago
            if ($nuevo_estado === 'confirmado') {
                $admin_id = (int) $_SESSION['usuario']['id_usuario'];
                $conn->query("UPDATE pagos SET estado = 'confirmado', confirmado_por = $admin_id, fecha_pago = NOW() WHERE id_pedido = $id_pedido");
            }
            $conn->commit();
            flash_set('success', 'Estado actualizado correctamente.');
        }
    }
    redirigir("admin/pedidos?id=$id_pedido");
}
?>

<div class="sp-admin-wrap">
    <?php include ROOT_PATH . '/admin/includes/sidebar.php'; ?>

    <div class="sp-admin-content">
        <div class="sp-admin-header">
            <h1 class="sp-admin-titulo">Pedidos</h1>
        </div>

        <?php if ($pedido_detalle): ?>
        <!-- ── DETALLE PEDIDO ──────────────────────────── -->
        <a href="<?= BASE_URL ?>/admin/pedidos" class="sp-btn-outline" style="margin-bottom:20px;display:inline-flex">
            <i class="bi bi-arrow-left"></i> Volver
        </a>

        <div class="sp-admin-grid" style="grid-template-columns:1fr 340px">

            <div class="sp-admin-card">
                <div class="sp-admin-card-header">
                    <h3><?= sanitizar($pedido_detalle['comprobante'] ?? '#'.$id_ver) ?></h3>
                    <span class="sp-estado sp-estado-<?= $pedido_detalle['estado'] ?>"><?= ucfirst($pedido_detalle['estado']) ?></span>
                </div>

                <!-- Productos -->
                <?php while ($item = $items_detalle->fetch_assoc()): ?>
                <div class="sp-checkout-item">
                    <div class="sp-checkout-item-img">
                        <img src="<?= imagen_producto($item['imagen']) ?>" alt="<?= sanitizar($item['nombre']) ?>">
                        <span class="sp-checkout-qty"><?= $item['cantidad'] ?></span>
                    </div>
                    <div class="sp-checkout-item-info">
                        <p><?= sanitizar($item['nombre']) ?></p>
                        <?php if (!empty($item['talla'])): ?>
                        <small>Talla: <?= sanitizar($item['talla']) ?></small>
                        <?php endif; ?>
                    </div>
                    <span class="sp-checkout-item-precio"><?= formatear_precio($item['precio_unit'] * $item['cantidad']) ?></span>
                </div>
                <?php endwhile; ?>

                <div class="sp-resumen-divider"></div>
                <div class="sp-resumen-linea"><span>Subtotal</span><span><?= formatear_precio($pedido_detalle['subtotal']) ?></span></div>
                <div class="sp-resumen-linea"><span>Envío</span><span><?= formatear_precio($pedido_detalle['costo_envio']) ?></span></div>
                <?php if ($pedido_detalle['descuento'] > 0): ?>
                <div class="sp-resumen-linea"><span>Descuento</span><span>-<?= formatear_precio($pedido_detalle['descuento']) ?></span></div>
                <?php endif; ?>
                <div class="sp-resumen-linea sp-resumen-total"><span>Total</span><span><?= formatear_precio($pedido_detalle['total']) ?></span></div>
            </div>

            <div>
                <!-- Info cliente -->
                <div class="sp-admin-card" style="margin-bottom:16px">
                    <h4 class="sp-filtro-titulo">Cliente</h4>
                    <p style="font-size:14px;color:var(--text-secondary);margin-bottom:6px">
                        <i class="bi bi-person" style="color:var(--accent)"></i> <?= sanitizar($pedido_detalle['nombre_usuario']) ?>
                    </p>
                    <p style="font-size:14px;color:var(--text-secondary);margin-bottom:6px">
                        <i class="bi bi-envelope" style="color:var(--accent)"></i> <?= sanitizar($pedido_detalle['email']) ?>
                    </p>
                    <p style="font-size:14px;color:var(--text-secondary);margin-bottom:6px">
                        <i class="bi bi-phone" style="color:var(--accent)"></i> <?= sanitizar($pedido_detalle['telefono'] ?? 'N/A') ?>
                    </p>
                    <p style="font-size:14px;color:var(--text-secondary)">
                        <i class="bi bi-geo-alt" style="color:var(--accent)"></i> <?= sanitizar($pedido_detalle['dir_envio']) ?>
                    </p>
                </div>

                <!-- Pago -->
                <div class="sp-admin-card" style="margin-bottom:16px">
                    <h4 class="sp-filtro-titulo">Pago</h4>
                    <p style="font-size:14px;color:var(--text-secondary);margin-bottom:6px">
                        <i class="bi bi-<?= $pedido_detalle['metodo_pago'] === 'yape' ? 'qr-code' : 'cash' ?>" style="color:var(--accent)"></i>
                        <?= $pedido_detalle['metodo_pago'] === 'yape' ? 'Yape QR' : 'Efectivo' ?>
                    </p>
                    <span class="sp-estado sp-estado-<?= $pedido_detalle['pago_estado'] ?? 'pendiente' ?>">
                        Pago: <?= ucfirst($pedido_detalle['pago_estado'] ?? 'pendiente') ?>
                    </span>
                    <?php if ($pedido_detalle['metodo_pago'] === 'yape'): ?>
                        <?php if (!empty($pedido_detalle['yape_qr'])): ?>
                        <p style="font-size:12px;color:var(--text-muted);margin:14px 0 6px">Comprobante enviado por el cliente:</p>
                        <a href="<?= YAPE_URL . rawurlencode($pedido_detalle['yape_qr']) ?>" target="_blank">
                            <img src="<?= YAPE_URL . rawurlencode($pedido_detalle['yape_qr']) ?>" alt="Comprobante Yape"
                                 style="width:100%;max-height:320px;object-fit:contain;border-radius:var(--radius-md);border:1px solid var(--border);background:var(--bg-secondary)">
                        </a>
                        <?php else: ?>
                        <p style="font-size:12px;color:var(--text-muted);margin-top:12px">
                            <i class="bi bi-hourglass-split"></i> El cliente aún no ha subido el comprobante de Yape.
                        </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Cambiar estado -->
                <div class="sp-admin-card">
                    <h4 class="sp-filtro-titulo">Cambiar estado</h4>
                    <form method="POST">
                        <input type="hidden" name="accion" value="cambiar_estado">
                                        <?= csrf_campo() ?>
                        <input type="hidden" name="id_pedido" value="<?= $id_ver ?>">
                        <div class="sp-form-group" style="margin-bottom:12px">
                            <select name="nuevo_estado" class="sp-admin-form" style="padding:10px;width:100%">
                                <?php foreach (['pendiente','confirmado','enviado','entregado','cancelado'] as $est): ?>
                                <option value="<?= $est ?>" <?= $pedido_detalle['estado'] === $est ? 'selected' : '' ?>>
                                    <?= ucfirst($est) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="sp-btn-primary w-100">
                            <i class="bi bi-check-circle"></i> Actualizar estado
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- ── LISTA PEDIDOS ──────────────────────────── -->

        <!-- Filtros -->
        <div class="sp-admin-filtros">
            <form method="GET" action="<?= BASE_URL ?>/admin/pedidos" style="display:flex;gap:10px;flex-wrap:wrap">
                <input type="text" name="q" placeholder="Buscar cliente o código..." value="<?= $q ?>" class="sp-admin-search">
                <select name="estado" onchange="this.form.submit()" class="sp-admin-select">
                    <option value="">Todos los estados</option>
                    <?php foreach (['pendiente','confirmado','enviado','entregado','cancelado'] as $est): ?>
                    <option value="<?= $est ?>" <?= $estado === $est ? 'selected' : '' ?>><?= ucfirst($est) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="sp-btn-primary"><i class="bi bi-search"></i></button>
                <?php if (!empty($q) || !empty($estado)): ?>
                <a href="<?= BASE_URL ?>/admin/pedidos" class="sp-btn-outline">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="sp-admin-card">
            <div class="sp-tabla-wrap">
                <table class="sp-tabla">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Pago</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pedidos->num_rows === 0): ?>
                        <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:32px">No hay pedidos</td></tr>
                        <?php else: ?>
                        <?php while ($p = $pedidos->fetch_assoc()): ?>
                        <tr>
                            <td><span class="sp-codigo"><?= sanitizar($p['comprobante'] ?? '#'.$p['id_pedido']) ?></span></td>
                            <td><?= sanitizar($p['nombre_usuario']) ?></td>
                            <td><?= formatear_fecha($p['fecha']) ?></td>
                            <td><?= formatear_precio($p['total']) ?></td>
                            <td>
                                <span class="sp-badge-pago">
                                    <i class="bi bi-<?= $p['metodo_pago'] === 'yape' ? 'qr-code' : 'cash' ?>"></i>
                                    <?= $p['metodo_pago'] === 'yape' ? 'Yape' : 'Efectivo' ?>
                                </span>
                            </td>
                            <td><span class="sp-estado sp-estado-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/pedidos?id=<?= $p['id_pedido'] ?>" class="sp-admin-btn-sm">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>