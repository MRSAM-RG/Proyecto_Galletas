<?php
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: ../views/admin/admin.php');
    exit();
}

$db = new MySQL();
$db->conectar();
$queryManager = new QueryManager($db);

$id = intval($_GET['id']);
$stmt = $db->conexion->prepare("UPDATE productos SET estado = 0 WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    header('Location: ../views/admin/admin.php?success=Producto desactivado correctamente');
} else {
    header('Location: ../views/admin/admin.php?error=Error al desactivar el producto');
}

$db->desconectar();
exit();
?>
