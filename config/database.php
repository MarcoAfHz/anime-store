<?php

define('DB_HOST',     'localhost');
define('DB_USER',     'root');        // en XAMPP por defecto es root
define('DB_PASS',     '');            // en XAMPP por defecto está vacío
define('DB_NAME',     'anime_store');
define('DB_CHARSET',  'utf8mb4');

function conectar() {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            error_log('Error de conexión: ' . $conn->connect_error);
            die(json_encode([
                'error' => 'No se pudo conectar a la base de datos.'
            ]));
        }

        $conn->set_charset(DB_CHARSET);
    }

    return $conn;
}