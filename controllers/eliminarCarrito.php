<?php
session_start();
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['carrito_id'])) {
    $carrito_id = intval($_POST['carrito_id']);
    $usuario_id = $_SESSION['usuario_id'];

    $db = new MySQL();
    $db->conectar();
    $queryManager = new QueryManager($db);
    
    if ($queryManager->deleteCartItem($carrito_id, $usuario_id)) {
        header('Location: ../views/carrito.php?success=Producto eliminado del carrito');
    } else {
        header('Location: ../views/carrito.php?error=Error al eliminar el producto del carrito');
    }
    
    $db->desconectar();
}

header('Location: ../views/carrito.php');
exit();
