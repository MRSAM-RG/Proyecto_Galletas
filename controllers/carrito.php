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
    $tamano = 'normal';
    $presentacion = isset($_POST['presentacion']) && in_array($_POST['presentacion'], ['unidad','paquete3','paquete_mixto']) ? $_POST['presentacion'] : 'unidad';

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
    if ($presentacion === 'paquete_mixto') {
        // Obtener el precio global del paquete mixto
        $stmt_precio = $db->conexion->prepare("SELECT precio FROM precios_productos WHERE presentacion = 'paquete_mixto' LIMIT 1");
        $stmt_precio->execute();
        $result_precio = $stmt_precio->get_result();
        $precio_row = $result_precio->fetch_assoc();
        
        if (!$precio_row) {
            // Si no hay precio de paquete mixto, usar el valor por defecto
            $precio = 75000;
        } else {
            $precio = $precio_row['precio'];
        }
    } else {
        // Para unidad y paquete3, usar el precio específico del producto
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
    }

    $usuario_id = $_SESSION['usuario_id'];

    // Verificar si ya existe en el carrito
    $carrito_item = $queryManager->getCartItem($usuario_id, $producto_id, $tamano, $presentacion);
    $cantidad_en_carrito = $carrito_item ? intval($carrito_item['cantidad']) : 0;
    $nueva_cantidad = $cantidad_en_carrito + $cantidad;

    // Calcular el total de galletas en el carrito
    $total_galletas = $queryManager->getCartCount($usuario_id);
    $galletas_a_agregar = $presentacion === 'paquete3' || $presentacion === 'paquete_mixto' ? $cantidad * 3 : $cantidad;
    $total_despues_agregar = $total_galletas + $galletas_a_agregar;

    // Verificar si excede el límite de 25 galletas
    if ($total_despues_agregar > 25) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'error' => 'Has excedido el límite de 25 galletas. Para pedidos mayores, por favor contáctanos por correo.',
                'limit_exceeded' => true
            ]);
            exit();
        } else {
            header('Location: ../views/index.php?error=Has excedido el límite de 25 galletas. Para pedidos mayores, por favor contáctanos por correo.');
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
