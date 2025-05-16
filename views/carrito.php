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
        .product-card {
            position: relative;
            padding-bottom: 60px;
        }
        .product-card p {
            margin: 5px 0;
        }
        .product-card .precio {
            color: #a14a7f;
            font-weight: bold;
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
                <li><a href="carrito.php">Carrito</a></li>
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
                    <div class="product-grid">
                        <?php foreach ($carrito_items as $item): ?>
                            <div class="product-card">
                                <img src="../assets/img/<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                                <h3><?php echo htmlspecialchars_decode($item['nombre']); ?></h3>
                                <p><strong>Tamaño:</strong> <?php echo ucfirst($item['tamano']); ?></p>
                                <p><strong>Presentación:</strong> <?php echo $item['presentacion'] === 'unidad' ? 'Unidad' : 'Paquete de 3'; ?></p>
                                <p><strong>Cantidad:</strong> <?php echo $item['cantidad']; ?></p>
                                <p class="precio"><strong>Precio unitario:</strong> <?= '$' . number_format($item['precio'], 0, ',', '.') ?></p>
                                <p class="precio"><strong>Subtotal:</strong> <?= '$' . number_format($item['precio'] * $item['cantidad'], 0, ',', '.') ?></p>
                                <form action="../controllers/eliminarCarrito.php" method="POST" style="margin-top: 1rem;">
                                    <input type="hidden" name="carrito_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn-eliminar" onclick="return confirm('¿Estás seguro de eliminar este producto del carrito?')">Eliminar del carrito</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="text-align:right;margin-top:2rem;">
                        <h2>Total: $<?php echo number_format($total, 2); ?></h2>
                        <button type="button" onclick="document.getElementById('direccionModal').style.display='block'">Proceder al Pago</button>
                    </div>
                    <!-- Modal de dirección -->
                    <div id="direccionModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.3);z-index:1000;align-items:center;justify-content:center;">
                        <div style="background:#fff;padding:2rem 2.5rem;border-radius:16px;max-width:400px;margin:auto;position:relative;box-shadow:0 4px 20px rgba(0,0,0,0.15);">
                            <h2>Dirección de Envío</h2>
                            <form action="../controllers/procesar_compra.php" method="POST" onsubmit="return validarDireccion()">
                                <label for="direccion">Dirección completa:</label>
                                <input type="text" id="direccion" name="direccion" required style="width:100%;margin:12px 0 18px 0;padding:10px;border-radius:8px;border:1px solid #ccc;">
                                <label for="telefono">Número de teléfono:</label>
                                <input type="tel" id="telefono" name="telefono" required pattern="[0-9]{10}" style="width:100%;margin:12px 0 18px 0;padding:10px;border-radius:8px;border:1px solid #ccc;" placeholder="Ej: 1234567890">
                                <div style="display:flex;gap:1rem;justify-content:flex-end;">
                                    <button type="button" onclick="document.getElementById('direccionModal').style.display='none'" style="background:#eee;color:#a14a7f;">Cancelar</button>
                                    <button type="submit">Confirmar Pedido</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <script>
                    function validarDireccion() {
                        var dir = document.getElementById('direccion').value.trim();
                        var tel = document.getElementById('telefono').value.trim();
                        if (dir.length < 5) {
                            alert('Por favor ingresa una dirección válida.');
                            return false;
                        }
                        if (!/^[0-9]{10}$/.test(tel)) {
                            alert('Por favor ingresa un número de teléfono válido de 10 dígitos.');
                            return false;
                        }
                        return true;
                    }
                    // Cerrar modal con Escape
                    window.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') document.getElementById('direccionModal').style.display = 'none';
                    });
                    </script>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <script>
    document.getElementById('hamburger-btn').addEventListener('click', function() {
        document.querySelector('.nav-links').classList.toggle('open');
    });
    </script>
</body>
</html>
