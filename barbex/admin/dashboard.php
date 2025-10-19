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

// Get statistics
$stats = [];

// Total products
$query = "SELECT COUNT(*) as total FROM products";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total users
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total orders
$query = "SELECT COUNT(*) as total FROM orders";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total blog posts
try {
    $query = "SELECT COUNT(*) as total FROM blog_posts";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['blog_posts'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (Exception $e) {
    $stats['blog_posts'] = 0; // Default to 0 if table doesn't exist
}

// Recent orders
$query = "SELECT o.*, u.name as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Low stock products
$query = "SELECT * FROM products WHERE stock < 10 AND status = 'active' ORDER BY stock ASC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - BarbeX</title>
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
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        .stat-card h3 {
            font-size: 2rem;
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
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4><i class="fas fa-tachometer-alt"></i> Panel de Administración - BarbeX</h4>
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
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-box"></i> Productos
                    </a>
                    <a class="nav-link" href="categories.php">
                        <i class="fas fa-tags"></i> Categorías
                    </a>
                    <a class="nav-link" href="orders.php">
                        <i class="fas fa-shopping-cart"></i> Pedidos
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users"></i> Usuarios
                    </a>
                    <a class="nav-link" href="blog.php">
                        <i class="fas fa-blog"></i> Blog
                    </a>
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 py-4">
                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $stats['products']; ?></h3>
                            <p><i class="fas fa-box"></i> Productos Totales</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $stats['users']; ?></h3>
                            <p><i class="fas fa-users"></i> Clientes Registrados</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $stats['orders']; ?></h3>
                            <p><i class="fas fa-shopping-cart"></i> Pedidos Totales</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $stats['blog_posts']; ?></h3>
                            <p><i class="fas fa-blog"></i> Artículos del Blog</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Orders -->
                    <div class="col-md-8">
                        <div class="table-responsive">
                            <h5 class="mb-3"><i class="fas fa-clock"></i> Pedidos Recientes</h5>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name'] ?: 'Cliente Anónimo'); ?></td>
                                        <td>$<?php echo number_format($order['total'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                echo $order['order_status'] == 'pending' ? 'warning' :
                                                     ($order['order_status'] == 'processing' ? 'info' :
                                                      ($order['order_status'] == 'shipped' ? 'primary' :
                                                       ($order['order_status'] == 'delivered' ? 'success' : 'secondary')));
                                            ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Low Stock Alert -->
                    <div class="col-md-4">
                        <div class="table-responsive">
                            <h5 class="mb-3"><i class="fas fa-exclamation-triangle text-warning"></i> Stock Bajo</h5>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td>
                                            <span class="badge bg-danger"><?php echo $product['stock']; ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($low_stock)): ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-success">
                                            <i class="fas fa-check-circle"></i> Todos los productos tienen stock suficiente
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <h5 class="mb-3"><i class="fas fa-bolt"></i> Acciones Rápidas</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <a href="products.php?action=add" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-plus"></i><br>Agregar Producto
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="categories.php" class="btn btn-success btn-lg w-100">
                                        <i class="fas fa-tags"></i><br>Gestionar Categorías
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="orders.php" class="btn btn-warning btn-lg w-100">
                                        <i class="fas fa-list"></i><br>Ver Pedidos
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="users.php" class="btn btn-info btn-lg w-100">
                                        <i class="fas fa-users"></i><br>Gestionar Usuarios
                                    </a>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <a href="blog.php" class="btn btn-secondary btn-lg w-100">
                                        <i class="fas fa-blog"></i><br>Gestionar Blog
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>