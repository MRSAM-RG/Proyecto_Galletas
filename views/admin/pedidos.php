<?php
require_once '../../models/MySQL.php';
require_once '../../models/QueryManager.php';
require_once '../../config/security.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Filtro de estado
$estado = isset($_GET['estado']) ? $_GET['estado'] : 'todos';

$db = new MySQL();
$db->conectar();
$queryManager = new QueryManager($db);

// Obtener pedidos y detalles
$pedidos = $queryManager->getAllOrders($estado);
if ($pedidos === false) {
    $error = "Error al obtener los pedidos. Por favor, intente nuevamente.";
} else {
    $detalles = $queryManager->getAllOrderDetails();
    if ($detalles === false) {
        $error = "Error al obtener los detalles de los pedidos. Por favor, intente nuevamente.";
    } else {
        // Agrupar detalles por pedido_id
        $mapa_detalles = [];
        while ($row = $detalles->fetch_assoc()) {
            $mapa_detalles[$row['pedido_id']][] = $row;
        }
    }
}

$db->desconectar();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/pedidos.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .btn-rosa {
            background: #ffb3c6;
            color: #a14a7f;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            font-weight: bold;
            box-shadow: 0 2px 6px rgba(161,74,127,0.08);
        }
        .btn-rosa:hover {
            background: #ff92b2;
            color: #fff;
        }
    </style>
</head>
<body>
<nav class="custom-navbar">
        <div class="custom-logo">
            <img src="../../assets/img/logo.png" alt="Logo Empresa">
            <a href="../index.php"><span style="color:#ff92b2;font-size:1.5rem;font-weight:bold;">Galería de Galletas</span></a>
        </div>
        <ul class="custom-nav-links">
            <li><a href="admin.php">Admin</a></li>
            <li><a href="../carrito.php">Carrito</a></li>
            <li><a href="../../controllers/logout.php">Cerrar Sesión</a></li>
        </ul>
    </nav>
    <div class="login-container" style="max-width:1000px;">
        <h1>Gestión de Pedidos</h1>
        <form class="filtro-form" method="get" action="">
            <label for="estado">Filtrar por estado:</label>
            <select name="estado" id="estado">
                <option value="todos" <?php if($estado==='todos') echo 'selected'; ?>>Todos</option>
                <option value="pendiente" <?php if($estado==='pendiente') echo 'selected'; ?>>Pendiente</option>
                <option value="completado" <?php if($estado==='completado') echo 'selected'; ?>>Completado</option>
            </select>
            <br>
            <br>
            <button type="submit">Filtrar</button>
        </form>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        <?php if ($pedidos && $pedidos->num_rows > 0): ?>
            <?php $modalIndex = 0; while ($pedido = $pedidos->fetch_assoc()): ?>
                <div class="pedido-box">
                    <div class="pedido-header">
                        <span><b>ID Pedido:</b> <?php echo $pedido['id']; ?></span>
                        <span><b>Cliente:</b> <?php echo htmlspecialchars($pedido['usuario']); ?></span>
                        <span><b>Fecha:</b> <?php echo $pedido['fecha']; ?></span>
                        <span><b>Estado:</b> <?php echo htmlspecialchars($pedido['estado']); ?></span>
                        <span><b>Dirección:</b> <?php echo htmlspecialchars($pedido['direccion']); ?></span>
                        <span><b>Teléfono:</b> <?php echo htmlspecialchars($pedido['telefono']); ?></span>
                        <?php if ($pedido['estado'] === 'pendiente'): ?>
                            <a href="../../controllers/cambiar_estado_pedido.php?id=<?php echo $pedido['id']; ?>" class="btn-completar" onclick="return confirm('¿Marcar este pedido como completado?')">Marcar como Completado</a>
                        <?php endif; ?>
                        <button type="button" class="btn btn-rosa" data-bs-toggle="modal" data-bs-target="#modalDetalles<?php echo $modalIndex; ?>">Ver detalles</button>
                    </div>
                </div>
                <!-- Modal Detalles Pedido -->
                <div class="modal fade" id="modalDetalles<?php echo $modalIndex; ?>" tabindex="-1" aria-labelledby="modalDetallesLabel<?php echo $modalIndex; ?>" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalDetallesLabel<?php echo $modalIndex; ?>">Detalles del Pedido #<?php echo $pedido['id']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                      </div>
                      <div class="modal-body">
                        <table class="table table-bordered table-striped">
                          <thead>
                            <tr>
                              <th>Producto</th>
                              <th>Cantidad</th>
                              <th>Precio</th>
                              <th>Tamaño</th>
                              <th>Presentación</th>
                              <th>Subtotal</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $total = 0;
                            if (isset($mapa_detalles[$pedido['id']])):
                                foreach ($mapa_detalles[$pedido['id']] as $detalle):
                                    $subtotal = $detalle['cantidad'] * $detalle['precio'];
                                    $total += $subtotal;
                            ?>
                                <tr>
                                  <td><?php echo htmlspecialchars_decode($detalle['producto']); ?></td>
                                  <td><?php echo $detalle['cantidad']; ?></td>
                                  <td>$<?php echo number_format($detalle['precio'], 0, ',', '.'); ?></td>
                                  <td><?php echo htmlspecialchars($detalle['tamano']); ?></td>
                                  <td><?php echo htmlspecialchars($detalle['presentacion'] === 'unidad' ? 'Unidad' : 'Paquete de 3'); ?></td>
                                  <td>$<?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                          </tbody>
                          <tfoot>
                            <tr>
                              <th colspan="5" style="text-align:right;">Total:</th>
                              <th>$<?php echo number_format($total, 0, ',', '.'); ?></th>
                            </tr>
                          </tfoot>
                        </table>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                      </div>
                    </div>
                  </div>
                </div>
                <?php $modalIndex++; endwhile; ?>
        <?php else: ?>
            <div class="error" style="margin:2rem 0;">No hay pedidos registrados.</div>
        <?php endif; ?>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 