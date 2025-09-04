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

// Paginación
$pedidos_por_pagina = 10;
$total_pedidos = $queryManager->getTotalOrdersCount($estado);
$total_paginas = ceil($total_pedidos / $pedidos_por_pagina);
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina_actual = max(1, min($pagina_actual, $total_paginas > 0 ? $total_paginas : 1)); // Asegura que la página actual sea válida
$offset = ($pagina_actual - 1) * $pedidos_por_pagina;

// Obtener pedidos y detalles
$pedidos = $queryManager->getAllOrders($estado, $pedidos_por_pagina, $offset);
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
    <script src="../../assets/js/sweetalert2.all.min.js"></script>
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
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin-top: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .table th, .table td {
            padding: 0.7em 0.5em;
            border: 1px solid #e0e0e0;
            text-align: left;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
            border-radius: 6px;
        }
        .bg-warning { background: #ffe082; color: #a14a7f; }
        .bg-success { background: #4caf50; color: #fff; }
        .btn-group {
            display: flex;
            gap: 0.5rem;
        }
        /* Modal personalizado */
        .modal-custom {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0; width: 100%; height: 100%;
            overflow: auto; background: rgba(0,0,0,0.4);
        }
        .modal-content-custom {
            background: #fff; margin: 5% auto; padding: 20px; border-radius: 12px;
            width: 90%; max-width: 700px; position: relative;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
        }
        .close {
            color: #a14a7f; position: absolute; right: 20px; top: 10px; font-size: 2rem; cursor: pointer;
        }
        .modal-content-custom h5 {
            margin-top: 0;
        }
        .modal-content-custom .row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 1.2rem;
        }
        .modal-content-custom .col-md-6 {
            flex: 1 1 50%;
            min-width: 200px;
        }
        @media (max-width: 600px) {
            .modal-content-custom { padding: 10px; }
            .modal-content-custom .row { flex-direction: column; }
            .modal-content-custom .col-md-6 { min-width: 100%; }
        }
        .btn-completar {
            background: #b2ffb3;
            color: #218838;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            font-weight: bold;
            box-shadow: 0 2px 6px rgba(33,136,56,0.08);
            margin-right: 0.5rem;
            display: inline-block;
        }
        .btn-completar:hover {
            background: #28a745;
            color: #fff;
        }
        @media (max-width: 900px) {
            .login-container { padding: 0; }
            .table { font-size: 0.97em; }
        }
        @media (max-width: 700px) {
            .login-container { padding: 0; }
            .table th, .table td { padding: 0.4em 0.2em; font-size: 0.93em; }
            .btn-completar, .btn-rosa {
                width: 100%;
                margin-bottom: 0.4em;
                font-size: 0.98em;
                padding: 10px 0;
                box-sizing: border-box;
            }
            .btn-group {
                flex-direction: column;
                gap: 0.2em;
                align-items: stretch;
            }
        }
        @media (max-width: 500px) {
            .table th, .table td { font-size: 0.90em; }
            h1 { font-size: 1.1em; }
            .login-container { padding: 0; }
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .btn-pagination {
            padding: 8px 16px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            color: #495057;
            text-decoration: none;
            transition: all 0.3s ease;
            min-width: 40px;
            text-align: center;
        }

        .btn-pagination:hover {
            background-color: #e9ecef;
            border-color: #ced4da;
        }

        .btn-pagination.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        /* Estilos para el modal de detalles */
        .modal-details {
            max-width: 800px;
            width: 95%;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .modal-details h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .modal-details .order-info {
            margin-bottom: 20px;
        }

        .modal-details .order-info p {
            margin: 5px 0;
            color: #666;
        }

        .modal-details .order-items {
            margin-top: 20px;
        }

        .modal-details .order-items h3 {
            color: #444;
            margin-bottom: 15px;
        }

        .modal-details .order-items table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .modal-details .order-items th,
        .modal-details .order-items td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .modal-details .order-items th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .modal-details .order-total {
            margin-top: 20px;
            text-align: right;
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }

        .modal-details .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 5px;
        }

        .modal-details .close-button:hover {
            color: #333;
        }

        @media (max-width: 768px) {
            .modal-details {
                width: 100%;
                margin: 10px;
                padding: 15px;
            }

            .modal-details .order-items table {
                display: block;
                overflow-x: auto;
            }

            .modal-details .order-items th,
            .modal-details .order-items td {
                padding: 8px;
                font-size: 14px;
            }

            .pagination {
                gap: 4px;
            }

            .btn-pagination {
                padding: 6px 12px;
                font-size: 14px;
            }
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
        <li><a href="../carrito.php"><img src="../assets/img/carrito.png" alt="Carrito"></a></li>
        <li><a href="../../controllers/logout.php">Cerrar Sesión</a></li>
    </ul>
</nav>
<div class="login-container" style="max-width:1000px;">
    <h1>Gestión de Pedidos</h1>
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
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $modals = '';
                    // Reiniciar el puntero del resultado para asegurar que recorremos todos los pedidos
                    $pedidos->data_seek(0);
                    while ($pedido = $pedidos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $pedido['id']; ?></td>
                            <td><?php echo htmlspecialchars($pedido['usuario']); ?></td>
                            <td><?php echo $pedido['fecha']; ?></td>
                            <td>
                                <span class="badge <?php echo $pedido['estado'] === 'pendiente' ? 'bg-warning' : 'bg-success'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($pedido['estado'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($pedido['direccion']); ?></td>
                            <td><?php echo htmlspecialchars($pedido['telefono']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if ($pedido['estado'] === 'pendiente'): ?>
                                        <button type="button"
                                                class="btn-completar"
                                                onclick="return confirmarCambioEstado(event, <?php echo $pedido['id']; ?>)">
                                            Completar
                                        </button>
                                    <?php endif; ?>
                                    <button type="button"
                                            class="btn btn-sm btn-rosa btn-ver-detalles"
                                            data-id="modal-<?php echo $pedido['id']; ?>">
                                        Ver detalles
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php
                    // Acumula el modal personalizado en una variable
                    ob_start();
                    ?>
                    <div id="modal-<?php echo $pedido['id']; ?>" class="modal-custom">
                        <div class="modal-content-custom">
                            <span class="close" onclick="cerrarModal('modal-<?php echo $pedido['id']; ?>')">&times;</span>
                            <h5>Detalles del Pedido #<?php echo $pedido['id']; ?></h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['usuario']); ?></p>
                                    <p><strong>Fecha:</strong> <?php echo $pedido['fecha']; ?></p>
                                    <p><strong>Estado:</strong> <?php echo ucfirst(htmlspecialchars($pedido['estado'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Dirección:</strong> <?php echo htmlspecialchars($pedido['direccion']); ?></p>
                                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($pedido['telefono']); ?></p>
                                </div>
                            </div>
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
                                        <th colspan="5" class="text-end">Total:</th>
                                        <th>$<?php echo number_format($total, 0, ',', '.'); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <?php
                    $modals .= ob_get_clean();
                    endwhile;
                    ?>
                </tbody>
            </table>

            <!-- Paginación -->
            <div class="pagination">
                <?php if ($total_paginas > 1): ?>
                    <?php if ($pagina_actual > 1): ?>
                        <a href="?pagina=1<?php echo $estado !== 'todos' ? '&estado=' . $estado : ''; ?>" class="btn-pagination" title="Primera página">&laquo;&laquo;</a>
                        <a href="?pagina=<?php echo ($pagina_actual - 1); ?><?php echo $estado !== 'todos' ? '&estado=' . $estado : ''; ?>" class="btn-pagination" title="Página anterior">&laquo;</a>
                    <?php endif; ?>

                    <?php
                    $inicio = max(1, $pagina_actual - 2);
                    $fin = min($total_paginas, $pagina_actual + 2);
                    
                    if ($inicio > 1) {
                        echo '<span class="btn-pagination">...</span>';
                    }
                    
                    for ($i = $inicio; $i <= $fin; $i++): ?>
                        <a href="?pagina=<?php echo $i; ?><?php echo $estado !== 'todos' ? '&estado=' . $estado : ''; ?>" 
                           class="btn-pagination <?php echo $i === $pagina_actual ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor;
                    
                    if ($fin < $total_paginas) {
                        echo '<span class="btn-pagination">...</span>';
                    }
                    ?>

                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a href="?pagina=<?php echo ($pagina_actual + 1); ?><?php echo $estado !== 'todos' ? '&estado=' . $estado : ''; ?>" class="btn-pagination" title="Página siguiente">&raquo;</a>
                        <a href="?pagina=<?php echo $total_paginas; ?><?php echo $estado !== 'todos' ? '&estado=' . $estado : ''; ?>" class="btn-pagination" title="Última página">&raquo;&raquo;</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Aquí, fuera de la tabla, imprime todos los modales personalizados -->
            <?php echo $modals; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info" role="alert">
            No hay pedidos registrados.
        </div>
    <?php endif; ?>
</div>
<script>
    function confirmarCambioEstado(event, pedidoId) {
        event.preventDefault();
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¿Deseas marcar este pedido como completado?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Sí, completar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../controllers/actualizar_estado_pedido.php?id=' + pedidoId + '&estado=completado';
            }
        });
    }

    function abrirModal(modalId) {
        console.log('Attempting to open modal:', modalId);
        document.getElementById(modalId).style.display = 'block';
    }

    function cerrarModal(modalId) {
        console.log('Attempting to close modal:', modalId);
        document.getElementById(modalId).style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        var detailButtons = document.querySelectorAll('.btn-ver-detalles');
        console.log('Found detail buttons:', detailButtons.length);
        detailButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var modalId = this.getAttribute('data-id');
                console.log('Detail button clicked for modal:', modalId);
                abrirModal(modalId);
            });
        });
    });

    // Opcional: Cerrar modal haciendo clic fuera de él
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-custom')) {
            event.target.style.display = "none";
        }
    }

    document.getElementById('hamburger-btn').addEventListener('click', function() {
        document.querySelector('.nav-links').classList.toggle('open');
    });
    document.getElementById('filtroTabla').addEventListener('keyup', function() {
        var filtro = this.value.toLowerCase();
        var filas = document.querySelectorAll('.table-responsive tbody tr');
        filas.forEach(function(fila) {
            var texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(filtro) ? '' : 'none';
        });
    });
</script>
</body>
</html> 