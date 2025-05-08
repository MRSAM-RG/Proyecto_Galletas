<?php

class MySQL {

    public $conexion;
    private $host = 'localhost';
    private $usuario = 'root';
    private $clave = '';
    private $db = 'galletas_db';

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
}

?>