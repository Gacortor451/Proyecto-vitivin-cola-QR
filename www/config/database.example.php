<?php

class Database {
    private $host = "postgres_db";   // Cambiar según entorno
    private $db_name = "midb";       // Cambiar según entorno
    private $username = "usuario";   // Rellenar por el usuario
    private $password = "password";  // Rellenar por el usuario
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "pgsql:host={$this->host};port=5432;dbname={$this->db_name};";
            $this->conn = new PDO($dsn, $this->username, $this->password);

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch(PDOException $exception) {
            echo "Error de conexión";
        }

        return $this->conn;
    }
}
