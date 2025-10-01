<?php
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';
require_once '../config/security.php';

session_start();
setSecurityHeaders();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../views/login.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/carrito.php?error=' . urlencode('Método no permitido')); exit;
}

$carrito_id = filter_input(INPUT_POST, 'carrito_id', FILTER_VALIDATE_INT);
if (!$carrito_id) {
    header('Location: ../views/carrito.php?error=' . urlencode('Ítem inválido')); exit;
}

$db = new MySQL();
$db->conectar();
$qm = new QueryManager($db);

$qm->deleteCartItem($carrito_id, (int)$_SESSION['usuario_id']);

$db->desconectar();
header('Location: ../views/carrito.php');
exit;
