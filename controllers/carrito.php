<?php
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';
require_once '../config/security.php';

session_start();
setSecurityHeaders();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../views/login.php');
    exit();
}

$db = new MySQL();
$db->conectar();
$queryManager = new QueryManager($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar entrada
    $producto_id = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
    $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1, 'max_range' => 99]]);
    $tamano = isset($_POST['tamano']) && in_array($_POST['tamano'], ['normal','jumbo']) ? $_POST['tamano'] : 'normal';
    $presentacion = isset($_POST['presentacion']) && in_array($_POST['presentacion'], ['unidad','paquete3']) ? $_POST['presentacion'] : 'unidad';

    if (!$producto_id) {
        header('Location: ../views/index.php?error=Producto inválido');
        exit();
    }

    // Verificar si el producto existe y está disponible
    $producto = $queryManager->getProductById($producto_id);

    if (!$producto) {
        header('Location: ../views/index.php?error=Producto no disponible');
        exit();
    }

    $usuario_id = $_SESSION['usuario_id'];

    // Verificar si ya existe en el carrito
    $carrito_item = $queryManager->getCartItem($usuario_id, $producto_id, $tamano, $presentacion);

    if ($carrito_item) {
        $nueva_cantidad = $carrito_item['cantidad'] + $cantidad;
        
        if ($queryManager->updateCartItem($carrito_item['id'], $nueva_cantidad)) {
            header('Location: ../views/index.php?success=Producto actualizado en el carrito');
        } else {
            header('Location: ../views/index.php?error=Error al actualizar el carrito');
        }
    } else {
        // Insertar nuevo
        if ($queryManager->addToCart($usuario_id, $producto_id, $cantidad, $tamano, $presentacion)) {
            header('Location: ../views/index.php?success=Producto agregado al carrito');
        } else {
            header('Location: ../views/index.php?error=Error al agregar al carrito');
        }
    }
} else {
    header('Location: ../views/index.php?error=Método no permitido');
}

$db->desconectar();
exit();
