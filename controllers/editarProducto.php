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
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
    $precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
    $factor_diferencial = filter_input(INPUT_POST, 'factor_diferencial', FILTER_SANITIZE_STRING);

    // Validación de stock
    $stock_normal = filter_input(INPUT_POST, 'stock_normal', FILTER_VALIDATE_INT);
    $stock_jumbo = filter_input(INPUT_POST, 'stock_jumbo', FILTER_VALIDATE_INT);

    if ($stock_normal === false || $stock_jumbo === false || $stock_normal < 0 || $stock_jumbo < 0) {
        header('Location: ../views/admin/admin.php?error=El stock debe ser un número entero positivo');
        exit();
    }

    $imagen = null;
    if (!empty($_FILES['imagen']['name'])) {
        $imagen = $_FILES['imagen']['name'];
        $rutaDestino = '../assets/img/' . basename($imagen);
        if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
            header('Location: ../views/admin/admin.php?error=Error al subir la imagen');
            exit();
        }
    } else {
        $imagen = null;
    }

    if ($queryManager->updateProduct($id, $nombre, $descripcion, $precio, $factor_diferencial, $imagen)) 
        // Actualizar precios por tamaño y presentación
        $precios = [
            ['normal', 'unidad', $_POST['precio_normal_unidad']],
            ['normal', 'paquete3', $_POST['precio_normal_paquete3']],
            ['jumbo', 'unidad', $_POST['precio_jumbo_unidad']],
            ['jumbo', 'paquete3', $_POST['precio_jumbo_paquete3']]
        ];
        // Eliminar precios anteriores
        $stmt_del = $db->conexion->prepare("DELETE FROM precios_productos WHERE producto_id = ?");
        $stmt_del->bind_param('i', $id);
        $stmt_del->execute();
        // Insertar nuevos precios
        $stmt_precios = $db->conexion->prepare("INSERT INTO precios_productos (producto_id, tamano, presentacion, precio) VALUES (?, ?, ?, ?)");
        foreach ($precios as $precio_arr) {
            $tamano = $precio_arr[0];
            $presentacion = $precio_arr[1];
            $valor = $precio_arr[2];
            $stmt_precios->bind_param('issd', $id, $tamano, $presentacion, $valor);
            $stmt_precios->execute();
        }

        // Actualizar el stock (upsert manual)
        foreach ([['normal', $stock_normal], ['jumbo', $stock_jumbo]] as [$tamano, $stock]) {
            $stmt_update = $db->conexion->prepare("UPDATE stock_productos SET stock = ? WHERE producto_id = ? AND tamano = ?");
            $stmt_update->bind_param('iis', $stock, $id, $tamano);
            $stmt_update->execute();
            if ($stmt_update->affected_rows === 0) {
                // No existía, insertar
                $stmt_insert = $db->conexion->prepare("INSERT INTO stock_productos (producto_id, tamano, stock) VALUES (?, ?, ?)");
                $stmt_insert->bind_param('isi', $id, $tamano, $stock);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
            $stmt_update->close();
        }

        header('Location: ../views/admin/admin.php?success=Producto actualizado correctamente');
    } else {
        if ($imagen) {
            unlink('../assets/img/' . $imagen);
        }
        header('Location: ../views/admin/admin.php?error=Error al actualizar el producto');
    }
    exit();

$producto = $queryManager->getProductById($id);
if (!$producto) {
    header('Location: ../views/admin/admin.php?error=Producto no encontrado');
    exit();
}

$db->desconectar();
?>