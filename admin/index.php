<?php
$titulo_pagina = 'Panel Admin';
$css_extra     = 'admin';
require_once '../includes/header.php';
requiere_admin();

$conn = conectar();

// Estadísticas
$total_productos  = $conn->query("SELECT COUNT(*) as t FROM productos WHERE activo = 1")->fetch_assoc()['t'];
$total_usuarios   = $conn->query("SELECT COUNT(*) as t FROM usuarios WHERE rol = 'cliente'")->fetch_assoc()['t'];
$total_pedidos    = $conn->query("SELECT COUNT(*) as t FROM pedidos")->fetch_assoc()['t'];
$pedidos_pend     = $conn->query("SELECT COUNT(*) as t FROM pedidos WHERE estado = 'pendiente'")->fetch_assoc()['t'];
$ingresos         = $conn->query("SELECT COALESCE(SUM(total),0) as t FROM pedidos WHERE estado != 'cancelado'")->fetch_assoc()['t'];

// Pedidos recientes
$pedidos_recientes = $conn->query("
    SELECT p.*, u.nombre AS nombre_usuario, c.codigo AS comprobante
    FROM pedidos p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    LEFT JOIN comprobantes c ON c.id_pedido = p.id_pedido
    ORDER BY p.fecha DESC
    LIMIT 8
");

// Productos con poco stock
$stock_bajo = $conn->query("
    SELECT id_producto, nombre, stock FROM productos
    WHERE activo = 1 AND stock <= 5
    ORDER BY stock ASC
    LIMIT 5
");
?>

<div class="sp-admin-wrap">

    <!-- Sidebar admin -->
    <?php include ROOT_PATH . '/admin/includes/sidebar.php'; ?>

    <!-- Contenido -->
    <div class="sp-admin-content">

        <div class="sp-admin-header">
            <h1 class="sp-admin-titulo">Dashboard</h1>
            <span class="sp-admin-fecha"><?= date('d/m/Y H:i') ?></span>
        </div>

        <!-- Stats -->
        <div class="sp-stats-grid">
            <div class="sp-stat-card">
                <div class="sp-stat-icon" style="background:rgba(232,93,36,0.15);color:#e85d24">
                    <i class="bi bi-bag"></i>
                </div>
                <div class="sp-stat-info">
                    <span class="sp-stat-valor"><?= $total_pedidos ?></span>
                    <span class="sp-stat-label">Total pedidos</span>
                </div>
            </div>
            <div class="sp-stat-card">
                <div class="sp-stat-icon" style="background:rgba(239,159,39,0.15);color:#EF9F27">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="sp-stat-info">
                    <span class="sp-stat-valor"><?= $pedidos_pend ?></span>
                    <span class="sp-stat-label">Pendientes</span>
                </div>
            </div>
            <div class="sp-stat-card">
                <div class="sp-stat-icon" style="background:rgba(29,158,117,0.15);color:#1d9e75">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="sp-stat-info">
                    <span class="sp-stat-valor">S/ <?= number_format($ingresos, 2) ?></span>
                    <span class="sp-stat-label">Ingresos totales</span>
                </div>
            </div>
            <div class="sp-stat-card">
                <div class="sp-stat-icon" style="background:rgba(55,138,221,0.15);color:#378add">
                    <i class="bi bi-people"></i>
                </div>
                <div class="sp-stat-info">
                    <span class="sp-stat-valor"><?= $total_usuarios ?></span>
                    <span class="sp-stat-label">Clientes</span>
                </div>
            </div>
        </div>

        <div class="sp-admin-grid">

            <!-- Pedidos recientes -->
            <div class="sp-admin-card">
                <div class="sp-admin-card-header">
                    <h3>Pedidos recientes</h3>
                    <a href="<?= BASE_URL ?>/admin/pedidos" class="sp-admin-link">Ver todos →</a>
                </div>
                <div class="sp-tabla-wrap">
                    <table class="sp-tabla">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Pago</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = $pedidos_recientes->fetch_assoc()): ?>
                            <tr>
                                <td><span class="sp-codigo"><?= sanitizar($p['comprobante'] ?? '#'.$p['id_pedido']) ?></span></td>
                                <td><?= sanitizar($p['nombre_usuario']) ?></td>
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
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stock bajo -->
            <div class="sp-admin-card">
                <div class="sp-admin-card-header">
                    <h3>Stock bajo ⚠️</h3>
                    <a href="<?= BASE_URL ?>/admin/productos" class="sp-admin-link">Ver productos →</a>
                </div>
                <?php if ($stock_bajo->num_rows === 0): ?>
                <p style="color:var(--text-muted);font-size:14px;padding:16px 0">Todo el stock está bien.</p>
                <?php else: ?>
                <div class="sp-tabla-wrap">
                    <table class="sp-tabla">
                        <thead>
                            <tr><th>Producto</th><th>Stock</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php while ($s = $stock_bajo->fetch_assoc()): ?>
                            <tr>
                                <td><?= sanitizar($s['nombre']) ?></td>
                                <td>
                                    <span style="color:<?= $s['stock'] == 0 ? 'var(--error)' : 'var(--warning)' ?>;font-weight:700">
                                        <?= $s['stock'] ?> uds.
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/admin/productos?editar=<?= $s['id_producto'] ?>" class="sp-admin-btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>