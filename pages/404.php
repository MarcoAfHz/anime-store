<?php
http_response_code(404);
$titulo_pagina = 'Página no encontrada';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="sp-404">
    <div class="sp-404-num">404</div>
    <h1>Esta página se fue al mundo ninja</h1>
    <p>No encontramos lo que buscas. Puede que el enlace esté roto o que el producto ya no exista.</p>
    <div class="sp-hero-btns">
        <a href="<?= BASE_URL ?>" class="sp-btn-primary"><i class="bi bi-house"></i> Ir al inicio</a>
        <a href="<?= BASE_URL ?>/catalogo" class="sp-btn-outline"><i class="bi bi-grid-3x3-gap"></i> Ver catálogo</a>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
