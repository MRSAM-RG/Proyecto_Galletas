<?php
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';
require_once '../config/security.php';

session_start();
setSecurityHeaders();

// Temporalmente habilitar visualización de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    try {
        // Validar y sanitizar entrada
        $producto_id = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
        $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1, 'max_range' => 99]]);
        $tamano = isset($_POST['tamano']) && in_array($_POST['tamano'], ['normal','jumbo']) ? $_POST['tamano'] : 'normal';
        $presentacion = isset($_POST['presentacion']) && in_array($_POST['presentacion'], ['unidad','paquete3']) ? $_POST['presentacion'] : 'unidad';

        if (!$producto_id) {
            // Considerar si esta validación debe devolver JSON para AJAX
            if ($isAjax) {
                 header('Content-Type: application/json');
                 echo json_encode(['success' => false, 'error' => 'Producto inválido recibido']);
                 exit();
            } else {
                 header('Location: ../views/index.php?error=Producto inválido');
                 exit();
            }
        }

        // Verificar si el producto existe y está disponible
        $producto = $queryManager->getProductById($producto_id);

        if (!$producto) {
             if ($isAjax) {
                 header('Content-Type: application/json');
                 echo json_encode(['success' => false, 'error' => 'Producto no encontrado o no disponible']);
                 exit();
             } else {
                 header('Location: ../views/index.php?error=Producto no disponible');
                 exit();
             }
        }

        // Obtener el stock disponible real
        $stock_disponible = $queryManager->getStockActual($producto_id, $tamano);

        // Obtener el precio específico según tamaño y presentación
        $stmt_precio = $db->conexion->prepare("SELECT precio FROM precios_productos WHERE producto_id = ? AND tamano = ? AND presentacion = ?");
        $stmt_precio->bind_param('iss', $producto_id, $tamano, $presentacion);
        $stmt_precio->execute();
        $result_precio = $stmt_precio->get_result();
        $precio_row = $result_precio->fetch_assoc();
        
        if (!$precio_row) {
             if ($isAjax) {
                 header('Content-Type: application/json');
                 echo json_encode(['success' => false, 'error' => 'Precio no disponible para esta combinación']);
                 exit();
             } else {
                 header('Location: ../views/index.php?error=Precio no disponible para esta combinación');
                 exit();
             }
        }

        // Calcular la cantidad real a verificar según la presentación
        $factor = ($presentacion === 'paquete3') ? 3 : 1;
        $cantidad_real = $cantidad * $factor;

        // Verificar stock suficiente (stock total disponible vs cantidad solicitada + cantidad ya en carrito)
        // Obtener todos los ítems del carrito para ese producto y tamaño para sumar las unidades existentes
        $usuario_id = $_SESSION['usuario_id'];
        $result_items_en_carrito = $db->conexion->prepare("SELECT cantidad, presentacion FROM carrito WHERE usuario_id = ? AND producto_id = ? AND tamano = ?");
        $result_items_en_carrito->bind_param('iis', $usuario_id, $producto_id, $tamano);
        $result_items_en_carrito->execute();
        $items_en_carrito = $result_items_en_carrito->get_result();
        
        $total_unidades_ya_en_carrito = 0;
        while ($row_carrito = $items_en_carrito->fetch_assoc()) {
            $row_factor_carrito = ($row_carrito['presentacion'] === 'paquete3') ? 3 : 1;
            $total_unidades_ya_en_carrito += $row_carrito['cantidad'] * $row_factor_carrito;
        }
        
        $total_unidades_despues = $total_unidades_ya_en_carrito + $cantidad_real;

        // Validar stock suficiente considerando el carrito actual
        if ($total_unidades_despues > $stock_disponible) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'No hay suficiente stock disponible para la cantidad total solicitada, incluyendo lo que ya tienes en el carrito.']);
                exit();
            } else {
                header('Location: ../views/index.php?stock_error=1');
                exit();
            }
        }

        // --- Lógica para añadir o actualizar el carrito --- 
        // Verificar si ya existe un ítem EXACTO en el carrito (mismo tamaño Y misma presentación)
        $carrito_item_exacto = $queryManager->getCartItem($usuario_id, $producto_id, $tamano, $presentacion);
        
        if ($carrito_item_exacto) {
            // Si existe un ítem exacto, actualizar su cantidad
            $nueva_cantidad_total_carrito = intval($carrito_item_exacto['cantidad']) + $cantidad;
            if ($queryManager->updateCartItem($carrito_item_exacto['id'], $nueva_cantidad_total_carrito)) {
                 if ($isAjax) {
                     header('Content-Type: application/json');
                     echo json_encode(['success' => true, 'message' => 'Cantidad del producto actualizada en el carrito.']);
                     exit();
                 } else {
                     header('Location: ../views/index.php?added=1');
                     exit();
                 }
            } else {
                 if ($isAjax) {
                     header('Content-Type: application/json');
                     echo json_encode(['success' => false, 'error' => 'Error al actualizar la cantidad del producto en el carrito.']);
                     exit();
                 } else {
                     header('Location: ../views/index.php?error=Error al actualizar el carrito');
                     exit();
                 }
            }
        } else {
            // Si no existe un ítem exacto, añadir uno nuevo
            if ($queryManager->addToCart($usuario_id, $producto_id, $cantidad, $tamano, $presentacion)) {
                 if ($isAjax) {
                     header('Content-Type: application/json');
                     echo json_encode(['success' => true, 'message' => 'Producto agregado al carrito.']);
                     exit();
                 } else {
                     header('Location: ../views/index.php?added=1');
                     exit();
                 }
            } else {
                 if ($isAjax) {
                     header('Content-Type: application/json');
                     echo json_encode(['success' => false, 'error' => 'Error al agregar el producto al carrito.']);
                     exit();
                 } else {
                     header('Location: ../views/index.php?error=Error al agregar al carrito');
                     exit();
                 }
            }
        }

    } catch (Exception $e) {
        // Capturar cualquier otra excepción no manejada
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error interno del servidor: ' . $e->getMessage()]);
            exit();
        } else {
            header('Location: ../views/index.php?error=Error inesperado: ' . urlencode($e->getMessage()));
            exit();
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
