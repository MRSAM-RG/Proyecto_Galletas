<?php
require_once '../config/security.php';
session_start();

// Si ya está logueado, redirigir al index
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Galletas</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/sweetalert2.all.min.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="../assets/img/Logo.png" alt="Logo Empresa">
            <a href="index.php"><span style="color:#ff92b2;font-size:1.5rem;font-weight:bold;">Dulce Tentación</span></a>
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
                <li><a href="carrito.php"><img src="../assets/img/carrito.png" alt="Carrito"> <span id="cart-count" class="cart-count"></span></a></li>
                <li><a href="../controllers/logout.php">Cerrar Sesión</a></li>
            <?php else: ?>
                <li><a href="login.php">Iniciar Sesión</a></li>
                <li><a href="registro.php">Registrarse</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="login-container">
        <h1>Iniciar Sesión</h1>
        <form action="../controllers/login.php" method="POST" id="loginForm">
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" required>
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Iniciar Sesión</button>
        </form>
        <p style="margin-top: 1rem;">
            ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
        </p>
    </div>
</body>
<script>
document.getElementById('hamburger-btn').addEventListener('click', function() {
    document.querySelector('.nav-links').classList.toggle('open');
});

// Validación del formulario de login
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        Swal.fire({
            title: 'Error',
            text: 'Por favor ingresa un email válido',
            icon: 'error',
            confirmButtonColor: '#a14a7f'
        });
        return;
    }
    
    if (password.length < 6) {
        Swal.fire({
            title: 'Error',
            text: 'La contraseña debe tener al menos 6 caracteres',
            icon: 'error',
            confirmButtonColor: '#a14a7f'
        });
        return;
    }
    
    this.submit();
});

// Mostrar mensajes de error del servidor
<?php if (isset($_GET['error'])): ?>
Swal.fire({
    title: 'Error',
    text: '<?php echo htmlspecialchars($_GET['error']); ?>',
    icon: 'error',
    confirmButtonColor: '#a14a7f'
});
<?php endif; ?>

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
</script>
</html>
