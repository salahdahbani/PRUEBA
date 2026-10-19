<?php
require_once __DIR__ . '/../config.php';

abstract class ReservaDB {

    // Conecta con la base de datos y devuelve la conexión
    public static function connectDB() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8";
            $connection = new PDO($dsn, DB_USER, DB_PASS);
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            http_response_code(500);
            die("No se ha podido conectar con la base de datos");
        }
        return $connection;
    }
}
?>