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
$productos = $queryManager->getAllProducts(false);

// Obtener precios para todos los productos
$precios = [];
$stmt = $db->conexion->prepare("SELECT producto_id, tamano, presentacion, precio FROM precios_productos");
$stmt->execute();
$result_precios = $stmt->get_result();
while ($row = $result_precios->fetch_assoc()) {
    if (!isset($precios[$row['producto_id']])) $precios[$row['producto_id']] = [];
    if (!isset($precios[$row['producto_id']][$row['tamano']])) $precios[$row['producto_id']][$row['tamano']] = [];
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
    <script src="../../assets/js/sweetalert2.all.min.js"></script>
    <style>
    /* Responsive table */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        margin-bottom: 1.5rem;
    }
    table {
        width: 100%;
        min-width: 700px;
        border-collapse: collapse;
    }
    th, td {
        padding: 0.7em 0.5em;
        text-align: left;
        white-space: nowrap;
    }
    .acciones-flex {
        display: flex;
        gap: 0.5em;
        justify-content: flex-start;
        align-items: center;
    }
    @media (max-width: 900px) {
        table { min-width: 600px; }
        .login-container { padding: 0; }
    }
    @media (max-width: 600px) {
        table { min-width: 500px; font-size: 0.95em; }
        .login-container { padding: 0; }
        th, td { padding: 0.5em 0.3em; }
        .acciones-flex {
            flex-direction: column;
            gap: 0.3em;
            align-items: stretch;
        }
        .btn-editar, .btn-eliminar { width: 100%; margin-bottom: 0; }
    }
    </style>
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
        <?php if (isset($_GET['error'])): ?>
            <div class="error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio Normal</th>
                    <th>Precio Paq. 3 Normal</th>
                    <th>Precio Paq. Mixto Normal</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($producto = $productos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $producto['id']; ?></td>
                        <td><img src="../../assets/img/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" style="width:50px;height:50px;object-fit:cover;"></td>
                        <td><?php echo htmlspecialchars_decode($producto['nombre']); ?></td>
                        <td><?php echo htmlspecialchars_decode($producto['descripcion']); ?></td>
                        <td>$<?= isset($precios[$producto['id']]['normal']['unidad']) ? number_format($precios[$producto['id']]['normal']['unidad'], 0, ',', '.') : 'N/A' ?></td>
                        <td>$<?= isset($precios[$producto['id']]['normal']['paquete3']) ? number_format($precios[$producto['id']]['normal']['paquete3'], 0, ',', '.') : 'N/A' ?></td>
                        <td>$<?= isset($precios[$producto['id']]['normal']['paquete_mixto']) ? number_format($precios[$producto['id']]['normal']['paquete_mixto'], 0, ',', '.') : 'N/A' ?></td>
                        <td>
                            <div class="acciones-flex">
                                <button onclick="window.location.href='editarProducto.php?id=<?php echo $producto['id']; ?>'" class="btn-editar">Editar</button>
                                <button onclick="confirmarEliminacion(<?php echo $producto['id']; ?>)" class="btn-eliminar">Eliminar</button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
    <script>
    function confirmarEliminacion(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `../../controllers/eliminarProducto.php?id=${id}`;
            }
        });
    }

    // Mostrar mensajes de éxito o error
    <?php if (isset($_GET['success'])): ?>
    Swal.fire({
        title: '¡Éxito!',
        text: '<?php echo htmlspecialchars($_GET['success']); ?>',
        icon: 'success',
        confirmButtonColor: '#a14a7f'
    });
    <?php endif; ?>

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