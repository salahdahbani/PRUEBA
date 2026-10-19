<?php
require_once 'ReservaDB.php';
require_once 'ReservaEvento.php';

class Reserva implements JsonSerializable {
    private $id;
    private $nombre_huesped;
    private $email_huesped;
    private $nombre_alojamiento;
    private $fecha_entrada;
    private $fecha_salida;
    private $estado; // pendiente | confirmada | cancelada
    private $importe;
    private $notas;
    private $fecha_creacion;

    public function __construct($id, $nombre_huesped = "", $email_huesped = "", $nombre_alojamiento = "", $fecha_entrada = "", $fecha_salida = "", $estado = "pendiente", $importe = 0, $notas = "", $fecha_creacion = "") {
        $this->id = $id;
        $this->nombre_huesped = $nombre_huesped;
        $this->email_huesped = $email_huesped;
        $this->nombre_alojamiento = $nombre_alojamiento;
        $this->fecha_entrada = $fecha_entrada;
        $this->fecha_salida = $fecha_salida;
        $this->estado = $estado;
        $this->importe = $importe;
        $this->notas = $notas;
        $this->fecha_creacion = $fecha_creacion;
    }

    // GETTERS Y SETTERS
    public function getId() { return $this->id; }
    public function getEstado() { return $this->estado; }

    public function setEstado($estado) { $this->estado = $estado; }

    // Necesario para json_encode y para pasar los datos a Twig (los atributos son private)
    public function jsonSerialize(): array {
        return get_object_vars($this);
    }

    // Crea un objeto Reserva a partir de una fila de la base de datos
    private static function desdeFila($fila) {
        return new Reserva($fila['id'], $fila['nombre_huesped'], $fila['email_huesped'], $fila['nombre_alojamiento'], $fila['fecha_entrada'], $fila['fecha_salida'], $fila['estado'], $fila['importe'], $fila['notas'], $fila['fecha_creacion']);
    }

    /**
     * LISTAR
     */
    public static function getReservas($estado = "", $desde = "", $hasta = "", $huesped = "") {
        $conexion = ReservaDB::connectDB();

        $sql = "SELECT * FROM reserva WHERE 1=1";
        $params = [];

        if ($estado != "") {
            $sql .= " AND estado = :estado";
            $params['estado'] = $estado;
        }
        if ($desde != "") {
            $sql .= " AND fecha_entrada >= :desde";
            $params['desde'] = $desde;
        }
        if ($hasta != "") {
            $sql .= " AND fecha_salida <= :hasta";
            $params['hasta'] = $hasta;
        }
        if ($huesped != "") {
            $sql .= " AND nombre_huesped LIKE :huesped";
            $params['huesped'] = "%" . $huesped . "%";
        }
        $sql .= " ORDER BY fecha_creacion DESC";

        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);

        $reservas = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reservas[] = self::desdeFila($fila);
        }
        return $reservas;
    }

    /**
     * DETALLE
     */
    public static function getReservaById($id) {
        $conexion = ReservaDB::connectDB();
        $stmt = $conexion->prepare("SELECT * FROM reserva WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return self::desdeFila($fila);
        }
        return null;
    }

    /**
     * AÑADIR
     */
    public function insert() {
        $conexion = ReservaDB::connectDB();
        try {
            $stmt = $conexion->prepare(
                "INSERT INTO reserva (nombre_huesped, email_huesped, nombre_alojamiento, fecha_entrada, fecha_salida, importe, notas)
                 VALUES (:nombre, :email, :alojamiento, :entrada, :salida, :importe, :notas)"
            );
            $stmt->execute([
                'nombre'      => $this->nombre_huesped,
                'email'       => $this->email_huesped,
                'alojamiento' => $this->nombre_alojamiento,
                'entrada'     => $this->fecha_entrada,
                'salida'      => $this->fecha_salida,
                'importe'     => $this->importe,
                'notas'       => $this->notas
            ]);
            $this->id = $conexion->lastInsertId();

            ReservaEvento::registrar($this->id, 'creada', 'Reserva creada');
            return true;
        } catch (PDOException $e) {
            error_log("Error al insertar reserva: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ACTUALIZAR ESTADO
     */
    public function update() {
        $conexion = ReservaDB::connectDB();
        try {
            $stmt = $conexion->prepare("UPDATE reserva SET estado = :estado WHERE id = :id");
            $stmt->execute([
                'estado' => $this->estado,
                'id'     => $this->id
            ]);

            ReservaEvento::registrar($this->id, $this->estado, 'Reserva ' . $this->estado);
            return true;
        } catch (PDOException $e) {
            error_log("Error al actualizar reserva: " . $e->getMessage());
            return false;
        }
    }

    /**
     * VALIDAR
     */
    public static function validar($datos) {
        $errores = [];

        if (empty($datos['nombre_huesped']) || !is_string($datos['nombre_huesped'])) {
            $errores['nombre_huesped'] = "El nombre es obligatorio";
        }
        if (empty($datos['email_huesped']) || !filter_var($datos['email_huesped'], FILTER_VALIDATE_EMAIL)) {
            $errores['email_huesped'] = "El email no es válido";
        }
        if (empty($datos['nombre_alojamiento']) || !is_string($datos['nombre_alojamiento'])) {
            $errores['nombre_alojamiento'] = "El alojamiento es obligatorio";
        }

        if (!self::fechaValida($datos['fecha_entrada'] ?? null) || !self::fechaValida($datos['fecha_salida'] ?? null)) {
            $errores['fechas'] = "Las fechas deben tener formato AAAA-MM-DD y existir";
        } elseif ($datos['fecha_salida'] <= $datos['fecha_entrada']) {
            $errores['fechas'] = "La salida tiene que ser posterior a la entrada";
        }

        if (!isset($datos['importe']) || !is_numeric($datos['importe']) || $datos['importe'] < 0) {
            $errores['importe'] = "El importe debe ser un número mayor o igual que 0";
        }
        return $errores;
    }

    // 2025-13-45 no pasa
    private static function fechaValida($fecha) {
        if (!is_string($fecha)) return false;
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
}
?>