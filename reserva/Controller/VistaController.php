<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Model/Reserva.php';
require_once __DIR__ . '/../Model/ReservaEvento.php';

class VistaController {
    private $twig;

    public function __construct() {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../View');
        $this->twig = new \Twig\Environment($loader, ['autoescape' => 'html']);
    }

    // Lee un dato de la URL (si no es texto, lo deja vacío)
    private function get($nombre) {
        $valor = $_GET[$nombre] ?? '';
        return is_string($valor) ? trim($valor) : '';
    }

    public function lista() {
        $filtros = [
            'estado'  => $this->get('estado'),
            'desde'   => $this->get('desde'),
            'hasta'   => $this->get('hasta'),
            'huesped' => $this->get('huesped')
        ];

        $reservas = Reserva::getReservas($filtros['estado'], $filtros['desde'], $filtros['hasta'], $filtros['huesped']);

        echo $this->twig->render('lista.twig', [
            'reservas' => array_map(fn($r) => $r->jsonSerialize(), $reservas),
            'filtros'  => $filtros
        ]);
    }

    public function detalle() {
        $id = $this->get('id');
        $reserva = ctype_digit($id) ? Reserva::getReservaById($id) : null;

        if ($reserva == null) {
            http_response_code(404);
            echo "Reserva no encontrada";
            return;
        }

        echo $this->twig->render('detalle.twig', [
            'reserva' => $reserva->jsonSerialize(),
            'eventos' => ReservaEvento::getByReserva($id)
        ]);
    }

    public function nueva() {
        echo $this->twig->render('nueva.twig');
    }
}
?>