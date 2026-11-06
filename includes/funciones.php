<?php

// ── Seguridad ──────────────────────────────────────────────────

function limpiar($dato) {
    $conn = conectar();
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($dato))));
}

function sanitizar($dato) {
    return htmlspecialchars(strip_tags(trim($dato)));
}

function escapar($dato) {
    $conn = conectar();
    return $conn->real_escape_string($dato);
}

// ── Redirecciones ──────────────────────────────────────────────

// Descarta el HTML que ya se haya generado (ver ob_start en config.php)
function limpiar_salida() {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

function redirigir($url) {
    limpiar_salida();
    header('Location: ' . BASE_URL . '/' . $url);
    exit;
}

function redirigir_url($url) {
    limpiar_salida();
    header('Location: ' . $url);
    exit;
}

// ¿La petición viene de la API (fetch) en vez de una página?
function es_peticion_api() {
    return str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/');
}

// ── Formato ────────────────────────────────────────────────────

function formatear_precio($precio) {
    return TIENDA_MONEDA . ' ' . number_format($precio, 2, '.', ',');
}

function formatear_fecha($fecha) {
    return date('d/m/Y', strtotime($fecha));
}

function formatear_fecha_hora($fecha) {
    return date('d/m/Y H:i', strtotime($fecha));
}

// ── Imágenes ───────────────────────────────────────────────────

function imagen_producto($imagen) {
    if (!empty($imagen) && file_exists(UPLOADS_PATH . $imagen)) {
        return UPLOADS_URL . $imagen;
    }
    return IMG_DEFAULT;
}

function subir_imagen($archivo, $carpeta = 'productos') {
    $extensiones = ['jpg', 'jpeg', 'png', 'webp'];
    $extension   = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $extensiones)) {
        return ['error' => 'Formato no permitido. Usa JPG, PNG o WEBP.'];
    }

    if ($archivo['size'] > 2 * 1024 * 1024) {
        return ['error' => 'La imagen no puede superar 2MB.'];
    }

    // Comprobar que de verdad es una imagen (no solo la extensión)
    if (@getimagesize($archivo['tmp_name']) === false) {
        return ['error' => 'El archivo no es una imagen válida.'];
    }

    $directorio = ROOT_PATH . '/assets/img/' . $carpeta;
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    $prefijo   = ['productos' => 'prod_', 'banners' => 'banner_', 'categorias' => 'cat_', 'yape' => 'yape_'][$carpeta] ?? 'img_';
    $nombre    = uniqid($prefijo) . '.' . $extension;
    $ruta      = $directorio . '/' . $nombre;

    if (move_uploaded_file($archivo['tmp_name'], $ruta)) {
        return ['success' => true, 'nombre' => $nombre];
    }

    return ['error' => 'Error al subir la imagen.'];
}

// ── Paginación ─────────────────────────────────────────────────

function paginar($total, $pagina_actual, $por_pagina = PRODUCTOS_POR_PAGINA) {
    $total_paginas = ceil($total / $por_pagina);
    $offset        = ($pagina_actual - 1) * $por_pagina;

    return [
        'total'         => $total,
        'pagina_actual' => $pagina_actual,
        'total_paginas' => $total_paginas,
        'offset'        => $offset,
        'por_pagina'    => $por_pagina,
        'hay_anterior'  => $pagina_actual > 1,
        'hay_siguiente' => $pagina_actual < $total_paginas,
    ];
}

// ── Comprobantes ───────────────────────────────────────────────

function generar_codigo_comprobante() {
    $año    = date('Y');
    $conn   = conectar();
    $result = $conn->query("SELECT COUNT(*) as total FROM comprobantes WHERE YEAR(fecha_emision) = $año");
    $row    = $result->fetch_assoc();
    $numero = str_pad($row['total'] + 1, 5, '0', STR_PAD_LEFT);
    return 'SP-' . $año . '-' . $numero;
}

// ── Cupones ────────────────────────────────────────────────────

function validar_cupon($codigo, $subtotal) {
    $conn   = conectar();
    $codigo = escapar($codigo);
    $hoy    = date('Y-m-d');

    $sql = "SELECT * FROM cupones 
            WHERE codigo = '$codigo' 
            AND activo = 1 
            AND (fecha_expira IS NULL OR fecha_expira >= '$hoy')
            AND (limite_uso IS NULL OR usos_actuales < limite_uso)";

    $result = $conn->query($sql);

    if ($result->num_rows === 0) {
        return ['error' => 'Cupón no válido o expirado.'];
    }

    $cupon     = $result->fetch_assoc();
    $descuento = 0;

    if ($cupon['tipo'] === 'porcentaje') {
        $descuento = ($subtotal * $cupon['descuento']) / 100;
    } else {
        $descuento = $cupon['descuento'];
    }

    return [
        'success'    => true,
        'cupon'      => $cupon,
        'descuento'  => min($descuento, $subtotal),
    ];
}

// Descuento del cupón guardado en sesión, recalculado sobre el subtotal actual.
// Si el cupón ya no es válido (caducado, agotado...) se quita de la sesión.
function descuento_cupon_actual($subtotal) {
    if (empty($_SESSION['cupon']['codigo'])) {
        return 0;
    }

    $resultado = validar_cupon($_SESSION['cupon']['codigo'], $subtotal);
    if (isset($resultado['error'])) {
        unset($_SESSION['cupon']);
        return 0;
    }

    $_SESSION['cupon']['id']        = $resultado['cupon']['id_cupon'];
    $_SESSION['cupon']['descuento'] = round($resultado['descuento'], 2);
    return $_SESSION['cupon']['descuento'];
}

// ── Estrellas ──────────────────────────────────────────────────

function mostrar_estrellas($puntuacion) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $puntuacion ? '★' : '☆';
    }
    return $html;
}

// ── Slugify (para URLs limpias) ────────────────────────────────

function slugify($texto) {
    $texto = strtolower(trim($texto));
    $texto = str_replace(
        ['á','é','í','ó','ú','ñ','ü'],
        ['a','e','i','o','u','n','u'],
        $texto
    );
    $texto = preg_replace('/[^a-z0-9\-]/', '-', $texto);
    $texto = preg_replace('/-+/', '-', $texto);
    return trim($texto, '-');
}

// ── JSON response (para API) ───────────────────────────────────

function json_response($data, $status = 200) {
    limpiar_salida();
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}