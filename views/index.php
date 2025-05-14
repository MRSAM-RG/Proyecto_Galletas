<?php

require_once '../config/security.php';
require_once '../models/MySQL.php';
require_once '../models/QueryManager.php';

session_start();
setSecurityHeaders();

$db = new MySQL();
$db->conectar();
$queryManager = new QueryManager($db);

// Obtener productos
$result = $queryManager->getAllProducts();

if (!$result) {
    die("Error al obtener los productos");
}

$db->desconectar();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galería de Galletas</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="../../assets/img/logo.png" alt="Logo Empresa">
            <a href="index.php"><span style="color:#ff92b2;font-size:1.5rem;font-weight:bold;">Galería de Galletas</span></a>
        </div>
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

    <header class="hero innovador">
        <div class="blob blob1"></div>
        <div class="blob blob2"></div>
        <div class="hero-content">
            <h1>¡Bienvenido a la Galería de Galletas!</h1>
            <p>Descubre nuestras deliciosas galletas artesanales, hechas con amor y los mejores ingredientes.</p>
        </div>
    </header>

    <main>
        <section class="product-section">
            <div style="max-width:1200px;margin:auto;">
                <div style="display:flex;flex-wrap:wrap;gap:2rem;justify-content:center;margin-bottom:2.5rem;">
                    <div style="background:#fff;border-radius:16px;box-shadow:0 4px 10px rgba(0,0,0,0.08);padding:2rem 1.5rem;max-width:400px;flex:1 1 300px;min-width:260px;">
                        <h2 style="color:#c2185b;margin-bottom:1rem;">Misión</h2>
                        <p style="color:#333;font-size:1.05rem;">Ofrecer al consumidor galletas de la máxima
                            calidad, con la máxima frescura y a
                            precios justos. Para conseguirlo con rentabilidad,
                            trabajamos en la innovación en producto y la
                            búsqueda de la eficiencia operativa, para así
                            poder generar valor constante y un crecimiento
                            sostenible.</p>
                    </div>
                    <div style="background:#fff;border-radius:16px;box-shadow:0 4px 10px rgba(0,0,0,0.08);padding:2rem 1.5rem;max-width:400px;flex:1 1 300px;min-width:260px;">
                        <h2 style="color:#c2185b;margin-bottom:1rem;">Visión</h2>
                        <p style="color:#333;font-size:1.05rem;">Ser el referente a nivel nacional en galletas
                        horneados, cuyo reconocimiento provenga
                        tanto de consumidores, proveedores y la sociedad
                        y velando por una cadena alimentaria
                        sostenible.</p>
                    </div>
                </div>
                <h2>Nuestros Productos</h2>
                <div class="product-grid">
                    <?php while ($producto = $result->fetch_assoc()): ?>
                        <div class="product-card">
                            <img src="../assets/img/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" style="width:400px;height:400px;object-fit:cover;display:block">
                            <h3><?php echo htmlspecialchars_decode($producto['nombre']); ?></h3>
                            <p><?php echo htmlspecialchars_decode($producto['descripcion']); ?></p>
                            <p class="precio"><?= '$' . number_format($producto['precio'], 0, ',', '.') ?></p>
                            <?php if (isset($_SESSION['usuario_id'])): ?>
                                <form class="add-cart-form" action="../controllers/carrito.php" method="POST" style="margin-top: 1rem;">
                                    <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                    <label style="font-size:0.95rem;">Tamaño:</label>
                                    <select name="tamano" style="margin:0 0.5rem 0 0.5rem;">
                                        <option value="normal">Normal</option>
                                        <option value="jumbo">Jumbo</option>
                                    </select>
                                    <label style="font-size:0.95rem;">Presentación:</label>
                                    <select name="presentacion" style="margin:0 0.5rem 0 0.5rem;">
                                        <option value="unidad">Unidad</option>
                                        <option value="paquete3">Paquete de 3</option>
                                    </select>
                                    <input type="number" name="cantidad" value="1" min="1" style="width: 60px; margin-right: 0.5rem;">
                                    <button type="submit">Agregar al Carrito</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
    </main>
    <footer class="footer">
        <div class="social-icons">
            <a href="#"><img src="../assets/img/instagram.png" alt="Instagram"></a>
            <a href="#"><img src="../assets/img/facebook.png" alt="Facebook"></a>
            <a href="#"><img src="../assets/img/whatsapp.png" alt="WhatsApp"></a>
        </div>
        <p>© 2025 Galería de Galletas. Todos los derechos reservados.</p>
        <p>Iconos de <a href="https://icons8.com" target="_blank">Icons8</a></p>
    </footer>
    <div id="toast" class="toast" style="display:none;position:fixed;top:90px;right:30px;z-index:9999;background:#a14a7f;color:#fff;padding:16px 28px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.12);font-size:1.1rem;">Producto agregado al carrito</div>
    <script>
    // Toast de éxito
    function showToast(msg, isError = false) {
        var t = document.getElementById('toast');
        t.textContent = msg;
        t.style.backgroundColor = isError ? '#dc3545' : '#a14a7f';
        t.style.display = 'block';
        setTimeout(function(){ t.style.display = 'none'; }, 1220);
    }
    // Interceptar formularios de agregar al carrito
    var forms = document.querySelectorAll('.add-cart-form');
    forms.forEach(function(form){
        form.addEventListener('submit', function(e){
            e.preventDefault();
            var data = new FormData(form);
            fetch(form.action, {method:'POST', body:data})
            .then(r=>r.text())
            .then(response=>{
                if(response.includes('success')) {
                    showToast('Producto agregado al carrito');
                    actualizarCarrito();
                } else if(response.includes('error')) {
                    showToast('Error al agregar al carrito', true);
                }
            })
            .catch(error => {
                showToast('Error al procesar la solicitud', true);
            });
        });
    });
    // Actualizar numerito del carrito
    function actualizarCarrito() {
        fetch('carrito.php?count=1')
        .then(r=>r.json())
        .then(d=>{
            document.getElementById('cart-count').textContent = d.count>0 ? d.count : '';
        })
        .catch(error => {
            console.error('Error al actualizar el carrito:', error);
        });
    }
    document.addEventListener('DOMContentLoaded', actualizarCarrito);
    </script>
</body>
</html>
