<?php
$titulo_pagina    = 'Inicio';
$meta_descripcion = 'ShopPeruvian — Importaciones anime y urban style. Ropa, figuras y accesorios exclusivos con envíos a todo Perú.';
require_once 'includes/header.php';

$conn = conectar();

// Banners activos
$banners    = $conn->query("SELECT * FROM banners WHERE activo = 1 ORDER BY orden ASC");

// Categorías activas
$categorias = $conn->query("SELECT * FROM categorias WHERE activa = 1 ORDER BY id_categoria ASC");

// Productos destacados (máx 8)
$destacados = $conn->query("
    SELECT p.*, c.nombre AS categoria_nombre,
           COALESCE(AVG(r.puntuacion), 0) AS puntuacion_media,
           COUNT(r.id_resena) AS total_resenas,
           (SELECT url FROM imagenes_producto WHERE id_producto = p.id_producto AND principal = 1 LIMIT 1) AS imagen_principal
    FROM productos p
    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
    LEFT JOIN resenas r ON r.id_producto = p.id_producto
    WHERE p.activo = 1 AND p.destacado = 1
    GROUP BY p.id_producto
    ORDER BY p.fecha_creacion DESC
    LIMIT 4
");

// Productos nuevos (máx 8)
$nuevos = $conn->query("
    SELECT p.*, c.nombre AS categoria_nombre,
           COALESCE(AVG(r.puntuacion), 0) AS puntuacion_media,
           COUNT(r.id_resena) AS total_resenas,
           (SELECT url FROM imagenes_producto WHERE id_producto = p.id_producto AND principal = 1 LIMIT 1) AS imagen_principal
    FROM productos p
    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
    LEFT JOIN resenas r ON r.id_producto = p.id_producto
    WHERE p.activo = 1
    GROUP BY p.id_producto
    ORDER BY p.fecha_creacion DESC
    LIMIT 4
");

// Productos en oferta
$ofertas = $conn->query("
    SELECT p.*, c.nombre AS categoria_nombre,
           COALESCE(AVG(r.puntuacion), 0) AS puntuacion_media,
           COUNT(r.id_resena) AS total_resenas,
           (SELECT url FROM imagenes_producto WHERE id_producto = p.id_producto AND principal = 1 LIMIT 1) AS imagen_principal
    FROM productos p
    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
    LEFT JOIN resenas r ON r.id_producto = p.id_producto
    WHERE p.activo = 1 AND p.stock > 0 AND p.precio_anterior > p.precio
    GROUP BY p.id_producto
    ORDER BY RAND()
    LIMIT 4
");
?>

<!-- ── HERO SLIDER COMPLETO ───────────────────────────────────── -->
<section class="sp-hero-full">

    <?php
    $hayBanners = $banners && $banners->num_rows > 0;
    if ($hayBanners):
        $primero = true;
        while ($banner = $banners->fetch_assoc()):
    ?>
    <?php $img_banner = BASE_URL . '/assets/img/banners/' . rawurlencode($banner['imagen']); ?>
    <div class="sp-hero-slide <?= $primero ? 'active' : '' ?>">
        <!-- Fondo: la misma imagen difuminada (ambiente de color de cada slide) -->
        <div class="sp-hero-ambient" style="background-image:url('<?= $img_banner ?>')"></div>
        <div class="sp-hero-overlay"></div>

        <div class="sp-hero-layout">
            <div class="sp-hero-content">
                <?php if (!empty($banner['titulo'])): ?>
                <div class="sp-hero-badge"><?= sanitizar($banner['titulo']) ?></div>
                <?php endif; ?>
                <?php if (!empty($banner['subtitulo'])): ?>
                <h2><?= sanitizar($banner['subtitulo']) ?></h2>
                <?php endif; ?>
                <div class="sp-hero-btns">
                    <a href="<?= BASE_URL . ($banner['enlace'] ?? '/catalogo') ?>" class="sp-btn-primary">
                        <i class="bi bi-grid-3x3-gap"></i> Ver colección
                    </a>
                    <a href="<?= BASE_URL ?>/catalogo?oferta=1" class="sp-btn-outline">
                        <i class="bi bi-lightning"></i> Ver ofertas
                    </a>
                </div>
            </div>

            <!-- La imagen se muestra entera, con su proporción real -->
            <figure class="sp-hero-figure">
                <img src="<?= $img_banner ?>" alt="<?= sanitizar($banner['subtitulo'] ?: $banner['titulo'] ?: 'Banner') ?>"
                     <?= $primero ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
            </figure>
        </div>
    </div>
    <?php
        $primero = false;
        endwhile;
    else:
    ?>
    <!-- Sin banners — slide por defecto -->
    <div class="sp-hero-slide active" style="background:#111">
        <div class="sp-hero-overlay"></div>
        <div class="sp-hero-content">
            <div class="sp-hero-badge">Nuevos drops cada semana</div>
            <h1>Importaciones<br><span>anime & urban</span><br>directo a tu puerta</h1>
            <p>Ropa exclusiva, figuras de colección y accesorios importados. Envíos a todo Perú.</p>
            <div class="sp-hero-btns">
                <a href="<?= BASE_URL ?>/catalogo" class="sp-btn-primary">
                    <i class="bi bi-grid-3x3-gap"></i> Ver catálogo
                </a>
                <a href="<?= BASE_URL ?>/catalogo?oferta=1" class="sp-btn-outline">
                    <i class="bi bi-lightning"></i> Ver ofertas
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Dots -->
    <div class="sp-slider-dots" id="sliderDots"></div>

    

</section>

<!-- ── CATEGORÍAS ─────────────────────────────────────────────── -->
<section class="sp-section">
    <div class="sp-section-header">
        <h2 class="sp-section-title">Categorías <span>¿qué buscas hoy?</span></h2>
    </div>
    <div class="sp-cats-grid">
        <?php
        $iconos = ['bi-tags', 'bi-emoji-smile', 'bi-bag', 'bi-award'];
        $i = 0;
        $categorias->data_seek(0);
        while ($cat = $categorias->fetch_assoc()):
            $icono = $iconos[$i] ?? 'bi-tag';
            $i++;
        ?>
        <a href="<?= BASE_URL ?>/catalogo?categoria=<?= $cat['id_categoria'] ?>" class="sp-cat-card">
            <i class="bi <?= $icono ?>"></i>
            <h3><?= sanitizar($cat['nombre']) ?></h3>
            <small><?= sanitizar($cat['descripcion']) ?></small>
        </a>
        <?php endwhile; ?>
    </div>
</section>

<div class="sp-divider"></div>

<!-- ── PRODUCTOS DESTACADOS ───────────────────────────────────── -->
<?php if ($destacados->num_rows > 0): ?>
<section class="sp-section">
    <div class="sp-section-header">
        <h2 class="sp-section-title">Destacados <span>los más pedidos</span></h2>
        <a href="<?= BASE_URL ?>/catalogo?destacado=1" class="sp-section-link">Ver todos →</a>
    </div>
    <div class="sp-productos-grid">
        <?php while ($prod = $destacados->fetch_assoc()): ?>
            <?php include 'includes/card_producto.php'; ?>
        <?php endwhile; ?>
    </div>
</section>
<div class="sp-divider"></div>
<?php endif; ?>

<!-- ── NUEVOS PRODUCTOS ───────────────────────────────────────── -->
<section class="sp-section">
    <div class="sp-section-header">
        <h2 class="sp-section-title">Nuevos <span>recién llegados</span></h2>
        <a href="<?= BASE_URL ?>/catalogo" class="sp-section-link">Ver todos →</a>
    </div>
    <div class="sp-productos-grid">
        <?php while ($prod = $nuevos->fetch_assoc()): ?>
            <?php include 'includes/card_producto.php'; ?>
        <?php endwhile; ?>
    </div>
</section>

<!-- ── BANNER MÉTODOS DE PAGO ─────────────────────────────────── -->
<div class="sp-banner-pagos">
    <div class="sp-banner-pagos-inner">
        <div>
            <h3>Paga con <span>Yape</span> o en efectivo</h3>
            <p>Sin complicaciones — elige cómo pagar al finalizar tu pedido</p>
        </div>
        <div class="sp-pagos-icons">
            <div class="sp-pago-item"><i class="bi bi-qr-code"></i><span>Yape QR</span></div>
            <div class="sp-pago-item"><i class="bi bi-cash"></i><span>Efectivo</span></div>
            <div class="sp-pago-item"><i class="bi bi-truck"></i><span>Envío Perú</span></div>
        </div>
        <a href="<?= BASE_URL ?>/catalogo" class="sp-btn-primary">Comprar ahora</a>
    </div>
</div>

<!-- ── OFERTAS ────────────────────────────────────────────────── -->
<?php if ($ofertas->num_rows > 0): ?>
<section class="sp-section">
    <div class="sp-section-header">
        <h2 class="sp-section-title">Ofertas <span>por tiempo limitado</span></h2>
        <a href="<?= BASE_URL ?>/catalogo?oferta=1" class="sp-section-link">Ver todas →</a>
    </div>
    <div class="sp-productos-grid">
        <?php while ($prod = $ofertas->fetch_assoc()): ?>
            <?php include 'includes/card_producto.php'; ?>
        <?php endwhile; ?>
    </div>
</section>
<?php endif; ?>

<script>
(function(){
    const slides        = document.querySelectorAll('.sp-hero-slide');
    const dotsContainer = document.getElementById('sliderDots');
    if (!slides.length || !dotsContainer || slides.length < 2) return;

    let actual = 0, timer;

    // Crear dots
    slides.forEach((_, i) => {
        const d     = document.createElement('span');
        d.className = 'sp-dot' + (i === 0 ? ' active' : '');
        d.onclick   = () => { irA(i); reset(); };
        dotsContainer.appendChild(d);
    });

    function irA(n) {
        slides[actual].classList.remove('active');
        dotsContainer.children[actual].classList.remove('active');
        actual = (n + slides.length) % slides.length;
        slides[actual].classList.add('active');
        dotsContainer.children[actual].classList.add('active');
    }

    function reset() {
        clearInterval(timer);
        timer = setInterval(() => irA(actual + 1), 4000);
    }

    window.sliderSiguiente = () => { irA(actual + 1); reset(); };
    window.sliderAnterior  = () => { irA(actual - 1); reset(); };

    timer = setInterval(() => irA(actual + 1), 4000);
})();
</script>

<?php require_once 'includes/footer.php'; ?>