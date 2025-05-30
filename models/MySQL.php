<?php

class MySQL {

    public $conexion;
    private $host = 'localhost';
    private $usuario = 'root';
    private $clave = '';
    private $db = 'bd_galletas';

    public function conectar() {
        $this->conexion = new mysqli($this->host, $this->usuario, $this->clave, $this->db);
        
        if ($this->conexion->connect_error) {
            die('Error de conexión: ' . $this->conexion->connect_error);
        }

        $this->conexion->set_charset("utf8");
        return $this->conexion;
    }

    public function desconectar() {
        if ($this->conexion) {
            $this->conexion->close();
        }
    }

    public function ejecutarConsulta($query) {
        $resultado = $this->conexion->query($query);
        if (!$resultado) {
            die('Error en la consulta: ' . $this->conexion->error);
        }
        return $resultado;
    }

    public function obtenerFilas($resultado) {
        return $resultado->fetch_assoc();
    }

    public function escapar($string) {
        return $this->conexion->real_escape_string($string);
    }

    public function obtenerError() {
        return $this->conexion->error;
    }

    public function iniciarTransaccion() {
        $this->conexion->begin_transaction();
    }

    public function confirmarTransaccion() {
        $this->conexion->commit();
    }

    public function revertirTransaccion() {
        $this->conexion->rollback();
    }

    public function obtenerUltimoId() {
        return $this->conexion->insert_id;
    }

    public function crearTablas() {
        // Tabla de precios por producto, tamaño y presentación
        $sql_precios = "CREATE TABLE IF NOT EXISTS precios_productos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            producto_id INT NOT NULL,
            tamano ENUM('normal') NOT NULL,
            presentacion ENUM('unidad', 'paquete3', 'paquete_mixto') NOT NULL,
            precio DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
            UNIQUE KEY unique_precio (producto_id, tamano, presentacion)
        )";
        $this->conexion->query($sql_precios);
    }
}

?>