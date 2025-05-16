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
    <title>Panel de Administración</title>
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
    <br><br>
    <div class="login-container" style="max-width:600px;">
        <h1>Panel de Administración</h1>
        <ul style="list-style:none;padding:0;margin:2rem 0;">
            <li style="margin-bottom:1.5rem;"><a href="productos.php" style="font-size:1.2rem;">Gestión de Productos</a></li>
            <li style="margin-bottom:1.5rem;"><a href="pedidos.php" style="font-size:1.2rem;">Ver Pedidos</a></li>
        </ul>
        <p>Desde aquí puedes administrar los productos, ver y gestionar los pedidos realizados por los clientes.</p>
    </div>
    <br><br>
    <footer class="footer">
        <div class="social-icons">
            <a href="#"><img src="../../assets/img/instagram.png" alt="Instagram"></a>
            <a href="#"><img src="../../assets/img/facebook.png" alt="Facebook"></a>
            <a href="#"><img src="../../assets/img/whatsapp.png" alt="WhatsApp"></a>
        </div>
        <p>© 2025 Galería de Galletas. Todos los derechos reservados.</p>
        <p>Iconos de <a href="https://icons8.com" target="_blank">Icons8</a></p>
    </footer>
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
