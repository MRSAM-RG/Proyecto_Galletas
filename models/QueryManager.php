<?php

class QueryManager {
    private $db;

    public function __construct(MySQL $db) {
        $this->db = $db;
    }

    // ====== USUARIOS ======
    public function getUserByEmail($email) {
        $stmt = $this->db->conexion->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function createUser($nombre, $email, $passwordHash) {
        $stmt = $this->db->conexion->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $email, $passwordHash);
        return $stmt->execute();
    }

    public function checkEmailExists($email) {
        $stmt = $this->db->conexion->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return mysqli_num_rows($result) > 0;
    }

    // ====== PRODUCTOS ======
    public function getAllProducts() {
        $stmt = $this->db->conexion->prepare("SELECT * FROM productos WHERE estado = 'activo' ORDER BY id DESC");
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getProductById($id) {
        $stmt = $this->db->conexion->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function createProduct($nombre, $descripcion, $precio, $imagen) {
        $stmt = $this->db->conexion->prepare("INSERT INTO productos (nombre, descripcion, precio, imagen, estado) VALUES (?, ?, ?, ?, 'activo')");
        $stmt->bind_param("ssds", $nombre, $descripcion, $precio, $imagen);
        return $stmt->execute();
    }

    public function updateProduct($id, $nombre, $descripcion, $precio, $imagen = null) {
        if ($imagen) {
            $stmt = $this->db->conexion->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, imagen = ? WHERE id = ?");
            $stmt->bind_param("ssdsi", $nombre, $descripcion, $precio, $imagen, $id);
        } else {
            $stmt = $this->db->conexion->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ? WHERE id = ?");
            $stmt->bind_param("ssdi", $nombre, $descripcion, $precio, $id);
        }
        return $stmt->execute();
    }

    public function deleteProduct($id) {
        // En lugar de eliminar, actualizamos el estado a inactivo
        $stmt = $this->db->conexion->prepare("UPDATE productos SET estado = 'inactivo' WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function reactivateProduct($id) {
        // Método para reactivar un producto
        $stmt = $this->db->conexion->prepare("UPDATE productos SET estado = 'activo' WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getAllProductsIncludingInactive() {
        // Método para obtener todos los productos, incluyendo los inactivos
        $stmt = $this->db->conexion->prepare("SELECT * FROM productos ORDER BY id DESC");
        $stmt->execute();
        return $stmt->get_result();
    }

    // ====== CARRITO ======
    public function getCartItems($usuario_id, $producto_id = null) {
        if ($producto_id === null) {
            $stmt = $this->db->conexion->prepare("
                SELECT carrito.*, productos.nombre, productos.imagen, pp.precio
                FROM carrito
                JOIN productos ON carrito.producto_id = productos.id 
                JOIN precios_productos pp ON pp.producto_id = carrito.producto_id AND pp.tamano = carrito.tamano AND pp.presentacion = carrito.presentacion
                WHERE carrito.usuario_id = ?;
            ");
            $stmt->bind_param("i", $usuario_id);
        } else {
            $stmt = $this->db->conexion->prepare("
                SELECT carrito.*, productos.nombre, productos.imagen, pp.precio
                FROM carrito
                JOIN productos ON carrito.producto_id = productos.id 
                JOIN precios_productos pp ON pp.producto_id = carrito.producto_id AND pp.tamano = carrito.tamano AND pp.presentacion = carrito.presentacion
                WHERE carrito.usuario_id = ? AND carrito.producto_id = ?;
            ");
            $stmt->bind_param("ii", $usuario_id, $producto_id);
        }
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getCartItem($usuario_id, $producto_id, $tamano, $presentacion) {
        $stmt = $this->db->conexion->prepare("SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ? AND tamano = ? AND presentacion = ?");
        $stmt->bind_param("iiss", $usuario_id, $producto_id, $tamano, $presentacion);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function addToCart($usuario_id, $producto_id, $cantidad, $tamano, $presentacion) {
        $stmt = $this->db->conexion->prepare("INSERT INTO carrito (usuario_id, producto_id, cantidad, tamano, presentacion) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $usuario_id, $producto_id, $cantidad, $tamano, $presentacion);
        return $stmt->execute();
    }

    public function updateCartItem($id, $cantidad) {
        $stmt = $this->db->conexion->prepare("UPDATE carrito SET cantidad = ? WHERE id = ?");
        $stmt->bind_param("ii", $cantidad, $id);
        return $stmt->execute();
    }

    public function deleteCartItem($id, $usuario_id) {
        $stmt = $this->db->conexion->prepare("DELETE FROM carrito WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $id, $usuario_id);
        return $stmt->execute();
    }

    public function clearCart($usuario_id) {
        $stmt = $this->db->conexion->prepare("DELETE FROM carrito WHERE usuario_id = ?");
        $stmt->bind_param("i", $usuario_id);
        return $stmt->execute();
    }

    public function getCartCount($usuario_id) {
        $stmt = $this->db->conexion->prepare("SELECT SUM(cantidad) as total FROM carrito WHERE usuario_id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return intval($result['total'] ?? 0);
    }

    // ====== PEDIDOS ======
    public function createOrder($usuario_id, $direccion, $telefono) {
        $stmt = $this->db->conexion->prepare("
            INSERT INTO pedidos (usuario_id, direccion, telefono, estado, fecha)
            VALUES (?, ?, ?, 'pendiente', NOW())
        ");
        
        if (!$stmt) {
            // debug_log("Error al preparar la consulta de creación de pedido: " . $this->db->conexion->error);
            return false;
        }
        
        $stmt->bind_param('iss', $usuario_id, $direccion, $telefono);
        
        if (!$stmt->execute()) {
            // debug_log("Error al crear el pedido: " . $stmt->error);
            return false;
        }
        
        $pedido_id = $this->db->conexion->insert_id;
        // debug_log("Pedido creado con ID: " . $pedido_id);
        return $pedido_id;
    }

    public function addOrderDetail($pedido_id, $producto_id, $cantidad, $precio, $tamano, $presentacion) {
        // Insertar el detalle del pedido
        $stmt = $this->db->conexion->prepare("
            INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario, tamano, presentacion)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            // debug_log("Error al preparar la inserción del detalle: " . $this->db->conexion->error);
            return false;
        }
        
        $stmt->bind_param('iiidss', $pedido_id, $producto_id, $cantidad, $precio, $tamano, $presentacion);
        
        if (!$stmt->execute()) {
            // debug_log("Error al insertar el detalle del pedido: " . $stmt->error);
            return false;
        }
        
        // debug_log("Detalle del pedido agregado exitosamente para el pedido ID: $pedido_id");
        return true;
    }

    public function getOrderById($id) {
        $stmt = $this->db->conexion->prepare("SELECT * FROM pedidos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getUserOrders($usuario_id) {
        $stmt = $this->db->conexion->prepare("
            SELECT p.*, COUNT(dp.id) as total_items 
            FROM pedidos p 
            LEFT JOIN detalle_pedido dp ON p.id = dp.pedido_id 
            WHERE p.usuario_id = ? 
            GROUP BY p.id 
            ORDER BY p.fecha DESC
        ");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getOrderDetails($pedido_id) {
        $stmt = $this->db->conexion->prepare("
            SELECT dp.*, p.nombre as producto_nombre 
            FROM detalle_pedido dp 
            JOIN productos p ON dp.producto_id = p.id 
            WHERE dp.pedido_id = ?
        ");
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function updateOrderStatus($pedido_id, $estado) {
        $stmt = $this->db->conexion->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $estado, $pedido_id);
        return $stmt->execute();
    }

    public function getAllOrders($estado = null, $limit = null, $offset = null) {
        $sql = "SELECT p.*, COALESCE(u.nombre, 'Usuario Eliminado') AS usuario FROM pedidos p LEFT JOIN usuarios u ON p.usuario_id = u.id";
        $params = [];
        $types = '';

        if ($estado && $estado !== 'todos') {
            $sql .= " WHERE p.estado = ?";
            $params[] = $estado;
            $types .= 's';
        }

        $sql .= " ORDER BY p.fecha DESC";

        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii'; // Assuming limit and offset are integers
        }

        $stmt = $this->db->conexion->prepare($sql);
        if (!$stmt) {
            error_log('Error al preparar la consulta de pedidos: ' . $this->db->conexion->error);
            return false;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            error_log('Error al ejecutar la consulta de pedidos: ' . $stmt->error);
            return false;
        }
        
        return $stmt->get_result();
    }

    public function getAllOrderDetails() {
        $stmt = $this->db->conexion->prepare("
            SELECT d.pedido_id, pr.nombre AS producto, d.cantidad, d.precio_unitario AS precio, d.tamano, d.presentacion 
            FROM detalle_pedido d 
            JOIN productos pr ON d.producto_id = pr.id
        ");
        if (!$stmt) {
            error_log('Error al preparar la consulta de detalles de pedidos: ' . $this->db->conexion->error);
            return false;
        }
        
        if (!$stmt->execute()) {
            error_log('Error al ejecutar la consulta de detalles de pedidos: ' . $stmt->error);
            return false;
        }
        
        return $stmt->get_result();
    }

    public function getTotalOrdersCount($estado = null) {
        $sql = "SELECT COUNT(*) as total FROM pedidos";
        $params = [];
        $types = '';

        if ($estado && $estado !== 'todos') {
            $sql .= " WHERE estado = ?";
            $params[] = $estado;
            $types .= 's';
        }

        $stmt = $this->db->conexion->prepare($sql);
        if (!$stmt) {
            error_log('Error al preparar la consulta de conteo de pedidos: ' . $this->db->conexion->error);
            return false;
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            error_log('Error al ejecutar la consulta de conteo de pedidos: ' . $stmt->error);
            return false;
        }

        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }

    // Obtener precios por tamaño y presentación
    public function getProductPrices($producto_id) {
        $stmt = $this->db->conexion->prepare("SELECT tamano, presentacion, precio FROM precios_productos WHERE producto_id = ?");
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $precios = [];
        while ($row = $result->fetch_assoc()) {
            $precios[$row['tamano']][$row['presentacion']] = $row['precio'];
        }
        return $precios;
    }
} 