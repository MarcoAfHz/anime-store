<?php
require_once dirname(__DIR__) . '/config/config.php';

$accion = $_GET['accion'] ?? '';

if ($accion === 'provincias') {
    $id_departamento = (int) ($_GET['id_departamento'] ?? 0);
    if ($id_departamento <= 0) json_response([]);
    
    $conn   = conectar();
    $result = $conn->query("SELECT id_provincia, nombre FROM provincias WHERE id_departamento = $id_departamento ORDER BY nombre ASC");
    $provincias = [];
    while ($row = $result->fetch_assoc()) {
        $provincias[] = $row;
    }
    json_response($provincias);
}

// Más endpoints aquí en el futuro (filtros, búsqueda, etc.)
json_response(['error' => 'Acción no válida.'], 400);