<?php
$titulo_pagina = 'Pedido confirmado';
$css_extra     = 'carrito';
require_once '../includes/header.php';
requiere_login();

$conn      = conectar();
$id_pedido = (int) ($_GET['id'] ?? 0);
$id_usuario = (int) $_SESSION['usuario']['id_usuario'];

if ($id_pedido <= 0) redirigir('');

// Verificar que el pedido pertenece al usuario
$pedido = $conn->query("
    SELECT p.*, c.codigo AS codigo_comprobante,
           pg.yape_qr, pg.estado AS pago_estado,
           pr.nombre AS provincia_nombre,
           d.nombre AS departamento_nombre
    FROM pedidos p
    LEFT JOIN comprobantes c ON c.id_pedido = p.id_pedido
    LEFT JOIN pagos pg ON pg.id_pedido = p.id_pedido
    LEFT JOIN provincias pr ON pr.id_provincia = (SELECT id_provincia FROM usuarios WHERE id_usuario = p.id_usuario)
    LEFT JOIN departamentos d ON d.id_departamento = pr.id_departamento
    WHERE p.id_pedido = $id_pedido AND p.id_usuario = $id_usuario
")->fetch_assoc();

if (!$pedido) redirigir('');

// Detalle del pedido
$detalle = $conn->query("
    SELECT dp.*, p.nombre, p.precio,
           (SELECT url FROM imagenes_producto WHERE id_producto = dp.id_producto AND principal = 1 LIMIT 1) AS imagen
    FROM detalle_pedido dp
    JOIN productos p ON dp.id_producto = p.id_producto
    WHERE dp.id_pedido = $id_pedido
");
?>

<div class="sp-container" style="padding-top:48px;padding-bottom:64px">

    <!-- Éxito -->
    <div class="sp-confirmado-header">
        <div class="sp-confirmado-icon">
            <i class="bi bi-check-lg"></i>
        </div>
        <h1>¡Pedido confirmado!</h1>
        <p>Tu pedido <strong><?= sanitizar($pedido['codigo_comprobante']) ?></strong> ha sido recibido correctamente.</p>
    </div>

    <div class="sp-confirmado-layout">

        <!-- Detalle pedido -->
        <div class="sp-confirmado-detalle">

            <!-- Productos -->
            <div class="sp-checkout-bloque">
                <h3 class="sp-checkout-titulo"><i class="bi bi-bag-check"></i> Productos del pedido</h3>
                <?php while ($item = $detalle->fetch_assoc()): ?>
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
                    <span class="sp-checkout-item-precio">
                        <?= formatear_precio($item['precio_unit'] * $item['cantidad']) ?>
                    </span>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Resumen de pago -->
            <div class="sp-checkout-bloque">
                <h3 class="sp-checkout-titulo"><i class="bi bi-receipt"></i> Resumen</h3>

                <div class="sp-resumen-linea">
                    <span>Subtotal</span>
                    <span><?= formatear_precio($pedido['subtotal']) ?></span>
                </div>
                <div class="sp-resumen-linea">
                    <span>Envío</span>
                    <span><?= $pedido['costo_envio'] > 0 ? formatear_precio($pedido['costo_envio']) : '<span style="color:var(--success)">Gratis</span>' ?></span>
                </div>
                <?php if ($pedido['descuento'] > 0): ?>
                <div class="sp-resumen-linea sp-resumen-descuento">
                    <span>Descuento</span>
                    <span>-<?= formatear_precio($pedido['descuento']) ?></span>
                </div>
                <?php endif; ?>
                <div class="sp-resumen-divider"></div>
                <div class="sp-resumen-linea sp-resumen-total">
                    <span>Total</span>
                    <span><?= formatear_precio($pedido['total']) ?></span>
                </div>
            </div>
        </div>

        <!-- Info pago y envío -->
        <div>
            <!-- Método de pago -->
            <div class="sp-checkout-bloque">
                <h3 class="sp-checkout-titulo">
                    <i class="bi bi-<?= $pedido['metodo_pago'] === 'yape' ? 'qr-code' : 'cash' ?>"></i>
                    Método de pago
                </h3>

                <?php if ($pedido['metodo_pago'] === 'yape'): ?>
                <div class="sp-confirmado-yape">
                    <p>Yapea <strong><?= formatear_precio($pedido['total']) ?></strong> al siguiente número:</p>
                    <div class="sp-yape-numero">
                        <i class="bi bi-phone"></i>
                        <span><?= YAPE_NUMERO ?></span>
                    </div>
                    <p class="sp-yape-instruccion">
                        A nombre de <strong><?= YAPE_TITULAR ?></strong>.
                        Pon como concepto tu código: <strong><?= sanitizar($pedido['codigo_comprobante']) ?></strong>
                    </p>
                    <div class="sp-yape-aviso">
                        <i class="bi bi-exclamation-circle"></i>
                        Tu pedido se procesará una vez confirmemos el pago por Yape.
                    </div>

                    <?php if (($pedido['pago_estado'] ?? '') === 'confirmado'): ?>
                    <div class="sp-yape-enviado"><i class="bi bi-check-circle-fill"></i> Pago confirmado. ¡Gracias!</div>
                    <?php elseif ($pedido['estado'] !== 'cancelado'): ?>
                    <div class="sp-yape-subir">
                        <h4>¿Ya yapeaste? Envíanos la captura</h4>
                        <p>Así confirmamos tu pago más rápido.</p>

                        <div id="yapeEnviado" class="sp-yape-enviado" <?= empty($pedido['yape_qr']) ? 'hidden' : '' ?>>
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Comprobante recibido. Lo revisaremos en breve.</span>
                        </div>
                        <img id="yapePreview" class="sp-yape-preview" alt="Comprobante de Yape"
                             src="<?= !empty($pedido['yape_qr']) ? YAPE_URL . rawurlencode($pedido['yape_qr']) : '' ?>"
                             <?= empty($pedido['yape_qr']) ? 'hidden' : '' ?>>

                        <form id="yapeForm" enctype="multipart/form-data" style="margin-top:12px">
                            <input type="hidden" name="id_pedido" value="<?= $id_pedido ?>">
                            <?= csrf_campo() ?>
                            <label class="sp-yape-drop">
                                <i class="bi bi-cloud-arrow-up"></i>
                                <span id="yapeLabel"><?= empty($pedido['yape_qr']) ? 'Toca para elegir la captura' : 'Cambiar captura' ?></span>
                                <small style="color:var(--text-muted)">JPG, PNG o WEBP · máx 2MB</small>
                                <input type="file" name="comprobante" accept="image/jpeg,image/png,image/webp" id="yapeArchivo">
                            </label>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="sp-confirmado-efectivo">
                    <i class="bi bi-cash-stack"></i>
                    <p>Pagarás <strong><?= formatear_precio($pedido['total']) ?></strong> en efectivo al recibir tu pedido.</p>
                    <p>Ten el monto exacto listo para la entrega.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Datos de envío -->
            <div class="sp-checkout-bloque">
                <h3 class="sp-checkout-titulo"><i class="bi bi-truck"></i> Datos de envío</h3>
                <div class="sp-confirmado-envio">
                    <p><i class="bi bi-person"></i> <?= sanitizar($_SESSION['usuario']['nombre']) ?></p>
                    <p><i class="bi bi-geo-alt"></i> <?= sanitizar($pedido['dir_envio']) ?></p>
                    <?php if (!empty($pedido['departamento_nombre'])): ?>
                    <p><i class="bi bi-map"></i> <?= sanitizar($pedido['departamento_nombre']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Botones -->
            <div class="sp-confirmado-btns">
                <a href="<?= BASE_URL ?>/mi-cuenta?tab=pedidos" class="sp-btn-primary w-100">
                    <i class="bi bi-box"></i> Ver mis pedidos
                </a>
                <a href="<?= BASE_URL ?>/catalogo" class="sp-btn-outline w-100" style="margin-top:10px">
                    <i class="bi bi-grid-3x3-gap"></i> Seguir comprando
                </a>
            </div>
        </div>

    </div>
</div>

<script>
document.getElementById('yapeArchivo')?.addEventListener('change', async (e) => {
    const archivo = e.target.files[0];
    if (!archivo) return;

    const label = document.getElementById('yapeLabel');
    label.textContent = 'Subiendo...';

    try {
        const res  = await fetch(`${BASE_URL}/api/yape.php`, {
            method: 'POST',
            body:   new FormData(document.getElementById('yapeForm')),
        });
        const json = await res.json();

        if (json.success) {
            const img  = document.getElementById('yapePreview');
            img.src    = json.url;
            img.hidden = false;
            document.getElementById('yapeEnviado').hidden = false;
            label.textContent = 'Cambiar captura';
            mostrarToast(json.mensaje, 'success');
        } else {
            label.textContent = 'Toca para elegir la captura';
            mostrarToast(json.error || 'No se pudo subir el comprobante', 'error');
        }
    } catch {
        label.textContent = 'Toca para elegir la captura';
        mostrarToast('Error de conexión', 'error');
    }
    e.target.value = '';
});
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>