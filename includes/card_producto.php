<?php
/** @var array $prod */
$imagen    = !empty($prod['imagen_principal']) ? UPLOADS_URL . $prod['imagen_principal'] : IMG_DEFAULT;
$precio    = formatear_precio($prod['precio']);
$en_oferta = !empty($prod['precio_anterior']) && $prod['precio_anterior'] > $prod['precio'];
$pct_desc  = $en_oferta ? round(100 - ($prod['precio'] / $prod['precio_anterior']) * 100) : 0;
$estrellas = mostrar_estrellas(round($prod['puntuacion_media'] ?? 0));
$resenas   = (int) ($prod['total_resenas'] ?? 0);
$es_nuevo  = !empty($prod['fecha_creacion']) && strtotime($prod['fecha_creacion']) > strtotime('-30 days');
$destacado = !empty($prod['destacado']);

// Wishlist activa
$en_wishlist = false;
if (esta_logueado()) {
    $uid      = (int) $_SESSION['usuario']['id_usuario'];
    $pid      = (int) $prod['id_producto'];
    $wcheck   = conectar()->query("SELECT id_wishlist FROM wishlist WHERE id_usuario = $uid AND id_producto = $pid");
    $en_wishlist = $wcheck->num_rows > 0;
}

// Tallas disponibles
$tallas_prod = conectar()->query("SELECT talla FROM producto_tallas WHERE id_producto = {$prod['id_producto']} AND stock > 0 ORDER BY FIELD(talla,'XS','S','M','L','XL','XXL'), CAST(talla AS UNSIGNED), talla");
$hay_tallas  = $tallas_prod && $tallas_prod->num_rows > 0;
?>
<div class="sp-prod-card">
    <a href="<?= BASE_URL ?>/producto?id=<?= $prod['id_producto'] ?>" class="sp-prod-img">
        <img src="<?= $imagen ?>"
             alt="<?= sanitizar($prod['nombre']) ?>"
             loading="lazy">
    </a>
    <div class="sp-prod-info">
        <a href="<?= BASE_URL ?>/producto?id=<?= $prod['id_producto'] ?>">
            <p class="sp-prod-nombre"><?= sanitizar($prod['nombre']) ?></p>
        </a>
        <?php if ($resenas > 0): ?>
        <div class="sp-prod-stars">
            <?= $estrellas ?>
            <span>(<?= $resenas ?>)</span>
        </div>
        <?php endif; ?>
        <div>
            <span class="sp-prod-precio"><?= $precio ?></span>
            <?php if ($en_oferta): ?>
            <span class="sp-prod-precio-anterior"><?= formatear_precio($prod['precio_anterior']) ?></span>
            <?php endif; ?>
        </div>
        <?php if ($en_oferta || $es_nuevo || $destacado || $hay_tallas): ?>
        <div class="sp-prod-tags">
            <?php if ($en_oferta): ?>
                <span class="sp-tag sp-tag-oferta">-<?= $pct_desc ?>%</span>
            <?php endif; ?>
            <?php if ($es_nuevo): ?>
                <span class="sp-tag sp-tag-nuevo">Nuevo</span>
            <?php endif; ?>
            <?php if ($destacado): ?>
                <span class="sp-tag sp-tag-hot">Top</span>
            <?php endif; ?>
            <?php if ($hay_tallas): ?>
                <?php while ($t = $tallas_prod->fetch_assoc()): ?>
                <span class="sp-tag" style="background:var(--bg-hover);color:var(--text-secondary)">
                    <?= sanitizar($t['talla']) ?>
                </span>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="sp-prod-actions">
        <?php if ($hay_tallas): ?>
        <a class="sp-btn-carrito" href="<?= BASE_URL ?>/producto?id=<?= $prod['id_producto'] ?>">
            <i class="bi bi-rulers"></i> Elegir talla
        </a>
        <?php else: ?>
        <button class="sp-btn-carrito"
                onclick="agregarAlCarrito(<?= $prod['id_producto'] ?>, 1, null)">
            <i class="bi bi-bag-plus"></i> Añadir
        </button>
        <?php endif; ?>
        <button class="sp-btn-wish <?= $en_wishlist ? 'sp-wish-active' : '' ?>"
                onclick="toggleWishlist(<?= $prod['id_producto'] ?>, this)"
                title="<?= $en_wishlist ? 'Quitar de wishlist' : 'Añadir a wishlist' ?>">
            <i class="bi <?= $en_wishlist ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
        </button>
    </div>
</div>