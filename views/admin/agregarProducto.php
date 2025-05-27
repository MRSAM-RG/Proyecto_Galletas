<?php
require_once '../../config/security.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto</title>
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

    <div class="login-container" style="max-width:480px;">
        <h1 style="color:#a14a7f;font-size:2.3rem;margin-bottom:1.2rem;">Agregar Producto</h1>
        <?php if (isset($_GET['error'])): ?>
            <div class="error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        <form action="../../controllers/agregarProducto.php" method="POST" enctype="multipart/form-data" style="text-align:left;">
            <div class="form-group">
                <label for="nombre">Nombre del Producto</label>
                <input type="text" id="nombre" name="nombre" required placeholder="Ej: Galleta de chocolate">
            </div>
            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" required placeholder="Describe el producto..." style="resize:vertical;"></textarea>
            </div>
            <div class="form-group">
                <label for="precio">Precio Base</label>
                <input type="number" id="precio" name="precio" step="0.01" min="0" required placeholder="Ej: 25000 (25.000)">
            </div>
            <div class="form-group">
                <h3>Precios por Tamaño y Presentación</h3>
                <div class="precios-grid">
                    <div class="precio-item">
                        <label>Normal - Unidad:</label>
                        <input type="number" name="precio_normal_unidad" step="0.01" min="0" required>
                    </div>
                    <div class="precio-item">
                        <label>Normal - Paquete de 3:</label>
                        <input type="number" name="precio_normal_paquete3" step="0.01" min="0" required>
                    </div>
                    <div class="precio-item">
                        <label>Jumbo - Unidad:</label>
                        <input type="number" name="precio_jumbo_unidad" step="0.01" min="0" required>
                    </div>
                    <div class="precio-item">
                        <label>Jumbo - Paquete de 3:</label>
                        <input type="number" name="precio_jumbo_paquete3" step="0.01" min="0" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="imagen">Imagen</label>
                <input type="file" id="imagen" name="imagen" accept="image/*" required onchange="previewImg(event)">
                <div id="preview" style="margin-top:10px;"></div>
                <small style="color:#a14a7f;">Solo imágenes JPG, PNG o WebP. Tamaño recomendado: 400x400px.</small>
            </div>
            <button type="submit" class="btn btn-agregar" style="width:100%;margin-top:18px;">Agregar Producto</button>
        </form>
    </div>
    <script>
    function previewImg(e) {
        var file = e.target.files[0];
        var preview = document.getElementById('preview');
        preview.innerHTML = '';
        if (file && file.type.match('image.*')) {
            var reader = new FileReader();
            reader.onload = function(evt) {
                preview.innerHTML = '<img src="'+evt.target.result+'" style="max-width:120px;max-height:120px;border-radius:10px;border:2px solid #ff92b2;box-shadow:0 2px 8px #ffd1e0;">';
            };
            reader.readAsDataURL(file);
        }
    }
    </script>
    <script>
    document.getElementById('hamburger-btn').addEventListener('click', function() {
        document.querySelector('.nav-links').classList.toggle('open');
    });
    </script>
    <script>
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
</body>
</html> 