<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Método no permitido.'], 405);
}

requiere_login();

$accion     = $_POST['accion'] ?? '';
$id_usuario = (int) $_SESSION['usuario']['id_usuario'];
$conn       = conectar();

switch ($accion) {

    // ── ACTUALIZAR PERFIL ──────────────────────────────────────
    case 'actualizar_perfil':
        $nombre      = sanitizar($_POST['nombre']      ?? '');
        $telefono    = sanitizar($_POST['telefono']    ?? '');
        $direccion   = sanitizar($_POST['direccion']   ?? '');
        $id_provincia = (int) ($_POST['id_provincia']  ?? 0);

        if (empty($nombre)) {
            json_response(['error' => 'El nombre es obligatorio.']);
        }

        $nombre_esc   = escapar($nombre);
        $telefono_esc = escapar($telefono);
        $dir_esc      = escapar($direccion);
        $prov_sql     = $id_provincia > 0 ? $id_provincia : 'NULL';

        $conn->query("
            UPDATE usuarios 
            SET nombre = '$nombre_esc', 
                telefono = '$telefono_esc', 
                direccion = '$dir_esc',
                id_provincia = $prov_sql
            WHERE id_usuario = $id_usuario
        ");

        // Actualizar sesión
        $_SESSION['usuario']['nombre'] = $nombre;

        json_response(['success' => true, 'mensaje' => 'Perfil actualizado correctamente.']);
        break;

    // ── CAMBIAR CONTRASEÑA ─────────────────────────────────────
    case 'cambiar_password':
        $password_actual = $_POST['password_actual'] ?? '';
        $password_nueva  = $_POST['password_nueva']  ?? '';

        if (empty($password_actual) || empty($password_nueva)) {
            json_response(['error' => 'Completa todos los campos.']);
        }

        if (strlen($password_nueva) < 8) {
            json_response(['error' => 'La nueva contraseña debe tener mínimo 8 caracteres.']);
        }

        $resultado = cambiar_password($id_usuario, $password_actual, $password_nueva);

        if (isset($resultado['error'])) {
            json_response(['error' => $resultado['error']]);
        }

        json_response(['success' => true, 'mensaje' => 'Contraseña actualizada correctamente.']);
        break;

    default:
        json_response(['error' => 'Acción no válida.'], 400);
}