<?php
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../views/login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: ../views/admin/productos.php?error=ID de producto no especificado');
    exit();
}

$db = new MySQL();
$db->conectar();
$queryManager = new QueryManager($db);

$id = $_GET['id'];

if ($queryManager->reactivateProduct($id)) {
    header('Location: ../views/admin/productos.php?success=Producto reactivado correctamente');
} else {
    header('Location: ../views/admin/productos.php?error=Error al reactivar el producto');
}

$db->desconectar();
?> 