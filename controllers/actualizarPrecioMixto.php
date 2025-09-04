<?php
require_once '../models/MySQL.php';
session_start();

// Validación de sesión y rol de administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../views/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $precio_mixto = isset($_POST['precio_mixto']) ? floatval($_POST['precio_mixto']) : 0;

    // Validar que el precio sea mayor a 0
    if ($precio_mixto <= 0) {
        header('Location: ../views/admin/productos.php?error=El precio debe ser mayor a 0');
        exit();
    }

    $db = new MySQL();
    $db->conectar();

    // Actualizar el precio del paquete mixto para todos los productos
    $stmt = $db->conexion->prepare("UPDATE precios_productos SET precio = ? WHERE presentacion = 'paquete_mixto'");
    $stmt->bind_param("d", $precio_mixto);
    
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $db->desconectar();
        header('Location: ../views/admin/productos.php?success=Precio del paquete mixto actualizado correctamente (' . $affected_rows . ' productos afectados)');
    } else {
        $db->desconectar();
        header('Location: ../views/admin/productos.php?error=Error al actualizar el precio del paquete mixto');
    }
} else {
    header('Location: ../views/admin/productos.php');
}
?>