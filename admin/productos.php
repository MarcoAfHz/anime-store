<?php
$titulo_pagina = 'Productos — Admin';
$css_extra     = 'admin';
require_once '../includes/header.php';
requiere_admin();

$conn = conectar();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar($_POST['csrf_token'] ?? '');
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $id          = (int) ($_POST['id_producto'] ?? 0);
        $nombre      = trim(strip_tags($_POST['nombre']      ?? ''));
        $descripcion = trim(strip_tags($_POST['descripcion'] ?? ''));
        $precio      = (float) ($_POST['precio']       ?? 0);
        $precio_ant  = (float) ($_POST['precio_anterior'] ?? 0);
        $precio_ant_sql = $precio_ant > $precio ? $precio_ant : 'NULL';
        $stock       = (int)   ($_POST['stock']        ?? 0);
        $id_cat      = (int)   ($_POST['id_categoria'] ?? 0);
        $destacado   = isset($_POST['destacado']) ? 1 : 0;
        $activo      = isset($_POST['activo'])    ? 1 : 0;

        $nombre_esc = escapar($nombre);
        $desc_esc   = escapar($descripcion);

        if ($id > 0) {
            $conn->query("UPDATE productos SET nombre='$nombre_esc', descripcion='$desc_esc', precio=$precio, precio_anterior=$precio_ant_sql, stock=$stock, id_categoria=$id_cat, destacado=$destacado, activo=$activo WHERE id_producto=$id");
        } else {
            $conn->query("INSERT INTO productos (nombre, descripcion, precio, precio_anterior, stock, id_categoria, destacado, activo) VALUES ('$nombre_esc', '$desc_esc', $precio, $precio_ant_sql, $stock, $id_cat, $destacado, $activo)");
            $id = $conn->insert_id;
        }

        // Tallas
        $conn->query("DELETE FROM producto_tallas WHERE id_producto = $id");
        if (!empty($_POST['tallas'])) {
            foreach ($_POST['tallas'] as $talla => $stock_talla) {
                $talla_esc   = escapar(sanitizar($talla));
                $stock_talla = (int) $stock_talla;
                if (!empty($talla_esc) && $stock_talla >= 0) {
                    $conn->query("INSERT INTO producto_tallas (id_producto, talla, stock) VALUES ($id, '$talla_esc', $stock_talla)");
                }
            }
        }

        // Imagen
        if (!empty($_FILES['imagen']['name'])) {
            $resultado = subir_imagen($_FILES['imagen'], 'productos');
            if (isset($resultado['success'])) {
                $conn->query("UPDATE imagenes_producto SET principal = 0 WHERE id_producto = $id");
                $nombre_img = escapar($resultado['nombre']);
                $conn->query("INSERT INTO imagenes_producto (id_producto, url, orden, principal) VALUES ($id, '$nombre_img', 1, 1)");
            }
        }

        flash_set('success', 'Producto guardado correctamente.');
        redirigir('admin/productos');
    }

    if ($accion === 'eliminar') {
        $id = (int) ($_POST['id_producto'] ?? 0);
        $conn->query("UPDATE productos SET activo = 0 WHERE id_producto = $id");
        flash_set('success', 'Producto desactivado.');
        redirigir('admin/productos');
    }
}

// Producto a editar
$editar = null;
$tallas_editar = [];
if (isset($_GET['editar'])) {
    $id_editar = (int) $_GET['editar'];
    $editar    = $conn->query("
        SELECT p.*, (SELECT url FROM imagenes_producto WHERE id_producto = p.id_producto AND principal = 1 LIMIT 1) AS imagen
        FROM productos p WHERE p.id_producto = $id_editar
    ")->fetch_assoc();
    $t_res = $conn->query("SELECT * FROM producto_tallas WHERE id_producto = $id_editar ORDER BY talla");
    while ($t = $t_res->fetch_assoc()) $tallas_editar[$t['talla']] = $t['stock'];
}

$productos   = $conn->query("
    SELECT p.*, c.nombre AS categoria_nombre,
           (SELECT url FROM imagenes_producto WHERE id_producto = p.id_producto AND principal = 1 LIMIT 1) AS imagen
    FROM productos p
    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
    ORDER BY p.fecha_creacion DESC
");
$categorias  = $conn->query("SELECT * FROM categorias WHERE activa = 1 ORDER BY nombre");
$tallas_ropa = ['XS','S','M','L','XL','XXL'];
$tallas_zapa = ['36','37','38','39','40','41','42','43','44','45'];
?>

<div class="sp-admin-wrap">
    <?php include ROOT_PATH . '/admin/includes/sidebar.php'; ?>

    <div class="sp-admin-content">
        <div class="sp-admin-header">
            <h1 class="sp-admin-titulo"><?= $editar ? 'Editar: '.sanitizar($editar['nombre']) : (isset($_GET['nuevo']) ? 'Nuevo producto' : 'Productos') ?></h1>
            <?php if (!$editar && !isset($_GET['nuevo'])): ?>
            <a href="?nuevo=1" class="sp-btn-primary"><i class="bi bi-plus-lg"></i> Nuevo producto</a>
            <?php endif; ?>
        </div>

        <?php if ($editar || isset($_GET['nuevo'])): ?>
        <!-- ── FORMULARIO ──────────────────────────────── -->
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="guardar">
                                        <?= csrf_campo() ?>
            <input type="hidden" name="id_producto" value="<?= $editar['id_producto'] ?? 0 ?>">

            <div class="sp-prod-form-layout">

                <!-- Columna izquierda -->
                <div>
                    <div class="sp-admin-card" style="margin-bottom:16px">
                        <h4 class="sp-filtro-titulo">Información básica</h4>

                        <div class="sp-form-group">
                            <label>Nombre *</label>
                            <input type="text" name="nombre" value="<?= sanitizar($editar['nombre'] ?? '') ?>" placeholder="Ej: Polo Naruto Shippuden" required>
                        </div>

                        <div class="sp-form-group">
                            <label>Categoría *</label>
                            <select name="id_categoria" required>
                                <option value="">Selecciona categoría</option>
                                <?php $categorias->data_seek(0); while ($cat = $categorias->fetch_assoc()): ?>
                                <option value="<?= $cat['id_categoria'] ?>" <?= ($editar['id_categoria'] ?? '') == $cat['id_categoria'] ? 'selected' : '' ?>>
                                    <?= sanitizar($cat['nombre']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="sp-form-group">
                            <label>Descripción</label>
                            <textarea name="descripcion" rows="3" placeholder="Describe el producto..."><?= sanitizar($editar['descripcion'] ?? '') ?></textarea>
                        </div>

                        <div class="sp-form-row">
                            <div class="sp-form-group">
                                <label>Precio (S/) *</label>
                                <input type="number" name="precio" step="0.01" min="0" value="<?= $editar['precio'] ?? '' ?>" placeholder="0.00" required>
                            </div>
                            <div class="sp-form-group">
                                <label>Precio anterior (S/) <span style="color:var(--text-muted)">· oferta</span></label>
                                <input type="number" name="precio_anterior" step="0.01" min="0" value="<?= $editar['precio_anterior'] ?? '' ?>" placeholder="Vacío = sin oferta">
                            </div>
                        </div>

                        <div class="sp-form-row">
                            <div class="sp-form-group">
                                <label>Stock general</label>
                                <input type="number" name="stock" min="0" value="<?= $editar['stock'] ?? 0 ?>">
                            </div>
                        </div>

                        <div style="display:flex;gap:20px">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--text-secondary)">
                                <input type="checkbox" name="destacado" value="1" <?= !empty($editar['destacado']) ? 'checked' : '' ?> style="accent-color:var(--accent);width:16px;height:16px">
                                ⭐ Destacado
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--text-secondary)">
                                <input type="checkbox" name="activo" value="1" <?= ($editar['activo'] ?? 1) ? 'checked' : '' ?> style="accent-color:var(--accent);width:16px;height:16px">
                                ✅ Activo
                            </label>
                        </div>
                    </div>

                    <!-- Tallas -->
                    <div class="sp-admin-card">
                        <h4 class="sp-filtro-titulo">Tallas y stock</h4>
                        <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px">
                            Activa solo las tallas que tiene este producto. Deja vacío si no aplica (figuras, accesorios).
                        </p>

                        <p style="font-size:11px;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px">Ropa</p>
                        <div class="sp-tallas-grid" style="margin-bottom:16px">
                            <?php foreach ($tallas_ropa as $t): ?>
                            <div class="sp-talla-row">
                                <label class="sp-talla-toggle <?= isset($tallas_editar[$t]) ? 'activa' : '' ?>" id="label-<?= $t ?>">
                                    <input type="checkbox" class="sp-talla-check" data-talla="<?= $t ?>"
                                           <?= isset($tallas_editar[$t]) ? 'checked' : '' ?>
                                           onchange="toggleTalla(this)">
                                    <?= $t ?>
                                </label>
                                <input type="number" name="tallas[<?= $t ?>]"
                                       min="0" value="<?= $tallas_editar[$t] ?? 0 ?>"
                                       class="sp-talla-num"
                                       <?= !isset($tallas_editar[$t]) ? 'disabled' : '' ?>>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <p style="font-size:11px;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px">Calzado</p>
                        <div class="sp-tallas-grid">
                            <?php foreach ($tallas_zapa as $t): ?>
                            <div class="sp-talla-row">
                                <label class="sp-talla-toggle <?= isset($tallas_editar[$t]) ? 'activa' : '' ?>" id="label-<?= $t ?>">
                                    <input type="checkbox" class="sp-talla-check" data-talla="<?= $t ?>"
                                           <?= isset($tallas_editar[$t]) ? 'checked' : '' ?>
                                           onchange="toggleTalla(this)">
                                    <?= $t ?>
                                </label>
                                <input type="number" name="tallas[<?= $t ?>]"
                                       min="0" value="<?= $tallas_editar[$t] ?? 0 ?>"
                                       class="sp-talla-num"
                                       <?= !isset($tallas_editar[$t]) ? 'disabled' : '' ?>>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha -->
                <div>
                    <div class="sp-admin-card">
                        <h4 class="sp-filtro-titulo">Imagen del producto</h4>

                        <?php if (!empty($editar['imagen'])): ?>
                        <div style="margin-bottom:16px;text-align:center">
                            <img src="<?= UPLOADS_URL . $editar['imagen'] ?>"
                                 style="width:160px;height:160px;object-fit:contain;border-radius:var(--radius-md);border:1px solid var(--border);padding:8px">
                            <p style="font-size:12px;color:var(--text-muted);margin-top:8px">Imagen actual</p>
                        </div>
                        <?php else: ?>
                        <div style="width:160px;height:160px;background:var(--bg-secondary);border:1px dashed var(--border);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:var(--text-muted);flex-direction:column;gap:8px">
                            <i class="bi bi-image" style="font-size:32px"></i>
                            <span style="font-size:12px">Sin imagen</span>
                        </div>
                        <?php endif; ?>

                        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" style="width:100%;font-size:13px;color:var(--text-secondary)">
                        <p style="font-size:11px;color:var(--text-muted);margin-top:8px">JPG, PNG o WEBP · Máx 2MB<br>Cuadrada 1:1 · Fondo negro</p>
                    </div>

                    <div class="sp-admin-btns" style="flex-direction:column;margin-top:16px">
                        <button type="submit" class="sp-btn-primary w-100">
                            <i class="bi bi-check-circle"></i> Guardar producto
                        </button>
                        <a href="<?= BASE_URL ?>/admin/productos" class="sp-btn-outline w-100" style="text-align:center">
                            Cancelar
                        </a>
                    </div>
                </div>

            </div>
        </form>

        <?php else: ?>
        <!-- ── LISTA PRODUCTOS ────────────────────────── -->
        <div class="sp-admin-card">
            <div class="sp-tabla-wrap">
                <table class="sp-tabla">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($productos->num_rows === 0): ?>
                        <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:32px">No hay productos aún.</td></tr>
                        <?php else: ?>
                        <?php while ($p = $productos->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="<?= $p['imagen'] ? UPLOADS_URL . $p['imagen'] : IMG_DEFAULT ?>"
                                     style="width:44px;height:44px;object-fit:contain;border-radius:6px;border:1px solid var(--border)">
                            </td>
                            <td style="color:var(--text-primary);font-weight:500"><?= sanitizar($p['nombre']) ?></td>
                            <td><?= sanitizar($p['categoria_nombre'] ?? '—') ?></td>
                            <td><?= formatear_precio($p['precio']) ?></td>
                            <td style="color:<?= $p['stock'] <= 5 ? 'var(--warning)' : 'var(--text-secondary)' ?>;font-weight:<?= $p['stock'] <= 5 ? '700' : '400' ?>">
                                <?= $p['stock'] ?>
                            </td>
                            <td>
                                <span class="sp-estado <?= $p['activo'] ? 'sp-estado-confirmado' : 'sp-estado-cancelado' ?>">
                                    <?= $p['activo'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <a href="?editar=<?= $p['id_producto'] ?>" class="sp-admin-btn-sm" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/producto?id=<?= $p['id_producto'] ?>" target="_blank" class="sp-admin-btn-sm" title="Ver en tienda">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form method="POST" onsubmit="return confirm('¿Desactivar este producto?')" style="display:inline">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="id_producto" value="<?= $p['id_producto'] ?>">
                                        <button type="submit" class="sp-admin-btn-sm" title="Desactivar" style="border-color:var(--error);color:var(--error)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleTalla(chk) {
    const row   = chk.closest('.sp-talla-row');
    const input = row.querySelector('.sp-talla-num');
    const label = row.querySelector('.sp-talla-toggle');
    input.disabled = !chk.checked;
    if (!chk.checked) input.value = 0;
    label.classList.toggle('activa', chk.checked);
}
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>