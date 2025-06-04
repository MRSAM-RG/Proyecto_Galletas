<?php
require_once '../../models/MySQL.php';
require_once '../../models/QueryManager.php';
require_once '../../config/security.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$db = new MySQL();
$db->conectar();
$queryManager = new QueryManager($db);
$productos = $queryManager->getAllProducts(true);
// Obtener stock de todos los productos
$stocks = [];
$result_stock = $db->conexion->query("SELECT producto_id, tamano, stock FROM stock_productos");
while ($row = $result_stock->fetch_assoc()) {
    $pid = $row['producto_id'];
    $tam = $row['tamano'];
    $stocks[$pid][$tam] = $row['stock'];
}
// Obtener precios de todos los productos por tamaño y presentación
$precios = [];
$result_precios = $db->conexion->query("SELECT producto_id, tamano, presentacion, precio FROM precios_productos");
while ($row = $result_precios->fetch_assoc()) {
    $precios[$row['producto_id']][$row['tamano']][$row['presentacion']] = $row['precio'];
}
$db->desconectar();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/pedidos.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="../../assets/img/logo.png" alt="Logo Empresa">
            <a href="../index.php"><span style="color:#ff92b2;font-size:1.5rem;font-weight:bold;">Galería de Galletas</span></a>
        </div>
        <button class="hamburger" id="hamburger-btn" aria-label="Abrir menú">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <ul class="nav-links">
            <li><a href="admin.php">Admin</a></li>
            <li><a href="../carrito.php">Carrito <span id="cart-count" class="cart-count"></span></a></li>
            <li><a href="../../controllers/logout.php">Cerrar Sesión</a></li>
        </ul>
    </nav>
    <div class="login-container" style="max-width:900px;">
        <h1>Gestión de Productos</h1>
        <a href="../admin/agregarProducto.php" class="btn" style="margin-bottom:1.5rem;display:inline-block;">+ Agregar Producto</a>
        
            <table>
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Precio Normal (Unidad)</th>
                        <th>Precio Jumbo (Unidad)</th>
                        <th>Stock Normal</th>
                        <th>Stock Jumbo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($producto = $productos->fetch_assoc()): ?>
                    <?php
                    $precio_normal_unidad = isset($precios[$producto['id']]['normal']['unidad']) ? '$' . number_format($precios[$producto['id']]['normal']['unidad'], 0, ',', '.') : 'N/A';
                    $precio_jumbo_unidad = isset($precios[$producto['id']]['jumbo']['unidad']) ? '$' . number_format($precios[$producto['id']]['jumbo']['unidad'], 0, ',', '.') : 'N/A';
                    $stock_normal = isset($stocks[$producto['id']]['normal']) ? $stocks[$producto['id']]['normal'] : 'N/A';
                    $stock_jumbo = isset($stocks[$producto['id']]['jumbo']) ? $stocks[$producto['id']]['jumbo'] : 'N/A';
                    ?>
                    <tr>
                        <td><img src="../../assets/img/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="img" style="width:60px;height:60px;object-fit:cover;border-radius:8px;"></td>
                        <td><?php echo htmlspecialchars_decode($producto['nombre']); ?></td>
                        <td><?php echo htmlspecialchars_decode($producto['descripcion']); ?></td>
                        <td><?= $precio_normal_unidad ?></td>
                        <td><?= $precio_jumbo_unidad ?></td>
                        <td><?= $stock_normal ?></td>
                        <td><?= $stock_jumbo ?></td>
                        <td><?= $producto['estado'] == 1 ? '<span style="color:green;font-weight:bold;">Activo</span>' : '<span style="color:red;font-weight:bold;">Inactivo</span>' ?></td>
                        <td>
                            <a href="editarProducto.php?id=<?php echo $producto['id']; ?>">Editar</a> |
                            <a href="../../controllers/eliminarProducto.php?id=<?php echo $producto['id']; ?>" onclick="return confirm('¿Eliminar producto?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

    </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script>
document.getElementById('hamburger-btn').addEventListener('click', function() {
    document.querySelector('.nav-links').classList.toggle('open');
});

// Actualiza el contador del carrito
function updateCartCount() {
    var cartCount = document.getElementById('cart-count');
    if (!cartCount) return;
    fetch('../carrito.php?count=1')
        .then(res => res.json())
        .then(data => {
            cartCount.textContent = data.count > 0 ? '(' + data.count + ')' : '';
        });
}
updateCartCount();

// Mostrar notificaciones con SweetAlert2
<?php if (isset($_GET['success'])): ?>
Swal.fire({
    icon: 'success',
    title: '¡Éxito!',
    text: '<?= htmlspecialchars($_GET['success']) ?>',
    confirmButtonColor: '#a14a7f',
    allowOutsideClick: false
});
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
Swal.fire({
    icon: 'error',
    title: '¡Error!',
    text: '<?= htmlspecialchars($_GET['error']) ?>',
    confirmButtonColor: '#a14a7f',
    allowOutsideClick: false
});
<?php endif; ?>
</script>
</html> 