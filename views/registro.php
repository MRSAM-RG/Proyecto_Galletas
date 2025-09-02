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
    <title>Registro - Galletas</title>
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
                <li><a href="carrito.php">Carrito <span id="cart-count" class="cart-count"></span></a></li>
                <li><a href="../controllers/logout.php">Cerrar Sesión</a></li>
            <?php else: ?>
                <li><a href="login.php">Iniciar Sesión</a></li>
                <li><a href="registro.php">Registrarse</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="login-container">
        <h1>Crear Cuenta</h1>
        <?php if (isset($_GET['error'])): ?>
            <div class="error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        <form action="../controllers/registro.php" method="POST" id="registroForm">
            <label for="nombre">Nombre Completo</label>
            <input type="text" id="nombre" name="nombre" required>
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" required>
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
            <small style="display:block;margin-bottom:10px;">La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas y números.</small>
            <label for="confirm_password">Confirmar Contraseña</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <button type="submit">Registrarse</button>
        </form>
        <p style="margin-top: 1rem;">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </p>
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
    fetch('carrito.php?count=1')
        .then(res => res.json())
        .then(data => {
            cartCount.textContent = data.count > 0 ? '(' + data.count + ')' : '';
        });
}
updateCartCount();

// Validación del formulario de registro
document.getElementById('registroForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const nombre = document.getElementById('nombre').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    // Validaciones
    if (nombre.length < 3) {
        Swal.fire({
            title: 'Error',
            text: 'El nombre debe tener al menos 3 caracteres',
            icon: 'error',
            confirmButtonColor: '#a14a7f'
        });
        return;
    }
    
    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        Swal.fire({
            title: 'Error',
            text: 'Por favor ingresa un email válido',
            icon: 'error',
            confirmButtonColor: '#a14a7f'
        });
        return;
    }
    
    if (password.length < 8) {
        Swal.fire({
            title: 'Error',
            text: 'La contraseña debe tener al menos 8 caracteres',
            icon: 'error',
            confirmButtonColor: '#a14a7f'
        });
        return;
    }
    
    if (!password.match(/[A-Z]/)) {
        Swal.fire({
            title: 'Error',
            text: 'La contraseña debe contener al menos una letra mayúscula',
            icon: 'error',
            confirmButtonColor: '#a14a7f'
        });
        return;
    }
    
    if (!password.match(/[a-z]/)) {
        Swal.fire({
            title: 'Error',
            text: 'La contraseña debe contener al menos una letra minúscula',
            icon: 'error',
            confirmButtonColor: '#a14a7f'
        });
        return;
    }
    
    if (!password.match(/[0-9]/)) {
        Swal.fire({
            title: 'Error',
            text: 'La contraseña debe contener al menos un número',
            icon: 'error',
            confirmButtonColor: '#a14a7f'
        });
        return;
    }
    
    if (password !== confirmPassword) {
        Swal.fire({
            title: 'Error',
            text: 'Las contraseñas no coinciden',
            icon: 'error',
            confirmButtonColor: '#a14a7f'
        });
        return;
    }
    
    // Si todo está bien, enviar el formulario
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
</script>
</html>
