<?php
require_once '../models/MySQL.php';
require_once '../config/security.php';
session_start();

if ( !isset( $_SESSION[ 'usuario_id' ] ) || $_SESSION[ 'rol' ] !== 'admin' ) {
    header( 'Location: ../views/index.php' );
    exit();
}

function toFloat( $val ) {
    if ( is_numeric( $val ) ) {
        return ( float )$val;
    }
    $s = trim( ( string )$val );
    // quita símbolos comunes
    $s = str_replace( array( '$', ' ', '\xc2\xa0' ), '', $s );
    $hasComma = strpos( $s, ',' ) !== false;
    $hasDot   = strpos( $s, '.' ) !== false;

    if ( $hasComma && $hasDot ) {
        // es-CO: . miles, , decimal
        $s = str_replace( '.', '', $s );
        $s = str_replace( ',', '.', $s );
    } else {
        // solo coma -> decimal
        $s = str_replace( ',', '.', $s );
    }
    return is_numeric( $s ) ? ( float )$s : 0.0;
}

$db = new MySQL();
$db->conectar();

if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    if ( !isset( $_POST[ 'csrf_token' ] ) || !validate_csrf_token( $_POST[ 'csrf_token' ] ) ) {
        $db->desconectar();
        $idForUrl = isset( $_POST[ 'id' ] ) ? intval( $_POST[ 'id' ] ) : 0;
        header( 'Location: ../views/admin/editarProducto.php?id=' . $idForUrl . '&error=Token CSRF inválido' );
        exit();
    }

    // --- Datos base ---
    $id          = isset( $_POST[ 'id' ] ) ? intval( $_POST[ 'id' ] ) : 0;
    $nombre      = isset( $_POST[ 'nombre' ] ) ? trim( $_POST[ 'nombre' ] ) : '';
    $descripcion = isset( $_POST[ 'descripcion' ] ) ? trim( $_POST[ 'descripcion' ] ) : '';

    if ( $id <= 0 ) {
        $db->desconectar();
        header( 'Location: ../views/admin/admin.php?error=ID inválido' );
        exit();
    }

    // --- Precios 6/9/12 ---
    $pa6  = isset( $_POST[ 'precios' ][ 'paquete6' ] )  ? toFloat( $_POST[ 'precios' ][ 'paquete6' ] )  : 0;
    $pa9  = isset( $_POST[ 'precios' ][ 'paquete9' ] )  ? toFloat( $_POST[ 'precios' ][ 'paquete9' ] )  : 0;
    $pa12 = isset( $_POST[ 'precios' ][ 'paquete12' ] ) ? toFloat( $_POST[ 'precios' ][ 'paquete12' ] ) : 0;

    if ( $pa6 <= 0 || $pa9 <= 0 || $pa12 <= 0 ) {
        $db->desconectar();
        header( 'Location: ../views/admin/editarProducto.php?id=' . $id . '&error=Los precios deben ser mayores a 0' );
        exit();
    }

    // --- Actualiza datos del producto ---
    $stmt = $db->conexion->prepare( 'UPDATE productos SET nombre = ?, descripcion = ? WHERE id = ?' );
    $stmt->bind_param( 'ssi', $nombre, $descripcion, $id );
    if ( !$stmt->execute() ) {
        $db->desconectar();
        header( 'Location: ../views/admin/editarProducto.php?id=' . $id . '&error=Error al actualizar el producto' );
        exit();
    }

    // --- Reemplaza precios del producto ---
    $stmt = $db->conexion->prepare( 'DELETE FROM precios_productos WHERE producto_id = ?' );
    $stmt->bind_param( 'i', $id );
    $stmt->execute();

    $stmtPrecio = $db->conexion->prepare( 'INSERT INTO precios_productos (producto_id, tamano, presentacion, precio) VALUES (?, "normal", ?, ?)' );
    if ( !$stmtPrecio ) {
        $db->desconectar();
        header( 'Location: ../views/admin/editarProducto.php?id=' . $id . '&error=No se pudo preparar inserción de precios' );
        exit();
    }

    $ok = true;

    $pres = 'paquete6';
    $stmtPrecio->bind_param( 'isd', $id, $pres, $pa6 );
    $ok = $ok && $stmtPrecio->execute();

    $pres = 'paquete9';
    $stmtPrecio->bind_param( 'isd', $id, $pres, $pa9 );
    $ok = $ok && $stmtPrecio->execute();

    $pres = 'paquete12';
    $stmtPrecio->bind_param( 'isd', $id, $pres, $pa12 );
    $ok = $ok && $stmtPrecio->execute();

    // Opcional: registro de mixto con precio global placeholder
    $precio_mixto_global = 75000.0;
    $stmtMix = $db->conexion->prepare( 'INSERT INTO precios_productos (producto_id, tamano, presentacion, precio) VALUES (?, "normal", "paquete_mixto", ?)' );
    if ( $stmtMix ) {
        $stmtMix->bind_param( 'id', $id, $precio_mixto_global );
        $stmtMix->execute();
    }

    if ( !$ok ) {
        $db->desconectar();
        header( 'Location: ../views/admin/editarProducto.php?id=' . $id . '&error=Error al guardar los precios' );
        exit();
    }

    // --- Imagen ( opcional ) ---
    if ( isset( $_FILES[ 'imagen' ] ) && $_FILES[ 'imagen' ][ 'error' ] === UPLOAD_ERR_OK ) {
        $imagen = $_FILES[ 'imagen' ];
        $tipo   = $imagen[ 'type' ];
        $peso   = $imagen[ 'size' ];
        $tmp    = $imagen[ 'tmp_name' ];

        if ( $tipo !== 'image/jpeg' && $tipo !== 'image/png' && $tipo !== 'image/webp' ) {
            $db->desconectar();
            header( 'Location: ../views/admin/editarProducto.php?id=' . $id . '&error=Tipo de archivo no permitido' );
            exit();
        }
        if ( $peso > 5 * 1024 * 1024 ) {
            $db->desconectar();
            header( 'Location: ../views/admin/editarProducto.php?id=' . $id . '&error=La imagen es demasiado grande' );
            exit();
        }

        $extension = pathinfo( $imagen[ 'name' ], PATHINFO_EXTENSION );
        $nombre_archivo = uniqid( '', true ) . '.' . $extension;

        if ( move_uploaded_file( $tmp, '../assets/img/' . $nombre_archivo ) ) {
            $stmt = $db->conexion->prepare( 'UPDATE productos SET imagen = ? WHERE id = ?' );
            $stmt->bind_param( 'si', $nombre_archivo, $id );
            $stmt->execute();
        }
    }

    $db->desconectar();
    header( 'Location: ../views/admin/productos.php?success=Producto actualizado correctamente' );
    exit();
}

// Si llega por GET directo, regresa al admin
$db->desconectar();
header( 'Location: ../views/admin/admin.php' );
exit();
