<?php

class QueryManager
 {
    /** @var MySQL */
    private $db;

    public function __construct( MySQL $db )
 {
        $this->db = $db;
    }

    /* ===  ===  ===  ===  ===  ===  ===  ===  =
    * USUARIOS
    * ===  ===  ===  ===  ===  ===  ===  ===  = */

    public function getUserByEmail( $email )
 {
        $stmt = $this->db->conexion->prepare( 'SELECT * FROM usuarios WHERE email = ?' );
        $stmt->bind_param( 's', $email );
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function createUser( $nombre, $email, $passwordHash )
 {
        $stmt = $this->db->conexion->prepare( 'INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)' );
        $stmt->bind_param( 'sss', $nombre, $email, $passwordHash );
        return $stmt->execute();
    }

    public function checkEmailExists( $email )
 {
        $stmt = $this->db->conexion->prepare( 'SELECT 1 FROM usuarios WHERE email = ? LIMIT 1' );
        $stmt->bind_param( 's', $email );
        $stmt->execute();
        $res = $stmt->get_result();
        return $res && $res->num_rows > 0;
    }

    /* ===  ===  ===  ===  ===  ===  ===  ===  =
    * PRODUCTOS
    * ===  ===  ===  ===  ===  ===  ===  ===  = */

    public function getAllProducts()
 {
        $stmt = $this->db->conexion->prepare( "SELECT id, nombre, descripcion, imagen, estado
                                              FROM productos
                                              WHERE estado = 'activo'
                                              ORDER BY id ASC" );
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getAllProductsIncludingInactive()
 {
        $stmt = $this->db->conexion->prepare( "SELECT id, nombre, descripcion, imagen, estado
                                              FROM productos
                                              ORDER BY id ASC" );
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getProductById( $id )
 {
        $stmt = $this->db->conexion->prepare( 'SELECT id, nombre, descripcion, imagen, estado FROM productos WHERE id = ? LIMIT 1' );
        $stmt->bind_param( 'i', $id );
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
    * Devuelve un mapa: $precios[ tamano ][ presentacion ] = precio
    */

    public function getProductPrices( $producto_id )
 {
        $stmt = $this->db->conexion->prepare( 'SELECT tamano, presentacion, precio FROM precios_productos WHERE producto_id = ?' );
        $stmt->bind_param( 'i', $producto_id );
        $stmt->execute();
        $rs = $stmt->get_result();

        $precios = [];
        while ( $row = $rs->fetch_assoc() ) {
            $precios[ $row[ 'tamano' ] ][ $row[ 'presentacion' ] ] = ( float )$row[ 'precio' ];
        }
        return $precios;
    }

    /* ===  ===  ===  ===  ===  ===  ===  ===  =
    * CARRITO
    * ===  ===  ===  ===  ===  ===  ===  ===  = */

    public function getCartItem( $usuario_id, $producto_id, $tamano, $presentacion )
 {
        $sql = 'SELECT id, usuario_id, producto_id, cantidad, tamano, presentacion
                FROM carrito
                WHERE usuario_id = ? AND producto_id = ? AND tamano = ? AND presentacion = ?
                LIMIT 1';
        $stmt = $this->db->conexion->prepare( $sql );
        $stmt->bind_param( 'iiss', $usuario_id, $producto_id, $tamano, $presentacion );
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc() ?: null;
    }

    public function addToCart( $usuario_id, $producto_id, $cantidad, $tamano, $presentacion )
 {
        $sql = 'INSERT INTO carrito (usuario_id, producto_id, cantidad, tamano, presentacion)
                VALUES (?, ?, ?, ?, ?)';
        $stmt = $this->db->conexion->prepare( $sql );
        $stmt->bind_param( 'iiiss', $usuario_id, $producto_id, $cantidad, $tamano, $presentacion );
        return $stmt->execute();
    }

    public function updateCartItem( $carrito_id, $nueva_cantidad )
 {
        $sql = 'UPDATE carrito SET cantidad = ? WHERE id = ?';
        $stmt = $this->db->conexion->prepare( $sql );
        $stmt->bind_param( 'ii', $nueva_cantidad, $carrito_id );
        return $stmt->execute();
    }

    public function clearCart( $usuario_id )
 {
        $stmt = $this->db->conexion->prepare( 'DELETE FROM carrito WHERE usuario_id = ?' );
        $stmt->bind_param( 'i', $usuario_id );
        return $stmt->execute();
    }
    
    public function getCartItems(int $usuario_id) {
    $sql = "
        SELECT
            c.id, c.usuario_id, c.producto_id, c.cantidad, c.tamano, c.presentacion,
            p.nombre, p.descripcion, p.imagen,
            CASE
                WHEN LOWER(TRIM(c.presentacion)) = 'paquete_mixto' THEN
                    (SELECT precio
                       FROM precios_productos
                      WHERE LOWER(TRIM(presentacion)) = 'paquete_mixto'
                      LIMIT 1)
                ELSE pp.precio
            END AS precio
        FROM carrito c
        INNER JOIN productos p
                ON p.id = c.producto_id
        LEFT JOIN precios_productos pp
               ON pp.producto_id = c.producto_id
              AND LOWER(TRIM(pp.tamano)) = LOWER(TRIM(c.tamano))
              AND LOWER(TRIM(pp.presentacion)) = LOWER(TRIM(c.presentacion))
        WHERE c.usuario_id = ?
    ";
    $stmt = $this->db->conexion->prepare($sql);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    return $stmt->get_result();
}

    /**
    * Cantidad total de galletas ( no paquetes ) para badge / validación límite.
    */

    public function getCartCount( $usuario_id )
 {
        $sql = "
        SELECT COALESCE(SUM(
            c.cantidad * CASE LOWER(TRIM(c.presentacion))
                WHEN 'paquete6'      THEN 6
                WHEN 'paquete9'      THEN 9
                WHEN 'paquete12'     THEN 12
                WHEN 'paquete_mixto' THEN 3
                ELSE 1
            END
        ), 0) AS total
        FROM carrito c
        WHERE c.usuario_id = ?";
        $stmt = $this->db->conexion->prepare( $sql );
        $stmt->bind_param( 'i', $usuario_id );
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return ( int )$row[ 'total' ];
    }

    /* ===  ===  ===  ===  ===  ===  ===  ===  =
    * PEDIDOS
    * ===  ===  ===  ===  ===  ===  ===  ===  = */

    public function createOrder( $usuario_id, $direccion, $telefono )
 {
        $stmt = $this->db->conexion->prepare( "
            INSERT INTO pedidos (usuario_id, direccion, telefono, estado, fecha)
            VALUES (?, ?, ?, 'pendiente', NOW())
        " );
        if ( !$stmt ) return false;

        $stmt->bind_param( 'iss', $usuario_id, $direccion, $telefono );
        if ( !$stmt->execute() ) return false;

        return $this->db->conexion->insert_id;
    }

    /**
    * Inserta el detalle con columna 'precio' ( como usan tus controladores ).
    */

    public function addOrderDetail( $pedido_id, $producto_id, $cantidad, $precio, $tamano, $presentacion )
 {
        $stmt = $this->db->conexion->prepare( "
            INSERT INTO detalle_pedido
                (pedido_id, producto_id, cantidad, precio_unitario, tamano, presentacion)
            VALUES (?, ?, ?, ?, ?, ?)
        " );
        if ( !$stmt ) return false;

        $stmt->bind_param( 'iiidss', $pedido_id, $producto_id, $cantidad, $precio, $tamano, $presentacion );
        return $stmt->execute();
    }

    public function getOrderById( $id )
 {
        $stmt = $this->db->conexion->prepare( 'SELECT * FROM pedidos WHERE id = ?' );
        $stmt->bind_param( 'i', $id );
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getUserOrders( $usuario_id )
 {
        $stmt = $this->db->conexion->prepare( "
            SELECT p.*, COUNT(dp.id) AS total_items
            FROM pedidos p
            LEFT JOIN detalle_pedido dp ON p.id = dp.pedido_id
            WHERE p.usuario_id = ?
            GROUP BY p.id
            ORDER BY p.fecha DESC
        " );
        $stmt->bind_param( 'i', $usuario_id );
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getOrderDetails( $pedido_id )
 {
        $stmt = $this->db->conexion->prepare( "
            SELECT dp.*, pr.nombre AS producto_nombre
            FROM detalle_pedido dp
            JOIN productos pr ON dp.producto_id = pr.id
            WHERE dp.pedido_id = ?
        " );
        $stmt->bind_param( 'i', $pedido_id );
        $stmt->execute();
        return $stmt->get_result();
    }

    public function updateOrderStatus( $pedido_id, $estado )
 {
        $stmt = $this->db->conexion->prepare( 'UPDATE pedidos SET estado = ? WHERE id = ?' );
        $stmt->bind_param( 'si', $estado, $pedido_id );
        return $stmt->execute();
    }

    public function getAllOrders( $estado = null, $limit = null, $offset = null )
 {
        $sql = "SELECT p.*, COALESCE(u.nombre, 'Usuario Eliminado') AS usuario
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id";
        $params = [];
        $types  = '';

        if ( $estado && $estado !== 'todos' ) {
            $sql .= ' WHERE p.estado = ?';
            $params[] = $estado;
            $types   .= 's';
        }

        $sql .= ' ORDER BY p.fecha DESC';

        if ( $limit !== null && $offset !== null ) {
            $sql .= ' LIMIT ? OFFSET ?';
            $params[] = $limit;
            $params[] = $offset;
            $types   .= 'ii';
        }

        $stmt = $this->db->conexion->prepare( $sql );
        if ( !$stmt ) return false;

        if ( !empty( $params ) ) {
            $stmt->bind_param( $types, ...$params );
        }

        if ( !$stmt->execute() ) return false;

        return $stmt->get_result();
    }

    public function getAllOrderDetails()
 {
        $stmt = $this->db->conexion->prepare( "
            SELECT d.pedido_id,
                   pr.nombre AS producto,
                   d.cantidad,
                   d.precio_unitario,
                   d.tamano,
                   d.presentacion
            FROM detalle_pedido d
            JOIN productos pr ON d.producto_id = pr.id
        " );
        if ( !$stmt ) return false;
        if ( !$stmt->execute() ) return false;
        return $stmt->get_result();
    }

    public function getTotalOrdersCount( $estado = null )
 {
        $sql = 'SELECT COUNT(*) AS total FROM pedidos';
        $params = [];
        $types  = '';

        if ( $estado && $estado !== 'todos' ) {
            $sql .= ' WHERE estado = ?';
            $params[] = $estado;
            $types   .= 's';
        }

        $stmt = $this->db->conexion->prepare( $sql );
        if ( !$stmt ) return false;

        if ( !empty( $params ) ) {
            $stmt->bind_param( $types, ...$params );
        }

        if ( !$stmt->execute() ) return false;

        $row = $stmt->get_result()->fetch_assoc();
        return ( int )$row[ 'total' ];
    }

    public function deleteCartItem(int $carrito_id, int $usuario_id) {
    $sql = "DELETE FROM carrito WHERE id = ? AND usuario_id = ?";
    $stmt = $this->db->conexion->prepare($sql);
    $stmt->bind_param('ii', $carrito_id, $usuario_id);
    return $stmt->execute();
}
}
