<?php
$titulo_pagina = 'Usuarios — Admin';
$css_extra     = 'admin';
require_once '../includes/header.php';
requiere_admin();

$conn     = conectar();
$admin_id = (int) $_SESSION['usuario']['id_usuario'];

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar($_POST['csrf_token'] ?? '');
    $accion = $_POST['accion'] ?? '';
    $id     = (int) ($_POST['id_usuario'] ?? 0);

    // Nadie puede quitarse a sí mismo el acceso al panel
    if ($id === $admin_id) {
        flash_set('error', 'No puedes modificar tu propia cuenta desde aquí.');
        redirigir('admin/usuarios');
    }

    if ($accion === 'toggle_activo' && $id > 0) {
        $conn->query("UPDATE usuarios SET activo = 1 - activo WHERE id_usuario = $id");
        flash_set('success', 'Estado del usuario actualizado.');
    }

    if ($accion === 'cambiar_rol' && $id > 0) {
        $rol = ($_POST['rol'] ?? '') === 'admin' ? 'admin' : 'cliente';
        $conn->query("UPDATE usuarios SET rol = '$rol' WHERE id_usuario = $id");
        flash_set('success', $rol === 'admin' ? 'El usuario ahora es administrador.' : 'El usuario ahora es cliente.');
    }

    redirigir('admin/usuarios' . (!empty($_POST['volver']) ? '?' . preg_replace('/[^a-z0-9=&_%+-]/i', '', $_POST['volver']) : ''));
}

// Filtros
$q   = trim($_GET['q']   ?? '');
$rol = $_GET['rol'] ?? '';

$where = ['1=1'];
if ($q !== '')                              $where[] = "(u.nombre LIKE '%" . escapar($q) . "%' OR u.email LIKE '%" . escapar($q) . "%' OR u.telefono LIKE '%" . escapar($q) . "%')";
if (in_array($rol, ['admin', 'cliente']))   $where[] = "u.rol = '$rol'";
$where_sql = implode(' AND ', $where);

$usuarios = $conn->query("
    SELECT u.*, pr.nombre AS provincia_nombre,
           COUNT(p.id_pedido)                                                   AS total_pedidos,
           COALESCE(SUM(CASE WHEN p.estado != 'cancelado' THEN p.total END), 0) AS total_gastado,
           MAX(p.fecha)                                                          AS ultimo_pedido
    FROM usuarios u
    LEFT JOIN pedidos p     ON p.id_usuario = u.id_usuario
    LEFT JOIN provincias pr ON pr.id_provincia = u.id_provincia
    WHERE $where_sql
    GROUP BY u.id_usuario
    ORDER BY u.fecha_registro DESC
");

$totales = $conn->query("
    SELECT COUNT(*) AS total,
           SUM(rol = 'cliente') AS clientes,
           SUM(rol = 'admin')   AS admins,
           SUM(fecha_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS nuevos
    FROM usuarios
")->fetch_assoc();
?>

<div class="sp-admin-wrap">
    <?php include ROOT_PATH . '/admin/includes/sidebar.php'; ?>

    <div class="sp-admin-content">
        <div class="sp-admin-header">
            <h1 class="sp-admin-titulo">Usuarios</h1>
        </div>

        <!-- Stats -->
        <div class="sp-stats-grid">
            <div class="sp-stat-card">
                <div class="sp-stat-icon" style="background:rgba(55,138,221,0.15);color:#378add"><i class="bi bi-people"></i></div>
                <div class="sp-stat-info">
                    <span class="sp-stat-valor"><?= (int) $totales['clientes'] ?></span>
                    <span class="sp-stat-label">Clientes</span>
                </div>
            </div>
            <div class="sp-stat-card">
                <div class="sp-stat-icon" style="background:rgba(29,158,117,0.15);color:#1d9e75"><i class="bi bi-person-plus"></i></div>
                <div class="sp-stat-info">
                    <span class="sp-stat-valor"><?= (int) $totales['nuevos'] ?></span>
                    <span class="sp-stat-label">Nuevos (30 días)</span>
                </div>
            </div>
            <div class="sp-stat-card">
                <div class="sp-stat-icon" style="background:rgba(232,93,36,0.15);color:#e85d24"><i class="bi bi-shield-lock"></i></div>
                <div class="sp-stat-info">
                    <span class="sp-stat-valor"><?= (int) $totales['admins'] ?></span>
                    <span class="sp-stat-label">Administradores</span>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="sp-admin-filtros">
            <form method="GET" action="<?= BASE_URL ?>/admin/usuarios" style="display:flex;gap:10px;flex-wrap:wrap">
                <input type="text" name="q" placeholder="Buscar nombre, email o teléfono..." value="<?= sanitizar($q) ?>" class="sp-admin-search">
                <select name="rol" onchange="this.form.submit()" class="sp-admin-select">
                    <option value="">Todos los roles</option>
                    <option value="cliente" <?= $rol === 'cliente' ? 'selected' : '' ?>>Clientes</option>
                    <option value="admin"   <?= $rol === 'admin'   ? 'selected' : '' ?>>Administradores</option>
                </select>
                <button type="submit" class="sp-btn-primary"><i class="bi bi-search"></i></button>
                <?php if ($q !== '' || $rol !== ''): ?>
                <a href="<?= BASE_URL ?>/admin/usuarios" class="sp-btn-outline">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="sp-admin-card">
            <div class="sp-tabla-wrap">
                <table class="sp-tabla">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Contacto</th>
                            <th>Pedidos</th>
                            <th>Total gastado</th>
                            <th>Registro</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($usuarios->num_rows === 0): ?>
                        <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:32px">No se encontraron usuarios.</td></tr>
                        <?php endif; ?>
                        <?php while ($u = $usuarios->fetch_assoc()): $es_yo = (int) $u['id_usuario'] === $admin_id; ?>
                        <tr>
                            <td>
                                <div style="color:var(--text-primary);font-weight:500"><?= sanitizar($u['nombre']) ?><?= $es_yo ? ' <span style="color:var(--accent);font-size:11px">(tú)</span>' : '' ?></div>
                                <?php if (!empty($u['provincia_nombre'])): ?>
                                <small style="color:var(--text-muted)"><i class="bi bi-geo-alt"></i> <?= sanitizar($u['provincia_nombre']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?= sanitizar($u['email']) ?></div>
                                <?php if (!empty($u['telefono'])): ?>
                                <small style="color:var(--text-muted)"><?= sanitizar($u['telefono']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['total_pedidos'] > 0): ?>
                                <a href="<?= BASE_URL ?>/admin/pedidos?q=<?= urlencode($u['nombre']) ?>" title="Ver sus pedidos"><?= $u['total_pedidos'] ?></a>
                                <?php else: ?>0<?php endif; ?>
                            </td>
                            <td><?= formatear_precio($u['total_gastado']) ?></td>
                            <td><?= formatear_fecha($u['fecha_registro']) ?></td>
                            <td>
                                <span class="sp-estado <?= $u['rol'] === 'admin' ? 'sp-estado-enviado' : 'sp-estado-entregado' ?>">
                                    <?= $u['rol'] === 'admin' ? 'Admin' : 'Cliente' ?>
                                </span>
                            </td>
                            <td>
                                <span class="sp-estado <?= $u['activo'] ? 'sp-estado-confirmado' : 'sp-estado-cancelado' ?>">
                                    <?= $u['activo'] ? 'Activo' : 'Bloqueado' ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!$es_yo): ?>
                                <div style="display:flex;gap:6px">
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('<?= $u['activo'] ? '¿Bloquear a este usuario? No podrá iniciar sesión.' : '¿Desbloquear a este usuario?' ?>')">
                                        <input type="hidden" name="accion" value="toggle_activo">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                        <input type="hidden" name="volver" value="<?= sanitizar($_SERVER['QUERY_STRING'] ?? '') ?>">
                                        <button type="submit" class="sp-admin-btn-sm" title="<?= $u['activo'] ? 'Bloquear' : 'Desbloquear' ?>"
                                                style="<?= $u['activo'] ? 'border-color:var(--error);color:var(--error)' : '' ?>">
                                            <i class="bi bi-<?= $u['activo'] ? 'slash-circle' : 'check-circle' ?>"></i>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('<?= $u['rol'] === 'admin' ? '¿Quitar permisos de administrador?' : '¿Dar permisos de administrador? Podrá gestionar toda la tienda.' ?>')">
                                        <input type="hidden" name="accion" value="cambiar_rol">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                        <input type="hidden" name="rol" value="<?= $u['rol'] === 'admin' ? 'cliente' : 'admin' ?>">
                                        <input type="hidden" name="volver" value="<?= sanitizar($_SERVER['QUERY_STRING'] ?? '') ?>">
                                        <button type="submit" class="sp-admin-btn-sm" title="<?= $u['rol'] === 'admin' ? 'Quitar admin' : 'Hacer admin' ?>">
                                            <i class="bi bi-shield<?= $u['rol'] === 'admin' ? '-x' : '-plus' ?>"></i>
                                        </button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <span style="color:var(--text-muted)">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
