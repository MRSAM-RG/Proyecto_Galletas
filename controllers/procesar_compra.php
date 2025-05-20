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
    // Calcular cantidad real a descontar según presentación
    $descontar = ($item['presentacion'] === 'paquete3') ? $item['cantidad'] * 3 : $item['cantidad'];
    // Validar stock antes de descontar
    $stmt_check = $db->conexion->prepare("SELECT stock FROM stock_productos WHERE producto_id = ? AND tamano = ?");
    $stmt_check->bind_param('is', $item['producto_id'], $item['tamano']);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_stock = $result_check->fetch_assoc();
    $stock_actual = $row_stock ? intval($row_stock['stock']) : 0;
    if ($stock_actual < $descontar) {
        $db->desconectar();
        header('Location: ../views/carrito.php?error=Stock insuficiente para "' . $item['nombre'] . '" (' . $item['tamano'] . ')');
        exit();
    }
    // Descontar stock solo si hay suficiente
    $stmt = $db->conexion->prepare("UPDATE stock_productos SET stock = stock - ? WHERE producto_id = ? AND tamano = ? AND stock >= ?");
    $stmt->bind_param('iisi', $descontar, $item['producto_id'], $item['tamano'], $descontar);
    $stmt->execute();
}

// Vaciar carrito
$queryManager->clearCart($usuario_id);

$db->desconectar();
header('Location: ../views/index.php?success=¡Pedido realizado con éxito!');
exit(); 