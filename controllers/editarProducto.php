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
    $precio = floatval($_POST['precio']);
    $factor_diferencial = filter_input(INPUT_POST, 'factor_diferencial', FILTER_SANITIZE_STRING);

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

    if ($queryManager->updateProduct($id, $nombre, $descripcion, $precio, $factor_diferencial, $imagen)) {
        // Actualizar stock si se enviaron los campos
        if (isset($_POST['stock_normal']) && isset($_POST['stock_jumbo'])) {
            $stock_normal = intval($_POST['stock_normal']);
            $stock_jumbo = intval($_POST['stock_jumbo']);
            // Actualizar stock normal
            $stmt_stock = $db->conexion->prepare("UPDATE stock_productos SET stock = ? WHERE producto_id = ? AND tamano = 'normal'");
            $stmt_stock->bind_param('ii', $stock_normal, $id);
            $stmt_stock->execute();
            // Actualizar stock jumbo
            $stmt_stock = $db->conexion->prepare("UPDATE stock_productos SET stock = ? WHERE producto_id = ? AND tamano = 'jumbo'");
            $stmt_stock->bind_param('ii', $stock_jumbo, $id);
            $stmt_stock->execute();
        }
        header('Location: ../views/admin/admin.php?success=Producto actualizado correctamente');
    } else {
        if ($imagen) {
            unlink('../assets/img/' . $imagen);
        }
        header('Location: ../views/admin/admin.php?error=Error al actualizar el producto');
    }
    exit();
}

$producto = $queryManager->getProductById($id);
if (!$producto) {
    header('Location: ../views/admin/admin.php?error=Producto no encontrado');
    exit();
}

$db->desconectar();
?>