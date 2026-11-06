<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Método no permitido.'], 405);
}

requiere_login();
csrf_validar($_POST['csrf_token'] ?? '');

$conn       = conectar();
$id_usuario = (int) $_SESSION['usuario']['id_usuario'];
$id_pedido  = (int) ($_POST['id_pedido'] ?? 0);

// El pedido debe ser del usuario, pagarse con Yape y seguir pendiente
$pedido = $conn->query("
    SELECT p.id_pedido, p.estado, pg.estado AS pago_estado
    FROM pedidos p
    JOIN pagos pg ON pg.id_pedido = p.id_pedido
    WHERE p.id_pedido = $id_pedido AND p.id_usuario = $id_usuario AND p.metodo_pago = 'yape'
")->fetch_assoc();

if (!$pedido) {
    json_response(['error' => 'Pedido no encontrado.'], 404);
}

if ($pedido['estado'] === 'cancelado' || $pedido['pago_estado'] === 'confirmado') {
    json_response(['error' => 'Este pedido ya no admite comprobantes.']);
}

if (empty($_FILES['comprobante']['name']) || $_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
    json_response(['error' => 'Selecciona la captura de tu pago.']);
}

$resultado = subir_imagen($_FILES['comprobante'], 'yape');

if (isset($resultado['error'])) {
    json_response(['error' => $resultado['error']]);
}

$archivo = escapar($resultado['nombre']);
$conn->query("UPDATE pagos SET yape_qr = '$archivo', fecha_pago = NOW() WHERE id_pedido = $id_pedido");

json_response([
    'success' => true,
    'mensaje' => '¡Comprobante enviado! Confirmaremos tu pago en breve.',
    'url'     => YAPE_URL . rawurlencode($resultado['nombre']),
]);
