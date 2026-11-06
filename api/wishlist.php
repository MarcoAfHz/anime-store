<?php
require_once dirname(__DIR__) . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Método no permitido.'], 405);
}

// Requiere login
if (!esta_logueado()) {
    json_response(['error' => 'Debes iniciar sesión.', 'login_required' => true]);
}

$id_producto = (int) ($_POST['id_producto'] ?? 0);
$id_usuario  = (int) $_SESSION['usuario']['id_usuario'];
$conn        = conectar();

if ($id_producto <= 0) {
    json_response(['error' => 'Producto no válido.']);
}

// Verificar si ya está en wishlist
$existe = $conn->query("SELECT id_wishlist FROM wishlist WHERE id_usuario = $id_usuario AND id_producto = $id_producto");

if ($existe->num_rows > 0) {
    // Quitar de wishlist
    $conn->query("DELETE FROM wishlist WHERE id_usuario = $id_usuario AND id_producto = $id_producto");
    json_response(['success' => true, 'accion' => 'quitado']);
} else {
    // Añadir a wishlist
    $conn->query("INSERT INTO wishlist (id_usuario, id_producto) VALUES ($id_usuario, $id_producto)");
    json_response(['success' => true, 'accion' => 'agregado']);
}