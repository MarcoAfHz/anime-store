<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Método no permitido.'], 405);
}

requiere_login();
csrf_validar($_POST['csrf_token'] ?? '');

$id_producto = (int) ($_POST['id_producto'] ?? 0);
$puntuacion  = (int) ($_POST['puntuacion']  ?? 0);
$comentario  = sanitizar($_POST['comentario'] ?? '');
$id_usuario  = (int) $_SESSION['usuario']['id_usuario'];
$conn        = conectar();

if ($id_producto <= 0) {
    json_response(['error' => 'Producto no válido.']);
}

if ($puntuacion < 1 || $puntuacion > 5) {
    json_response(['error' => 'La puntuación debe ser entre 1 y 5.']);
}

// Verificar que el producto existe
$prod = $conn->query("SELECT id_producto FROM productos WHERE id_producto = $id_producto AND activo = 1");
if ($prod->num_rows === 0) {
    json_response(['error' => 'Producto no encontrado.']);
}

// Verificar que no haya reseñado ya
$existe = $conn->query("SELECT id_resena FROM resenas WHERE id_usuario = $id_usuario AND id_producto = $id_producto");
if ($existe->num_rows > 0) {
    json_response(['error' => 'Ya has dejado una reseña para este producto.']);
}

$comentario_esc = escapar($comentario);

$conn->query("
    INSERT INTO resenas (id_producto, id_usuario, puntuacion, comentario)
    VALUES ($id_producto, $id_usuario, $puntuacion, '$comentario_esc')
");

json_response(['success' => true, 'mensaje' => '¡Reseña publicada correctamente!']);