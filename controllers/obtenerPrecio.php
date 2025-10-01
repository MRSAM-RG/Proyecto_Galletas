<?php
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';
require_once '../config/security.php';

session_start();
setSecurityHeaders();
header('Content-Type: application/json');

if (!isset($_GET['producto_id'], $_GET['tamano'], $_GET['presentacion'])) {
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros requeridos']); exit;
}

$producto_id  = filter_input(INPUT_GET, 'producto_id', FILTER_VALIDATE_INT);
$tamano       = trim((string)($_GET['tamano'] ?? ''));
$presentacion = strtolower(trim((string)($_GET['presentacion'] ?? '')));

$permitidas = ['paquete6','paquete9','paquete12','paquete_mixto'];
if (!$producto_id || $tamano !== 'normal' || !in_array($presentacion, $permitidas, true)) {
    echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']); exit;
}

$db = new MySQL();
$db->conectar();

if ($presentacion === 'paquete_mixto') {
    $stmt = $db->conexion->prepare("
        SELECT precio
          FROM precios_productos
         WHERE LOWER(TRIM(presentacion)) = 'paquete_mixto'
         LIMIT 1
    ");
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    echo json_encode(['success' => true, 'precio' => $res ? (float)$res['precio'] : 75000]);
} else {
    $stmt = $db->conexion->prepare("
        SELECT precio
          FROM precios_productos
         WHERE producto_id  = ?
           AND LOWER(TRIM(tamano)) = 'normal'
           AND LOWER(TRIM(presentacion)) = ?
         LIMIT 1
    ");
    $stmt->bind_param('is', $producto_id, $presentacion);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    echo $res
        ? json_encode(['success' => true, 'precio' => (float)$res['precio']])
        : json_encode(['success' => false, 'error' => 'Precio no encontrado']);
}

$db->desconectar();
