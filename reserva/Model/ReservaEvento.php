<?php
require_once 'ReservaDB.php';

class ReservaEvento {

    public static function registrar($reserva_id, $tipo, $descripcion) {
        $conexion = ReservaDB::connectDB();
        $stmt = $conexion->prepare("INSERT INTO reserva_evento (reserva_id, tipo, descripcion) VALUES (:reserva_id, :tipo, :descripcion)");
        $stmt->execute([
            'reserva_id'  => $reserva_id,
            'tipo'        => $tipo,
            'descripcion' => $descripcion
        ]);
    }

    public static function getByReserva($reserva_id) {
        $conexion = ReservaDB::connectDB();
        $stmt = $conexion->prepare("SELECT * FROM reserva_evento WHERE reserva_id = :id ORDER BY fecha_creacion ASC");
        $stmt->execute(['id' => $reserva_id]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
?>