<aside class="sp-admin-sidebar">
    <div class="sp-admin-logo">
        <a href="<?= BASE_URL ?>/admin">SHOP<span>PERUVIAN</span></a>
        <small>Panel Admin</small>
    </div>

    <nav class="sp-admin-nav">
        <a href="<?= BASE_URL ?>/admin" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="<?= BASE_URL ?>/admin/pedidos" class="<?= basename($_SERVER['PHP_SELF']) === 'pedidos.php' ? 'active' : '' ?>">
            <i class="bi bi-bag"></i> Pedidos
            <?php
            $pend = conectar()->query("SELECT COUNT(*) as t FROM pedidos WHERE estado = 'pendiente'")->fetch_assoc()['t'];
            if ($pend > 0): ?>
            <span class="sp-admin-badge"><?= $pend ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/productos" class="<?= basename($_SERVER['PHP_SELF']) === 'productos.php' ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i> Productos
        </a>
        <a href="<?= BASE_URL ?>/admin/categorias" class="<?= basename($_SERVER['PHP_SELF']) === 'categorias.php' ? 'active' : '' ?>">
            <i class="bi bi-tags"></i> Categorías
        </a>
        <a href="<?= BASE_URL ?>/admin/banners" class="<?= basename($_SERVER['PHP_SELF']) === 'banners.php' ? 'active' : '' ?>">
            <i class="bi bi-image"></i> Banners
        </a>
        <a href="<?= BASE_URL ?>/admin/cupones" class="<?= basename($_SERVER['PHP_SELF']) === 'cupones.php' ? 'active' : '' ?>">
            <i class="bi bi-ticket-perforated"></i> Cupones
        </a>
        <a href="<?= BASE_URL ?>/admin/usuarios" class="<?= basename($_SERVER['PHP_SELF']) === 'usuarios.php' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Usuarios
        </a>
        <div class="sp-admin-nav-divider"></div>
        <a href="<?= BASE_URL ?>" target="_blank">
            <i class="bi bi-shop"></i> Ver tienda
        </a>
        <a href="<?= BASE_URL ?>/logout" class="sp-admin-logout">
            <i class="bi bi-box-arrow-right"></i> Cerrar sesión
        </a>
    </nav>
</aside>