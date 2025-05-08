<?php
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../views/login.php?error=Debes iniciar sesión para comprar');
    exit();
}

$db = new MySQL();
$db->conectar();
$queryManager = new QueryManager($db);
$usuario_id = $_SESSION['usuario_id'];

// Obtener productos del carrito
$result = $queryManager->getCartItems($usuario_id);
$carrito = [];
while ($row = $result->fetch_assoc()) {
    $carrito[] = $row;
}

if (empty($carrito)) {
    $db->desconectar();
    header('Location: ../views/carrito.php?error=Tu carrito está vacío');
    exit();
}

$direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
if (strlen($direccion) < 5) {
    $db->desconectar();
    header('Location: ../views/carrito.php?error=Dirección inválida');
    exit();
}

$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
if (!preg_match('/^[0-9]{10}$/', $telefono)) {
    $db->desconectar();
    header('Location: ../views/carrito.php?error=Número de teléfono inválido');
    exit();
}

// Crear pedido
$pedido_id = $queryManager->createOrder($usuario_id, $direccion, $telefono);

// Insertar detalles del pedido
foreach ($carrito as $item) {
    $queryManager->addOrderDetail(
        $pedido_id, 
        $item['producto_id'], 
        $item['cantidad'], 
        $item['precio'], 
        $item['tamano'], 
        $item['presentacion']
    );
}

// Vaciar carrito
$queryManager->clearCart($usuario_id);

$db->desconectar();
header('Location: ../views/index.php?success=¡Pedido realizado con éxito!');
exit(); 