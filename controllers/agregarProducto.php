<?php
require_once '../models/MySQL.php';
session_start();

// Validación de sesión y rol de administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = new MySQL();
$db->conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    
    // Validar que los precios existan y sean números válidos
    $precio_normal = isset($_POST['precio_normal']) ? floatval($_POST['precio_normal']) : 0;
    $precio_jumbo = isset($_POST['precio_jumbo']) ? floatval($_POST['precio_jumbo']) : 0;
    $precio_normal_paquete3 = isset($_POST['precio_normal_paquete3']) ? floatval($_POST['precio_normal_paquete3']) : 0;
    $precio_jumbo_paquete3 = isset($_POST['precio_jumbo_paquete3']) ? floatval($_POST['precio_jumbo_paquete3']) : 0;

    // Validar que los precios sean mayores a 0
    if ($precio_normal <= 0 || $precio_jumbo <= 0 || $precio_normal_paquete3 <= 0 || $precio_jumbo_paquete3 <= 0) {
        header('Location: ../views/admin/agregarProducto.php?error=Los precios deben ser mayores a 0');
        exit();
    }

    // Validar y procesar la imagen
    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        header('Location: ../views/admin/agregarProducto.php?error=Debe seleccionar una imagen');
        exit();
    }

    $imagen = $_FILES['imagen'];
    $tipo = $imagen['type'];
    $tamano = $imagen['size'];
    $temp = $imagen['tmp_name'];
    
    // Validar tipo de archivo
    if ($tipo !== 'image/jpeg' && $tipo !== 'image/png' && $tipo !== 'image/webp') {
        header('Location: ../views/admin/agregarProducto.php?error=Tipo de archivo no permitido');
        exit();
    }
    
    // Validar tamaño (5MB máximo)
    if ($tamano > 5 * 1024 * 1024) {
        header('Location: ../views/admin/agregarProducto.php?error=La imagen es demasiado grande');
        exit();
    }
    
    // Generar nombre único para la imagen
    $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
    $nombre_archivo = uniqid() . '.' . $extension;
    
    // Insertar el producto
    $stmt = $db->conexion->prepare("INSERT INTO productos (nombre, descripcion, imagen) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $descripcion, $nombre_archivo);
    
    if (!$stmt->execute()) {
        $db->desconectar();
        header('Location: ../views/admin/agregarProducto.php?error=Error al crear el producto');
        exit();
    }

    $producto_id = $db->conexion->insert_id;

    // Insertar los precios
    $stmt = $db->conexion->prepare("INSERT INTO precios_productos (producto_id, tamano, presentacion, precio) VALUES (?, ?, ?, ?)");
    
    // Precio Normal Unidad
    $tamano = 'normal';
    $presentacion = 'unidad';
    $stmt->bind_param("issd", $producto_id, $tamano, $presentacion, $precio_normal);
    $stmt->execute();

    // Precio Jumbo Unidad
    $tamano = 'jumbo';
    $presentacion = 'unidad';
    $stmt->bind_param("issd", $producto_id, $tamano, $presentacion, $precio_jumbo);
    $stmt->execute();

    // Precio Normal Paquete 3
    $tamano = 'normal';
    $presentacion = 'paquete3';
    $stmt->bind_param("issd", $producto_id, $tamano, $presentacion, $precio_normal_paquete3);
    $stmt->execute();

    // Precio Jumbo Paquete 3
    $tamano = 'jumbo';
    $presentacion = 'paquete3';
    $stmt->bind_param("issd", $producto_id, $tamano, $presentacion, $precio_jumbo_paquete3);
    $stmt->execute();

    // Mover la imagen
    if (move_uploaded_file($temp, '../assets/img/' . $nombre_archivo)) {
        $db->desconectar();
        header('Location: ../views/admin/productos.php?success=Producto agregado correctamente');
        exit();
    } else {
        // Si falla al mover la imagen, eliminar el producto
        $stmt = $db->conexion->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $db->desconectar();
        header('Location: ../views/admin/agregarProducto.php?error=Error al subir la imagen');
        exit();
    }
}

$db->desconectar();
?>