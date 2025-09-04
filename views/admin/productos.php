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
$productos = $queryManager->getAllProductsIncludingInactive();

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

// Obtener el precio actual del paquete mixto (tomar el primero que encontremos como referencia)
$precio_mixto_actual = 75000; // valor por defecto
$stmt_mixto = $db->conexion->prepare("SELECT precio FROM precios_productos WHERE presentacion = 'paquete_mixto' LIMIT 1");
$stmt_mixto->execute();
$result_mixto = $stmt_mixto->get_result();
if ($row_mixto = $result_mixto->fetch_assoc()) {
    $precio_mixto_actual = $row_mixto['precio'];
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
    .estado-badge {
        padding: 0.3em 0.6em;
        border-radius: 4px;
        font-size: 0.9em;
        font-weight: 500;
    }
    .estado-badge.activo {
        background-color: #28a745;
        color: white;
    }
    .estado-badge.inactivo {
        background-color: #dc3545;
        color: white;
    }
    .btn-reactivar {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 0.5em 1em;
        border-radius: 4px;
        cursor: pointer;
    }
    .btn-reactivar:hover {
        background-color: #218838;
    }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="../../assets/img/Logo.png" alt="Logo Empresa">
            <a href="../index.php"><span style="color:#ff92b2;font-size:1.5rem;font-weight:bold;">Dulce Tentación</span></a>
        </div>
        <button class="hamburger" id="hamburger-btn" aria-label="Abrir menú">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <ul class="nav-links">
            <li><a href="admin.php">Admin</a></li>
            <li><a href="../carrito.php"><img src="../assets/img/carrito.png" alt="Carrito"> <span id="cart-count" class="cart-count"></span></a></li>
            <li><a href="../../controllers/logout.php">Cerrar Sesión</a></li>
        </ul>
    </nav>
    <div class="login-container" style="max-width:900px;">
        <h1>Gestión de Productos</h1>
        <a href="../admin/agregarProducto.php" class="btn" style="margin-bottom:1.5rem;display:inline-block;">+ Agregar Producto</a>
        
        <!-- Sección para editar precio del paquete mixto -->
        <div style="background:#f8f9fa;padding:1rem;border-radius:8px;margin-bottom:1.5rem;border:1px solid #dee2e6;">
            <h3 style="color:#a14a7f;margin-bottom:1rem;">Precio del Paquete Mixto</h3>
            <form style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;" onsubmit="actualizarPrecioMixto(event)">
                <label style="font-weight:500;">Precio actual:</label>
                <input type="number" id="precio_mixto" step="0.01" min="0" value="<?= $precio_mixto_actual ?>" style="padding:0.5rem;border:1px solid #ccc;border-radius:4px;width:120px;" required>
                <button type="submit" class="btn" style="background:#a14a7f;color:white;padding:0.5rem 1rem;border:none;border-radius:4px;cursor:pointer;">Actualizar</button>
                <small style="color:#666;">Este precio se aplicará a todos los productos</small>
            </form>
        </div>
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
                    <th>Estado</th>
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
                        <td>$<?= number_format($precio_mixto_actual, 0, ',', '.') ?> <small style="color:#a14a7f;">(Global)</small></td>
                        <td>
                            <span class="estado-badge <?php echo $producto['estado'] === 'activo' ? 'activo' : 'inactivo'; ?>">
                                <?php echo ucfirst($producto['estado']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="acciones-flex">
                                <button onclick="window.location.href='editarProducto.php?id=<?php echo $producto['id']; ?>'" class="btn-editar">Editar</button>
                                <?php if ($producto['estado'] === 'activo'): ?>
                                    <button onclick="confirmarEliminacion(<?php echo $producto['id']; ?>)" class="btn-eliminar">Desactivar</button>
                                <?php else: ?>
                                    <button onclick="confirmarReactivacion(<?php echo $producto['id']; ?>)" class="btn-reactivar">Reactivar</button>
                                <?php endif; ?>
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
            text: 'El producto se marcará como inactivo',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `../../controllers/eliminarProducto.php?id=${id}`;
            }
        });
    }

    function confirmarReactivacion(id) {
        Swal.fire({
            title: '¿Reactivar producto?',
            text: 'El producto volverá a estar disponible',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, reactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `../../controllers/reactivarProducto.php?id=${id}`;
            }
        });
    }

    function actualizarPrecioMixto(event) {
        event.preventDefault();
        const precio = document.getElementById('precio_mixto').value;
        
        if (precio <= 0) {
            Swal.fire({
                title: 'Error',
                text: 'El precio debe ser mayor a 0',
                icon: 'error',
                confirmButtonColor: '#a14a7f'
            });
            return;
        }

        Swal.fire({
            title: '¿Actualizar precio?',
            text: `El precio del paquete mixto se cambiará a $${new Intl.NumberFormat('es-CO').format(precio)} para todos los productos`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#a14a7f',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, actualizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Crear un formulario oculto para enviar la actualización
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../controllers/actualizarPrecioMixto.php';
                
                const inputPrecio = document.createElement('input');
                inputPrecio.type = 'hidden';
                inputPrecio.name = 'precio_mixto';
                inputPrecio.value = precio;
                
                form.appendChild(inputPrecio);
                document.body.appendChild(form);
                form.submit();
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