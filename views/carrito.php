<?php
require_once '../config/security.php';
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';

session_start();
setSecurityHeaders();

if (isset($_GET['count']) && $_GET['count'] == 1) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['count' => 0]);
        exit;
    }
    $db = new MySQL();
    $db->conectar();
    $queryManager = new QueryManager($db);
    $total = $queryManager->getCartCount($_SESSION['usuario_id']);
    $db->desconectar();
    echo json_encode(['count' => $total]);
    exit;
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$db = new MySQL();
$db->conectar();
$queryManager = new QueryManager($db);

// Obtener productos del carrito
$usuario_id = $_SESSION['usuario_id'];
$result = $queryManager->getCartItems($usuario_id);

// Calcular total
$total = 0;
$carrito_items = [];
while ($item = $result->fetch_assoc()) {
    $carrito_items[] = $item;
    $total += $item['precio'] * $item['cantidad'];
}

$db->desconectar();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - Galletas</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/sweetalert2.all.min.js"></script>
    <style>
        .btn-eliminar {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }
        .btn-eliminar:hover {
            background-color: #c82333;
        }
        .cart-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.10);
            padding: 2rem 1.5rem;
            max-width: 100%;
            overflow-x: unset;
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0 0 0;
        }
        .cart-table th, .cart-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .cart-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .cart-table img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .cart-table .precio {
            color: #a14a7f;
            font-weight: bold;
        }
        @media (max-width: 900px) {
            .cart-card {
                padding: 1rem 0.5rem;
                overflow-x: auto;
            }
            .cart-table {
                min-width: 700px;
            }
            .cart-table th, .cart-table td {
                padding: 0.5rem;
                font-size: 0.95rem;
            }
            .cart-table img {
                width: 60px;
                height: 60px;
            }
        }
        @media (max-width: 600px) {
            .cart-card {
                padding: 0.5rem 0.2rem;
            }
            .cart-table {
                min-width: 500px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="../assets/img/logo.png" alt="Logo Empresa">
            <a href="index.php"><span style="color:#ff92b2;font-size:1.5rem;font-weight:bold;">Galería de Galletas</span></a>
        </div>
        <button class="hamburger" id="hamburger-btn" aria-label="Abrir menú">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <ul class="nav-links">
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <?php if ($_SESSION['rol'] === 'admin'): ?>
                    <li><a href="admin/admin.php">Admin</a></li>
                <?php endif; ?>
                <li><a href="carrito.php">Carrito <span id="cart-count" class="cart-count"></span></a></li>
                <li><a href="../controllers/logout.php">Cerrar Sesión</a></li>
            <?php else: ?>
                <li><a href="login.php">Iniciar Sesión</a></li>
                <li><a href="registro.php">Registrarse</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <main>
        <section class="product-section">
            <div style="max-width:800px;margin:auto;">
                <h1>Carrito de Compras</h1>
                <?php if (empty($carrito_items)): ?>
                    <div class="error" style="margin:2rem 0;">Tu carrito está vacío. <a href="index.php">Ver productos</a></div>
                <?php else: ?>
                    <div class="cart-card" style="background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.10);padding:2rem 1.5rem;overflow-x:auto;max-width: 100%;">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Imagen</th>
                                    <th>Tamaño</th>
                                    <th>Presentación</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unitario</th>
                                    <th>Subtotal</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($carrito_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars_decode($item['nombre']); ?></td>
                                        <td><img src="../assets/img/<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>"></td>
                                        <td><?php echo ucfirst($item['tamano']); ?></td>
                                        <td><?php echo $item['presentacion'] === 'unidad' ? 'Unidad' : 'Paquete de 3'; ?></td>
                                        <td><?php echo $item['cantidad']; ?></td>
                                        <td class="precio"><?= '$' . number_format($item['precio'], 0, ',', '.') ?></td>
                                        <td class="precio"><?= '$' . number_format($item['precio'] * $item['cantidad'], 0, ',', '.') ?></td>
                                        <td>
                                            <form action="../controllers/eliminarCarrito.php" method="POST" class="delete-form">
                                                <input type="hidden" name="carrito_id" value="<?php echo $item['id']; ?>">
                                                <button type="button" class="btn-eliminar">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align:right;margin-top:2rem;">
                        <h2>Total: $<?php echo number_format($total, 2); ?></h2>
                        <button type="button" onclick="document.getElementById('direccionModal').style.display='block'">Procesar Pedido</button>
                    </div>
                    <!-- Modal de dirección -->
                    <div id="direccionModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.3);z-index:1000;align-items:center;justify-content:center;">
                        <div style="background:#fff;padding:2rem 2.5rem;border-radius:16px;max-width:400px;margin:auto;position:relative;box-shadow:0 4px 20px rgba(0,0,0,0.15);">
                            <h2>Dirección de Envío</h2>
                            <form action="../controllers/procesar_compra.php" method="POST" onsubmit="return validarDireccion(event)">
                                <label for="direccion">Dirección completa:</label>
                                <input type="text" id="direccion" name="direccion" required style="width:100%;margin:12px 0 18px 0;padding:10px;border-radius:8px;border:1px solid #ccc;">
                                <label for="telefono">Número de teléfono:</label>
                                <input type="tel" id="telefono" name="telefono" required pattern="[0-9]{10}" style="width:100%;margin:12px 0 18px 0;padding:10px;border-radius:8px;border:1px solid #ccc;" placeholder="Ej: 1234567890">
                                <div style="display:flex;gap:1rem;justify-content:flex-end;">
                                    <button type="submit">Confirmar Pedido</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <script>
    document.getElementById('hamburger-btn').addEventListener('click', function() {
        document.querySelector('.nav-links').classList.toggle('open');
    });
    </script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
    // Actualiza el contador del carrito
    function updateCartCount() {
        var cartCount = document.getElementById('cart-count');
        if (!cartCount) return;
        fetch('carrito.php?count=1')
            .then(res => res.json())
            .then(data => {
                cartCount.textContent = data.count > 0 ? '(' + data.count + ')' : '';
            });
    }
    updateCartCount();

    // Agregar event listeners a todos los botones de eliminar
    document.addEventListener('DOMContentLoaded', function() {
        // Validar stock al cambiar cantidad
        document.querySelectorAll('input[name="cantidad"]').forEach(input => {
            input.addEventListener('change', function() {
                const form = this.closest('form');
                const productoId = form.querySelector('input[name="producto_id"]').value;
                const tamano = form.querySelector('input[name="tamano"]').value;
                const cantidad = this.value;

                fetch(`../controllers/verificar_stock.php?producto_id=${productoId}&tamano=${tamano}&cantidad=${cantidad}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            Swal.fire({
                                title: 'Stock insuficiente',
                                text: data.error,
                                icon: 'error',
                                confirmButtonColor: '#a14a7f'
                            });
                            this.value = this.defaultValue;
                        }
                    });
            });
        });

        document.querySelectorAll('.btn-eliminar').forEach(button => {
            button.addEventListener('click', function() {
                const form = this.closest('form');
                
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: '¿Deseas eliminar este producto del carrito?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });

    function validarDireccion(event) {
        event.preventDefault();
        var dir = document.getElementById('direccion').value.trim();
        var tel = document.getElementById('telefono').value.trim();
        
        if (dir.length < 5) {
            Swal.fire({
                title: 'Error',
                text: 'Por favor ingresa una dirección válida.',
                icon: 'error',
                confirmButtonColor: '#a14a7f'
            });
            return false;
        }
        if (!/^[0-9]{10}$/.test(tel)) {
            Swal.fire({
                title: 'Error',
                text: 'Por favor ingresa un número de teléfono válido de 10 dígitos.',
                icon: 'error',
                confirmButtonColor: '#a14a7f'
            });
            return false;
        }

        // Mostrar loading mientras se procesa
        Swal.fire({
            title: 'Procesando pedido',
            text: 'Por favor espere...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Enviar el formulario realmente
        event.target.submit();
        return false;
    }

    // Cerrar modal con Escape
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') document.getElementById('direccionModal').style.display = 'none';
    });

    // Mostrar notificaciones con SweetAlert2
    <?php if (isset($_GET['success'])): ?>
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!','text': '<?= htmlspecialchars($_GET['success']) ?>',
        confirmButtonColor: '#a14a7f',
        allowOutsideClick: false
    });
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    Swal.fire({
        icon: 'error',
        title: '¡Error!','text': '<?= htmlspecialchars($_GET['error']) ?>',
        confirmButtonColor: '#a14a7f',
        allowOutsideClick: false
    });
    <?php endif; ?>
    </script>
</body>
</html>
