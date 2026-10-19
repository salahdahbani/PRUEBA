<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'Controller/ReservaController.php';

$metodo = $_SERVER['REQUEST_METHOD'];

// Parto la URL en trozos: /reservas/3/estado -> ['reservas', '3', 'estado']
$ruta = trim($_SERVER['PATH_INFO'] ?? '', '/');
$partes = explode('/', $ruta);

$controlador = new ReservaController();

try {
    if ($partes[0] !== 'reservas') {
        $controlador->noEncontrado();
    }

    // /reservas
    if (count($partes) == 1) {
        if ($metodo == 'GET') {
            $controlador->listar();
        } elseif ($metodo == 'POST') {
            $controlador->crear();
        } else {
            $controlador->metodoNoPermitido();
        }
    }

    // /reservas/3
    if (count($partes) == 2) {
        if ($metodo == 'GET') {
            $controlador->detalle($partes[1]);
        } else {
            $controlador->metodoNoPermitido();
        }
    }

    // /reservas/3/estado
    if (count($partes) == 3 && $partes[2] == 'estado') {
        if ($metodo == 'PUT') {
            $controlador->cambiarEstado($partes[1]);
        } else {
            $controlador->metodoNoPermitido();
        }
    }

    $controlador->noEncontrado();

} catch (Exception $e) {
    // Cualquier fallo inesperado lo guardo en el log
    error_log("Error en la API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>