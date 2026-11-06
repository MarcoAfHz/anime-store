<?php

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NOMBRE);
    session_start();
}

// ── Funciones de sesión ────────────────────────────────────────

function sesion_usuario() {
    return $_SESSION['usuario'] ?? null;
}

function esta_logueado() {
    return isset($_SESSION['usuario']['id_usuario']);
}

function es_admin() {
    return isset($_SESSION['usuario']['rol']) && $_SESSION['usuario']['rol'] === 'admin';
}

function iniciar_sesion_usuario($usuario) {
    $_SESSION['usuario'] = [
        'id_usuario' => $usuario['id_usuario'],
        'nombre'     => $usuario['nombre'],
        'email'      => $usuario['email'],
        'rol'        => $usuario['rol'],
    ];
}

function cerrar_sesion() {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . BASE_URL);
    exit;
}

// ── Carrito en sesión ──────────────────────────────────────────

function carrito_obtener() {
    return $_SESSION['carrito'] ?? [];
}

function carrito_total_items() {
    $carrito = carrito_obtener();
    return array_sum(array_column($carrito, 'cantidad'));
}

function carrito_agregar($id_producto, $cantidad, $talla = null) {
    $key = $id_producto . '_' . ($talla ?? 'unico');

    if (isset($_SESSION['carrito'][$key])) {
        $_SESSION['carrito'][$key]['cantidad'] += $cantidad;
    } else {
        $_SESSION['carrito'][$key] = [
            'id_producto' => $id_producto,
            'cantidad'    => $cantidad,
            'talla'       => $talla,
        ];
    }
}

function carrito_quitar($key) {
    unset($_SESSION['carrito'][$key]);
}

function carrito_vaciar() {
    $_SESSION['carrito'] = [];
}

// ── Mensajes flash ─────────────────────────────────────────────
// Para mostrar mensajes de éxito o error después de redirigir

function flash_set($tipo, $mensaje) {
    $_SESSION['flash'] = [
        'tipo'    => $tipo,      // 'success' | 'error' | 'warning'
        'mensaje' => $mensaje,
    ];
}

function flash_get() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}