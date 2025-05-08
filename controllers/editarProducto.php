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