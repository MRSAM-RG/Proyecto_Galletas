<?php
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';
require_once '../config/security.php';

session_start();
setSecurityHeaders();

header('Content-Type: application/json');

if (!isset($_GET['producto_id']) || !isset($_GET['tamano']) || !isset($_GET['presentacion'])) {
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros requeridos']);
    exit();
}

$producto_id = filter_input(INPUT_GET, 'producto_id', FILTER_VALIDATE_INT);
$tamano = filter_input(INPUT_GET, 'tamano', FILTER_SANITIZE_STRING);
$presentacion = filter_input(INPUT_GET, 'presentacion', FILTER_SANITIZE_STRING);

if (!$producto_id || !in_array($tamano, ['normal', 'jumbo']) || !in_array($presentacion, ['unidad', 'paquete3'])) {
    echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
    exit();
}

$db = new MySQL();
$db->conectar();

$stmt = $db->conexion->prepare("SELECT precio FROM precios_productos WHERE producto_id = ? AND tamano = ? AND presentacion = ?");
$stmt->bind_param('iss', $producto_id, $tamano, $presentacion);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'precio' => $row['precio']]);
} else {
    echo json_encode(['success' => false, 'error' => 'Precio no encontrado']);
}

$db->desconectar(); 