<?php
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';
require_once '../config/security.php';

session_start();
setSecurityHeaders();

if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión']);
    exit();
}

$producto_id = filter_input(INPUT_GET, 'producto_id', FILTER_VALIDATE_INT);
$tamano = filter_input(INPUT_GET, 'tamano', FILTER_SANITIZE_STRING);
$cantidad = filter_input(INPUT_GET, 'cantidad', FILTER_VALIDATE_INT);

if (!$producto_id || !$tamano || !$cantidad) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
    exit();
}

$db = new MySQL();
$db->conectar();
$queryManager = new QueryManager($db);

if (!$queryManager->verificarStock($producto_id, $tamano, $cantidad)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No hay suficiente stock disponible']);
    exit();
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
exit(); 