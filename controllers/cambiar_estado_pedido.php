<?php
require_once '../models/MySQL.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../views/index.php?error=Acceso no autorizado');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: ../views/admin/pedidos.php?error=ID de pedido no especificado');
    exit();
}

$pedido_id = intval($_GET['id']);
$db = new MySQL();
$db->conectar();

// Verificar que el pedido existe
$stmt = $db->conexion->prepare("SELECT id FROM pedidos WHERE id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $db->desconectar();
    header('Location: ../views/admin/pedidos.php?error=Pedido no encontrado');
    exit();
}

// Cambiar estado a completado
$stmt = $db->conexion->prepare("UPDATE pedidos SET estado = 'completado' WHERE id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$db->desconectar();

header('Location: ../views/admin/pedidos.php?success=Pedido marcado como completado');
exit(); 