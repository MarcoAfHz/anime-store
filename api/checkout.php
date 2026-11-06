<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Método no permitido.'], 405);
}

requiere_login();

$conn       = conectar();
$carrito    = carrito_obtener();
$id_usuario = (int) $_SESSION['usuario']['id_usuario'];

if (empty($carrito)) {
    json_response(['error' => 'El carrito está vacío.']);
}

// ── VALIDAR DATOS ──────────────────────────────────────────────
$nombre      = sanitizar($_POST['nombre']      ?? '');
$telefono    = sanitizar($_POST['telefono']    ?? '');
$direccion   = sanitizar($_POST['direccion']   ?? '');
$id_provincia = (int) ($_POST['id_provincia']  ?? 0);
$metodo_pago = sanitizar($_POST['metodo_pago'] ?? '');
$notas       = sanitizar($_POST['notas']       ?? '');

if (empty($nombre) || empty($telefono) || empty($direccion) || $id_provincia <= 0) {
    json_response(['error' => 'Completa todos los datos de envío.']);
}

if (!in_array($metodo_pago, ['efectivo', 'yape'])) {
    json_response(['error' => 'Método de pago no válido.']);
}

// ── CALCULAR TOTALES ───────────────────────────────────────────
$subtotal = 0;
$items    = [];

foreach ($carrito as $key => $item) {
    $id   = (int) $item['id_producto'];
    $prod = $conn->query("SELECT id_producto, nombre, precio, stock FROM productos WHERE id_producto = $id AND activo = 1")->fetch_assoc();

    if (!$prod) continue;

    // Verificar stock
    if (!empty($item['talla'])) {
        $talla_esc = escapar($item['talla']);
        $stock_talla = $conn->query("SELECT stock FROM producto_tallas WHERE id_producto = $id AND talla = '$talla_esc'")->fetch_assoc();
        if (!$stock_talla || $stock_talla['stock'] < $item['cantidad']) {
            json_response(['error' => "Stock insuficiente para {$prod['nombre']} talla {$item['talla']}"]);
        }
    } else {
        if ($prod['stock'] < $item['cantidad']) {
            json_response(['error' => "Stock insuficiente para {$prod['nombre']}"]);
        }
    }

    $subtotal += $prod['precio'] * $item['cantidad'];
    $items[]   = [
        'id_producto' => $id,
        'cantidad'    => $item['cantidad'],
        'precio_unit' => $prod['precio'],
        'talla'       => $item['talla'] ?? null,
    ];
}

if (empty($items)) {
    json_response(['error' => 'Los productos de tu carrito ya no están disponibles.']);
}

// Costo envío
$dep = $conn->query("SELECT d.costo_envio, p.nombre AS provincia, d.nombre AS departamento FROM provincias p JOIN departamentos d ON p.id_departamento = d.id_departamento WHERE p.id_provincia = $id_provincia")->fetch_assoc();
if (!$dep) {
    json_response(['error' => 'La provincia seleccionada no es válida.']);
}
$costo_envio = (float) $dep['costo_envio'];

// Cupón
$descuento = descuento_cupon_actual($subtotal);
$id_cupon  = isset($_SESSION['cupon']['id']) ? (int) $_SESSION['cupon']['id'] : null;
$total     = max(0, $subtotal + $costo_envio - $descuento);

// ── CREAR PEDIDO ───────────────────────────────────────────────
$conn->begin_transaction();

try {
    // Actualizar datos del usuario
    $nombre_esc    = escapar($nombre);
    $telefono_esc  = escapar($telefono);
    $direccion_esc = escapar($direccion);

    $conn->query("UPDATE usuarios SET nombre = '$nombre_esc', telefono = '$telefono_esc', direccion = '$direccion_esc', id_provincia = $id_provincia WHERE id_usuario = $id_usuario");

    // Crear pedido
    $dir_envio_esc = escapar("$direccion, {$dep['provincia']} ({$dep['departamento']})");
    $notas_esc     = escapar($notas);
    $id_cupon_sql  = $id_cupon ? $id_cupon : 'NULL';

    $conn->query("
        INSERT INTO pedidos (id_usuario, id_cupon, subtotal, descuento, costo_envio, total, dir_envio, metodo_pago, notas)
        VALUES ($id_usuario, $id_cupon_sql, $subtotal, $descuento, $costo_envio, $total, '$dir_envio_esc', '$metodo_pago', '$notas_esc')
    ");

    $id_pedido = $conn->insert_id;

    // Insertar detalle
    foreach ($items as $item) {
        $id_prod    = $item['id_producto'];
        $cantidad   = $item['cantidad'];
        $precio     = $item['precio_unit'];
        $talla_sql  = $item['talla'] ? "'" . escapar($item['talla']) . "'" : 'NULL';

        $conn->query("INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unit, talla) VALUES ($id_pedido, $id_prod, $cantidad, $precio, $talla_sql)");

        // Descontar stock
        if ($item['talla']) {
            $talla_esc = escapar($item['talla']);
            $conn->query("UPDATE producto_tallas SET stock = stock - $cantidad WHERE id_producto = $id_prod AND talla = '$talla_esc'");
        }
        $conn->query("UPDATE productos SET stock = stock - $cantidad WHERE id_producto = $id_prod");
    }

    // Crear registro de pago
    $conn->query("INSERT INTO pagos (id_pedido, tipo, estado) VALUES ($id_pedido, '$metodo_pago', 'pendiente')");

    // Crear comprobante interno
    $codigo = generar_codigo_comprobante();
    $conn->query("INSERT INTO comprobantes (id_pedido, codigo) VALUES ($id_pedido, '$codigo')");

    // Actualizar usos del cupón
    if ($id_cupon) {
        $conn->query("UPDATE cupones SET usos_actuales = usos_actuales + 1 WHERE id_cupon = $id_cupon");
    }

    $conn->commit();

    // Limpiar carrito y cupón
    carrito_vaciar();
    unset($_SESSION['cupon']);

    json_response([
        'success'    => true,
        'id_pedido'  => $id_pedido,
        'codigo'     => $codigo,
        'metodo'     => $metodo_pago,
        'total'      => $total,
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log('Error checkout: ' . $e->getMessage());
    json_response(['error' => 'Error al procesar el pedido. Inténtalo de nuevo.']);
}