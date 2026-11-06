<?php

// ── URL base del proyecto ──────────────────────────────────────
// En local con Laragon:
define('BASE_URL', 'http://shop-peruvian.test');
// En producción (Hostinger) cambia a:
// define('BASE_URL', 'https://www.shopperuvian.com');

// ── Nombre y datos de la tienda ────────────────────────────────
define('TIENDA_NOMBRE',    'ShopPeruvian');
define('TIENDA_EMAIL',     'admin@shopperuvian.com');
define('TIENDA_TELEFONO',  '+51 999 999 999');
define('TIENDA_DIRECCION', 'Lima, Perú');
define('TIENDA_MONEDA',    'S/');

// ── Yape ───────────────────────────────────────────────────────
define('YAPE_NUMERO',      '999 999 999');
define('YAPE_TITULAR',     'ShopPeruvian');

// ── Rutas de archivos ──────────────────────────────────────────
define('ROOT_PATH',        dirname(__DIR__));
define('UPLOADS_PATH',     ROOT_PATH . '/assets/img/productos/');
define('UPLOADS_URL',      BASE_URL  . '/assets/img/productos/');
define('YAPE_PATH',        ROOT_PATH . '/assets/img/yape/');
define('YAPE_URL',         BASE_URL  . '/assets/img/yape/');

// ── Imagen por defecto si no hay foto ─────────────────────────
define('IMG_DEFAULT',      BASE_URL  . '/assets/img/no-imagen.png');

// ── Paginación ─────────────────────────────────────────────────
define('PRODUCTOS_POR_PAGINA', 12);

// ── Sesión ─────────────────────────────────────────────────────
define('SESSION_NOMBRE', 'shop_peruvian_session');

// ── Entorno ────────────────────────────────────────────────────
// 'development' muestra errores | 'production' los oculta
define('ENTORNO', 'development');

if (ENTORNO === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ── Buffer de salida ───────────────────────────────────────────
// Permite redirigir aunque la cabecera HTML ya se haya generado.
// Siempre propio y sin límite: el output_buffering de php.ini (p. ej. 4096) se vacía solo al llenarse.
ob_start();

// ── Zona horaria Peru ──────────────────────────────────────────
date_default_timezone_set('America/Lima');

// ── Autoload de archivos base ──────────────────────────────────
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/includes/funciones.php';