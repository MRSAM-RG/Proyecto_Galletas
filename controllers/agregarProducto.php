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
    // Sanitización de datos
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);

    // Validación de campos vacíos
    if (empty($nombre) || empty($descripcion)) {
        header('Location: ../views/admin/admin.php?error=Todos los campos son obligatorios');
        exit();
    }

    // Validación de precios
    $precio_normal_unidad = filter_input(INPUT_POST, 'precio_normal_unidad', FILTER_VALIDATE_FLOAT);
    $precio_normal_paquete3 = filter_input(INPUT_POST, 'precio_normal_paquete3', FILTER_VALIDATE_FLOAT);
    $precio_jumbo_unidad = filter_input(INPUT_POST, 'precio_jumbo_unidad', FILTER_VALIDATE_FLOAT);
    $precio_jumbo_paquete3 = filter_input(INPUT_POST, 'precio_jumbo_paquete3', FILTER_VALIDATE_FLOAT);

    if ($precio_normal_unidad === false || $precio_normal_paquete3 === false || 
        $precio_jumbo_unidad === false || $precio_jumbo_paquete3 === false) {
        header('Location: ../views/admin/admin.php?error=Los precios deben ser números válidos');
        exit();
    }

    if ($precio_normal_unidad <= 0 || $precio_normal_paquete3 <= 0 || 
        $precio_jumbo_unidad <= 0 || $precio_jumbo_paquete3 <= 0) {
        header('Location: ../views/admin/admin.php?error=Los precios deben ser mayores a 0');
        exit();
    }

    // Validación de stock
    $stock_normal = filter_input(INPUT_POST, 'stock_normal', FILTER_VALIDATE_INT);
    $stock_jumbo = filter_input(INPUT_POST, 'stock_jumbo', FILTER_VALIDATE_INT);

    if ($stock_normal === false || $stock_jumbo === false || $stock_normal < 0 || $stock_jumbo < 0) {
        header('Location: ../views/admin/admin.php?error=El stock debe ser un número entero positivo');
        exit();
    }

    // Validación de longitud
    if (strlen($nombre) < 3 || strlen($nombre) > 100) {
        header('Location: ../views/admin/admin.php?error=El nombre debe tener entre 3 y 100 caracteres');
        exit();
    }

    if (strlen($descripcion) > 500) {
        header('Location: ../views/admin/admin.php?error=La descripción no puede exceder los 500 caracteres');
        exit();
    }

    // Validación de la imagen
    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        header('Location: ../views/admin/admin.php?error=Error al subir la imagen');
        exit();
    }

    // Validación del tipo de archivo
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
    $tipoArchivo = $_FILES['imagen']['type'];
    
    if (!in_array($tipoArchivo, $tiposPermitidos)) {
        header('Location: ../views/admin/admin.php?error=Formato de imagen no permitido. Use JPG, PNG o GIF');
        exit();
    }

    // Validación del tamaño de la imagen (máximo 5MB)
    if ($_FILES['imagen']['size'] > 5242880) {
        header('Location: ../views/admin/admin.php?error=La imagen no puede ser mayor a 5MB');
        exit();
    }

    // Generar nombre único para la imagen
    $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
    $nombreImagen = uniqid() . '.' . $extension;
    $rutaDestino = '../assets/img/' . $nombreImagen;

    // Mover la imagen a la carpeta de imágenes
    if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
        header('Location: ../views/admin/admin.php?error=Error al guardar la imagen');
        exit();
    }

    // Sanitización adicional
    $nombre = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $descripcion = htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8');

    // Usar consultas preparadas para prevenir SQL injection
    $stmt = $db->conexion->prepare("INSERT INTO productos (nombre, descripcion, imagen) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $descripcion, $nombreImagen);
    
    if ($stmt->execute()) {
        // Obtener el ID del producto recién insertado
        $producto_id = $stmt->insert_id;
        
        // Insertar los precios específicos
        $precios = [
            ['normal', 'unidad', $precio_normal_unidad],
            ['normal', 'paquete3', $precio_normal_paquete3],
            ['jumbo', 'unidad', $precio_jumbo_unidad],
            ['jumbo', 'paquete3', $precio_jumbo_paquete3]
        ];
        
        $stmt_precios = $db->conexion->prepare("INSERT INTO precios_productos (producto_id, tamano, presentacion, precio) VALUES (?, ?, ?, ?)");
        
        foreach ($precios as $precio) {
            $tamano = $precio[0];
            $presentacion = $precio[1];
            $valor = $precio[2];
            $stmt_precios->bind_param('issd', $producto_id, $tamano, $presentacion, $valor);
            if (!$stmt_precios->execute()) {
                // Si hay error al insertar precios, eliminar el producto y la imagen
                $stmt_delete = $db->conexion->prepare("DELETE FROM productos WHERE id = ?");
                $stmt_delete->bind_param('i', $producto_id);
                $stmt_delete->execute();
                unlink($rutaDestino);
                header('Location: ../views/admin/admin.php?error=Error al agregar los precios del producto');
                exit();
            }
        }

        // Insertar el stock
        $stmt_stock = $db->conexion->prepare("INSERT INTO stock_productos (producto_id, tamano, stock) VALUES (?, ?, ?)");
        
        // Insertar stock normal
        $tamano_stock = 'normal';
        $stmt_stock->bind_param('isi', $producto_id, $tamano_stock, $stock_normal);
        if (!$stmt_stock->execute()) {
            // Si hay error al insertar stock, eliminar el producto, precios e imagen
            $stmt_delete = $db->conexion->prepare("DELETE FROM productos WHERE id = ?");
            $stmt_delete->bind_param('i', $producto_id);
            $stmt_delete->execute();
            $stmt_delete_precios = $db->conexion->prepare("DELETE FROM precios_productos WHERE producto_id = ?");
            $stmt_delete_precios->bind_param('i', $producto_id);
            $stmt_delete_precios->execute();
            unlink($rutaDestino);
            header('Location: ../views/admin/admin.php?error=Error al agregar el stock del producto');
            exit();
        }
        
        // Insertar stock jumbo
        $tamano_stock = 'jumbo';
        $stmt_stock->bind_param('isi', $producto_id, $tamano_stock, $stock_jumbo);
        if (!$stmt_stock->execute()) {
            // Si hay error al insertar stock jumbo, eliminar el producto, precios e imagen
            $stmt_delete = $db->conexion->prepare("DELETE FROM productos WHERE id = ?");
            $stmt_delete->bind_param('i', $producto_id);
            $stmt_delete->execute();
            $stmt_delete_precios = $db->conexion->prepare("DELETE FROM precios_productos WHERE producto_id = ?");
            $stmt_delete_precios->bind_param('i', $producto_id);
            $stmt_delete_precios->execute();
            unlink($rutaDestino);
            header('Location: ../views/admin/admin.php?error=Error al agregar el stock del producto');
            exit();
        }
        
        header('Location: ../views/admin/admin.php?success=Producto agregado correctamente');
    } else {
        // Si hay error, eliminar la imagen subida
        unlink($rutaDestino);
        header('Location: ../views/admin/admin.php?error=Error al agregar el producto');
    }
    exit();
}

$db->desconectar();
?>