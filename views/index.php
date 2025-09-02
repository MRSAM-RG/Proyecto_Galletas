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

// Obtener precios para todos los productos
$precios = [];
$stmt = $db->conexion->prepare("SELECT producto_id, tamano, precio FROM precios_productos WHERE presentacion = 'unidad'");
$stmt->execute();
$result_precios = $stmt->get_result();
while ($row = $result_precios->fetch_assoc()) {
    $precios[$row['producto_id']][$row['tamano']] = $row['precio'];
}

$db->desconectar();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dulce Tentación</title>
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

    <header class="hero innovador" style="background: url('../assets/img/fondo.png') center center / cover no-repeat;">
        <div class="hero-content">
            <h1>¡Bienvenido a la galeria de galletas!</h1>
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
                            <div class="precios-container">
                                <p class="precio">Normal: <span id="precio-<?= $producto['id'] ?>">$<?= isset($precios[$producto['id']]['normal']) ? number_format($precios[$producto['id']]['normal'], 0, ',', '.') : 'N/A' ?></span></p>
                            </div>
                            <?php if (isset($_SESSION['usuario_id'])): ?>
                                <form class="add-cart-form" action="../controllers/carrito.php" method="POST" style="margin-top: 1rem;">
                                    <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                    <label style="font-size:0.95rem;">Presentación:</label>
                                    <select name="presentacion" style="margin:0 0.5rem 0 0.5rem;" onchange="actualizarPrecio(<?php echo $producto['id']; ?>)">
                                        <option value="unidad">Unidad</option>
                                        <option value="paquete3">Paquete de 3</option>
                                        <option value="paquete_mixto">Paquete Mixto</option>
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
    <section class="contact-section">
        <div style="max-width: 800px; margin: 0 auto; padding: 0 1rem;">
            <h2 style="text-align: center; color: #c2185b; margin-bottom: 2rem;">Contáctanos</h2>
            <form id="contactForm" action="../controllers/contact.php" method="POST" style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.08);">
                <div style="margin-bottom: 1rem;">
                    <label for="name" style="display: block; margin-bottom: 0.5rem; color: #333;">Nombre</label>
                    <input type="text" id="name" name="name" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label for="email" style="display: block; margin-bottom: 0.5rem; color: #333;">Email</label>
                    <input type="email" id="email" name="email" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label for="subject" style="display: block; margin-bottom: 0.5rem; color: #333;">Asunto</label>
                    <input type="text" id="subject" name="subject" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label for="message" style="display: block; margin-bottom: 0.5rem; color: #333;">Mensaje</label>
                    <textarea id="message" name="message" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 6px; min-height: 150px;"></textarea>
                </div>
                <button type="submit" style="background: #c2185b; color: white; padding: 1rem 2rem; border: none; border-radius: 6px; cursor: pointer; width: 100%; font-size: 1.1rem;">Enviar Mensaje</button>
            </form>
        </div>
    </section>
    <footer class="footer">
        <div class="social-icons">
            <a href="https://www.instagram.com/mariana_go08?igsh=MW40d2JnZjZ2M3E3"><img src="../assets/img/instagram.png" alt="Instagram"></a>
            <a href="https://wa.me/573173953818"><img src="../assets/img/whatsapp.png" alt="WhatsApp"></a>
        </div>
        <p>© 2025 Dulce Tentación. Todos los derechos reservados.</p>
        <p>Iconos de <a href="https://icons8.com" target="_blank">Icons8</a></p>
    </footer>
    <div id="toast" class="toast" style="display:none;position:fixed;top:90px;right:30px;z-index:9999;background:#a14a7f;color:#fff;padding:16px 28px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.12);font-size:1.1rem;">Producto agregado al carrito</div>
</body>
<script>
document.getElementById('hamburger-btn').addEventListener('click', function() {
    document.querySelector('.nav-links').classList.toggle('open');
});
// Mostrar toast si el producto fue agregado
if (window.location.search.includes('added=1')) {
    var toast = document.getElementById('toast');
    if (toast) {
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 2500);
    }
}
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
// AJAX para agregar al carrito sin recargar
if (document.querySelectorAll('.add-cart-form').length) {
    document.querySelectorAll('.add-cart-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            fetch('../controllers/carrito.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    var toast = document.getElementById('toast');
                    if (toast) {
                        toast.style.display = 'block';
                        setTimeout(() => { 
                            toast.style.display = 'none';
                            window.location.reload();
                        }, 1200);
                    } else {
                        window.location.reload();
                    }
                    updateCartCount();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    Swal.fire({
                        icon: data.limit_exceeded ? 'warning' : 'error',
                        title: data.limit_exceeded ? 'Límite de galletas excedido' : '¡Error!',
                        text: data.error || 'No hay suficiente stock disponible para este producto.',
                        confirmButtonColor: '#a14a7f'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un error al agregar el producto al carrito.',
                    confirmButtonColor: '#a14a7f'
                });
            });
        });
    });
}
function actualizarPrecio(productoId) {
    const presentacion = document.querySelector(`.product-card input[name='producto_id'][value='${productoId}']`).closest('.product-card').querySelector("select[name='presentacion']").value;
    fetch(`../controllers/obtenerPrecio.php?producto_id=${productoId}&tamano=normal&presentacion=${presentacion}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById(`precio-${productoId}`).textContent = '$' + new Intl.NumberFormat('es-CO').format(data.precio);
            }
        });
}
// Llamar a actualizarPrecio al cargar la página para cada producto
window.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.product-card').forEach(card => {
        const id = card.querySelector('input[name="producto_id"]').value;
        actualizarPrecio(id);
    });
});

// Manejo del formulario de contacto
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('../controllers/contact.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.includes('Mensaje enviado correctamente')) {
            Swal.fire({
                title: '¡Mensaje Enviado!',
                text: 'Gracias por contactarnos. Te responderemos pronto.',
                icon: 'success',
                confirmButtonColor: '#a14a7f'
            }).then(() => {
                this.reset();
            });
        } else {
            Swal.fire({
                title: 'Error',
                text: 'Hubo un error al enviar el mensaje. Por favor, intenta nuevamente.',
                icon: 'error',
                confirmButtonColor: '#a14a7f'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Error',
            text: 'Hubo un error al enviar el mensaje. Por favor, intenta nuevamente.',
            icon: 'error',
            confirmButtonColor: '#a14a7f'
        });
    });
});
</script>
<?php if (isset($_GET['stock_error'])): ?>
<script>
Swal.fire({
    icon: 'error',
    title: '¡Stock insuficiente!',
    text: 'No hay suficiente stock disponible para este producto.',
    confirmButtonColor: '#a14a7f'
});
</script>
<?php endif; ?>
</html>
