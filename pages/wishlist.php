<?php
$titulo_pagina = 'Lista de deseos';
$css_extra     = 'catalogo';
require_once '../includes/header.php';
requiere_login();

$conn       = conectar();
$id_usuario = (int) $_SESSION['usuario']['id_usuario'];

$wishlist = $conn->query("
    SELECT w.id_wishlist, w.fecha_agregado, p.*,
           (SELECT url FROM imagenes_producto WHERE id_producto = p.id_producto AND principal = 1 LIMIT 1) AS imagen_principal,
           COALESCE(AVG(r.puntuacion), 0) AS puntuacion_media,
           COUNT(r.id_resena) AS total_resenas
    FROM wishlist w
    JOIN productos p ON w.id_producto = p.id_producto
    LEFT JOIN resenas r ON r.id_producto = p.id_producto
    WHERE w.id_usuario = $id_usuario AND p.activo = 1
    GROUP BY w.id_wishlist
    ORDER BY w.fecha_agregado DESC
");
?>

<div class="sp-container" style="padding-top:32px;padding-bottom:48px">

    <nav class="sp-breadcrumb">
        <a href="<?= BASE_URL ?>">Inicio</a> <span>/</span>
        <span>Lista de deseos</span>
    </nav>

    <div class="sp-section-header">
        <h1 class="sp-page-title">
            Lista de deseos
            <span><?= $wishlist->num_rows ?> producto<?= $wishlist->num_rows !== 1 ? 's' : '' ?></span>
        </h1>
    </div>

    <?php if ($wishlist->num_rows === 0): ?>
    <div class="sp-carrito-vacio">
        <i class="bi bi-heart"></i>
        <h3>Tu lista de deseos está vacía</h3>
        <p>Guarda productos que te gusten para comprarlos después.</p>
        <a href="<?= BASE_URL ?>/catalogo" class="sp-btn-primary">
            <i class="bi bi-grid-3x3-gap"></i> Ver catálogo
        </a>
    </div>
    <?php else: ?>
    <div class="sp-productos-grid">
        <?php while ($prod = $wishlist->fetch_assoc()): ?>
            <?php include ROOT_PATH . '/includes/card_producto.php'; ?>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>