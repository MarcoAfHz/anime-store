<?php
$titulo_pagina = 'Cupones — Admin';
$css_extra     = 'admin';
require_once '../includes/header.php';
requiere_admin();

$conn = conectar();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar($_POST['csrf_token'] ?? '');
    $accion = $_POST['accion'] ?? '';
    $id     = (int) ($_POST['id_cupon'] ?? 0);

    if ($accion === 'guardar') {
        $codigo     = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $_POST['codigo'] ?? ''));
        $tipo       = ($_POST['tipo'] ?? '') === 'soles' ? 'soles' : 'porcentaje';
        $descuento  = round((float) ($_POST['descuento'] ?? 0), 2);
        $limite     = (int) ($_POST['limite_uso'] ?? 0);
        $expira     = $_POST['fecha_expira'] ?? '';
        $activo     = isset($_POST['activo']) ? 1 : 0;

        $volver = 'admin/cupones' . ($id ? "?editar=$id" : '?nuevo=1');

        if (strlen($codigo) < 3 || strlen($codigo) > 30) {
            flash_set('error', 'El código debe tener entre 3 y 30 caracteres (letras, números, - o _).');
            redirigir($volver);
        }
        if ($descuento <= 0 || ($tipo === 'porcentaje' && $descuento > 100)) {
            flash_set('error', $tipo === 'porcentaje' ? 'El porcentaje debe estar entre 1 y 100.' : 'El descuento debe ser mayor que 0.');
            redirigir($volver);
        }

        $codigo_esc = escapar($codigo);
        $existe     = $conn->query("SELECT id_cupon FROM cupones WHERE codigo = '$codigo_esc' AND id_cupon != $id");
        if ($existe->num_rows > 0) {
            flash_set('error', "Ya existe un cupón con el código $codigo.");
            redirigir($volver);
        }

        $limite_sql = $limite > 0 ? $limite : 'NULL';
        $expira_sql = preg_match('/^\d{4}-\d{2}-\d{2}$/', $expira) ? "'$expira'" : 'NULL';

        if ($id > 0) {
            $conn->query("UPDATE cupones SET codigo = '$codigo_esc', tipo = '$tipo', descuento = $descuento, limite_uso = $limite_sql, fecha_expira = $expira_sql, activo = $activo WHERE id_cupon = $id");
        } else {
            $conn->query("INSERT INTO cupones (codigo, tipo, descuento, limite_uso, fecha_expira, activo) VALUES ('$codigo_esc', '$tipo', $descuento, $limite_sql, $expira_sql, $activo)");
        }

        flash_set('success', "Cupón $codigo guardado.");
        redirigir('admin/cupones');
    }

    if ($accion === 'toggle' && $id > 0) {
        $conn->query("UPDATE cupones SET activo = 1 - activo WHERE id_cupon = $id");
        redirigir('admin/cupones');
    }

    if ($accion === 'eliminar' && $id > 0) {
        // Los pedidos que lo usaron conservan su descuento (FK ON DELETE SET NULL)
        $conn->query("DELETE FROM cupones WHERE id_cupon = $id");
        flash_set('success', 'Cupón eliminado.');
        redirigir('admin/cupones');
    }
}

// Cupón a editar
$editar = null;
if (isset($_GET['editar'])) {
    $id_editar = (int) $_GET['editar'];
    $editar    = $conn->query("SELECT * FROM cupones WHERE id_cupon = $id_editar")->fetch_assoc();
}

$cupones = $conn->query("SELECT * FROM cupones ORDER BY activo DESC, id_cupon DESC");
$hoy     = date('Y-m-d');
?>

<div class="sp-admin-wrap">
    <?php include ROOT_PATH . '/admin/includes/sidebar.php'; ?>

    <div class="sp-admin-content">
        <div class="sp-admin-header">
            <h1 class="sp-admin-titulo"><?= $editar ? 'Editar cupón ' . sanitizar($editar['codigo']) : (isset($_GET['nuevo']) ? 'Nuevo cupón' : 'Cupones') ?></h1>
            <?php if (!$editar && !isset($_GET['nuevo'])): ?>
            <a href="?nuevo=1" class="sp-btn-primary"><i class="bi bi-plus-lg"></i> Nuevo cupón</a>
            <?php endif; ?>
        </div>

        <?php if ($editar || isset($_GET['nuevo'])): ?>
        <!-- ── FORMULARIO ──────────────────────────────── -->
        <form method="POST" class="sp-admin-card" style="max-width:640px">
            <input type="hidden" name="accion" value="guardar">
            <?= csrf_campo() ?>
            <input type="hidden" name="id_cupon" value="<?= $editar['id_cupon'] ?? 0 ?>">

            <div class="sp-form-group">
                <label>Código *</label>
                <input type="text" name="codigo" value="<?= sanitizar($editar['codigo'] ?? '') ?>" placeholder="Ej: OTAKU10"
                       required minlength="3" maxlength="30" style="text-transform:uppercase;letter-spacing:1px">
            </div>

            <div class="sp-form-row">
                <div class="sp-form-group">
                    <label>Tipo de descuento</label>
                    <select name="tipo">
                        <option value="porcentaje" <?= ($editar['tipo'] ?? '') === 'porcentaje' ? 'selected' : '' ?>>Porcentaje (%)</option>
                        <option value="soles" <?= ($editar['tipo'] ?? '') === 'soles' ? 'selected' : '' ?>>Monto fijo (S/)</option>
                    </select>
                </div>
                <div class="sp-form-group">
                    <label>Descuento *</label>
                    <input type="number" name="descuento" step="0.01" min="0.01" value="<?= $editar['descuento'] ?? '' ?>" placeholder="10" required>
                </div>
            </div>

            <div class="sp-form-row">
                <div class="sp-form-group">
                    <label>Límite de usos <span style="color:var(--text-muted)">(vacío = sin límite)</span></label>
                    <input type="number" name="limite_uso" min="1" value="<?= $editar['limite_uso'] ?? '' ?>" placeholder="Ilimitado">
                </div>
                <div class="sp-form-group">
                    <label>Caduca el <span style="color:var(--text-muted)">(vacío = nunca)</span></label>
                    <input type="date" name="fecha_expira" value="<?= $editar['fecha_expira'] ?? '' ?>">
                </div>
            </div>

            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--text-secondary);margin-bottom:20px">
                <input type="checkbox" name="activo" value="1" <?= ($editar['activo'] ?? 1) ? 'checked' : '' ?> style="accent-color:var(--accent);width:16px;height:16px">
                Cupón activo
            </label>

            <div class="sp-admin-btns">
                <button type="submit" class="sp-btn-primary"><i class="bi bi-check-circle"></i> Guardar cupón</button>
                <a href="<?= BASE_URL ?>/admin/cupones" class="sp-btn-outline">Cancelar</a>
            </div>
        </form>

        <?php else: ?>
        <!-- ── LISTA ──────────────────────────────────── -->
        <div class="sp-admin-card">
            <div class="sp-tabla-wrap">
                <table class="sp-tabla">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descuento</th>
                            <th>Usos</th>
                            <th>Caduca</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($cupones->num_rows === 0): ?>
                        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px">No hay cupones. Crea el primero para tus campañas.</td></tr>
                        <?php endif; ?>
                        <?php while ($c = $cupones->fetch_assoc()):
                            $caducado = $c['fecha_expira'] && $c['fecha_expira'] < $hoy;
                            $agotado  = $c['limite_uso'] !== null && $c['usos_actuales'] >= $c['limite_uso'];
                            if (!$c['activo'])   { $estado = ['Inactivo', 'sp-estado-cancelado']; }
                            elseif ($caducado)   { $estado = ['Caducado', 'sp-estado-cancelado']; }
                            elseif ($agotado)    { $estado = ['Agotado',  'sp-estado-pendiente']; }
                            else                 { $estado = ['Activo',   'sp-estado-confirmado']; }
                        ?>
                        <tr>
                            <td><span class="sp-codigo"><?= sanitizar($c['codigo']) ?></span></td>
                            <td style="color:var(--text-primary);font-weight:600">
                                <?= $c['tipo'] === 'porcentaje' ? rtrim(rtrim($c['descuento'], '0'), '.') . '%' : formatear_precio($c['descuento']) ?>
                            </td>
                            <td><?= $c['usos_actuales'] ?><?= $c['limite_uso'] !== null ? ' / ' . $c['limite_uso'] : '' ?></td>
                            <td><?= $c['fecha_expira'] ? formatear_fecha($c['fecha_expira']) : '—' ?></td>
                            <td><span class="sp-estado <?= $estado[1] ?>"><?= $estado[0] ?></span></td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <a href="?editar=<?= $c['id_cupon'] ?>" class="sp-admin-btn-sm" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="accion" value="toggle">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="id_cupon" value="<?= $c['id_cupon'] ?>">
                                        <button type="submit" class="sp-admin-btn-sm" title="<?= $c['activo'] ? 'Desactivar' : 'Activar' ?>">
                                            <i class="bi bi-<?= $c['activo'] ? 'pause' : 'play' ?>"></i>
                                        </button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('¿Eliminar el cupón <?= sanitizar($c['codigo']) ?>?')" style="display:inline">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="id_cupon" value="<?= $c['id_cupon'] ?>">
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
