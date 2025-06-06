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
    <style>
        /* Remove Bootstrap button styles */
        /* .btn-rosa {
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
        } */

        /* Add styles for the table, search input, and status */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden; /* Ensures rounded corners apply to the table */
        }

        .orders-table th, .orders-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .orders-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }

        .orders-table tbody tr:hover {
            background-color: #f9f9f9;
        }

        .orders-table td .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.9em;
            color: #fff;
            display: inline-block;
        }

        .orders-table td .status.completado {
            background-color: #4CAF50; /* Green */
        }

        .orders-table td .status.pendiente {
             background-color: #ff9800; /* Orange */
        }

        .orders-table td .status.cancelado {
             background-color: #f44336; /* Red */
        }

        .orders-table td .btn-details {
            background: #ffb3c6;
            color: #a14a7f;
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            font-weight: bold;
            box-shadow: 0 1px 4px rgba(161,74,127,0.08);
            text-decoration: none; /* Remove underline from link */
            display: inline-block; /* Allows padding and margin */
            text-align: center;
        }

        .orders-table td .btn-details:hover {
            background: #ff92b2;
            color: #fff;
        }

        .search-input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box; /* Include padding in width */
            font-size: 1rem;
        }
        .filter-search-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .filter-form {
            margin-bottom: 0; /* Adjust margin if needed */
        }

        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top, higher than other elements */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; /* 5% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            max-width: 700px;
            border-radius: 10px;
            position: relative;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-body {
            margin-top: 20px;
        }

        .modal-body h2 {
            color: #a14a7f;
            margin-top: 15px;
            margin-bottom: 10px;
            font-size: 1.3rem;
        }

        .modal-body table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .modal-body table th, .modal-body table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .modal-body table th {
            background-color: #f2f2f2;
        }

        .modal-body table tfoot td {
            font-weight: bold;
            text-align: right;
        }
        .modal-body table tfoot td:last-child {
            text-align: left;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .filter-search-container {
                flex-direction: column; /* Stack filter and search vertically */
                align-items: stretch;
            }

            .filter-form {
                width: 100%; /* Make filter form take full width */
                margin-bottom: 15px; /* Add space below filter form */
            }

            .search-input {
                width: 100% !important; /* Make search input take full width */
            }

            /* Enable horizontal scrolling for tables on small screens */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
            }

            .modal-content {
                width: 95%; /* Adjust modal width on smaller screens */
                margin: 20px auto; /* Adjust margin */
            }
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

    <div class="login-container" style="max-width:1000px;">
        <h1>Gestión de Pedidos</h1>

        <div class="filter-search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Buscar en pedidos..." style="width: 50%;">
        </div>

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
                <table class="orders-table">
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
                    <tbody id="ordersTableBody">
                        <?php // Move the data fetching loop outside the table to access data by ID easier
                        $pedidos_data = [];
                        while ($pedido = $pedidos->fetch_assoc()) {
                            $pedidos_data[$pedido['id']] = $pedido;
                        }
                        ?>
                        <?php foreach ($pedidos_data as $pedido_id => $pedido): // Loop through the collected data ?>
                            <tr>
                                <td><?php echo $pedido['id']; ?></td>
                                <td><?php echo htmlspecialchars($pedido['usuario']); ?></td>
                                <td><?php echo $pedido['fecha']; ?></td>
                                <td><span class="status <?php echo strtolower($pedido['estado']); ?>"><?php echo htmlspecialchars($pedido['estado']); ?></span></td>
                                <td><?php echo htmlspecialchars($pedido['direccion']); ?></td>
                                <td><?php echo htmlspecialchars($pedido['telefono']); ?></td>
                                <td>
                                    <?php if ($pedido['estado'] === 'pendiente'): ?>
                                        <a href="../../controllers/cambiar_estado_pedido.php?id=<?php echo $pedido['id']; ?>" class="btn-completar" onclick="return confirm('¿Marcar este pedido como completado?')" style="margin-right: 10px;">Completar</a>
                                    <?php endif; ?>
                                    <button class="btn-details" data-order-id="<?php echo $pedido['id']; ?>">Ver detalles</button>
                                </td>
                            </tr>
                        <?php endforeach; // Close the foreach loop ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="error" style="margin:2rem 0;">No hay pedidos registrados.</div>
        <?php endif; ?>
    </div>

    <!-- The Modal -->
    <div id="orderModal" class="modal">
        <!-- Modal content -->
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-body" id="modalBody">
                <!-- Order details will be loaded here by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Hamburger menu functionality
            const hamburgerBtn = document.getElementById('hamburger-btn');
            const navLinks = document.querySelector('.nav-links');

            if (hamburgerBtn && navLinks) {
                hamburgerBtn.addEventListener('click', function() {
                    navLinks.classList.toggle('open');
                });
            }

            // Cart count update (optional, depending on if needed in admin)
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


            // Search functionality
            const searchInput = document.getElementById('searchInput');
            const ordersTableBody = document.getElementById('ordersTableBody');
            // Check if ordersTableBody exists before getting rows
            const rows = ordersTableBody ? ordersTableBody.getElementsByTagName('tr') : [];

            if (searchInput && ordersTableBody) {
                searchInput.addEventListener('keyup', function() {
                    const filter = searchInput.value.toLowerCase();
                    for (let i = 0; i < rows.length; i++) {
                        const row = rows[i];
                        const cells = row.getElementsByTagName('td');
                        let foundMatch = false;
                        for (let j = 0; j < cells.length; j++) {
                            const cell = cells[j];
                            if (cell) {
                                // Use textContent for better compatibility
                                const textValue = cell.textContent || cell.innerText;
                                if (textValue.toLowerCase().indexOf(filter) > -1) {
                                    foundMatch = true;
                                    break;
                                }
                            }
                        }
                        if (foundMatch) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    }
                });
            }

            // Modal functionality
            const modal = document.getElementById("orderModal");
            const modalBody = document.getElementById("modalBody");
            const closeButton = document.querySelector("#orderModal .close"); // More specific selector
            const detailButtons = document.querySelectorAll(".btn-details");

            // Embed PHP order details into a JavaScript variable
            const allOrderDetails = <?php echo json_encode($mapa_detalles); ?>;
            // Pass the main order data as well, keyed by ID for easy access
            const ordersMap = <?php echo json_encode($pedidos_data); ?>;

            detailButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-order-id');
                    const orderDetails = allOrderDetails[orderId];
                    const orderMainInfo = ordersMap[orderId];

                    if (orderDetails && orderMainInfo) {
                        let modalContentHtml = `
                            <h2>Detalles del Pedido #${orderMainInfo.id}</h2>
                            <p><strong>Cliente:</strong> ${orderMainInfo.usuario}</p>
                            <p><strong>Fecha:</strong> ${orderMainInfo.fecha}</p>
                            <p><strong>Estado:</strong> <span class="status ${orderMainInfo.estado.toLowerCase()}">${orderMainInfo.estado}</span></p>
                            <p><strong>Dirección:</strong> ${orderMainInfo.direccion}</p>
                            <p><strong>Teléfono:</strong> ${orderMainInfo.telefono}</p>

                            <h2>Productos del Pedido</h2>
                        `;

                        if (orderDetails.length > 0) {
                            modalContentHtml += `
                                <table class="details-table">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Cantidad</th>
                                            <th>Precio Unitario</th>
                                            <th>Tamaño</th>
                                            <th>Presentación</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                            `;

                            let total = 0;
                            orderDetails.forEach(detail => {
                                const subtotal = detail.cantidad * detail.precio;
                                total += subtotal;
                                modalContentHtml += `
                                    <tr>
                                        <td>${detail.producto}</td>
                                        <td>${detail.cantidad}</td>
                                        <td>$${parseFloat(detail.precio).toLocaleString('es-CO')}</td> <!-- Format as currency -->
                                        <td>${detail.tamano}</td>
                                        <td>${detail.presentacion === 'unidad' ? 'Unidad' : 'Paquete de 3'}</td>
                                        <td>$${subtotal.toLocaleString('es-CO')}</td> <!-- Format as currency -->
                                    </tr>
                                `;
                            });

                            modalContentHtml += `
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" style="text-align:right;">Total:</td>
                                            <td>$${total.toLocaleString('es-CO')}</td> <!-- Format as currency -->
                                        </tr>
                                    </tfoot>
                                </table>
                            `;
                        } else {
                            modalContentHtml += '<p>No hay detalles de productos para este pedido.</p>';
                        }

                        modalBody.innerHTML = modalContentHtml;
                        modal.style.display = "block"; // Show the modal
                    }
                });
            });

            // When the user clicks on (x), close the modal
            if (closeButton) {
                closeButton.onclick = function() {
                    modal.style.display = "none";
                }
            }

            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        });
    </script>
</body>
</html> 