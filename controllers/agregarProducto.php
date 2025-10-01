<?php
require_once '../models/MySQL.php';
session_start();

// Validación de sesión y rol de administrador
if ( !isset( $_SESSION[ 'usuario_id' ] ) || $_SESSION[ 'rol' ] !== 'admin' ) {
    header( 'Location: ../login.php' );
    exit();
}

$db = new MySQL();
$db->conectar();

if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    require_once '../config/security.php';
    if ( !isset( $_POST[ 'csrf_token' ] ) || !validate_csrf_token( $_POST[ 'csrf_token' ] ) ) {
        header( 'Location: ../views/admin/agregarProducto.php?error=Token CSRF inválido' );
        exit();
    }

    $nombre = isset( $_POST[ 'nombre' ] ) ? trim( $_POST[ 'nombre' ] ) : '';
    $descripcion = isset( $_POST[ 'descripcion' ] ) ? trim( $_POST[ 'descripcion' ] ) : '';

    // Precios requeridos: paquete6, paquete9, paquete12
    $pa6  = isset( $_POST[ 'precios' ][ 'paquete6' ] )  ? floatval( $_POST[ 'precios' ][ 'paquete6' ] )  : 0;
    $pa9  = isset( $_POST[ 'precios' ][ 'paquete9' ] )  ? floatval( $_POST[ 'precios' ][ 'paquete9' ] )  : 0;
    $pa12 = isset( $_POST[ 'precios' ][ 'paquete12' ] ) ? floatval( $_POST[ 'precios' ][ 'paquete12' ] ) : 0;

    // Precio fijo para el paquete mixto ( global por convención )
    $precio_normal_paquete_mixto = 75000;

    // Validaciones mínimas
    if ( $pa6 <= 0 || $pa9 <= 0 || $pa12 <= 0 ) {
        header( 'Location: ../views/admin/agregarProducto.php?error=Los precios deben ser mayores a 0' );
        exit();
    }

    // Validar y procesar la imagen
    if ( !isset( $_FILES[ 'imagen' ] ) || $_FILES[ 'imagen' ][ 'error' ] !== UPLOAD_ERR_OK ) {
        header( 'Location: ../views/admin/agregarProducto.php?error=Debe seleccionar una imagen' );
        exit();
    }

    $imagen = $_FILES[ 'imagen' ];
    $tipo   = $imagen[ 'type' ];
    $tamano = $imagen[ 'size' ];
    $temp   = $imagen[ 'tmp_name' ];

    // Validar tipo de archivo
    if ( $tipo !== 'image/jpeg' && $tipo !== 'image/png' && $tipo !== 'image/webp' ) {
        header( 'Location: ../views/admin/agregarProducto.php?error=Tipo de archivo no permitido' );
        exit();
    }

    // Validar tamaño ( 5MB máximo )
    if ( $tamano > 5 * 1024 * 1024 ) {
        header( 'Location: ../views/admin/agregarProducto.php?error=La imagen es demasiado grande' );
        exit();
    }

    // Generar nombre único para la imagen
    $extension = pathinfo( $imagen[ 'name' ], PATHINFO_EXTENSION );
    $nombre_archivo = uniqid( '', true ) . '.' . $extension;

    // Insertar el producto
    $stmt = $db->conexion->prepare( 'INSERT INTO productos (nombre, descripcion, imagen) VALUES (?, ?, ?)' );
    $stmt->bind_param( 'sss', $nombre, $descripcion, $nombre_archivo );

    if ( !$stmt->execute() ) {
        $db->desconectar();
        header( 'Location: ../views/admin/agregarProducto.php?error=Error al crear el producto' );
        exit();
    }

    $producto_id = $db->conexion->insert_id;

    // Insertar precios 6/9/12
    $stmtPrecio = $db->conexion->prepare( "INSERT INTO precios_productos (producto_id, tamano, presentacion, precio) VALUES (?, 'normal', ?, ?)" );
    // Paquete 6
    $pres = 'paquete6';
    $stmtPrecio->bind_param( 'isd', $producto_id, $pres, $pa6 );
    $stmtPrecio->execute();
    // Paquete 9
    $pres = 'paquete9';
    $stmtPrecio->bind_param( 'isd', $producto_id, $pres, $pa9 );
    $stmtPrecio->execute();
    // Paquete 12
    $pres = 'paquete12';
    $stmtPrecio->bind_param( 'isd', $producto_id, $pres, $pa12 );
    $stmtPrecio->execute();

    // Insertar registro para mixto ( si manejas precio global, tener uno por producto no molesta y mantiene tu flujo )
    $stmtMixto = $db->conexion->prepare( "INSERT INTO precios_productos (producto_id, tamano, presentacion, precio) VALUES (?, 'normal', 'paquete_mixto', ?)" );
    $stmtMixto->bind_param( 'id', $producto_id, $precio_normal_paquete_mixto );
    $stmtMixto->execute();

    // Mover la imagen
    if ( move_uploaded_file( $temp, '../assets/img/' . $nombre_archivo ) ) {
        $db->desconectar();
        header( 'Location: ../views/admin/productos.php?success=Producto agregado correctamente' );
        exit();
    } else {
        // Si falla al mover la imagen, eliminar el producto creado
        $stmt = $db->conexion->prepare( 'DELETE FROM productos WHERE id = ?' );
        $stmt->bind_param( 'i', $producto_id );
        $stmt->execute();

        $db->desconectar();
        header( 'Location: ../views/admin/agregarProducto.php?error=Error al subir la imagen' );
        exit();
    }
}

$db->desconectar();
?>
