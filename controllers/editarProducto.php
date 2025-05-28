<?php
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = new MySQL();
$db->conectar();
$queryManager = new QueryManager($db);

$id = $_POST['id'] ?? $_GET['id'] ?? null;
if (!$id) {
    header('Location: ../views/admin/admin.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    
    // Validar que los precios existan y sean números válidos
    $precio_normal = isset($_POST['precio_normal']) ? floatval($_POST['precio_normal']) : 0;
    $precio_jumbo = isset($_POST['precio_jumbo']) ? floatval($_POST['precio_jumbo']) : 0;
    $precio_normal_paquete3 = isset($_POST['precio_normal_paquete3']) ? floatval($_POST['precio_normal_paquete3']) : 0;
    $precio_jumbo_paquete3 = isset($_POST['precio_jumbo_paquete3']) ? floatval($_POST['precio_jumbo_paquete3']) : 0;

    // Validar que los precios sean mayores a 0
    if ($precio_normal <= 0 || $precio_jumbo <= 0 || $precio_normal_paquete3 <= 0 || $precio_jumbo_paquete3 <= 0) {
        header('Location: ../views/admin/editarProducto.php?id=' . $id . '&error=Los precios deben ser mayores a 0');
        exit();
    }

    // Actualizar información básica del producto
    $stmt = $db->conexion->prepare("UPDATE productos SET nombre = ?, descripcion = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nombre, $descripcion, $id);
    
    if (!$stmt->execute()) {
        $db->desconectar();
        header('Location: ../views/admin/editarProducto.php?id=' . $id . '&error=Error al actualizar el producto');
        exit();
    }

    // Actualizar precios
    // Primero eliminamos los precios existentes
    $stmt = $db->conexion->prepare("DELETE FROM precios_productos WHERE producto_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Insertar los nuevos precios
    $stmt = $db->conexion->prepare("INSERT INTO precios_productos (producto_id, tamano, presentacion, precio) VALUES (?, ?, ?, ?)");
    
    // Precio Normal Unidad
    $tamano = 'normal';
    $presentacion = 'unidad';
    $stmt->bind_param("issd", $id, $tamano, $presentacion, $precio_normal);
    $stmt->execute();

    // Precio Jumbo Unidad
    $tamano = 'jumbo';
    $presentacion = 'unidad';
    $stmt->bind_param("issd", $id, $tamano, $presentacion, $precio_jumbo);
    $stmt->execute();

    // Precio Normal Paquete 3
    $tamano = 'normal';
    $presentacion = 'paquete3';
    $stmt->bind_param("issd", $id, $tamano, $presentacion, $precio_normal_paquete3);
    $stmt->execute();

    // Precio Jumbo Paquete 3
    $tamano = 'jumbo';
    $presentacion = 'paquete3';
    $stmt->bind_param("issd", $id, $tamano, $presentacion, $precio_jumbo_paquete3);
    $stmt->execute();

    // Procesar la imagen si se subió una nueva
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen = $_FILES['imagen'];
        $tipo = $imagen['type'];
        $tamano = $imagen['size'];
        $temp = $imagen['tmp_name'];
        
        // Validar tipo de archivo
        if ($tipo !== 'image/jpeg' && $tipo !== 'image/png' && $tipo !== 'image/webp') {
            $db->desconectar();
            header('Location: ../views/admin/editarProducto.php?id=' . $id . '&error=Tipo de archivo no permitido');
            exit();
        }
        
        // Validar tamaño (5MB máximo)
        if ($tamano > 5 * 1024 * 1024) {
            $db->desconectar();
            header('Location: ../views/admin/editarProducto.php?id=' . $id . '&error=La imagen es demasiado grande');
            exit();
        }
        
        // Generar nombre único
        $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
        $nombre_archivo = uniqid() . '.' . $extension;
        
        // Mover archivo
        if (move_uploaded_file($temp, '../assets/img/' . $nombre_archivo)) {
            // Actualizar nombre de imagen en la base de datos
            $stmt = $db->conexion->prepare("UPDATE productos SET imagen = ? WHERE id = ?");
            $stmt->bind_param("si", $nombre_archivo, $id);
            $stmt->execute();
        }
    }

    $db->desconectar();
    header('Location: ../views/admin/productos.php?success=Producto actualizado correctamente');
    exit();
}

$producto = $queryManager->getProductById($id);
if (!$producto) {
    header('Location: ../views/admin/admin.php?error=Producto no encontrado');
    exit();
}

$db->desconectar();
?>