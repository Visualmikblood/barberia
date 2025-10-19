<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['order_status'];

    $query = "UPDATE orders SET order_status = :status, updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute(['status' => $new_status, 'id' => $order_id]);

    header("Location: orders.php?updated=1");
    exit();
}

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['payment_status'];

    $query = "UPDATE orders SET payment_status = :status, updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute(['status' => $new_status, 'id' => $order_id]);

    header("Location: orders.php?updated=1");
    exit();
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$query = "SELECT o.*, u.name as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE 1=1";

// If no user is found, use the customer_name from the order itself
$query = "SELECT o.*,
         COALESCE(u.name, o.customer_name) as customer_name
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $query .= " AND o.order_status = :status";
    $params['status'] = $status_filter;
}

if (!empty($date_filter)) {
    $query .= " AND DATE(o.created_at) = :date";
    $params['date'] = $date_filter;
}

if (!empty($search)) {
    $query .= " AND (o.order_number LIKE :search OR o.customer_name LIKE :search OR o.customer_email LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

$query .= " ORDER BY o.created_at DESC";

// Get orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$count_query = str_replace("SELECT o.*,
         COALESCE(u.name, o.customer_name) as customer_name
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id WHERE 1=1", "SELECT COUNT(*) as total FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE 1=1", $query);
$count_query = str_replace(" ORDER BY o.created_at DESC", "", $count_query);

$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_orders = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_orders / $per_page);

$query .= " LIMIT :limit OFFSET :offset";
$params['limit'] = $per_page;
$params['offset'] = $offset;

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key === 'limit' || $key === 'offset' ? $key : ":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order statistics
$stats_query = "SELECT
    COUNT(*) as total_orders,
    SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
    SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
    SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
    SUM(total) as total_revenue
    FROM orders";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$order_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - BarbeX</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/all.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-header {
            background: #333;
            color: white;
            padding: 20px 0;
        }
        .admin-sidebar {
            background: #f8f9fa;
            min-height: calc(100vh - 76px);
            border-right: 1px solid #dee2e6;
        }
        .admin-sidebar .nav-link {
            color: #333;
            padding: 12px 20px;
            border-radius: 0;
        }
        .admin-sidebar .nav-link:hover {
            background: #e9ecef;
            color: #667eea;
        }
        .admin-sidebar .nav-link.active {
            background: #667eea;
            color: white;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        .stat-card h4 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        .stat-card p {
            color: #6c757d;
            margin: 0;
        }
        .table-responsive {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8rem;
        }
        .order-details-modal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4><i class="fas fa-shopping-cart"></i> Gestión de Pedidos - BarbeX</h4>
                </div>
                <div class="col-md-6 text-end">
                    <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name']); ?> | </span>
                    <a href="../index.html" class="text-white me-3"><i class="fas fa-home"></i> Ver Sitio</a>
                    <a href="logout.php" class="text-white"><i class="fas fa-sign-out-alt"></i> Salir</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 admin-sidebar">
                <nav class="nav flex-column py-3">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-box"></i> Productos
                    </a>
                    <a class="nav-link" href="categories.php">
                        <i class="fas fa-tags"></i> Categorías
                    </a>
                    <a class="nav-link active" href="orders.php">
                        <i class="fas fa-shopping-cart"></i> Pedidos
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users"></i> Usuarios
                    </a>
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 py-4">
                <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Pedido actualizado correctamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h4><?php echo $order_stats['total_orders']; ?></h4>
                            <p><i class="fas fa-shopping-cart"></i> Total Pedidos</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h4><?php echo $order_stats['pending_orders']; ?></h4>
                            <p><i class="fas fa-clock"></i> Pendientes</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h4><?php echo $order_stats['processing_orders']; ?></h4>
                            <p><i class="fas fa-cog"></i> Procesando</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h4><?php echo $order_stats['shipped_orders']; ?></h4>
                            <p><i class="fas fa-truck"></i> Enviados</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h4><?php echo $order_stats['delivered_orders']; ?></h4>
                            <p><i class="fas fa-check-circle"></i> Entregados</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h4>$<?php echo number_format($order_stats['total_revenue'], 2); ?></h4>
                            <p><i class="fas fa-dollar-sign"></i> Ingresos Totales</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Estado del Pedido</label>
                                <select name="status" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Procesando</option>
                                    <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Enviado</option>
                                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Entregado</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fecha</label>
                                <input type="date" name="date" class="form-control" value="<?php echo $date_filter; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Buscar</label>
                                <input type="text" name="search" class="form-control" placeholder="Número de pedido, cliente, email..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                    <a href="orders.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Limpiar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Número de Pedido</th>
                                <th>Cliente</th>
                                <th>Email</th>
                                <th>Total</th>
                                <th>Estado del Pedido</th>
                                <th>Estado del Pago</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name'] ?: 'Cliente Anónimo'); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                <td>$<?php echo number_format($order['total'], 2); ?></td>
                                <td>
                                    <span class="badge status-badge bg-<?php
                                        echo $order['order_status'] == 'pending' ? 'warning' :
                                             ($order['order_status'] == 'processing' ? 'info' :
                                              ($order['order_status'] == 'shipped' ? 'primary' :
                                               ($order['order_status'] == 'delivered' ? 'success' : 'secondary')));
                                    ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge status-badge bg-<?php
                                        echo $order['payment_status'] == 'paid' ? 'success' :
                                             ($order['payment_status'] == 'pending' ? 'warning' : 'danger');
                                    ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info me-1" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-warning dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><h6 class="dropdown-header">Estado del Pedido</h6></li>
                                            <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'pending')">Pendiente</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'processing')">Procesando</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'shipped')">Enviado</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'delivered')">Entregado</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'cancelled')">Cancelado</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><h6 class="dropdown-header">Estado del Pago</h6></li>
                                            <li><a class="dropdown-item" href="#" onclick="updatePaymentStatus(<?php echo $order['id']; ?>, 'pending')">Pendiente</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updatePaymentStatus(<?php echo $order['id']; ?>, 'paid')">Pagado</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updatePaymentStatus(<?php echo $order['id']; ?>, 'failed')">Fallido</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No se encontraron pedidos</h5>
                        <p class="text-muted">No hay pedidos que coincidan con los filtros aplicados.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Orders pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&date=<?php echo urlencode($date_filter); ?>&search=<?php echo urlencode($search); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&date=<?php echo urlencode($date_filter); ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&date=<?php echo urlencode($date_filter); ?>&search=<?php echo urlencode($search); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade order-details-modal" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden forms for status updates -->
    <form id="updateOrderStatusForm" method="POST" style="display: none;">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="order_id" id="order_id_input">
        <input type="hidden" name="order_status" id="order_status_input">
    </form>

    <form id="updatePaymentStatusForm" method="POST" style="display: none;">
        <input type="hidden" name="update_payment" value="1">
        <input type="hidden" name="order_id" id="payment_order_id_input">
        <input type="hidden" name="payment_status" id="payment_status_input">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>

    <script>
        function viewOrderDetails(orderId) {
            fetch(`../api/order-details.php?order_id=${orderId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('orderDetailsContent').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
                })
                .catch(error => {
                    console.error('Error loading order details:', error);
                    alert('Error al cargar los detalles del pedido');
                });
        }

        function updateOrderStatus(orderId, status) {
            const statusText = {
                'pending': 'Pendiente',
                'processing': 'Procesando',
                'shipped': 'Enviado',
                'delivered': 'Entregado',
                'cancelled': 'Cancelado'
            };

            if (confirm(`¿Estás seguro de cambiar el estado del pedido a "${statusText[status]}"?`)) {
                document.getElementById('order_id_input').value = orderId;
                document.getElementById('order_status_input').value = status;
                document.getElementById('updateOrderStatusForm').submit();
            }
        }

        function updatePaymentStatus(orderId, status) {
            const statusText = {
                'pending': 'Pendiente',
                'paid': 'Pagado',
                'failed': 'Fallido'
            };

            if (confirm(`¿Estás seguro de cambiar el estado del pago a "${statusText[status]}"?`)) {
                document.getElementById('payment_order_id_input').value = orderId;
                document.getElementById('payment_status_input').value = status;
                document.getElementById('updatePaymentStatusForm').submit();
            }
        }
    </script>
</body>
</html>