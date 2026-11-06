<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/includes/auth.php';
$flash = flash_get();

// Categorías para el menú (las que se crean en el admin aparecen solas)
$menu_categorias = conectar()->query("SELECT id_categoria, nombre FROM categorias WHERE activa = 1 ORDER BY id_categoria")->fetch_all(MYSQLI_ASSOC);
$menu_iconos     = ['bi-tags', 'bi-emoji-smile', 'bi-bag', 'bi-award', 'bi-stars', 'bi-controller', 'bi-gift', 'bi-gem'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f0f0f">
    <meta name="base-url" content="<?= BASE_URL ?>">  <!-- ← añade aquí -->
    <title><?= isset($titulo_pagina) ? $titulo_pagina . ' — ' . TIENDA_NOMBRE : TIENDA_NOMBRE ?></title>
    <meta name="description" content="<?= isset($meta_descripcion) ? $meta_descripcion : 'Tienda de importaciones anime y urban style. Envíos a todo Perú.' ?>">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <!-- Estilos propios -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/paginas.css">

    <?php if (isset($css_extra)): ?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $css_extra ?>.css">
<?php endif; ?>
    <!-- PWA -->
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/favicon.ico">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/img/icon-192.png">
</head>
<body>

<!-- ── MENSAJE FLASH ────────────────────────────────────────── -->
<?php if ($flash): ?>
<div class="flash-message flash-<?= $flash['tipo'] ?>" id="flashMessage">
    <?= sanitizar($flash['mensaje']) ?>
    <button onclick="document.getElementById('flashMessage').remove()">✕</button>
</div>
<?php endif; ?>

<!-- ── NAVBAR ───────────────────────────────────────────────── -->
<nav class="sp-navbar">
    <div class="sp-navbar-inner">

        <!-- Logo -->
        <a href="<?= BASE_URL ?>" class="sp-logo">
            SHOP<span>ANIME</span>
        </a>

        <!-- Links -->
        <ul class="sp-nav-links">
            <li><a href="<?= BASE_URL ?>">Inicio</a></li>
            <li><a href="<?= BASE_URL ?>/catalogo">Catálogo</a></li>
            <li class="dropdown">
                <a href="#">Categorías <i class="bi bi-chevron-down"></i></a>
                <ul class="sp-dropdown">
                    <?php foreach ($menu_categorias as $mc): ?>
                    <li><a href="<?= BASE_URL ?>/catalogo?categoria=<?= $mc['id_categoria'] ?>"><?= sanitizar($mc['nombre']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <li><a href="<?= BASE_URL ?>/catalogo?oferta=1">Ofertas</a></li>
        </ul>

        <!-- Buscador -->
        <form class="sp-search" action="<?= BASE_URL ?>/catalogo" method="GET">
            <input type="text" name="q" placeholder="Buscar productos..." 
                   value="<?= isset($_GET['q']) ? sanitizar($_GET['q']) : '' ?>">
            <button type="submit"><i class="bi bi-search"></i></button>
        </form>

        <!-- Iconos derecha -->
        <div class="sp-nav-icons">

            <!-- Wishlist -->
            <a href="<?= BASE_URL ?>/wishlist" class="sp-nav-icon" title="Lista de deseos">
                <i class="bi bi-heart"></i>
            </a>

            <!-- Carrito -->
            <a href="<?= BASE_URL ?>/carrito" class="sp-nav-icon sp-carrito-icon" title="Carrito">
                <i class="bi bi-bag"></i>
                <?php if (carrito_total_items() > 0): ?>
                <span class="sp-badge-count"><?= carrito_total_items() ?></span>
                <?php endif; ?>
            </a>

            <!-- Usuario -->
            <?php if (esta_logueado()): ?>
            <div class="sp-nav-user dropdown">
                <button class="sp-nav-icon">
                    <i class="bi bi-person-circle"></i>
                    <span><?= sanitizar($_SESSION['usuario']['nombre']) ?></span>
                </button>
                <ul class="sp-dropdown sp-dropdown-right">
                    <li><a href="<?= BASE_URL ?>/mi-cuenta"><i class="bi bi-person"></i> Mi cuenta</a></li>
                    <li><a href="<?= BASE_URL ?>/mi-cuenta?tab=pedidos"><i class="bi bi-box"></i> Mis pedidos</a></li>
                    <li><a href="<?= BASE_URL ?>/wishlist"><i class="bi bi-heart"></i> Wishlist</a></li>
                    <?php if (es_admin()): ?>
                    <li class="sp-dropdown-divider"></li>
                    <li><a href="<?= BASE_URL ?>/admin"><i class="bi bi-gear"></i> Panel admin</a></li>
                    <?php endif; ?>
                    <li class="sp-dropdown-divider"></li>
                    <li><a href="<?= BASE_URL ?>/logout"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a></li>
                </ul>
            </div>
            <?php else: ?>
            <button class="sp-nav-icon" onclick="abrirModal('modalLogin')" title="Iniciar sesión">
                <i class="bi bi-person"></i>
            </button>
            <?php endif; ?>

            <!-- Menú hamburguesa móvil -->
            <button class="sp-hamburger" onclick="toggleMenu()" id="btnHamburger">
                <i class="bi bi-list"></i>
            </button>

        </div>
    </div>

    <!-- Menú móvil -->
    <!-- Overlay oscuro -->
    <div class="sp-drawer-overlay" id="drawerOverlay" onclick="toggleMenu()"></div>

    <!-- Menú drawer derecha -->
    <div class="sp-drawer" id="mobileMenu">
        <div class="sp-drawer-header">
            <span class="sp-logo">SHOP<span>ANIME</span></span>
            <button onclick="toggleMenu()" class="sp-drawer-close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <ul class="sp-drawer-links">
            <li><a href="<?= BASE_URL ?>" onclick="toggleMenu()"><i class="bi bi-house"></i> Inicio</a></li>
            <li><a href="<?= BASE_URL ?>/catalogo" onclick="toggleMenu()"><i class="bi bi-grid-3x3-gap"></i> Catálogo</a></li>
            <?php foreach ($menu_categorias as $i => $mc): ?>
            <li><a href="<?= BASE_URL ?>/catalogo?categoria=<?= $mc['id_categoria'] ?>" onclick="toggleMenu()"><i class="bi <?= $menu_iconos[$i % count($menu_iconos)] ?>"></i> <?= sanitizar($mc['nombre']) ?></a></li>
            <?php endforeach; ?>
            <li><a href="<?= BASE_URL ?>/catalogo?oferta=1" onclick="toggleMenu()"><i class="bi bi-lightning"></i> Ofertas</a></li>
            <li class="sp-drawer-divider"></li>
            <?php if (!esta_logueado()): ?>
            <li><a href="#" onclick="toggleMenu(); abrirModal('modalLogin')"><i class="bi bi-person"></i> Iniciar sesión</a></li>
            <li><a href="#" onclick="toggleMenu(); cambiarTab('registro')"><i class="bi bi-pencil"></i> Registrarse</a></li>
            <?php else: ?>
            <li><a href="<?= BASE_URL ?>/mi-cuenta" onclick="toggleMenu()"><i class="bi bi-person"></i> Mi cuenta</a></li>
            <li><a href="<?= BASE_URL ?>/logout"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- ── MODAL LOGIN / REGISTRO ───────────────────────────────── -->
<div class="sp-modal-overlay" id="modalLogin" onclick="cerrarModalOverlay(event, 'modalLogin')">
    <div class="sp-modal">
        <button class="sp-modal-close" onclick="cerrarModal('modalLogin')">✕</button>

        <!-- Tabs -->
        <div class="sp-modal-tabs">
            <button class="sp-tab active" id="tabLogin" onclick="cambiarTab('login')">Iniciar sesión</button>
            <button class="sp-tab" id="tabRegistro" onclick="cambiarTab('registro')">Registrarse</button>
        </div>

        <!-- Formulario Login -->
        <div id="formLogin">
            <div class="sp-modal-logo">SHOP<span>ANIME</span></div>
            <form action="<?= BASE_URL ?>/api/auth.php" method="POST" id="loginForm">
                <input type="hidden" name="accion" value="login">
                <?= csrf_campo() ?>
                <div class="sp-form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="tu@email.com" required>
                </div>
                <div class="sp-form-group">
                    <label>Contraseña</label>
                    <div class="sp-input-password">
                        <input type="password" name="password" id="loginPassword" placeholder="••••••••" required>
                        <button type="button" onclick="togglePassword('loginPassword')">
                            <i class="bi bi-eye" id="loginPasswordIcon"></i>
                        </button>
                    </div>
                </div>
                <input type="hidden" name="redirect" value="<?= sanitizar($_SERVER['REQUEST_URI'] ?? '/') ?>">
                <button type="submit" class="sp-btn-primary w-100">Iniciar sesión</button>
                <p class="sp-modal-footer-text">
                    ¿No tienes cuenta? <a href="#" onclick="cambiarTab('registro')">Regístrate gratis</a>
                </p>
            </form>
        </div>

        <!-- Formulario Registro -->
        <div id="formRegistro" style="display:none">
            <div class="sp-modal-logo">SHOP<span>ANIME</span></div>
            <form action="<?= BASE_URL ?>/api/auth.php" method="POST" id="registroForm">
                <input type="hidden" name="accion" value="registro">
                <?= csrf_campo() ?>
                <div class="sp-form-group">
                    <label>Nombre completo</label>
                    <input type="text" name="nombre" placeholder="Tu nombre" required>
                </div>
                <div class="sp-form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="tu@email.com" required>
                </div>
                <div class="sp-form-group">
                    <label>Contraseña</label>
                    <div class="sp-input-password">
                        <input type="password" name="password" id="regPassword" placeholder="Mínimo 8 caracteres" required minlength="8">
                        <button type="button" onclick="togglePassword('regPassword')">
                            <i class="bi bi-eye" id="regPasswordIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="sp-btn-primary w-100">Crear cuenta</button>
                <p class="sp-modal-footer-text">
                    ¿Ya tienes cuenta? <a href="#" onclick="cambiarTab('login')">Inicia sesión</a>
                </p>
            </form>
        </div>

    </div>
</div>

<!-- ── CONTENIDO DE LA PÁGINA ────────────────────────────────── -->
<main class="sp-main">