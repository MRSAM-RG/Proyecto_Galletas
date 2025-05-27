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
        foreach ($precios as $precio) {
            $stmt_precios->bind_param('issd', $id, $precio[0], $precio[1], $precio[2]);
            $stmt_precios->execute();
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