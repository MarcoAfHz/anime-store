<?php
$titulo_pagina = 'Banners — Admin';
$css_extra     = 'admin';
require_once '../includes/header.php';
requiere_admin();

$conn         = conectar();
$banners_url  = BASE_URL . '/assets/img/banners/';
$banners_path = ROOT_PATH . '/assets/img/banners/';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar($_POST['csrf_token'] ?? '');
    $accion = $_POST['accion'] ?? '';
    $id     = (int) ($_POST['id_banner'] ?? 0);

    if ($accion === 'guardar') {
        $titulo    = escapar(trim(strip_tags($_POST['titulo']    ?? '')));
        $subtitulo = escapar(trim(strip_tags($_POST['subtitulo'] ?? '')));
        $enlace    = trim($_POST['enlace'] ?? '');
        $orden     = max(1, min(255, (int) ($_POST['orden'] ?? 1)));
        $activo    = isset($_POST['activo']) ? 1 : 0;

        // Solo enlaces internos de la tienda (empiezan por /)
        if ($enlace === '' || !str_starts_with($enlace, '/') || str_starts_with($enlace, '//')) {
            $enlace = '/catalogo';
        }
        $enlace = escapar($enlace);

        $imagen = null;
        if (!empty($_FILES['imagen']['name'])) {
            $resultado = subir_imagen($_FILES['imagen'], 'banners');
            if (isset($resultado['error'])) {
                flash_set('error', $resultado['error']);
                redirigir('admin/banners' . ($id ? "?editar=$id" : '?nuevo=1'));
            }
            $imagen = escapar($resultado['nombre']);
        }

        if ($id > 0) {
            $set_imagen = $imagen ? ", imagen = '$imagen'" : '';
            $conn->query("UPDATE banners SET titulo = '$titulo', subtitulo = '$subtitulo', enlace = '$enlace', orden = $orden, activo = $activo $set_imagen WHERE id_banner = $id");
        } else {
            if (!$imagen) {
                flash_set('error', 'Sube una imagen para el banner.');
                redirigir('admin/banners?nuevo=1');
            }
            $conn->query("INSERT INTO banners (imagen, titulo, subtitulo, enlace, orden, activo) VALUES ('$imagen', '$titulo', '$subtitulo', '$enlace', $orden, $activo)");
        }

        flash_set('success', 'Banner guardado correctamente.');
        redirigir('admin/banners');
    }

    if ($accion === 'toggle' && $id > 0) {
        $conn->query("UPDATE banners SET activo = 1 - activo WHERE id_banner = $id");
        redirigir('admin/banners');
    }

    if ($accion === 'eliminar' && $id > 0) {
        $conn->query("DELETE FROM banners WHERE id_banner = $id");
        flash_set('success', 'Banner eliminado.');
        redirigir('admin/banners');
    }
}

// Banner a editar
$editar = null;
if (isset($_GET['editar'])) {
    $id_editar = (int) $_GET['editar'];
    $editar    = $conn->query("SELECT * FROM banners WHERE id_banner = $id_editar")->fetch_assoc();
}

$banners    = $conn->query("SELECT * FROM banners ORDER BY activo DESC, orden ASC, id_banner ASC");
$categorias = $conn->query("SELECT id_categoria, nombre FROM categorias WHERE activa = 1 ORDER BY nombre");
?>

<div class="sp-admin-wrap">
    <?php include ROOT_PATH . '/admin/includes/sidebar.php'; ?>

    <div class="sp-admin-content">
        <div class="sp-admin-header">
            <h1 class="sp-admin-titulo"><?= $editar ? 'Editar banner' : (isset($_GET['nuevo']) ? 'Nuevo banner' : 'Banners de portada') ?></h1>
            <?php if (!$editar && !isset($_GET['nuevo'])): ?>
            <a href="?nuevo=1" class="sp-btn-primary"><i class="bi bi-plus-lg"></i> Nuevo banner</a>
            <?php endif; ?>
        </div>

        <?php if ($editar || isset($_GET['nuevo'])): ?>
        <!-- ── FORMULARIO ──────────────────────────────── -->
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="guardar">
            <?= csrf_campo() ?>
            <input type="hidden" name="id_banner" value="<?= $editar['id_banner'] ?? 0 ?>">

            <div class="sp-prod-form-layout">
                <div class="sp-admin-card">
                    <h4 class="sp-filtro-titulo">Textos del banner</h4>

                    <div class="sp-form-group">
                        <label>Etiqueta <span style="color:var(--text-muted)">(texto pequeño arriba)</span></label>
                        <input type="text" name="titulo" maxlength="150" value="<?= sanitizar($editar['titulo'] ?? '') ?>" placeholder="Ej: Ropa anime exclusiva">
                    </div>

                    <div class="sp-form-group">
                        <label>Título grande</label>
                        <input type="text" name="subtitulo" maxlength="255" value="<?= sanitizar($editar['subtitulo'] ?? '') ?>" placeholder="Ej: Polos y hoodies importados">
                    </div>

                    <div class="sp-form-group">
                        <label>Enlace del botón "Ver colección"</label>
                        <input type="text" name="enlace" id="inputEnlace" value="<?= sanitizar($editar['enlace'] ?? '/catalogo') ?>" placeholder="/catalogo">
                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px">
                            <button type="button" class="sp-admin-btn-sm" onclick="ponerEnlace('/catalogo')">Catálogo</button>
                            <button type="button" class="sp-admin-btn-sm" onclick="ponerEnlace('/catalogo?oferta=1')">Ofertas</button>
                            <button type="button" class="sp-admin-btn-sm" onclick="ponerEnlace('/catalogo?destacado=1')">Destacados</button>
                            <?php while ($c = $categorias->fetch_assoc()): ?>
                            <button type="button" class="sp-admin-btn-sm" onclick="ponerEnlace('/catalogo?categoria=<?= $c['id_categoria'] ?>')"><?= sanitizar($c['nombre']) ?></button>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <div class="sp-form-row">
                        <div class="sp-form-group">
                            <label>Orden</label>
                            <input type="number" name="orden" min="1" max="255" value="<?= $editar['orden'] ?? 1 ?>">
                        </div>
                        <div class="sp-form-group" style="display:flex;align-items:flex-end">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--text-secondary);padding-bottom:12px">
                                <input type="checkbox" name="activo" value="1" <?= ($editar['activo'] ?? 1) ? 'checked' : '' ?> style="accent-color:var(--accent);width:16px;height:16px">
                                Visible en la portada
                            </label>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="sp-admin-card">
                        <h4 class="sp-filtro-titulo">Imagen <?= $editar ? '' : '*' ?></h4>
                        <?php if (!empty($editar['imagen'])): ?>
                        <img src="<?= $banners_url . rawurlencode($editar['imagen']) ?>" alt=""
                             style="width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:var(--radius-md);border:1px solid var(--border);margin-bottom:12px">
                        <?php endif; ?>
                        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" <?= $editar ? '' : 'required' ?>
                               style="width:100%;font-size:13px;color:var(--text-secondary)">
                        <p style="font-size:11px;color:var(--text-muted);margin-top:8px">JPG, PNG o WEBP · Máx 2MB<br>Horizontal, recomendado 1920×800</p>
                    </div>

                    <div class="sp-admin-btns" style="flex-direction:column;margin-top:16px">
                        <button type="submit" class="sp-btn-primary w-100"><i class="bi bi-check-circle"></i> Guardar banner</button>
                        <a href="<?= BASE_URL ?>/admin/banners" class="sp-btn-outline w-100" style="text-align:center">Cancelar</a>
                    </div>
                </div>
            </div>
        </form>

        <script>
        function ponerEnlace(url) { document.getElementById('inputEnlace').value = url; }
        </script>

        <?php else: ?>
        <!-- ── LISTA ──────────────────────────────────── -->
        <div class="sp-admin-card">
            <div class="sp-tabla-wrap">
                <table class="sp-tabla">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Textos</th>
                            <th>Enlace</th>
                            <th>Orden</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($banners->num_rows === 0): ?>
                        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px">No hay banners. La portada mostrará un mensaje por defecto.</td></tr>
                        <?php endif; ?>
                        <?php while ($b = $banners->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="<?= is_file($banners_path . $b['imagen']) ? $banners_url . rawurlencode($b['imagen']) : IMG_DEFAULT ?>" alt=""
                                     style="width:120px;height:56px;object-fit:cover;border-radius:6px;border:1px solid var(--border)">
                            </td>
                            <td>
                                <div style="color:var(--text-primary);font-weight:500"><?= sanitizar($b['subtitulo'] ?: '—') ?></div>
                                <small style="color:var(--text-muted)"><?= sanitizar($b['titulo'] ?? '') ?></small>
                            </td>
                            <td><code style="color:var(--text-secondary)"><?= sanitizar($b['enlace']) ?></code></td>
                            <td><?= $b['orden'] ?></td>
                            <td>
                                <span class="sp-estado <?= $b['activo'] ? 'sp-estado-confirmado' : 'sp-estado-cancelado' ?>">
                                    <?= $b['activo'] ? 'Visible' : 'Oculto' ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <a href="?editar=<?= $b['id_banner'] ?>" class="sp-admin-btn-sm" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="accion" value="toggle">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="id_banner" value="<?= $b['id_banner'] ?>">
                                        <button type="submit" class="sp-admin-btn-sm" title="<?= $b['activo'] ? 'Ocultar' : 'Mostrar' ?>">
                                            <i class="bi bi-eye<?= $b['activo'] ? '-slash' : '' ?>"></i>
                                        </button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('¿Eliminar este banner?')" style="display:inline">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="id_banner" value="<?= $b['id_banner'] ?>">
                                        <button type="submit" class="sp-admin-btn-sm" title="Eliminar" style="border-color:var(--error);color:var(--error)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
