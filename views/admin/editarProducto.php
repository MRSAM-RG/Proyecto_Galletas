<?php
require_once '../../models/MySQL.php';
require_once '../../models/QueryManager.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: admin.php?error=ID de producto no especificado');
    exit();
}

$db = new MySQL();
$db->conectar();
$queryManager = new QueryManager($db);
$producto = $queryManager->getProductById($_GET['id']);
// Obtener stock actual para ambos tamaños
$stock_normal = 0;
$stock_jumbo = 0;
$stmt = $db->conexion->prepare("SELECT tamano, stock FROM stock_productos WHERE producto_id = ?");
$stmt->bind_param("i", $_GET['id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if ($row['tamano'] === 'normal') $stock_normal = $row['stock'];
    if ($row['tamano'] === 'jumbo') $stock_jumbo = $row['stock'];
}
// Obtener precios actuales por tamaño y presentación
$precios = $queryManager->getProductPrices($_GET['id']);
$db->desconectar();
if (!$producto) {
    header('Location: admin.php?error=Producto no encontrado');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Producto</title>
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
    <div class="login-container">
        <h1 style="color:#a14a7f;font-size:2.3rem;margin-bottom:1.2rem;">Editar Producto</h1>
        <form action="../../controllers/editarProducto.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['id']) ?>">
            <label>Nombre:</label><br>
            <input type="text" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required><br><br>

            <label>Descripción:</label><br>
            <textarea name="descripcion" required><?= htmlspecialchars($producto['descripcion']) ?></textarea><br><br>

            <h3 style="color:#a14a7f;">Precios por Tamaño y Presentación</h3>
            <div class="precios-grid">
                <div class="precio-item">
                    <label>Normal - Unidad:</label>
                    <input type="number" name="precio_normal_unidad" step="0.01" min="0" required value="<?= isset($precios['normal']['unidad']) ? $precios['normal']['unidad'] : '' ?>">
                </div>
                <div class="precio-item">
                    <label>Normal - Paquete de 3:</label>
                    <input type="number" name="precio_normal_paquete3" step="0.01" min="0" required value="<?= isset($precios['normal']['paquete3']) ? $precios['normal']['paquete3'] : '' ?>">
                </div>
                <div class="precio-item">
                    <label>Jumbo - Unidad:</label>
                    <input type="number" name="precio_jumbo_unidad" step="0.01" min="0" required value="<?= isset($precios['jumbo']['unidad']) ? $precios['jumbo']['unidad'] : '' ?>">
                </div>
                <div class="precio-item">
                    <label>Jumbo - Paquete de 3:</label>
                    <input type="number" name="precio_jumbo_paquete3" step="0.01" min="0" required value="<?= isset($precios['jumbo']['paquete3']) ? $precios['jumbo']['paquete3'] : '' ?>">
                </div>
            </div>

            <label>Imagen (dejar vacío para mantener actual):</label><br>
            <input type="file" name="imagen" accept="image/*"><br><br>

            <button type="submit" class="btn">Guardar Cambios</button>
        </form>
    </div>
</body>
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
</script>
</html>