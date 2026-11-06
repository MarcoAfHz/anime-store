<?php
$titulo_pagina = 'Mi carrito';
$css_extra     = 'carrito';
require_once '../includes/header.php';

$conn    = conectar();
$carrito = carrito_obtener();
$items   = [];
$subtotal = 0;

foreach ($carrito as $key => $item) {
    $id   = (int) $item['id_producto'];
    $prod = $conn->query("
        SELECT p.*,
               (SELECT url FROM imagenes_producto WHERE id_producto = p.id_producto AND principal = 1 LIMIT 1) AS imagen_principal
        FROM productos p
        WHERE p.id_producto = $id AND p.activo = 1
    ")->fetch_assoc();

    if ($prod) {
        $prod['key']      = $key;
        $prod['cantidad'] = $item['cantidad'];
        $prod['talla']    = $item['talla'];
        $prod['subtotal'] = $prod['precio'] * $item['cantidad'];
        $subtotal        += $prod['subtotal'];
        $items[]          = $prod;
    }
}

$descuento   = descuento_cupon_actual($subtotal);
$costo_envio = 0;

if (esta_logueado() && isset($_SESSION['usuario']['id_provincia'])) {
    $id_prov     = (int) $_SESSION['usuario']['id_provincia'];
    $dep         = $conn->query("SELECT d.costo_envio FROM provincias p JOIN departamentos d ON p.id_departamento = d.id_departamento WHERE p.id_provincia = $id_prov");
    if ($dep->num_rows > 0) $costo_envio = (float) $dep->fetch_assoc()['costo_envio'];
}

$total = max(0, $subtotal + $costo_envio - $descuento);
?>

<div class="sp-container" style="padding-top:32px;padding-bottom:48px">

    <nav class="sp-breadcrumb">
        <a href="<?= BASE_URL ?>">Inicio</a> <span>/</span>
        <span>Mi carrito</span>
    </nav>

    <h1 class="sp-page-title">
        Mi carrito
        <span><?= carrito_total_items() ?> producto<?= carrito_total_items() !== 1 ? 's' : '' ?></span>
    </h1>

    <?php if (empty($items)): ?>
    <div class="sp-carrito-vacio" id="carritoVacio">
        <i class="bi bi-bag-x"></i>
        <h3>Tu carrito está vacío</h3>
        <p>Añade productos desde el catálogo para empezar a comprar.</p>
        <a href="<?= BASE_URL ?>/catalogo" class="sp-btn-primary">
            <i class="bi bi-grid-3x3-gap"></i> Ver catálogo
        </a>
    </div>

    <?php else: ?>
    <div class="sp-carrito-layout" id="carritoContenido">

        <!-- Lista productos -->
        <div class="sp-carrito-items">
            <?php foreach ($items as $item): ?>
            <div class="sp-carrito-fila carrito-fila" id="fila-<?= $item['key'] ?>">

                <div class="sp-carrito-img">
                    <a href="<?= BASE_URL ?>/producto?id=<?= $item['id_producto'] ?>">
                        <img src="<?= imagen_producto($item['imagen_principal']) ?>"
                             alt="<?= sanitizar($item['nombre']) ?>">
                    </a>
                </div>

                <div class="sp-carrito-info">
                    <a href="<?= BASE_URL ?>/producto?id=<?= $item['id_producto'] ?>">
                        <h4><?= sanitizar($item['nombre']) ?></h4>
                    </a>
                    <?php if (!empty($item['talla'])): ?>
                    <span class="sp-carrito-talla">Talla: <?= sanitizar($item['talla']) ?></span>
                    <?php endif; ?>
                    <span class="sp-carrito-precio-unit"><?= formatear_precio($item['precio']) ?> / unidad</span>
                </div>

                <div class="sp-carrito-cantidad">
                    <button class="btn-cantidad-menos" data-key="<?= $item['key'] ?>">−</button>
                    <input type="number"
                           id="cantidad-<?= $item['key'] ?>"
                           value="<?= $item['cantidad'] ?>"
                           min="1"
                           max="<?= $item['stock'] ?>"
                           readonly>
                    <button class="btn-cantidad-mas" data-key="<?= $item['key'] ?>">+</button>
                </div>

                <div class="sp-carrito-subtotal">
                    <span id="subtotal-<?= $item['key'] ?>"><?= formatear_precio($item['subtotal']) ?></span>
                </div>

                <button class="sp-carrito-quitar" onclick="quitarDelCarrito('<?= $item['key'] ?>')" title="Quitar">
                    <i class="bi bi-trash"></i>
                </button>

            </div>
            <?php endforeach; ?>

            <div class="sp-carrito-acciones">
                <a href="<?= BASE_URL ?>/catalogo" class="sp-btn-outline">
                    <i class="bi bi-arrow-left"></i> Seguir comprando
                </a>
                <button class="sp-btn-vaciar" onclick="vaciarCarrito()">
                    <i class="bi bi-trash"></i> Vaciar carrito
                </button>
            </div>
        </div>

        <!-- Resumen -->
        <div class="sp-carrito-resumen">
            <h3 class="sp-resumen-titulo">Resumen del pedido</h3>

            <div class="sp-resumen-linea">
                <span>Subtotal</span>
                <span id="carritoSubtotal"><?= formatear_precio($subtotal) ?></span>
            </div>

            <div class="sp-resumen-linea">
                <span>Envío</span>
                <?php if ($costo_envio > 0): ?>
                <span><?= formatear_precio($costo_envio) ?></span>
                <?php else: ?>
                <span style="color:var(--success)">A calcular</span>
                <?php endif; ?>
            </div>

            <div id="filaDescuento" style="display:<?= $descuento > 0 ? 'flex' : 'none' ?>" class="sp-resumen-linea sp-resumen-descuento">
                <span>Descuento</span>
                <span id="montoDescuento"><?= $descuento > 0 ? '-' . formatear_precio($descuento) : '' ?></span>
            </div>

            <div class="sp-resumen-divider"></div>

            <div class="sp-resumen-linea sp-resumen-total">
                <span>Total</span>
                <span id="carritoTotal"><?= formatear_precio($total) ?></span>
            </div>

            <div class="sp-cupon-box">
                <input type="text"
                       id="inputCupon"
                       placeholder="Código de cupón"
                       value="<?= isset($_SESSION['cupon']) ? sanitizar($_SESSION['cupon']['codigo']) : '' ?>"
                       <?= isset($_SESSION['cupon']) ? 'disabled' : '' ?>>
                <button id="btnCupon"
                        onclick="aplicarCupon()"
                        <?= isset($_SESSION['cupon']) ? 'disabled' : '' ?>>
                    <?= isset($_SESSION['cupon']) ? '✓ Aplicado' : 'Aplicar' ?>
                </button>
            </div>

            <?php if (esta_logueado()): ?>
            <a href="<?= BASE_URL ?>/checkout" class="sp-btn-primary w-100 sp-btn-checkout">
                <i class="bi bi-lock"></i> Proceder al pago
            </a>
            <?php else: ?>
            <button class="sp-btn-primary w-100 sp-btn-checkout" onclick="abrirModal('modalLogin')">
                <i class="bi bi-person"></i> Inicia sesión para pagar
            </button>
            <?php endif; ?>

            <div class="sp-resumen-pagos">
                <span><i class="bi bi-qr-code"></i> Yape QR</span>
                <span><i class="bi bi-cash"></i> Efectivo</span>
            </div>
        </div>

    </div>
    <?php endif; ?>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>