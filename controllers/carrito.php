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

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

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

    // Obtener stock disponible para el producto y tamaño
    $stmt_stock = $db->conexion->prepare("SELECT stock FROM stock_productos WHERE producto_id = ? AND tamano = ?");
    $stmt_stock->bind_param('is', $producto_id, $tamano);
    $stmt_stock->execute();
    $result_stock = $stmt_stock->get_result();
    $stock_row = $result_stock->fetch_assoc();
    $stock_disponible = $stock_row ? intval($stock_row['stock']) : 0;
    // Debug temporal: si no hay registro de stock para ese tamaño
    if ($stock_row === null) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No hay stock registrado para este tamaño.']);
            exit();
        } else {
            header('Location: ../views/index.php?error=No hay stock registrado para este tamaño.');
            exit();
        }
    }

    $usuario_id = $_SESSION['usuario_id'];

    // Verificar si ya existe en el carrito
    $carrito_item = $queryManager->getCartItem($usuario_id, $producto_id, $tamano, $presentacion);
    $cantidad_en_carrito = $carrito_item ? intval($carrito_item['cantidad']) : 0;
    $nueva_cantidad = $cantidad_en_carrito + $cantidad;

    // Validar stock suficiente
    if ($nueva_cantidad > $stock_disponible) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No hay suficiente stock disponible']);
            exit();
        } else {
            header('Location: ../views/index.php?stock_error=1');
            exit();
        }
    }

    if ($carrito_item) {
        if ($queryManager->updateCartItem($carrito_item['id'], $nueva_cantidad)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit();
            } else {
                header('Location: ../views/index.php?added=1');
                exit();
            }
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Error al actualizar el carrito']);
                exit();
            } else {
                header('Location: ../views/index.php?error=Error al actualizar el carrito');
                exit();
            }
        }
    } else {
        // Insertar nuevo
        if ($queryManager->addToCart($usuario_id, $producto_id, $cantidad, $tamano, $presentacion)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit();
            } else {
                header('Location: ../views/index.php?added=1');
                exit();
            }
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Error al agregar al carrito']);
                exit();
            } else {
                header('Location: ../views/index.php?error=Error al agregar al carrito');
                exit();
            }
        }
    }
} else {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit();
    } else {
        header('Location: ../views/index.php?error=Método no permitido');
        exit();
    }
}

$db->desconectar();
exit();
