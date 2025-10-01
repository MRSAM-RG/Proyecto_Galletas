<?php
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';
require_once '../config/security.php';

session_start();
setSecurityHeaders();

// Debe estar logueado
if ( !isset( $_SESSION[ 'usuario_id' ] ) ) {
    header( 'Location: ../views/login.php' );
    exit();
}

$db = new MySQL();
$db->conectar();
$queryManager = new QueryManager( $db );

$isAjax = !empty( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] )
&& strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) === 'xmlhttprequest';

if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {

    // -------- Entrada --------
    $producto_id  = filter_input( INPUT_POST, 'producto_id', FILTER_VALIDATE_INT );
    $cantidad     = filter_input(
        INPUT_POST,
        'cantidad',
        FILTER_VALIDATE_INT,
        [ 'options' => [ 'default' => 1, 'min_range' => 1, 'max_range' => 99 ] ]
    );
    $tamano       = 'normal';

    // normaliza presentacion ( trim + lower ) y fuerza un valor válido
    $presentacion = isset( $_POST[ 'presentacion' ] ) ? strtolower( trim( $_POST[ 'presentacion' ] ) ) : 'paquete6';
    $permitidas   = [ 'paquete6', 'paquete9', 'paquete12', 'paquete_mixto' ];
    if ( !$producto_id || !in_array( $presentacion, $permitidas, true ) ) {
        $msg = 'Producto o presentación inválidos';
        if ( $isAjax ) {
            header( 'Content-Type: application/json' );
            echo json_encode( [ 'success'=>false, 'error'=>$msg ] );
        } else {
            header( 'Location: ../views/index.php?error=' . urlencode( $msg ) );
        }
        $db->desconectar();
        exit();
    }

    // -------- Producto existe --------
    $producto = $queryManager->getProductById( $producto_id );
    if ( !$producto ) {
        $msg = 'Producto no disponible';
        if ( $isAjax ) {
            header( 'Content-Type: application/json' );
            echo json_encode( [ 'success'=>false, 'error'=>$msg ] );
        } else {
            header( 'Location: ../views/index.php?error=' . urlencode( $msg ) );
        }
        $db->desconectar();
        exit();
    }

    // -------- Precio por PAQUETE --------
    if ( $presentacion === 'paquete_mixto' ) {
        // precio global del mixto
        $stmt = $db->conexion->prepare( "
            SELECT precio
              FROM precios_productos
             WHERE LOWER(TRIM(presentacion)) = 'paquete_mixto'
             LIMIT 1
        " );
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $precio = $row ? ( float )$row[ 'precio' ] : 75000;
        // fallback
    } else {
        // precio específico por producto/tamaño/presentación
        $stmt = $db->conexion->prepare( "
            SELECT precio
              FROM precios_productos
             WHERE producto_id  = ?
               AND LOWER(TRIM(tamano)) = LOWER(TRIM(?))
               AND LOWER(TRIM(presentacion)) = LOWER(TRIM(?))
             LIMIT 1
        " );
        $stmt->bind_param( 'iss', $producto_id, $tamano, $presentacion );
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ( !$row ) {
            $msg = 'Precio no disponible para esta combinación';
            if ( $isAjax ) {
                header( 'Content-Type: application/json' );
                echo json_encode( [ 'success'=>false, 'error'=>$msg ] );
            } else {
                header( 'Location: ../views/index.php?error=' . urlencode( $msg ) );
            }
            $db->desconectar();
            exit();
        }
        $precio = ( float )$row[ 'precio' ];
    }

    // -------- Límite de 40 unidades --------
    $usuario_id = ( int )$_SESSION[ 'usuario_id' ];
    $factor = [ 'paquete6'=>6, 'paquete9'=>9, 'paquete12'=>12, 'paquete_mixto'=>3 ];
    $unidades_a_agregar = $factor[ $presentacion ] * $cantidad;

    $total_actual = $queryManager->getCartCount( $usuario_id );
    // retorna # de galletas ( no paquetes )
    if ( ( $total_actual + $unidades_a_agregar ) > 40 ) {
        $msg = 'Has excedido el límite de 40 galletas. Para pedidos mayores, contáctanos por correo.';
        if ( $isAjax ) {
            header( 'Content-Type: application/json' );
            echo json_encode( [ 'success'=>false, 'error'=>$msg, 'limit_exceeded'=>true ] );
        } else {
            header( 'Location: ../views/index.php?error=' . urlencode( $msg ) );
        }
        $db->desconectar();
        exit();
    }

    // -------- Insertar / Actualizar carrito --------
    $carrito_item = $queryManager->getCartItem( $usuario_id, $producto_id, $tamano, $presentacion );

    if ( $carrito_item ) {
        $nueva_cantidad = ( int )$carrito_item[ 'cantidad' ] + $cantidad;
        $ok = $queryManager->updateCartItem( ( int )$carrito_item[ 'id' ], $nueva_cantidad );
    } else {
        $ok = $queryManager->addToCart( $usuario_id, $producto_id, $cantidad, $tamano, $presentacion );
    }

    // -------- Respuesta --------
    if ( $isAjax ) {
        header( 'Content-Type: application/json' );
        echo json_encode( [ 'success' => ( bool )$ok ] );
    } else {
        header( 'Location: ../views/index.php?added=1' );
    }

    $db->desconectar();
    exit();
}

// Método no permitido
if ( $isAjax ) {
    header( 'Content-Type: application/json' );
    echo json_encode( [ 'success'=>false, 'error'=>'Método no permitido' ] );
} else {
    header( 'Location: ../views/index.php?error=Método no permitido' );
}
$db->desconectar();
exit();
