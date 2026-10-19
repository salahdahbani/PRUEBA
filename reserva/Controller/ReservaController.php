<?php
require_once __DIR__ . '/../Model/Reserva.php';
require_once __DIR__ . '/../Model/ReservaEvento.php';

class ReservaController {

    // Envía el JSON con su código HTTP 
    private function responder($codigo, $datos) {
        http_response_code($codigo);
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Lee el JSON del cuerpo de la petición
    private function leerJson() {
        $datos = json_decode(file_get_contents('php://input'), true);
        if (!is_array($datos)) {
            $this->responder(400, ['error' => 'El cuerpo debe ser un JSON válido']);
        }
        return $datos;
    }

    // Lee un filtro de la URL 
    private function filtro($nombre) {
        $valor = $_GET[$nombre] ?? '';
        if (!is_string($valor)) {
            $this->responder(400, ['error' => "El filtro $nombre no es válido"]);
        }
        return trim($valor);
    }

    private function comprobarId($id) {
        if (!ctype_digit($id) || $id < 1) {
            $this->responder(400, ['error' => 'El id no es válido']);
        }
    }

    // GET /reservas
    public function listar() {
        $estado  = $this->filtro('estado');
        $desde   = $this->filtro('desde');
        $hasta   = $this->filtro('hasta');
        $huesped = $this->filtro('huesped');

        if ($estado != '' && !in_array($estado, ['pendiente', 'confirmada', 'cancelada'])) {
            $this->responder(400, ['error' => 'Estado no válido']);
        }
        if ($desde != '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $this->responder(400, ['error' => 'La fecha "desde" debe tener formato AAAA-MM-DD']);
        }
        if ($hasta != '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $this->responder(400, ['error' => 'La fecha "hasta" debe tener formato AAAA-MM-DD']);
        }

        $reservas = Reserva::getReservas($estado, $desde, $hasta, $huesped);
        $this->responder(200, $reservas);
    }

    // GET /reservas/3
    public function detalle($id) {
        $this->comprobarId($id);

        $reserva = Reserva::getReservaById($id);
        if ($reserva == null) {
            $this->responder(404, ['error' => 'Reserva no encontrada']);
        }

        $this->responder(200, [
            'reserva' => $reserva,
            'eventos' => ReservaEvento::getByReserva($id)
        ]);
    }

    // POST /reservas
    public function crear() {
        $datos = $this->leerJson();

        // Quito espacios sobrantes de los textos
        foreach (['nombre_huesped', 'email_huesped', 'nombre_alojamiento', 'notas'] as $campo) {
            if (isset($datos[$campo]) && is_string($datos[$campo])) {
                $datos[$campo] = trim($datos[$campo]);
            }
        }

        $errores = Reserva::validar($datos);
        if (!empty($errores)) {
            $this->responder(400, ['errores' => $errores]);
        }

        $notas = isset($datos['notas']) && is_string($datos['notas']) ? $datos['notas'] : '';

        $reserva = new Reserva(
            null,
            $datos['nombre_huesped'],
            $datos['email_huesped'],
            $datos['nombre_alojamiento'],
            $datos['fecha_entrada'],
            $datos['fecha_salida'],
            'pendiente',
            $datos['importe'],
            $notas
        );

        if ($reserva->insert()) {
            $this->responder(201, ['id' => $reserva->getId(), 'mensaje' => 'Reserva creada']);
        }
        $this->responder(500, ['error' => 'No se ha podido crear la reserva']);
    }

    // PUT /reservas/3/estado
    public function cambiarEstado($id) {
        $this->comprobarId($id);
        $datos = $this->leerJson();

        $estado = $datos['estado'] ?? '';
        if (!is_string($estado) || !in_array($estado, ['confirmada', 'cancelada'])) {
            $this->responder(400, ['error' => 'El estado debe ser "confirmada" o "cancelada"']);
        }

        $reserva = Reserva::getReservaById($id);
        if ($reserva == null) {
            $this->responder(404, ['error' => 'Reserva no encontrada']);
        }
        if ($reserva->getEstado() == 'cancelada') {
            $this->responder(409, ['error' => 'Una reserva cancelada no se puede modificar']);
        }

        $reserva->setEstado($estado);
        if ($reserva->update()) {
            $this->responder(200, ['mensaje' => 'Reserva ' . $estado]);
        }
        $this->responder(500, ['error' => 'No se ha podido cambiar el estado']);
    }

    public function noEncontrado() {
        $this->responder(404, ['error' => 'Ruta no encontrada']);
    }

    public function metodoNoPermitido() {
        $this->responder(405, ['error' => 'Método no permitido']);
    }

}