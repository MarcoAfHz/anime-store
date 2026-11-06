<?php

// ── Proteger páginas que requieren login ───────────────────────

function requiere_login() {
    if (!esta_logueado()) {
        if (es_peticion_api()) {
            json_response(['error' => 'Debes iniciar sesión.', 'login_required' => true], 401);
        }
        flash_set('error', 'Debes iniciar sesión para acceder.');
        redirigir('login?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
    }
}

function requiere_admin() {
    requiere_login();
    if (!es_admin()) {
        if (es_peticion_api()) {
            json_response(['error' => 'No tienes permisos.'], 403);
        }
        flash_set('error', 'No tienes permisos para acceder.');
        redirigir('');
    }
}

// Solo permite volver a rutas internas (evita redirecciones a otras webs)
function url_retorno_segura($url) {
    if (is_string($url) && str_starts_with($url, '/') && !str_starts_with($url, '//')) {
        return BASE_URL . $url;
    }
    return BASE_URL;
}

// ── Login ──────────────────────────────────────────────────────

function login($email, $password) {
    $conn  = conectar();
    $email = escapar($email);

    $sql    = "SELECT * FROM usuarios WHERE email = '$email' AND activo = 1 LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows === 0) {
        return ['error' => 'Email o contraseña incorrectos.'];
    }

    $usuario = $result->fetch_assoc();

    if (!password_verify($password, $usuario['password'])) {
        return ['error' => 'Email o contraseña incorrectos.'];
    }

    iniciar_sesion_usuario($usuario);

    return ['success' => true, 'usuario' => $usuario];
}

// ── Registro ───────────────────────────────────────────────────

function registrar($nombre, $email, $password) {
    $conn  = conectar();
    $email = escapar($email);

    // Verificar si el email ya existe
    $check = $conn->query("SELECT id_usuario FROM usuarios WHERE email = '$email'");
    if ($check->num_rows > 0) {
        return ['error' => 'Este email ya está registrado.'];
    }

    $nombre   = escapar(sanitizar($nombre));
    $hash     = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO usuarios (nombre, email, password, rol) 
            VALUES ('$nombre', '$email', '$hash', 'cliente')";

    if ($conn->query($sql)) {
        // Loguear automáticamente después del registro
        $usuario = [
            'id_usuario' => $conn->insert_id,
            'nombre'     => $nombre,
            'email'      => $email,
            'rol'        => 'cliente',
        ];
        iniciar_sesion_usuario($usuario);
        return ['success' => true];
    }

    return ['error' => 'Error al crear la cuenta. Inténtalo de nuevo.'];
}

// ── Cambiar contraseña ─────────────────────────────────────────

function cambiar_password($id_usuario, $password_actual, $password_nueva) {
    $conn = conectar();

    $result = $conn->query("SELECT password FROM usuarios WHERE id_usuario = $id_usuario");
    $usuario = $result->fetch_assoc();

    if (!password_verify($password_actual, $usuario['password'])) {
        return ['error' => 'La contraseña actual no es correcta.'];
    }

    $hash = password_hash($password_nueva, PASSWORD_BCRYPT);
    $conn->query("UPDATE usuarios SET password = '$hash' WHERE id_usuario = $id_usuario");

    return ['success' => true];
}

// ── Token CSRF ─────────────────────────────────────────────────

function csrf_generar() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validar($token) {
    if (!isset($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        if (es_peticion_api()) {
            json_response(['error' => 'La sesión ha caducado. Recarga la página e inténtalo de nuevo.'], 419);
        }
        flash_set('error', 'Token de seguridad inválido.');
        redirigir('');
    }
}

function csrf_campo() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_generar() . '">';
}