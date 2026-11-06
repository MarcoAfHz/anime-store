<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/includes/auth.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Método no permitido.'], 405);
}

// Validar CSRF
csrf_validar($_POST['csrf_token'] ?? '');

$accion = $_POST['accion'] ?? '';

switch ($accion) {

    // ── LOGIN ──────────────────────────────────────────────────
    case 'login':
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            json_response(['error' => 'Completa todos los campos.']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => 'El email no es válido.']);
        }

        $resultado = login($email, $password);

        if (isset($resultado['error'])) {
            json_response(['error' => $resultado['error']]);
        }

        // Regenerar el ID de sesión al iniciar sesión (evita fijación de sesión)
        session_regenerate_id(true);

        $por_defecto = $resultado['usuario']['rol'] === 'admin' ? BASE_URL . '/admin' : BASE_URL;

        json_response([
            'success'  => true,
            'nombre'   => $resultado['usuario']['nombre'],
            'rol'      => $resultado['usuario']['rol'],
            'redirect' => !empty($_POST['redirect']) ? url_retorno_segura($_POST['redirect']) : $por_defecto,
        ]);
        break;

    // ── REGISTRO ───────────────────────────────────────────────
    case 'registro':
        $nombre   = trim($_POST['nombre']   ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($nombre) || empty($email) || empty($password)) {
            json_response(['error' => 'Completa todos los campos.']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => 'El email no es válido.']);
        }

        if (strlen($password) < 8) {
            json_response(['error' => 'La contraseña debe tener mínimo 8 caracteres.']);
        }

        if (strlen($nombre) < 2) {
            json_response(['error' => 'El nombre debe tener mínimo 2 caracteres.']);
        }

        $resultado = registrar($nombre, $email, $password);

        if (isset($resultado['error'])) {
            json_response(['error' => $resultado['error']]);
        }

        session_regenerate_id(true);

        json_response([
            'success'  => true,
            'nombre'   => $nombre,
            'redirect' => !empty($_POST['redirect']) ? url_retorno_segura($_POST['redirect']) : BASE_URL,
        ]);
        break;

    // ── LOGOUT ─────────────────────────────────────────────────
    case 'logout':
        cerrar_sesion();
        break;

    default:
        json_response(['error' => 'Acción no válida.'], 400);
}