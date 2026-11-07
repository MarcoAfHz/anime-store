<?php // Las categorías ($menu_categorias) se cargan en header.php ?>

</main><!-- fin sp-main -->

<!-- ── FOOTER ───────────────────────────────────────────────── -->
<footer class="sp-footer">
    <div class="sp-footer-inner">

        <!-- Columna 1 — Logo y descripción -->
        <div class="sp-footer-col">
            <a href="<?= BASE_URL ?>" class="sp-logo sp-footer-logo">
                ANIME<span>STORE</span>
            </a>
            <p class="sp-footer-desc">
                Importaciones anime y urban style. Productos exclusivos con envíos a todo Perú.
            </p>
            <div class="sp-footer-social">
                <a href="#" title="Instagram"><i class="bi bi-instagram"></i></a>
                <a href="#" title="TikTok"><i class="bi bi-tiktok"></i></a>
                <a href="#" title="Facebook"><i class="bi bi-facebook"></i></a>
                <a href="#" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
            </div>
        </div>

        <!-- Columna 2 — Categorías -->
        <div class="sp-footer-col">
            <h4 class="sp-footer-title">Categorías</h4>
            <ul class="sp-footer-links">
                <?php foreach ($menu_categorias as $i => $mc): ?>
                <li><a href="<?= BASE_URL ?>/catalogo?categoria=<?= $mc['id_categoria'] ?>"><i class="bi <?= $menu_iconos[$i % count($menu_iconos)] ?>"></i> <?= sanitizar($mc['nombre']) ?></a></li>
                <?php endforeach; ?>
                <li><a href="<?= BASE_URL ?>/catalogo?oferta=1"><i class="bi bi-lightning"></i> Ofertas</a></li>
            </ul>
        </div>

        <!-- Columna 3 — Mi cuenta -->
        <div class="sp-footer-col">
            <h4 class="sp-footer-title">Mi cuenta</h4>
            <ul class="sp-footer-links">
                <?php if (esta_logueado()): ?>
                <li><a href="<?= BASE_URL ?>/mi-cuenta"><i class="bi bi-person"></i> Mi perfil</a></li>
                <li><a href="<?= BASE_URL ?>/mi-cuenta?tab=pedidos"><i class="bi bi-box"></i> Mis pedidos</a></li>
                <li><a href="<?= BASE_URL ?>/wishlist"><i class="bi bi-heart"></i> Lista de deseos</a></li>
                <li><a href="<?= BASE_URL ?>/logout"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a></li>
                <?php else: ?>
                <li><a href="#" onclick="abrirModal('modalLogin')"><i class="bi bi-person"></i> Iniciar sesión</a></li>
                <li><a href="#" onclick="cambiarTab('registro')"><i class="bi bi-pencil"></i> Registrarse</a></li>
                <li><a href="<?= BASE_URL ?>/carrito"><i class="bi bi-bag"></i> Mi carrito</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Columna 4 — Contacto y pagos -->
        <div class="sp-footer-col">
            <h4 class="sp-footer-title">Contacto</h4>
            <ul class="sp-footer-links sp-footer-contact">
                <li>
                    <i class="bi bi-geo-alt"></i>
                    <?= TIENDA_DIRECCION ?>
                </li>
                <li>
                    <i class="bi bi-whatsapp"></i>
                    <a href="https://wa.me/<?= str_replace(['+', ' '], '', TIENDA_TELEFONO) ?>">
                        <?= TIENDA_TELEFONO ?>
                    </a>
                </li>
                <li>
                    <i class="bi bi-envelope"></i>
                    <a href="mailto:<?= TIENDA_EMAIL ?>"><?= TIENDA_EMAIL ?></a>
                </li>
            </ul>

            <h4 class="sp-footer-title" style="margin-top:20px">Métodos de pago</h4>
            <div class="sp-footer-pagos">
                <span class="sp-pago-badge">
                    <i class="bi bi-qr-code"></i> Yape QR
                </span>
                <span class="sp-pago-badge">
                    <i class="bi bi-cash"></i> Efectivo
                </span>
            </div>
        </div>

    </div>

    <!-- Footer bottom -->
    <div class="sp-footer-bottom">
        <p>© <?= date('Y') ?> <?= TIENDA_NOMBRE ?> — Lima, Perú. Todos los derechos reservados.</p>
        <div class="sp-footer-bottom-links">
            <a href="<?= BASE_URL ?>/terminos">Términos y condiciones</a>
            <a href="<?= BASE_URL ?>/privacidad">Política de privacidad</a>
        </div>
    </div>
</footer>

<!-- ── SCRIPTS ───────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/carrito.js"></script>

<!-- PWA Service Worker -->
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= BASE_URL ?>/assets/js/sw.js')
        .then(() => console.log('PWA activa'))
        .catch(err => console.log('SW error:', err));
}
</script>

</body>
</html>