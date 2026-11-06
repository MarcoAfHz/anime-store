<?php
$titulo_pagina = 'Categorías — Admin';
$css_extra     = 'admin';
require_once '../includes/header.php';
requiere_admin();

$conn = conectar();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar($_POST['csrf_token'] ?? '');
    $accion = $_POST['accion'] ?? '';
    $id     = (int) ($_POST['id_categoria'] ?? 0);

    if ($accion === 'guardar') {
        $nombre      = trim($_POST['nombre']      ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $activa      = isset($_POST['activa']) ? 1 : 0;

        if (mb_strlen($nombre) < 2) {
            flash_set('error', 'El nombre debe tener al menos 2 caracteres.');
            redirigir('admin/categorias' . ($id ? "?editar=$id" : '?nuevo=1'));
        }

        $nombre_esc = escapar(strip_tags($nombre));
        $desc_esc   = escapar(strip_tags($descripcion));

        if ($id > 0) {
            $conn->query("UPDATE categorias SET nombre = '$nombre_esc', descripcion = '$desc_esc', activa = $activa WHERE id_categoria = $id");
        } else {
            $conn->query("INSERT INTO categorias (nombre, descripcion, activa) VALUES ('$nombre_esc', '$desc_esc', $activa)");
        }

        flash_set('success', 'Categoría guardada correctamente.');
        redirigir('admin/categorias');
    }

    if ($accion === 'eliminar' && $id > 0) {
        $usados = (int) $conn->query("SELECT COUNT(*) AS t FROM productos WHERE id_categoria = $id")->fetch_assoc()['t'];
        if ($usados > 0) {
            flash_set('error', "No se puede eliminar: tiene $usados producto(s). Muévelos a otra categoría o desactívala.");
        } else {
            $conn->query("DELETE FROM categorias WHERE id_categoria = $id");
            flash_set('success', 'Categoría eliminada.');
        }
        redirigir('admin/categorias');
    }
}

// Categoría a editar
$editar = null;
if (isset($_GET['editar'])) {
    $id_editar = (int) $_GET['editar'];
    $editar    = $conn->query("SELECT * FROM categorias WHERE id_categoria = $id_editar")->fetch_assoc();
}

$categorias = $conn->query("
    SELECT c.*,
           COUNT(p.id_producto)                  AS total_productos,
           COALESCE(SUM(p.activo = 1), 0)        AS productos_activos
    FROM categorias c
    LEFT JOIN productos p ON p.id_categoria = c.id_categoria
    GROUP BY c.id_categoria
    ORDER BY c.id_categoria ASC
");
?>

<div class="sp-admin-wrap">
    <?php include ROOT_PATH . '/admin/includes/sidebar.php'; ?>

    <div class="sp-admin-content">
        <div class="sp-admin-header">
            <h1 class="sp-admin-titulo"><?= $editar ? 'Editar: ' . sanitizar($editar['nombre']) : (isset($_GET['nuevo']) ? 'Nueva categoría' : 'Categorías') ?></h1>
            <?php if (!$editar && !isset($_GET['nuevo'])): ?>
            <a href="?nuevo=1" class="sp-btn-primary"><i class="bi bi-plus-lg"></i> Nueva categoría</a>
            <?php endif; ?>
        </div>

        <?php if ($editar || isset($_GET['nuevo'])): ?>
        <!-- ── FORMULARIO ──────────────────────────────── -->
        <form method="POST" class="sp-admin-card" style="max-width:640px">
            <input type="hidden" name="accion" value="guardar">
            <?= csrf_campo() ?>
            <input type="hidden" name="id_categoria" value="<?= $editar['id_categoria'] ?? 0 ?>">

            <div class="sp-form-group">
                <label>Nombre *</label>
                <input type="text" name="nombre" value="<?= sanitizar($editar['nombre'] ?? '') ?>" placeholder="Ej: Ropa Anime" required minlength="2" maxlength="100">
            </div>

            <div class="sp-form-group">
                <label>Descripción</label>
                <textarea name="descripcion" rows="3" placeholder="Se muestra en la portada debajo del nombre"><?= sanitizar($editar['descripcion'] ?? '') ?></textarea>
            </div>

            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--text-secondary);margin-bottom:20px">
                <input type="checkbox" name="activa" value="1" <?= ($editar['activa'] ?? 1) ? 'checked' : '' ?> style="accent-color:var(--accent);width:16px;height:16px">
                Visible en la tienda
            </label>

            <div class="sp-admin-btns">
                <button type="submit" class="sp-btn-primary"><i class="bi bi-check-circle"></i> Guardar categoría</button>
                <a href="<?= BASE_URL ?>/admin/categorias" class="sp-btn-outline">Cancelar</a>
            </div>
        </form>

        <?php else: ?>
        <!-- ── LISTA ──────────────────────────────────── -->
        <div class="sp-admin-card">
            <div class="sp-tabla-wrap">
                <table class="sp-tabla">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Productos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($categorias->num_rows === 0): ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px">No hay categorías aún.</td></tr>
                        <?php endif; ?>
                        <?php while ($c = $categorias->fetch_assoc()): ?>
                        <tr>
                            <td style="color:var(--text-primary);font-weight:500"><?= sanitizar($c['nombre']) ?></td>
                            <td style="max-width:360px"><?= $c['descripcion'] ? sanitizar($c['descripcion']) : '—' ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/catalogo?categoria=<?= $c['id_categoria'] ?>" target="_blank" title="Ver en tienda">
                                    <?= $c['productos_activos'] ?> activos
                                    <?php if ($c['total_productos'] > $c['productos_activos']): ?>
                                    <span style="color:var(--text-muted)">/ <?= $c['total_productos'] ?></span>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td>
                                <span class="sp-estado <?= $c['activa'] ? 'sp-estado-confirmado' : 'sp-estado-cancelado' ?>">
                                    <?= $c['activa'] ? 'Visible' : 'Oculta' ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <a href="?editar=<?= $c['id_categoria'] ?>" class="sp-admin-btn-sm" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" onsubmit="return confirm('¿Eliminar esta categoría?')" style="display:inline">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="id_categoria" value="<?= $c['id_categoria'] ?>">
                                        <button type="submit" class="sp-admin-btn-sm" title="Eliminar"
                                                style="border-color:var(--error);color:var(--error)"
                                                <?= $c['total_productos'] > 0 ? 'disabled title="Tiene productos"' : '' ?>>
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
