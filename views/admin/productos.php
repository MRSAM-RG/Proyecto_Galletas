<?php
require_once '../../models/MySQL.php';
require_once '../../models/QueryManager.php';
require_once '../../config/security.php';
session_start();

if ( !isset( $_SESSION[ 'usuario_id' ] ) || $_SESSION[ 'rol' ] !== 'admin' ) {
    header( 'Location: ../index.php' );
    exit();
}

$db = new MySQL();
$db->conectar();
$qm = new QueryManager( $db );

$productos = $qm->getAllProductsIncludingInactive();

/* Mapa de precios: producto_id -> presentacion -> precio */
$precios = [];
$stmt = $db->conexion->prepare( "SELECT producto_id, presentacion, precio FROM precios_productos WHERE presentacion IN ('paquete6','paquete9','paquete12')" );
$stmt->execute();
$rs = $stmt->get_result();
while( $row = $rs->fetch_assoc() ) {
    $precios[ $row[ 'producto_id' ] ][ $row[ 'presentacion' ] ] = $row[ 'precio' ];
}

/* Precio actual del mixto ( global ) */
$precio_mixto_actual = 75000;
$stmt_mixto = $db->conexion->prepare( "SELECT precio FROM precios_productos WHERE presentacion = 'paquete_mixto' LIMIT 1" );
$stmt_mixto->execute();
$result_mixto = $stmt_mixto->get_result();
if ( $row_mixto = $result_mixto->fetch_assoc() ) {
    $precio_mixto_actual = $row_mixto[ 'precio' ];
}

$db->desconectar();
?>
<!DOCTYPE html>
<html lang = 'es'>
<head>
<meta charset = 'UTF-8' />
<meta name = 'viewport' content = 'width=device-width, initial-scale=1.0'/>
<title>Gestión de Productos</title>
<link rel = 'stylesheet' href = '../../assets/css/style.css'>
<link rel = 'stylesheet' href = '../../assets/css/pedidos.css'>
<script src = '../../assets/js/sweetalert2.all.min.js'></script>
<style>
.table-responsive {
    width:100%;
    overflow-x:auto;
    margin-bottom:1.5rem}
    table {
        width:100%;
        min-width:900px;
        border-collapse:collapse}
        th, td {
            padding:.7em .5em;
            text-align:left;
            white-space:nowrap}
            .acciones-flex {
                display:flex;
                gap:.5em;
                justify-content:flex-start;
                align-items:center}
                @media ( max-width:900px ) {
                    table {
                        min-width:700px}
                        .login-container {
                            padding:0}
                        }
                        @media ( max-width:600px ) {
                            table {
                                min-width:600px;
                                font-size:.95em}
                                th, td {
                                    padding:.5em .3em}
                                    .acciones-flex {
                                        flex-direction:column;
                                        gap:.3em;
                                        align-items:stretch}
                                        .btn-editar, .btn-eliminar {
                                            width:100%}
                                        }
                                        .estado-badge {
                                            padding:.3em .6em;
                                            border-radius:4px;
                                            font-size:.9em;
                                            font-weight:500}
                                            .estado-badge.activo {
                                                background:#28a745;
                                                color:#fff}
                                                .estado-badge.inactivo {
                                                    background:#dc3545;
                                                    color:#fff}
                                                    .btn-reactivar {
                                                        background:#28a745;
                                                        color:#fff;
                                                        border:none;
                                                        padding:.5em 1em;
                                                        border-radius:4px;
                                                        cursor:pointer}
                                                        .btn-reactivar:hover {
                                                            background:#218838}
                                                            </style>
                                                            </head>
                                                            <body>
                                                            <nav class = 'navbar'>
                                                            <div class = 'logo'><img src = '../../assets/img/Logo.png' alt = 'Logo Empresa'><a href = '../index.php'><span style = 'color:#ff92b2;font-size:1.5rem;font-weight:bold;'>Dulce Tentación</span></a></div>
                                                            <button class = 'hamburger' id = 'hamburger-btn' aria-label = 'Abrir menú'><span></span><span></span><span></span></button>
                                                            <ul class = 'nav-links'>
                                                            <li><a href = 'admin.php'>Admin</a></li>
                                                            <li><a href = '../../controllers/logout.php'>Cerrar Sesión</a></li>
                                                            </ul>
                                                            </nav>

                                                            <div class = 'login-container' style = 'max-width:1100px;'>
                                                            <h1>Gestión de Productos</h1>
                                                            <a href = '../admin/agregarProducto.php' class = 'btn' style = 'margin-bottom:1.5rem;display:inline-block;'>+ Agregar Producto</a>

                                                            <!-- Precio global del Paquete Mixto -->
                                                            <div style = 'background:#f8f9fa;padding:1rem;border-radius:8px;margin-bottom:1.5rem;border:1px solid #dee2e6;'>
                                                            <h3 style = 'color:#a14a7f;margin-bottom:1rem;'>Precio del Paquete Mixto</h3>
                                                            <form style = 'display:flex;align-items:center;gap:1rem;flex-wrap:wrap;' onsubmit = 'actualizarPrecioMixto(event)'>
                                                            <label style = 'font-weight:500;'>Precio actual:</label>
                                                            <input type = 'number' id = 'precio_mixto' step = '0.01' min = '0' value = "<?php echo $precio_mixto_actual; ?>" style = 'padding:.5rem;border:1px solid #ccc;border-radius:4px;width:140px;' required>
                                                            <button type = 'submit' class = 'btn' style = 'background:#a14a7f;color:white;padding:.5rem 1rem;border:none;border-radius:4px;cursor:pointer;'>Actualizar</button>
                                                            <small style = 'color:#666;'>Se aplica a TODOS los productos ( <b>paquete_mixto</b> ).</small>
                                                            </form>
                                                            </div>

                                                            <?php if ( isset( $_GET[ 'error' ] ) ): ?><div class = 'error'><?php echo htmlspecialchars( $_GET[ 'error' ] );
                                                            ?></div><?php endif;
                                                            ?>
                                                            <?php if ( isset( $_GET[ 'success' ] ) ): ?><div class = 'success'><?php echo htmlspecialchars( $_GET[ 'success' ] );
                                                            ?></div><?php endif;
                                                            ?>

                                                            <div class = 'table-responsive'>
                                                            <table>
                                                            <thead>
                                                            <tr>
                                                            <th>ID</th>
                                                            <th>Imagen</th>
                                                            <th>Nombre</th>
                                                            <th>Descripción</th>
                                                            <th>Precio Paq. 6</th>
                                                            <th>Precio Paq. 9</th>
                                                            <th>Precio Paq. 12</th>
                                                            <th>Mixto ( Global )</th>
                                                            <th>Estado</th>
                                                            <th>Acciones</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                            <?php while ( $producto = $productos->fetch_assoc() ): ?>
                                                            <tr>
                                                            <td><?php echo $producto[ 'id' ];
                                                            ?></td>
                                                            <td><img src = "../../assets/img/<?php echo htmlspecialchars($producto['imagen']); ?>" alt = "<?php echo htmlspecialchars($producto['nombre']); ?>" style = 'width:50px;height:50px;object-fit:cover;'></td>
                                                            <td><?php echo htmlspecialchars_decode( $producto[ 'nombre' ] );
                                                            ?></td>
                                                            <td><?php echo htmlspecialchars_decode( $producto[ 'descripcion' ] );
                                                            ?></td>
                                                            <td><?php echo isset( $precios[ $producto[ 'id' ] ][ 'paquete6' ] )  ? '$'.number_format( $precios[ $producto[ 'id' ] ][ 'paquete6' ], 0, ',', '.' )  : 'N/A';
                                                            ?></td>
                                                            <td><?php echo isset( $precios[ $producto[ 'id' ] ][ 'paquete9' ] )  ? '$'.number_format( $precios[ $producto[ 'id' ] ][ 'paquete9' ], 0, ',', '.' )  : 'N/A';
                                                            ?></td>
                                                            <td><?php echo isset( $precios[ $producto[ 'id' ] ][ 'paquete12' ] ) ? '$'.number_format( $precios[ $producto[ 'id' ] ][ 'paquete12' ], 0, ',', '.' ) : 'N/A';
                                                            ?></td>
                                                            <td><?php echo '$'.number_format( $precio_mixto_actual, 0, ',', '.' );
                                                            ?> <small style = 'color:#a14a7f;'>( Global )</small></td>
                                                            <td><span class = "estado-badge <?php echo $producto['estado']==='activo'?'activo':'inactivo'; ?>"><?php echo ucfirst( $producto[ 'estado' ] );
                                                            ?></span></td>
                                                            <td>
                                                            <div class = 'acciones-flex'>
                                                            <button onclick = "window.location.href='editarProducto.php?id=<?php echo $producto['id']; ?>'" class = 'btn-editar'>Editar</button>
                                                            <?php if ( $producto[ 'estado' ] === 'activo' ): ?>
                                                            <button onclick = "confirmarEliminacion(<?php echo $producto['id']; ?>)" class = 'btn-eliminar'>Desactivar</button>
                                                            <?php else: ?>
                                                            <button onclick = "confirmarReactivacion(<?php echo $producto['id']; ?>)" class = 'btn-reactivar'>Reactivar</button>
                                                            <?php endif;
                                                            ?>
                                                            </div>
                                                            </td>
                                                            </tr>
                                                            <?php endwhile;
                                                            ?>
                                                            </tbody>
                                                            </table>
                                                            </div>
                                                            </div>

                                                            <script>

                                                            function confirmarEliminacion( id ) {
                                                                Swal.fire( {
                                                                    title:'¿Estás seguro?', text:'El producto se marcará como inactivo', icon:'warning', showCancelButton:true, confirmButtonColor:'#dc3545', cancelButtonColor:'#6c757d', confirmButtonText:'Sí, desactivar', cancelButtonText:'Cancelar'}
                                                                )
                                                                .then( r=> {
                                                                    if ( r.isConfirmed ) window.location.href = '../../controllers/eliminarProducto.php?id='+id;
                                                                }
                                                            );
                                                        }

                                                        function confirmarReactivacion( id ) {
                                                            Swal.fire( {
                                                                title:'¿Reactivar producto?', text:'El producto volverá a estar disponible', icon:'question', showCancelButton:true, confirmButtonColor:'#28a745', cancelButtonColor:'#6c757d', confirmButtonText:'Sí, reactivar', cancelButtonText:'Cancelar'}
                                                            )
                                                            .then( r=> {
                                                                if ( r.isConfirmed ) window.location.href = '../../controllers/reactivarProducto.php?id='+id;
                                                            }
                                                        );
                                                    }

                                                    function actualizarPrecioMixto( e ) {
                                                        e.preventDefault();
                                                        const precio = document.getElementById( 'precio_mixto' ).value;
                                                        if ( precio <= 0 ) return Swal.fire( {
                                                            title:'Error', text:'El precio debe ser mayor a 0', icon:'error', confirmButtonColor:'#a14a7f'}
                                                        );
                                                        Swal.fire( {title:'¿Actualizar precio?', icon:'question', showCancelButton:true, confirmButtonColor:'#a14a7f', cancelButtonColor:'#6c757d', confirmButtonText:'Sí, actualizar', cancelButtonText:'Cancelar'})
                                                            .then( r=> {
                                                                if ( r.isConfirmed ) {
                                                                    // Tu controlador debe setear el global en precios_productos ( primer registro mixto o todos los mixtos )
                                                                    const form = document.createElement( 'form' );
                                                                    form.method = 'POST';
                                                                    form.action = '../../controllers/actualizarPrecioMixto.php';
                                                                    const input = document.createElement( 'input' );
                                                                    input.type = 'hidden';
                                                                    input.name = 'precio_mixto';
                                                                    input.value = precio;
                                                                    form.appendChild( input );
                                                                    document.body.appendChild( form );
                                                                    form.submit();
                                                                }
                                                            }
                                                        );
                                                    }
                                                    document.getElementById( 'hamburger-btn' ).addEventListener( 'click', ()=>document.querySelector( '.nav-links' ).classList.toggle( 'open' ) );

                                                    function updateCartCount() {
                                                        const el = document.getElementById( 'cart-count' );
                                                        if ( !el )return;
                                                        fetch( '../carrito.php?count=1' ).then( r=>r.json() ).then( d=> {
                                                            el.textContent = d.count>0?'('+d.count+')':'';
                                                        }
                                                    );
                                                }
                                                updateCartCount();

                                                <?php if ( isset( $_GET[ 'success' ] ) ): ?>
                                                Swal.fire( {
                                                    title:'¡Éxito!', text:'<?php echo htmlspecialchars($_GET['success']); ?>', icon:'success', confirmButtonColor:'#a14a7f'}
                                                );
                                                <?php endif;
                                                ?>
                                                <?php if ( isset( $_GET[ 'error' ] ) ): ?>
                                                Swal.fire( {
                                                    title:'Error', text:'<?php echo htmlspecialchars($_GET['error']); ?>', icon:'error', confirmButtonColor:'#a14a7f'}
                                                );
                                                <?php endif;
                                                ?>
                                                </script>
                                                </body>
                                                </html>
