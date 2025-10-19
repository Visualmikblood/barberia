<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle profile update
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (!empty($name)) {
        $query = "UPDATE users SET name = :name, phone = :phone, address = :address WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'id' => $_SESSION['user_id']
        ]);

        $_SESSION['user_name'] = $name; // Update session name
        $message = '<div class="alert alert-success">Perfil actualizado correctamente</div>';
    } else {
        $message = '<div class="alert alert-danger">El nombre es obligatorio</div>';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Get current user data
    $query = "SELECT password FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password && strlen($new_password) >= 6) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                'password' => $hashed_password,
                'id' => $_SESSION['user_id']
            ]);
            $message = '<div class="alert alert-success">Contraseña cambiada correctamente</div>';
        } else {
            $message = '<div class="alert alert-danger">Las contraseñas no coinciden o son muy cortas (mínimo 6 caracteres)</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Contraseña actual incorrecta</div>';
    }
}

// Get user data
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user orders
$orders_query = "SELECT o.*, COUNT(oi.id) as item_count
                 FROM orders o
                 LEFT JOIN order_items oi ON o.id = oi.order_id
                 WHERE o.user_id = :user_id
                 GROUP BY o.id
                 ORDER BY o.created_at DESC";
$orders_stmt = $db->prepare($orders_query);
$orders_stmt->execute(['user_id' => $_SESSION['user_id']]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - BarbeX</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/all.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/css/magnific-popup.css">
    <link rel="stylesheet" href="assets/css/meanmenu.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .account-sidebar {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .account-sidebar .nav-link {
            color: #333;
            padding: 12px 15px;
            margin-bottom: 5px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .account-sidebar .nav-link:hover {
            background: #e9ecef;
            color: #667eea;
        }
        .account-sidebar .nav-link.active {
            background: #667eea;
            color: white;
        }
        .account-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .order-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
        }
        .order-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipped { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <!-- Header Area Start -->
    <div class="header__sticky">
        <div class="header__area two">
            <div class="container custom__container">
                <div class="header__area-menubar">
                    <div class="header__area-menubar-left">
                        <div class="header__area-menubar-left-logo">
                            <a href="index.php"><img src="assets/img/logo-2.png" alt=""></a>
                            <div class="responsive-menu"></div>
                        </div>
                    </div>
                    <div class="header__area-menubar-right two">
                        <div class="header__area-menubar-right-menu menu-responsive">
                            <ul id="mobilemenu">
                                <li><a href="index.php">Inicio</a></li>
                                <li><a href="product-page.php">Productos</a></li>
                                <li><a href="services.html">Servicios</a></li>
                                <li><a href="contact.html">Contacto</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="header__area-menubar-right-box">
                        <div class="header__area-menubar-right-box-cart">
                            <a href="cart.php" class="header__area-menubar-right-box-cart-link">
                                <i class="fal fa-shopping-cart"></i>
                                <span class="cart-count">0</span>
                            </a>
                        </div>
                        <div class="header__area-menubar-right-box-btn">
                            <a href="logout.php" class="theme-border-btn">Cerrar Sesión<i class="far fa-angle-double-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Header Area End -->

    <!-- Page Banner Start -->
    <div class="page__banner" data-background="assets/img/bg/page.jpg">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    <div class="page__banner-title">
                        <h1>Mi Cuenta</h1>
                        <div class="page__banner-title-menu">
                            <ul>
                                <li><a href="index.php">Inicio</a></li>
                                <li><span>_</span>Mi Cuenta</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Page Banner End -->

    <!-- Account Area Start -->
    <div class="account__area section-padding">
        <div class="container">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-xl-3 col-lg-4">
                    <div class="account-sidebar">
                        <h5>Hola, <?php echo htmlspecialchars($user['name']); ?>!</h5>
                        <nav class="nav flex-column">
                            <a class="nav-link active" href="#dashboard" onclick="showSection('dashboard')">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            <a class="nav-link" href="#orders" onclick="showSection('orders')">
                                <i class="fas fa-shopping-bag"></i> Mis Pedidos
                            </a>
                            <a class="nav-link" href="#profile" onclick="showSection('profile')">
                                <i class="fas fa-user"></i> Mi Perfil
                            </a>
                            <a class="nav-link" href="#password" onclick="showSection('password')">
                                <i class="fas fa-key"></i> Cambiar Contraseña
                            </a>
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-xl-9 col-lg-8">
                    <div class="account-content">
                        <?php echo $message; ?>

                        <!-- Dashboard Section -->
                        <div id="dashboard-section" class="account-section">
                            <h4>Dashboard</h4>
                            <div class="row mt-4">
                                <div class="col-md-4">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <i class="fas fa-shopping-bag fa-2x text-primary mb-2"></i>
                                            <h5><?php echo count($orders); ?></h5>
                                            <p class="text-muted">Total de Pedidos</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <i class="fas fa-calendar fa-2x text-success mb-2"></i>
                                            <h5><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></h5>
                                            <p class="text-muted">Miembro desde</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <i class="fas fa-user fa-2x text-info mb-2"></i>
                                            <h5><?php echo ucfirst($user['role']); ?></h5>
                                            <p class="text-muted">Tipo de Cuenta</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Orders Section -->
                        <div id="orders-section" class="account-section" style="display: none;">
                            <h4>Mis Pedidos</h4>
                            <?php if (empty($orders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No tienes pedidos aún</h5>
                                <p class="text-muted">¡Empieza a comprar nuestros productos!</p>
                                <a href="product-page.php" class="btn btn-primary">Ver Productos</a>
                            </div>
                            <?php else: ?>
                            <div class="row">
                                <?php foreach ($orders as $order): ?>
                                <div class="col-12">
                                    <div class="order-card">
                                        <div class="row align-items-center">
                                            <div class="col-md-2">
                                                <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                            </div>
                                            <div class="col-md-2">
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                                </small>
                                            </div>
                                            <div class="col-md-2">
                                                <span class="order-status status-<?php echo $order['order_status']; ?>">
                                                    <?php echo ucfirst($order['order_status']); ?>
                                                </span>
                                            </div>
                                            <div class="col-md-2">
                                                <strong>$<?php echo number_format($order['total'], 2); ?></strong>
                                            </div>
                                            <div class="col-md-2">
                                                <?php echo $order['item_count']; ?> producto(s)
                                            </div>
                                            <div class="col-md-2">
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-eye"></i> Ver Detalles
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Profile Section -->
                        <div id="profile-section" class="account-section" style="display: none;">
                            <h4>Mi Perfil</h4>
                            <form method="POST" class="mt-4">
                                <input type="hidden" name="update_profile" value="1">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nombre Completo *</label>
                                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                        <small class="text-muted">El email no se puede cambiar</small>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Teléfono</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tipo de Cuenta</label>
                                        <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Dirección</label>
                                    <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Actualizar Perfil
                                </button>
                            </form>
                        </div>

                        <!-- Password Section -->
                        <div id="password-section" class="account-section" style="display: none;">
                            <h4>Cambiar Contraseña</h4>
                            <form method="POST" class="mt-4">
                                <input type="hidden" name="change_password" value="1">
                                <div class="mb-3">
                                    <label class="form-label">Contraseña Actual *</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nueva Contraseña *</label>
                                    <input type="password" name="new_password" class="form-control" required minlength="6">
                                    <small class="text-muted">Mínimo 6 caracteres</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirmar Nueva Contraseña *</label>
                                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Cambiar Contraseña
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Account Area End -->

    <!-- Footer -->
    <div class="footer__two">
        <div class="footer__area-shape">
            <img src="assets/img/shape/foorer.png" alt="">
        </div>
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    <div class="copyright__two-center">
                        <p>Copyright © 2022<a href="index.php"> ThemeOri</a> Website by Barbex</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Order Details -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
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

    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script>
        function showSection(sectionName) {
            // Hide all sections
            document.querySelectorAll('.account-section').forEach(section => {
                section.style.display = 'none';
            });

            // Show selected section
            document.getElementById(sectionName + '-section').style.display = 'block';

            // Update active nav link
            document.querySelectorAll('.account-sidebar .nav-link').forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        function viewOrderDetails(orderId) {
            // Load order details via AJAX
            fetch('api/order-details.php?order_id=' + orderId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('orderDetailsContent').innerHTML = data;
                    new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
                })
                .catch(error => {
                    console.error('Error loading order details:', error);
                    alert('Error al cargar los detalles del pedido');
                });
        }

        // Show dashboard by default
        document.addEventListener('DOMContentLoaded', function() {
            showSection('dashboard');
        });
    </script>
</body>
</html>