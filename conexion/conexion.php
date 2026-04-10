<?php
// conexion/conexion.php

class Conexion {
    private $host = "localhost";
    private $db_name = "gestion_discursos";
    private $username = "root"; // Usuario por defecto de XAMPP
    private $password = "";     // Contraseña por defecto de XAMPP suele estar vacía
    public $conn;

    public function obtenerConexion() {
        $this->conn = null;

        try {
            // Se establece la conexión PDO
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", $this->username, $this->password);
            
            // Configurar PDO para que lance excepciones cuando ocurra un error
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Opcional: Descomenta la siguiente línea solo para probar que conecta bien
            // echo "Conexión exitosa a la base de datos"; 
            
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>