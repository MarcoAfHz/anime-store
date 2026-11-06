<?php
require_once dirname(__DIR__) . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Método no permitido.'], 405);
}

$accion = $_POST['accion'] ?? '';
$conn   = conectar();

switch ($accion) {

    // ── AGREGAR ────────────────────────────────────────────────
    case 'agregar':
        $id_producto = (int) ($_POST['id_producto'] ?? 0);
        $cantidad    = (int) ($_POST['cantidad']    ?? 1);
        $talla       = sanitizar($_POST['talla']    ?? '');

        if ($id_producto <= 0) {
            json_response(['error' => 'Producto no válido.']);
        }

        // Verificar que el producto existe y tiene stock
        $prod = $conn->query("SELECT id_producto, nombre, precio, stock FROM productos WHERE id_producto = $id_producto AND activo = 1");

        if ($prod->num_rows === 0) {
            json_response(['error' => 'Producto no encontrado.']);
        }

        $producto = $prod->fetch_assoc();

        if ($producto['stock'] < $cantidad) {
            json_response(['error' => 'Stock insuficiente. Solo quedan ' . $producto['stock'] . ' unidades.']);
        }

        carrito_agregar($id_producto, $cantidad, $talla ?: null);

        json_response([
            'success'     => true,
            'total_items' => carrito_total_items(),
            'mensaje'     => $producto['nombre'] . ' añadido al carrito.',
        ]);
        break;

    // ── QUITAR ─────────────────────────────────────────────────
    case 'quitar':
        $key = sanitizar($_POST['key'] ?? '');

        if (empty($key)) {
            json_response(['error' => 'Clave no válida.']);
        }

        carrito_quitar($key);

        $totales = calcular_totales_carrito();

        json_response([
            'success'     => true,
            'total_items' => carrito_total_items(),
            'subtotal'    => $totales['subtotal'],
            'total'       => $totales['total'],
            'descuento'   => $totales['descuento'],
        ]);
        break;

    // ── ACTUALIZAR CANTIDAD ────────────────────────────────────
    case 'actualizar':
        $key      = sanitizar($_POST['key']      ?? '');
        $cantidad = (int) ($_POST['cantidad'] ?? 1);

        if (empty($key)) {
            json_response(['error' => 'Clave no válida.']);
        }

        if ($cantidad <= 0) {
            carrito_quitar($key);
        } else {
            $carrito = carrito_obtener();
            if (isset($carrito[$key])) {
                // Verificar stock
                $id_prod = $carrito[$key]['id_producto'];
                $stock   = $conn->query("SELECT stock FROM productos WHERE id_producto = $id_prod")->fetch_assoc()['stock'];

                if ($cantidad > $stock) {
                    json_response(['error' => 'Stock insuficiente. Solo quedan ' . $stock . ' unidades.']);
                }

                $_SESSION['carrito'][$key]['cantidad'] = $cantidad;
            }
        }

        $totales       = calcular_totales_carrito();
        $carrito       = carrito_obtener();
        $subtotal_item = 0;

        if (isset($carrito[$key])) {
            $id_prod       = $carrito[$key]['id_producto'];
            $precio        = $conn->query("SELECT precio FROM productos WHERE id_producto = $id_prod")->fetch_assoc()['precio'];
            $subtotal_item = $precio * $carrito[$key]['cantidad'];
        }

        json_response([
            'success'       => true,
            'total_items'   => carrito_total_items(),
            'subtotal'      => $totales['subtotal'],
            'total'         => $totales['total'],
            'descuento'     => $totales['descuento'],
            'subtotal_item' => $subtotal_item,
        ]);
        break;

    // ── VACIAR ─────────────────────────────────────────────────
    case 'vaciar':
        carrito_vaciar();
        json_response(['success' => true, 'total_items' => 0]);
        break;

    // ── CUPÓN ──────────────────────────────────────────────────
    case 'cupon':
        $codigo  = sanitizar($_POST['codigo'] ?? '');
        $totales = calcular_totales_carrito();

        $resultado = validar_cupon($codigo, $totales['subtotal']);

        if (isset($resultado['error'])) {
            json_response(['error' => $resultado['error']]);
        }

        // Guardar cupón en sesión
        $_SESSION['cupon'] = [
            'id'        => $resultado['cupon']['id_cupon'],
            'codigo'    => $codigo,
            'descuento' => $resultado['descuento'],
        ];

        $total_final = max(0, $totales['subtotal'] + $totales['envio'] - $resultado['descuento']);

        json_response([
            'success'   => true,
            'descuento' => $resultado['descuento'],
            'subtotal'  => $totales['subtotal'],
            'total'     => $total_final,
        ]);
        break;

    default:
        json_response(['error' => 'Acción no válida.'], 400);
}

// ── HELPER — calcular totales ──────────────────────────────────
function calcular_totales_carrito() {
    $carrito  = carrito_obtener();
    $conn     = conectar();
    $subtotal = 0;

    foreach ($carrito as $item) {
        $id   = (int) $item['id_producto'];
        $prod = $conn->query("SELECT precio FROM productos WHERE id_producto = $id")->fetch_assoc();
        if ($prod) {
            $subtotal += $prod['precio'] * $item['cantidad'];
        }
    }

    // Costo de envío desde sesión de usuario
    $envio = 0;
    if (esta_logueado() && isset($_SESSION['usuario']['id_provincia'])) {
        $id_prov = (int) $_SESSION['usuario']['id_provincia'];
        $dep     = $conn->query("SELECT d.costo_envio FROM provincias p JOIN departamentos d ON p.id_departamento = d.id_departamento WHERE p.id_provincia = $id_prov");
        if ($dep->num_rows > 0) {
            $envio = (float) $dep->fetch_assoc()['costo_envio'];
        }
    }

    $descuento = descuento_cupon_actual($subtotal);
    $total     = max(0, $subtotal + $envio - $descuento);

    return [
        'subtotal'  => round($subtotal, 2),
        'envio'     => round($envio, 2),
        'descuento' => round($descuento, 2),
        'total'     => round($total, 2),
    ];
}