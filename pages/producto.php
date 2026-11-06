<?php
require_once '../config/config.php';
require_once ROOT_PATH . '/includes/auth.php';

$id_producto = (int) ($_GET['id'] ?? 0);

if ($id_producto <= 0) {
    redirigir('catalogo');
}

$conn = conectar();

// Producto
$result = $conn->query("
    SELECT p.*, c.nombre AS categoria_nombre, c.id_categoria
    FROM productos p
    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
    WHERE p.id_producto = $id_producto AND p.activo = 1
");

if ($result->num_rows === 0) redirigir('catalogo');

$prod = $result->fetch_assoc();

// Imágenes
$imagenes = $conn->query("SELECT * FROM imagenes_producto WHERE id_producto = $id_producto ORDER BY orden ASC");

// Reseñas
$resenas = $conn->query("
    SELECT r.*, u.nombre AS nombre_usuario
    FROM resenas r
    JOIN usuarios u ON r.id_usuario = u.id_usuario
    WHERE r.id_producto = $id_producto
    ORDER BY r.fecha DESC
");

// Puntuación media
$punt = $conn->query("SELECT AVG(puntuacion) as media, COUNT(*) as total FROM resenas WHERE id_producto = $id_producto")->fetch_assoc();
$media_puntuacion = round((float)$punt['media'], 1);
$total_resenas    = (int) $punt['total'];

// Productos relacionados
$relacionados = $conn->query("
    SELECT p.*,
           (SELECT url FROM imagenes_producto WHERE id_producto = p.id_producto AND principal = 1 LIMIT 1) AS imagen_principal,
           COALESCE(AVG(r.puntuacion), 0) AS puntuacion_media,
           COUNT(r.id_resena) AS total_resenas
    FROM productos p
    LEFT JOIN resenas r ON r.id_producto = p.id_producto
    WHERE p.id_categoria = {$prod['id_categoria']}
    AND p.id_producto != $id_producto
    AND p.activo = 1
    GROUP BY p.id_producto
    ORDER BY RAND()
    LIMIT 4
");

// Wishlist
$en_wishlist = false;
if (esta_logueado()) {
    $uid = (int) $_SESSION['usuario']['id_usuario'];
    $w   = $conn->query("SELECT id_wishlist FROM wishlist WHERE id_usuario = $uid AND id_producto = $id_producto");
    $en_wishlist = $w->num_rows > 0;
}

// Ya reseñó este producto
$ya_reseno = false;
if (esta_logueado()) {
    $uid = (int) $_SESSION['usuario']['id_usuario'];
    $r   = $conn->query("SELECT id_resena FROM resenas WHERE id_usuario = $uid AND id_producto = $id_producto");
    $ya_reseno = $r->num_rows > 0;
}

$imagen_principal = '';
if ($imagenes->num_rows > 0) {
    $imagenes->data_seek(0);
    while ($img = $imagenes->fetch_assoc()) {
        if ($img['principal']) { $imagen_principal = $img['url']; break; }
    }
    if (!$imagen_principal) { $imagenes->data_seek(0); $imagen_principal = $imagenes->fetch_assoc()['url']; }
    $imagenes->data_seek(0);
}

$titulo_pagina    = sanitizar($prod['nombre']);
$meta_descripcion = sanitizar(substr($prod['descripcion'] ?? '', 0, 160));
$css_extra        = 'producto';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="sp-container" style="padding-top:32px;padding-bottom:48px">

    <!-- Breadcrumb -->
    <nav class="sp-breadcrumb">
        <a href="<?= BASE_URL ?>">Inicio</a><span>/</span>
        <a href="<?= BASE_URL ?>/catalogo">Catálogo</a><span>/</span>
        <a href="<?= BASE_URL ?>/catalogo?categoria=<?= $prod['id_categoria'] ?>"><?= sanitizar($prod['categoria_nombre']) ?></a><span>/</span>
        <span><?= sanitizar($prod['nombre']) ?></span>
    </nav>

    <!-- ── DETALLE PRODUCTO ──────────────────────────────────── -->
    <div class="sp-producto-layout">

        <!-- Galería -->
        <div class="sp-galeria">
            <div class="sp-galeria-principal">
                <img src="<?= $imagen_principal ? UPLOADS_URL . $imagen_principal : IMG_DEFAULT ?>"
                     alt="<?= sanitizar($prod['nombre']) ?>"
                     id="imgPrincipal">
            </div>
            <?php if ($imagenes->num_rows > 1): ?>
            <div class="sp-galeria-thumbs">
                <?php $imagenes->data_seek(0); while ($img = $imagenes->fetch_assoc()): ?>
                <div class="sp-thumb <?= $img['principal'] ? 'active' : '' ?>"
                     onclick="cambiarImagen('<?= UPLOADS_URL . $img['url'] ?>', this)">
                    <img src="<?= UPLOADS_URL . $img['url'] ?>" alt="<?= sanitizar($prod['nombre']) ?>">
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Info producto -->
        <div class="sp-producto-info">

            <span class="sp-prod-categoria-badge"><?= sanitizar($prod['categoria_nombre']) ?></span>

            <h1 class="sp-producto-titulo"><?= sanitizar($prod['nombre']) ?></h1>

            <!-- Estrellas -->
            <?php if ($total_resenas > 0): ?>
            <div class="sp-producto-stars">
                <span class="sp-stars-text"><?= mostrar_estrellas(round($media_puntuacion)) ?></span>
                <span class="sp-stars-media"><?= $media_puntuacion ?></span>
                <a href="#resenas" class="sp-stars-count"><?= $total_resenas ?> reseña<?= $total_resenas !== 1 ? 's' : '' ?></a>
            </div>
            <?php endif; ?>

            <!-- Precio -->
            <div class="sp-producto-precio">
                <?= formatear_precio($prod['precio']) ?>
                <?php if (!empty($prod['precio_anterior']) && $prod['precio_anterior'] > $prod['precio']): ?>
                <span class="sp-prod-precio-anterior" style="font-size:18px"><?= formatear_precio($prod['precio_anterior']) ?></span>
                <span class="sp-tag sp-tag-oferta" style="font-size:13px;vertical-align:middle">
                    -<?= round(100 - ($prod['precio'] / $prod['precio_anterior']) * 100) ?>%
                </span>
                <?php endif; ?>
            </div>

            <!-- Stock -->
            <div class="sp-producto-stock">
                <?php if ($prod['stock'] > 10): ?>
                <span class="sp-stock-ok"><i class="bi bi-check-circle"></i> En stock</span>
                <?php elseif ($prod['stock'] > 0): ?>
                <span class="sp-stock-poco"><i class="bi bi-exclamation-circle"></i> Últimas <?= $prod['stock'] ?> unidades</span>
                <?php else: ?>
                <span class="sp-stock-no"><i class="bi bi-x-circle"></i> Sin stock</span>
                <?php endif; ?>
            </div>

            <!-- Descripción -->
            <?php if (!empty($prod['descripcion'])): ?>
            <p class="sp-producto-desc"><?= nl2br(sanitizar($prod['descripcion'])) ?></p>
            <?php endif; ?>

            <!-- Tallas -->
<?php
$tallas_prod = $conn->query("SELECT * FROM producto_tallas WHERE id_producto = $id_producto ORDER BY FIELD(talla,'XS','S','M','L','XL','XXL'), CAST(talla AS UNSIGNED), talla");
if ($tallas_prod->num_rows > 0):
?>
<div class="sp-producto-tallas">
    <h4>Elige tu talla</h4>
    <div class="sp-filtro-tallas">
        <?php while ($t = $tallas_prod->fetch_assoc()): ?>
        <span class="sp-talla <?= $t['stock'] > 0 ? '' : 'agotada' ?>"
              data-talla="<?= $t['talla'] ?>"
              data-stock="<?= $t['stock'] ?>"
              onclick="<?= $t['stock'] > 0 ? 'seleccionarTalla(this)' : '' ?>"
              title="<?= $t['stock'] === 0 ? 'Agotado' : $t['stock'] . ' disponibles' ?>">
            <?= sanitizar($t['talla']) ?>
        </span>
        <?php endwhile; ?>
    </div>
    <input type="hidden" id="tallaSeleccionada" value="">
    <p class="sp-talla-aviso" id="tallaAviso" style="display:none;color:var(--error);font-size:12px;margin-top:6px">
        <i class="bi bi-exclamation-circle"></i> Selecciona una talla
    </p>
</div>
<?php endif; ?>

            <!-- Cantidad -->
            <?php if ($prod['stock'] > 0): ?>
            <div class="sp-producto-cantidad">
                <h4>Cantidad</h4>
                <div class="sp-cantidad-control">
                    <button type="button" onclick="cambiarCantidad(-1)">−</button>
                    <input type="number" id="cantidadInput" value="1" min="1" max="<?= $prod['stock'] ?>">
                    <button type="button" onclick="cambiarCantidad(1)">+</button>
                </div>
            </div>

            <!-- Botones -->
            <div class="sp-producto-btns">
                <button class="sp-btn-primary sp-btn-carrito-grande"
                        onclick="agregarDesdeProducto(<?= $prod['id_producto'] ?>)">
                    <i class="bi bi-bag-plus"></i> Añadir al carrito
                </button>
                <button class="sp-btn-wish <?= $en_wishlist ? 'sp-wish-active' : '' ?>"
                        onclick="toggleWishlist(<?= $prod['id_producto'] ?>, this)"
                        style="padding:12px 16px;font-size:20px">
                    <i class="bi <?= $en_wishlist ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                </button>
            </div>
            <?php else: ?>
            <div class="sp-producto-btns">
                <button class="sp-btn-primary" disabled>Sin stock disponible</button>
            </div>
            <?php endif; ?>

            <!-- Info envío -->
            <div class="sp-producto-envio">
                <div class="sp-envio-item">
                    <i class="bi bi-truck"></i>
                    <span>Envíos a todo Perú</span>
                </div>
                <div class="sp-envio-item">
                    <i class="bi bi-qr-code"></i>
                    <span>Pago por Yape QR o efectivo</span>
                </div>
                <div class="sp-envio-item">
                    <i class="bi bi-shield-check"></i>
                    <span>Compra 100% segura</span>
                </div>
            </div>

        </div>
    </div>

    <!-- ── RESEÑAS ────────────────────────────────────────────── -->
    <div class="sp-resenas-section" id="resenas">
        <h2 class="sp-section-title">Reseñas <span><?= $total_resenas ?> valoraciones</span></h2>

        <div class="sp-resenas-layout">

            <!-- Resumen -->
            <?php if ($total_resenas > 0): ?>
            <div class="sp-resenas-resumen">
                <div class="sp-resumen-media">
                    <span class="sp-resumen-numero"><?= $media_puntuacion ?></span>
                    <span class="sp-stars-text" style="font-size:24px"><?= mostrar_estrellas(round($media_puntuacion)) ?></span>
                    <span style="font-size:13px;color:var(--text-muted)"><?= $total_resenas ?> reseñas</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Lista reseñas -->
            <div class="sp-resenas-lista">
                <?php if ($resenas->num_rows > 0): ?>
                <?php while ($res = $resenas->fetch_assoc()): ?>
                <div class="sp-resena-card">
                    <div class="sp-resena-header">
                        <div class="sp-resena-avatar">
                            <?= strtoupper(substr($res['nombre_usuario'], 0, 1)) ?>
                        </div>
                        <div>
                            <p class="sp-resena-nombre"><?= sanitizar($res['nombre_usuario']) ?></p>
                            <p class="sp-resena-fecha"><?= formatear_fecha($res['fecha']) ?></p>
                        </div>
                        <div class="sp-resena-stars">
                            <?= mostrar_estrellas($res['puntuacion']) ?>
                        </div>
                    </div>
                    <?php if (!empty($res['comentario'])): ?>
                    <p class="sp-resena-comentario"><?= sanitizar($res['comentario']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                <p style="color:var(--text-muted);font-size:14px">Aún no hay reseñas para este producto.</p>
                <?php endif; ?>

                <!-- Formulario reseña -->
                <?php if (esta_logueado() && !$ya_reseno): ?>
                <div class="sp-resena-form">
                    <h4>Deja tu reseña</h4>
                    <form action="<?= BASE_URL ?>/api/resenas.php" method="POST" id="resenaForm">
                        <input type="hidden" name="id_producto" value="<?= $id_producto ?>">
                        <?= csrf_campo() ?>
                        <div class="sp-estrellas-selector" id="estrellasSelector">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="sp-estrella" data-valor="<?= $i ?>" onclick="seleccionarEstrella(<?= $i ?>)">★</span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="puntuacion" id="puntuacionInput" value="0">
                        <textarea name="comentario" placeholder="Cuéntanos tu experiencia con este producto..." rows="4"></textarea>
                        <button type="submit" class="sp-btn-primary">
                            <i class="bi bi-send"></i> Enviar reseña
                        </button>
                    </form>
                </div>
                <?php elseif (!esta_logueado()): ?>
                <p class="sp-resena-login">
                    <a href="#" onclick="abrirModal('modalLogin')">Inicia sesión</a> para dejar una reseña.
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── PRODUCTOS RELACIONADOS ─────────────────────────────── -->
    <?php if ($relacionados->num_rows > 0): ?>
    <div class="sp-relacionados">
        <div class="sp-section-header">
            <h2 class="sp-section-title">También te puede gustar</h2>
        </div>
        <div class="sp-productos-grid">
            <?php while ($prod = $relacionados->fetch_assoc()): ?>
                <?php include ROOT_PATH . '/includes/card_producto.php'; ?>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function cambiarImagen(url, thumb) {
    document.getElementById('imgPrincipal').src = url;
    document.querySelectorAll('.sp-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}

function cambiarCantidad(delta) {
    const input = document.getElementById('cantidadInput');
    const nueva = Math.max(1, Math.min(parseInt(input.value) + delta, parseInt(input.max)));
    input.value = nueva;
}

function agregarDesdeProducto(id, talla) {
    const cantidad = parseInt(document.getElementById('cantidadInput')?.value || 1);
    agregarAlCarrito(id, cantidad, talla || null);
}

function seleccionarTalla(el) {
    document.querySelectorAll('.sp-talla').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('tallaSeleccionada').value = el.dataset.talla;
    document.getElementById('tallaAviso').style.display = 'none';
}

function agregarDesdeProducto(id) {
    const talla    = document.getElementById('tallaSeleccionada')?.value || null;
    const hayTallas = document.querySelectorAll('.sp-talla').length > 0;

    if (hayTallas && !talla) {
        document.getElementById('tallaAviso').style.display = 'block';
        mostrarToast('Selecciona una talla', 'error');
        return;
    }

    const cantidad = parseInt(document.getElementById('cantidadInput')?.value || 1);
    agregarAlCarrito(id, cantidad, talla);
}

// Reseña via AJAX
const resenaForm = document.getElementById('resenaForm');
if (resenaForm) {
    resenaForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const puntuacion = document.getElementById('puntuacionInput').value;
        if (puntuacion == 0) { mostrarToast('Selecciona una puntuación', 'error'); return; }

        const btn  = resenaForm.querySelector('button[type=submit]');
        const data = new FormData(resenaForm);
        btn.disabled = true;

        try {
            const res  = await fetch(resenaForm.action, { method: 'POST', body: data });
            const json = await res.json();
            if (json.success) {
                mostrarToast('¡Reseña enviada!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                mostrarToast(json.error || 'Error al enviar', 'error');
                btn.disabled = false;
            }
        } catch {
            mostrarToast('Error de conexión', 'error');
            btn.disabled = false;
        }
    });
}
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>