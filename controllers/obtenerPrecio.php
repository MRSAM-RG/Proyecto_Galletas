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

if (!$producto_id || $tamano !== 'normal' || !in_array($presentacion, ['unidad', 'paquete3', 'paquete_mixto'])) {
    echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
    exit();
}

$db = new MySQL();
$db->conectar();

// Si es paquete mixto, obtener el precio desde la base de datos (el mismo para todos los productos)
if ($presentacion === 'paquete_mixto') {
    $stmt = $db->conexion->prepare("SELECT precio FROM precios_productos WHERE presentacion = 'paquete_mixto' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'precio' => $row['precio']]);
    } else {
        // Si no hay precio de paquete mixto, usar el valor por defecto
        echo json_encode(['success' => true, 'precio' => 75000]);
    }
} else {
    // Para unidad y paquete3, usar el precio específico del producto
    $stmt = $db->conexion->prepare("SELECT precio FROM precios_productos WHERE producto_id = ? AND tamano = ? AND presentacion = ?");
    $stmt->bind_param('iss', $producto_id, $tamano, $presentacion);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'precio' => $row['precio']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Precio no encontrado']);
    }
}

$db->desconectar(); 