<?php
require_once '../../models/MySQL.php';
require_once '../../models/QueryManager.php';
require_once '../../config/security.php';
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

// Obtener precios actuales
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
    <script src="../../assets/js/sweetalert2.all.min.js"></script>
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
        <form action="../../controllers/editarProducto.php" method="POST" enctype="multipart/form-data" id="editarForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
            <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['id']) ?>">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción:</label>
                <textarea id="descripcion" name="descripcion" required><?= htmlspecialchars($producto['descripcion']) ?></textarea>
            </div>

            <div class="form-group">
                <h3 style="color:#a14a7f;">Precios por Tamaño</h3>
                <div class="precios-grid">
                    <div class="precio-item">
                        <label>Precio Normal:</label>
                        <input type="number" name="precio_normal" step="0.01" min="0" required value="<?= isset($precios['normal']['unidad']) ? $precios['normal']['unidad'] : '' ?>">
                    </div>
                    <div class="precio-item">
                        <label>Precio Paquete 3 Normal:</label>
                        <input type="number" name="precio_normal_paquete3" step="0.01" min="0" required value="<?= isset($precios['normal']['paquete3']) ? $precios['normal']['paquete3'] : '' ?>">
                    </div>
                    <div class="precio-item">
                        <label>Precio Paquete Mixto Normal:</label>
                        <input type="number" name="precio_normal_paquete_mixto" step="0.01" min="0" required value="<?= isset($precios['normal']['paquete_mixto']) ? $precios['normal']['paquete_mixto'] : '' ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Imagen actual:</label>
                <img src="../../assets/img/<?= htmlspecialchars($producto['imagen']) ?>" alt="Imagen actual" style="max-width:200px;margin:10px 0;">
                <label for="imagen">Nueva imagen (opcional):</label>
                <input type="file" id="imagen" name="imagen" accept="image/*">
                <small style="color:#a14a7f;">Solo imágenes JPG, PNG o WebP. Tamaño recomendado: 400x400px.</small>
            </div>

            <button type="submit" class="btn btn-agregar" style="width:100%;margin-top:18px;">Guardar Cambios</button>
        </form>
    </div>
    <script>
    document.getElementById('editarForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const nombre = document.getElementById('nombre').value.trim();
        const descripcion = document.getElementById('descripcion').value.trim();
        const imagen = document.getElementById('imagen').files[0];
        
        if (nombre.length < 3) {
            Swal.fire({
                title: 'Error',
                text: 'El nombre debe tener al menos 3 caracteres',
                icon: 'error',
                confirmButtonColor: '#a14a7f'
            });
            return;
        }
        
        if (descripcion.length < 10) {
            Swal.fire({
                title: 'Error',
                text: 'La descripción debe tener al menos 10 caracteres',
                icon: 'error',
                confirmButtonColor: '#a14a7f'
            });
            return;
        }
        
        if (imagen) {
            // Validar tipo de imagen
            const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
            if (!tiposPermitidos.includes(imagen.type)) {
                Swal.fire({
                    title: 'Error',
                    text: 'Solo se permiten imágenes en formato JPG, PNG o WebP',
                    icon: 'error',
                    confirmButtonColor: '#a14a7f'
                });
                return;
            }
            
            // Validar tamaño de imagen (máximo 5MB)
            if (imagen.size > 5 * 1024 * 1024) {
                Swal.fire({
                    title: 'Error',
                    text: 'La imagen no debe superar los 5MB',
                    icon: 'error',
                    confirmButtonColor: '#a14a7f'
                });
                return;
            }
        }
        
        // Confirmar antes de guardar
        Swal.fire({
            title: '¿Guardar cambios?',
            text: '¿Estás seguro de que deseas guardar los cambios?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#a14a7f',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
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