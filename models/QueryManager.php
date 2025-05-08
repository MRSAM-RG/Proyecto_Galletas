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
        $stmt = $this->db->conexion->prepare("SELECT * FROM productos ORDER BY id DESC");
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
        $stmt = $this->db->conexion->prepare("INSERT INTO productos (nombre, descripcion, precio, imagen) VALUES (?, ?, ?, ?)");
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
        // Eliminar primero del carrito
        $stmt1 = $this->db->conexion->prepare("DELETE FROM carrito WHERE producto_id = ?");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();

        // Eliminar de detalle_pedido
        $stmt2 = $this->db->conexion->prepare("DELETE FROM detalle_pedido WHERE producto_id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();

        // Ahora sí eliminar el producto
        $stmt3 = $this->db->conexion->prepare("DELETE FROM productos WHERE id = ?");
        $stmt3->bind_param("i", $id);
        return $stmt3->execute();
    }

    // ====== CARRITO ======
    public function getCartItems($usuario_id, $producto_id = null) {
        if ($producto_id === null) {
            $stmt = $this->db->conexion->prepare("
                SELECT carrito.*, productos.nombre, productos.precio, productos.imagen 
                FROM carrito
                JOIN productos ON carrito.producto_id = productos.id 
                WHERE carrito.usuario_id = ?;
            ");
            $stmt->bind_param("i", $usuario_id);
        } else {
            $stmt = $this->db->conexion->prepare("
                SELECT carrito.*, productos.nombre, productos.precio, productos.imagen 
                FROM carrito
                JOIN productos ON carrito.producto_id = productos.id 
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
        if (!$stmt->execute()) {
            die("Error al insertar en carrito: " . $stmt->error);
        }
        return true;
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
        $stmt = $this->db->conexion->prepare("INSERT INTO pedidos (usuario_id, fecha, estado, direccion, telefono) VALUES (?, NOW(), 'pendiente', ?, ?)");
        $stmt->bind_param("iss", $usuario_id, $direccion, $telefono);
        $stmt->execute();
        return $this->db->conexion->insert_id;
    }

    public function addOrderDetail($pedido_id, $producto_id, $cantidad, $precio, $tamano, $presentacion) {
        $stmt = $this->db->conexion->prepare("INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio, tamano, presentacion) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidss", $pedido_id, $producto_id, $cantidad, $precio, $tamano, $presentacion);
        return $stmt->execute();
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

    public function getAllOrders($estado = null) {
        if ($estado && $estado !== 'todos') {
            $stmt = $this->db->conexion->prepare("
                SELECT p.*, u.nombre AS usuario 
                FROM pedidos p 
                JOIN usuarios u ON p.usuario_id = u.id 
                WHERE p.estado = ? 
                ORDER BY p.fecha DESC
            ");
            $stmt->bind_param("s", $estado);
        } else {
            $stmt = $this->db->conexion->prepare("
                SELECT p.*, u.nombre AS usuario 
                FROM pedidos p 
                JOIN usuarios u ON p.usuario_id = u.id 
                ORDER BY p.fecha DESC
            ");
        }
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getAllOrderDetails() {
        $stmt = $this->db->conexion->prepare("
            SELECT d.pedido_id, pr.nombre AS producto, d.cantidad, d.precio, d.tamano, d.presentacion 
            FROM detalle_pedido d 
            JOIN productos pr ON d.producto_id = pr.id
        ");
        $stmt->execute();
        return $stmt->get_result();
    }
} 