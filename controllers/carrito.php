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

    // Obtener el precio específico según tamaño y presentación
    $stmt_precio = $db->conexion->prepare("SELECT precio FROM precios_productos WHERE producto_id = ? AND tamano = ? AND presentacion = ?");
    $stmt_precio->bind_param('iss', $producto_id, $tamano, $presentacion);
    $stmt_precio->execute();
    $result_precio = $stmt_precio->get_result();
    $precio_row = $result_precio->fetch_assoc();
    
    if (!$precio_row) {
        header('Location: ../views/index.php?error=Precio no disponible para esta combinación');
        exit();
    }
    
    $precio = $precio_row['precio'];

    // Eliminar verificación de stock
    $usuario_id = $_SESSION['usuario_id'];

    // Verificar si ya existe en el carrito
    $carrito_item = $queryManager->getCartItem($usuario_id, $producto_id, $tamano, $presentacion);
    $cantidad_en_carrito = $carrito_item ? intval($carrito_item['cantidad']) : 0;
    $nueva_cantidad = $cantidad_en_carrito + $cantidad;

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
