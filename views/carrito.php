<?php
require_once '../config/security.php';
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';

session_start();
setSecurityHeaders();

/* ===  == Badge del carrito: ?count = 1 ===  == */
if ( isset( $_GET[ 'count' ] ) && $_GET[ 'count' ] == '1' ) {
    header( 'Content-Type: application/json' );
    if ( !isset( $_SESSION[ 'usuario_id' ] ) ) {
        echo json_encode( [ 'count' => 0 ] );
        exit;
    }
    $db = new MySQL();
    $db->conectar();
    $qm = new QueryManager( $db );
    $total = $qm->getCartCount( $_SESSION[ 'usuario_id' ] );
    // retorna # de galletas ( unidades )
    $db->desconectar();
    echo json_encode( [ 'count' => $total ] );
    exit;
}

/* ===  == Acceso ===  == */
if ( !isset( $_SESSION[ 'usuario_id' ] ) ) {
    header( 'Location: login.php' );
    exit();
}

/* ===  == Datos del carrito ===  == */
$db = new MySQL();
$db->conectar();
$qm = new QueryManager( $db );

$usuario_id = $_SESSION[ 'usuario_id' ];
$res = $qm->getCartItems( $usuario_id );
// debe traer alias 'precio' = precio por paquete

$items = [];
$subtotal = 0.0;
$unidades_total = 0;

// ---- mapa de unidades por presentación ----
$map_unidades = [
    'unidad'         => 1,
    'paquete6'       => 6,
    'paquete9'       => 9,
    'paquete12'      => 12,
    'paquete_mixto'  => 3, // ajusta si tu mixto equivale a otro número
];

// ---- armar arreglo para la tabla ----
$items = [];
$subtotal = 0.0;
$unidades_total = 0;

while ( $row = $res->fetch_assoc() ) {
    $pres   = $row[ 'presentacion' ] ?: 'unidad';
    $factor = $map_unidades[ strtolower( $pres ) ] ?? 1;

    // 'precio' viene del SELECT como precio por paquete
    $row[ 'precio_paquete' ] = ( float )$row[ 'precio' ];
    $row[ 'total_item' ]     = ( float )$row[ 'precio' ] * ( int )$row[ 'cantidad' ];
    $row[ 'unidades' ]       = $factor * ( int )$row[ 'cantidad' ];

    $items[] = $row;

    $subtotal       += $row[ 'total_item' ];
    $unidades_total += $row[ 'unidades' ];
}

$db->desconectar();

/* ===  == Helper etiqueta ===  == */

function labelPresentacion( $p ) {
    $p = strtolower( trim( $p ) );
    if ( $p === 'paquete6' )      return 'Paquete de 6';
    if ( $p === 'paquete9' )      return 'Paquete de 9';
    if ( $p === 'paquete12' )     return 'Paquete de 12';
    if ( $p === 'paquete_mixto' ) return 'Paquete Mixto';
    return ucfirst( $p );
}
?>
<!DOCTYPE html>
<html lang = 'es'>
<head>
<meta charset = 'UTF-8'>
<meta name = 'viewport' content = 'width=device-width, initial-scale=1.0'>
<title>Carrito de Compras - Galletas</title>
<link rel = 'stylesheet' href = '../assets/css/style.css'>
<script src = '../assets/js/sweetalert2.all.min.js'></script>
<style>
/* Botón eliminar */
.btn-eliminar {
    background:#dc3545;
    color:#fff;
    border:0;
    padding:8px 16px;
    border-radius:4px;
    cursor:pointer;
    font-size:.9rem}
    .btn-eliminar:hover {
        background:#c82333}

        /* Card y tabla */
        .cart-card {
            background:#fff;
            border-radius:16px;
            box-shadow:0 4px 20px rgba( 0, 0, 0, .10 );
            padding:2rem 1.5rem;
            max-width:100%;
            overflow-x:auto}
            .cart-table {
                width:100%;
                border-collapse:collapse;
                margin:2rem 0 0 0;
                min-width:800px}
                .cart-table th, .cart-table td {
                    padding:1rem;
                    text-align:left;
                    border-bottom:1px solid #ddd;
                    white-space:nowrap}
                    .cart-table th {
                        background:#f8f9fa;
                        font-weight:700}
                        .cart-table img {
                            width:80px;
                            height:80px;
                            object-fit:cover;
                            border-radius:8px}
                            .precio {
                                color:#a14a7f;
                                font-weight:700}

                                /* Navbar */
                                .navbar {
                                    display:flex;
                                    justify-content:space-between;
                                    align-items:center;
                                    gap:1rem;
                                    padding:.75rem 1rem}
                                    .nav-links {
                                        display:flex;
                                        align-items:center;
                                        gap:1rem;
                                        list-style:none;
                                        margin:0;
                                        padding:0}
                                        .nav-links li a {
                                            display:inline-flex;
                                            align-items:center;
                                            gap:.4rem;
                                            text-decoration:none}
                                            .cart-link {
                                                position:relative;
                                                display:inline-flex;
                                                align-items:center;
                                                padding:.35rem .6rem;
                                                border-radius:9999px;
                                                transition:background .2s ease, transform .05s ease}
                                                .cart-link:hover {
                                                    background:rgba( 0, 0, 0, .06 )}
                                                    .cart-link:active {
                                                        transform:translateY( 1px )}
                                                        .cart-icon {
                                                            width:28px;
                                                            height:28px;
                                                            object-fit:contain;
                                                            display:block;
                                                            filter:drop-shadow( 0 1px 1px rgba( 0, 0, 0, .15 ) )}
                                                            .cart-count {
                                                                position:absolute;
                                                                top:0;
                                                                right:0;
                                                                transform:translate( 45%, -45% );
                                                                min-width:20px;
                                                                height:20px;
                                                                padding:0 6px;
                                                                border-radius:9999px;
                                                                background:#dc3545;
                                                                color:#fff;
                                                                font-size:.75rem;
                                                                font-weight:700;
                                                                line-height:20px;
                                                                text-align:center;
                                                                box-shadow:0 2px 6px rgba( 220, 53, 69, .4 )}
                                                                .cart-count:empty {
                                                                    display:none}
                                                                    @media ( max-width:992px ) {
                                                                        .nav-links {
                                                                            gap:.5rem}
                                                                            .cart-icon {
                                                                                width:26px;
                                                                                height:26px}
                                                                            }
                                                                            </style>
                                                                            </head>
                                                                            <body>
                                                                            <nav class = 'navbar'>
                                                                            <div class = 'logo'>
                                                                            <img src = '../assets/img/Logo.png' alt = 'Logo Empresa'>
                                                                            <a href = 'index.php'><span style = 'color:#ff92b2;font-size:1.5rem;font-weight:bold;'>Dulce Tentación</span></a>
                                                                            </div>
                                                                            <button class = 'hamburger' id = 'hamburger-btn' aria-label = 'Abrir menú'><span></span><span></span><span></span></button>
                                                                            <ul class = 'nav-links'>
                                                                            <?php if ( isset( $_SESSION[ 'usuario_id' ] ) && $_SESSION[ 'rol' ] === 'admin' ): ?>
                                                                            <li><a href = 'admin/admin.php'>Admin</a></li>
                                                                            <?php endif;
                                                                            ?>
                                                                            <li>
                                                                            <a href = 'carrito.php' class = 'cart-link' aria-label = 'Ir al carrito'>
                                                                            <img class = 'cart-icon' src = '../assets/img/carrito.png' alt = 'Carrito'>
                                                                            <span id = 'cart-count' class = 'cart-count' aria-live = 'polite'></span>
                                                                            </a>
                                                                            </li>
                                                                            <li><a href = '../controllers/logout.php'>Cerrar Sesión</a></li>
                                                                            </ul>
                                                                            </nav>

                                                                            <main>
                                                                            <section class = 'product-section'>
                                                                            <div style = 'max-width:800px;margin:auto;'>
                                                                            <h1>Carrito de Compras</h1>

                                                                            <?php if ( empty( $items ) ): ?>
                                                                            <div class = 'error' style = 'margin:2rem 0;'>Tu carrito está vacío. <a href = 'index.php'>Ver productos</a></div>
                                                                            <?php else: ?>
                                                                            <div class = 'cart-card'>
                                                                            <table class = 'cart-table'>
                                                                            <thead>
                                                                            <tr>
                                                                            <th>Producto</th>
                                                                            <th>Imagen</th>
                                                                            <th>Presentación</th>
                                                                            <th>Cant. paquetes</th>
                                                                            <th>Unidades</th>
                                                                            <th>Precio paquete</th>
                                                                            <th>Total</th>
                                                                            <th>Acciones</th>
                                                                            </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                            <?php foreach ( $items as $it ): ?>
                                                                            <tr>
                                                                            <td><?php echo htmlspecialchars_decode( $it[ 'nombre' ] );
                                                                            ?></td>
                                                                            <td>
                                                                            <img src = "../assets/img/<?php echo htmlspecialchars($it['imagen']); ?>"
                                                                            alt = "<?php echo htmlspecialchars($it['nombre']); ?>">
                                                                            </td>
                                                                            <td><?php echo labelPresentacion( $it[ 'presentacion' ] );
                                                                            ?></td>
                                                                            <td><?php echo ( int )$it[ 'cantidad' ];
                                                                            ?></td>
                                                                            <td><?php echo ( int )$it[ 'unidades' ];
                                                                            ?></td>
                                                                            <td class = 'precio'>$<?php echo number_format( $it[ 'precio_paquete' ], 0, ',', '.' );
                                                                            ?></td>
                                                                            <td class = 'precio'>$<?php echo number_format( $it[ 'total_item' ], 0, ',', '.' );
                                                                            ?></td>
                                                                            <td>
                                                                            <form action = '../controllers/eliminarCarrito.php' method = 'POST' class = 'delete-form'>
                                                                            <input type = 'hidden' name = 'carrito_id' value = "<?php echo (int)$it['id']; ?>">
                                                                            <button type = 'button' class = 'btn-eliminar'>Eliminar</button>
                                                                            </form>
                                                                            </td>
                                                                            </tr>
                                                                            <?php endforeach;
                                                                            ?>
                                                                            </tbody>
                                                                            </table>

                                                                            <div style = 'text-align:right;margin-top:1rem;'>
                                                                            <p><strong>Unidades totales:</strong> <?php echo ( int )$unidades_total;
                                                                            ?></p>
                                                                            <p><strong>Subtotal:</strong> $<?php echo number_format( $subtotal, 0, ',', '.' );
                                                                            ?></p>
                                                                            </div>

                                                                            <div style = 'text-align:right;margin-top:1rem;'>
                                                                            <button type = 'button' onclick = "document.getElementById('direccionModal').style.display='block'">
                                                                            Procesar Pedido
                                                                            </button>
                                                                            </div>
                                                                            </div>
                                                                            <div id = 'direccionModal' style = 'display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.3);z-index:1000;align-items:center;justify-content:center;'>
                                                                            <div style = 'background:#fff;padding:2rem 2.5rem;border-radius:16px;max-width:400px;margin:auto;position:relative;box-shadow:0 4px 20px rgba(0,0,0,0.15);'>
                                                                            <h2>Dirección de Envío</h2>
                                                                            <form id = 'pedidoForm' action = '../controllers/procesar_compra.php' method = 'POST'>
                                                                            <label for = 'direccion'>Dirección completa:</label>
                                                                            <input type = 'text' id = 'direccion' name = 'direccion' required
                                                                            style = 'width:100%;margin:12px 0 18px 0;padding:10px;border-radius:8px;border:1px solid #ccc;'>
                                                                            <label for = 'telefono'>Número de teléfono:</label>
                                                                            <input type = 'tel' id = 'telefono' name = 'telefono' required pattern = '[0-9]{10}'
                                                                            style = 'width:100%;margin:12px 0 18px 0;padding:10px;border-radius:8px;border:1px solid #ccc;'
                                                                            placeholder = 'Ej: 3001234567'>
                                                                            <div style = 'display:flex;gap:1rem;justify-content:flex-end;'>
                                                                            <button type = 'button' onclick = "document.getElementById('direccionModal').style.display='none'">Cancelar</button>
                                                                            <button type = 'submit'>Confirmar Pedido</button>
                                                                            </div>
                                                                            </form>
                                                                            </div>
                                                                            </div>
                                                                            <?php endif;
                                                                            ?>
                                                                            </div>
                                                                            </section>
                                                                            </main>

                                                                            <script>
                                                                            document.getElementById( 'hamburger-btn' ).addEventListener( 'click', function() {
                                                                                document.querySelector( '.nav-links' ).classList.toggle( 'open' );
                                                                            }
                                                                        );

                                                                        // Mensajes
                                                                        <?php if ( isset( $_GET[ 'error' ] ) ): ?>
                                                                        Swal.fire( {
                                                                            title: 'Error', text: '<?php echo htmlspecialchars($_GET['error']); ?>', icon: 'error', confirmButtonColor: '#a14a7f' }
                                                                        );
                                                                        <?php endif;
                                                                        ?>
                                                                        <?php if ( isset( $_GET[ 'success' ] ) ): ?>
                                                                        Swal.fire( {
                                                                            title: '¡Pedido Realizado!',
                                                                            text: '<?php echo htmlspecialchars($_GET['success']); ?>',
                                                                            icon: 'success',
                                                                            confirmButtonColor: '#a14a7f'
                                                                        }
                                                                    ).then( ()=> {
                                                                        window.location.href = 'index.php';
                                                                    }
                                                                );
                                                                <?php endif;
                                                                ?>

                                                                // Contador del carrito

                                                                function updateCartCount() {
                                                                    var badge = document.getElementById( 'cart-count' );
                                                                    if ( !badge ) return;
                                                                    fetch( 'carrito.php?count=1' )
                                                                    .then( res => res.json() )
                                                                    .then( data => {
                                                                        badge.textContent = data.count > 0 ? '(' + data.count + ')' : '';
                                                                    }
                                                                );
                                                            }
                                                            updateCartCount();

                                                            // Confirmación de eliminar
                                                            document.addEventListener( 'DOMContentLoaded', function() {
                                                                document.querySelectorAll( '.btn-eliminar' ).forEach( btn => {
                                                                    btn.addEventListener( 'click', function() {
                                                                        const form = this.closest( 'form' );
                                                                        Swal.fire( {
                                                                            title: '¿Eliminar del carrito?',
                                                                            icon: 'warning',
                                                                            showCancelButton: true,
                                                                            confirmButtonColor: '#dc3545',
                                                                            cancelButtonColor: '#6c757d',
                                                                            confirmButtonText: 'Sí, eliminar',
                                                                            cancelButtonText: 'Cancelar'
                                                                        }
                                                                    ).then( r => {
                                                                        if ( r.isConfirmed ) form.submit();
                                                                    }
                                                                );
                                                            }
                                                        );
                                                    }
                                                );
                                            }
                                        );

                                        // Validación modal de pedido
                                        const formPedido = document.getElementById( 'pedidoForm' );
                                        if ( formPedido ) {
                                            formPedido.addEventListener( 'submit', function( e ) {
                                                e.preventDefault();
                                                const dir = document.getElementById( 'direccion' ).value.trim();
                                                const tel = document.getElementById( 'telefono' ).value.trim();
                                                if ( dir.length < 5 ) {
                                                    Swal.fire( {
                                                        title:'Error', text:'Dirección inválida', icon:'error', confirmButtonColor:'#a14a7f' }
                                                    );
                                                    return;
                                                }
                                                if ( !/^\d{10}$/.test( tel ) ) {
                                                        Swal.fire( {
                                                            title:'Error', text:'Teléfono inválido (10 dígitos)', icon:'error', confirmButtonColor:'#a14a7f' }
                                                        );
                                                        return;
                                                    }
                                                    Swal.fire( {
                                                        title:'¿Confirmar pedido?', icon:'question', showCancelButton:true,
                                                        confirmButtonColor:'#a14a7f', cancelButtonColor:'#6c757d'
                                                    }
                                                ).then( r => {
                                                    if ( r.isConfirmed ) this.submit();
                                                }
                                            );
                                        }
                                    );
                                }

                                function labelPresentacion( $p ) { 
                                    switch ( $p ) {
                                        case 'paquete6':      return 'Paquete de 6';
                                        case 'paquete9':      return 'Paquete de 9';
                                        case 'paquete12':     return 'Paquete de 12';
                                        case 'paquete_mixto': return 'Paquete Mixto';
                                        case 'unidad':
                                        default:              return 'Unidad';
                                    }
                                }

                                // Cerrar modal con Escape
                                window.addEventListener( 'keydown', e => {
                                    if ( e.key === 'Escape' ) document.getElementById( 'direccionModal' ).style.display = 'none';
                                }
                            );
                            </script>
                            </body>
                            </html>
