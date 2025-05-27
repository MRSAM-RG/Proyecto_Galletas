<?php
// file_put_contents(__DIR__ . '/../logs/prueba_inicio.log', date('Y-m-d H:i:s')." - Inicia procesar_compra.php\n", FILE_APPEND);
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';
session_start();
// file_put_contents(
//     __DIR__ . '/../logs/sesion_debug.log',
//     date('Y-m-d H:i:s') . " - usuario_id: " . (isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 'NO SET') .
//     " - METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n",
//     FILE_APPEND
// ); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../views/login.php?error=Debes iniciar sesión para comprar');
    exit();
}

$db = new MySQL();
$db->conectar();
$queryManager = new QueryManager($db);
$usuario_id = $_SESSION['usuario_id'];

// Función para logging
// function debug_log($message) {
//     $log_dir = __DIR__ . '/../logs';
//     $log_file = $log_dir . '/pedidos_debug.log';
//     
//     // Asegurarse de que el directorio existe
//     if (!file_exists($log_dir)) {
//         mkdir($log_dir, 0777, true);
//     }
//     
//     // Verificar si podemos escribir en el directorio
//     if (!is_writable($log_dir)) {
//         error_log("No se puede escribir en el directorio de logs: $log_dir");
//         return;
//     }
//     
//     $timestamp = date('Y-m-d H:i:s');
//     $log_message = "[$timestamp] $message\n";
//     
//     // Intentar escribir el log
//     if (file_put_contents($log_file, $log_message, FILE_APPEND) === false) {
//         error_log("No se pudo escribir en el archivo de log: $log_file");
//     }
// }

// Verificar si podemos escribir logs
// debug_log("=== INICIO DE NUEVO PROCESO DE PEDIDO ===");
// debug_log("Directorio actual: " . __DIR__);
// debug_log("Usuario ID: " . $usuario_id);

// Verificar conexión
if (!$db->conexion) {
    // debug_log("Error: No se pudo conectar a la base de datos");
    // error_log("Error de conexión a la base de datos: " . $db->conexion->connect_error);
    header('Location: ../views/carrito.php?error=Error de conexión a la base de datos');
    exit();
}

// debug_log("Conexión a la base de datos establecida correctamente");
// debug_log("Información de la base de datos: " . print_r($db->conexion->get_server_info(), true));

// Obtener productos del carrito
$result = $queryManager->getCartItems($usuario_id);
if (!$result) {
    // debug_log("Error: No se pudieron obtener los productos del carrito");
    // error_log("Error al obtener productos del carrito: " . $db->conexion->error);
    $db->desconectar();
    header('Location: ../views/carrito.php?error=Error al obtener los productos del carrito');
    exit();
}

$carrito = [];
while ($row = $result->fetch_assoc()) {
    $carrito[] = $row;
}

// debug_log("Productos en el carrito: " . count($carrito));
// debug_log("Contenido del carrito: " . print_r($carrito, true));

if (empty($carrito)) {
    // debug_log("Error: El carrito está vacío");
    $db->desconectar();
    header('Location: ../views/carrito.php?error=Tu carrito está vacío');
    exit();
}

$direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
if (strlen($direccion) < 5) {
    // debug_log("Error: Dirección inválida - longitud: " . strlen($direccion));
    $db->desconectar();
    header('Location: ../views/carrito.php?error=Dirección inválida');
    exit();
}

$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
if (!preg_match('/^[0-9]{10}$/', $telefono)) {
    // debug_log("Error: Teléfono inválido - valor: $telefono");
    $db->desconectar();
    header('Location: ../views/carrito.php?error=Número de teléfono inválido');
    exit();
}

// debug_log("Datos de envío validados - Dirección: $direccion, Teléfono: $telefono");

// Iniciar transacción
$db->iniciarTransaccion();
// debug_log("Transacción iniciada");

try {
    // Crear pedido
    // debug_log("Intentando crear pedido...");
    $pedido_id = $queryManager->createOrder($usuario_id, $direccion, $telefono);

    if (!$pedido_id) {
        // debug_log("Error: No se pudo crear el pedido");
        throw new Exception('Error al crear el pedido');
    }

    // debug_log("Pedido creado exitosamente con ID: $pedido_id");

    // Insertar detalles del pedido
    foreach ($carrito as $item) {
        // debug_log("Procesando item del carrito - Producto ID: {$item['producto_id']}, Cantidad: {$item['cantidad']}");
        
        // Verificar que el precio existe
        if (!isset($item['precio']) || $item['precio'] <= 0) {
            // debug_log("Error: Precio inválido para el producto ID: {$item['producto_id']}");
            throw new Exception('Error en el precio del producto');
        }
        
        if (!$queryManager->addOrderDetail(
            $pedido_id, 
            $item['producto_id'], 
            $item['cantidad'], 
            $item['precio'], 
            $item['tamano'], 
            $item['presentacion']
        )) {
            // debug_log("Error: No se pudo agregar el detalle del pedido para el producto ID: {$item['producto_id']}");
            throw new Exception('Error al agregar los detalles del pedido');
        }
        
        // debug_log("Detalle del pedido agregado exitosamente para el producto ID: {$item['producto_id']}");
    }

    // Vaciar carrito
    // debug_log("Intentando vaciar el carrito...");
    if (!$queryManager->clearCart($usuario_id)) {
        // debug_log("Error: No se pudo vaciar el carrito");
        throw new Exception('Error al vaciar el carrito');
    }
    // debug_log("Carrito vaciado exitosamente");

    // Confirmar transacción
    $db->confirmarTransaccion();
    // debug_log("Transacción confirmada exitosamente");
    
    $db->desconectar();
    // debug_log("Proceso de compra completado exitosamente");
    header('Location: ../views/carrito.php?success=¡Pedido realizado con éxito!&pedido_id=' . $pedido_id);
    exit();

} catch (Exception $e) {
    // Revertir transacción en caso de error
    // debug_log("Error en el proceso: " . $e->getMessage());
    $db->revertirTransaccion();
    // debug_log("Transacción revertida");
    $db->desconectar();
    header('Location: ../views/carrito.php?error=' . urlencode($e->getMessage()));
    exit();
} 