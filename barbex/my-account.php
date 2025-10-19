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
    $profile_image = '';

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'assets/img/profiles/';

        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = $_FILES['profile_image']['name'];
        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_size = $_FILES['profile_image']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validate file type and size
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (in_array($file_ext, $allowed_ext) && $file_size <= $max_size) {
            // Generate unique filename
            $new_filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $profile_image = $new_filename;

                // Delete old profile image if exists
                if (!empty($user['profile_image'])) {
                    $old_file = $upload_dir . $user['profile_image'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
            } else {
                $message = '<div class="alert alert-danger">Error al subir la imagen</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Tipo de archivo no válido o archivo demasiado grande (máx. 2MB)</div>';
        }
    }

    if (!empty($name)) {
        // Always include profile_image in the query, even if empty
        $query = "UPDATE users SET name = :name, phone = :phone, address = :address, profile_image = :profile_image WHERE id = :id";
        $params = [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'profile_image' => $profile_image ?: null, // Use null if no image
            'id' => $_SESSION['user_id']
        ];

        try {
            $stmt = $db->prepare($query);
            $result = $stmt->execute($params);

            if ($result) {
                $_SESSION['user_name'] = $name; // Update session name
                $message = '<div class="alert alert-success">Perfil actualizado correctamente</div>';

                // Re-fetch user data to reflect changes
                $query = "SELECT * FROM users WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->execute(['id' => $_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $message = '<div class="alert alert-danger">Error al actualizar el perfil. Inténtalo de nuevo.</div>';
            }
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            $message = '<div class="alert alert-danger">Error al actualizar el perfil: ' . $e->getMessage() . '</div>';
        }
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
                                <li class="menu-item-has-children"><a href="#">Home</a>
                                    <ul class="sub-menu">
                                        <li><a href="index.html">Home 01</a></li>
                                        <li><a href="index-2.html">Home 02</a></li>
                                        <li><a href="index-3.html">Home 03</a></li>
                                    </ul>
                                </li>
                                <li class="menu-item-has-children"><a href="#">Pages</a>
                                    <ul class="sub-menu">
                                        <li><a href="about.html">About</a></li>
                                        <li><a href="price.html">Price</a></li>
                                        <li><a href="team.html">Team</a></li>
                                        <li><a href="services.html">Services</a></li>
                                        <li><a href="services-details.html">Services Details</a></li>
                                    </ul>
                                </li>
                                <li class="menu-item-has-children"><a href="#">Shop</a>
                                    <ul class="sub-menu">
                                        <li><a href="product-page.php">Product Page</a></li>
                                        <li><a href="product-details.php">Product Details</a></li>
                                        <li><a href="cart.php">Cart</a></li>
                                        <li><a href="checkout.php">Checkout</a></li>
                                    </ul>
                                </li>
                                <li class="menu-item-has-children"><a href="#">Blog</a>
                                    <ul class="sub-menu">
                                        <li><a href="blog-grid.html">Blog Grid</a></li>
                                        <li><a href="blog-standard.html">Blog Standard</a></li>
                                        <li><a href="blog-details.html">Blog Details</a></li>
                                    </ul>
                                </li>
                                <li><a href="contact.html">Contact</a></li>
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
                                <li><a href="index.html">Inicio</a></li>
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
                            <a class="nav-link active" href="#dashboard" onclick="showSection('dashboard', this)">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            <a class="nav-link" href="#orders" onclick="showSection('orders', this)">
                                <i class="fas fa-shopping-bag"></i> Mis Pedidos
                            </a>
                            <a class="nav-link" href="#profile" onclick="showSection('profile', this)">
                                <i class="fas fa-user"></i> Mi Perfil
                            </a>
                            <a class="nav-link" href="#password" onclick="showSection('password', this)">
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
                            <form method="POST" enctype="multipart/form-data" class="mt-4">
                                <input type="hidden" name="update_profile" value="1">

                                <!-- Profile Image Section -->
                                <div class="text-center mb-4">
                                    <div class="profile-image-container">
                                        <?php if (!empty($user['profile_image'])): ?>
                                            <img src="assets/img/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>"
                                                 alt="Foto de perfil" class="profile-image rounded-circle"
                                                 style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #667eea;">
                                        <?php else: ?>
                                            <div class="profile-placeholder rounded-circle d-inline-flex align-items-center justify-content-center"
                                                 style="width: 120px; height: 120px; background: #f8f9fa; border: 3px solid #667eea;">
                                                <i class="fas fa-user fa-3x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-3">
                                        <label for="profile_image" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-camera"></i> Cambiar Foto
                                        </label>
                                        <input type="file" id="profile_image" name="profile_image" class="d-none"
                                               accept="image/*" onchange="previewImage(this)">
                                        <small class="text-muted d-block mt-1">Formatos: JPG, PNG, GIF. Máx: 2MB</small>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-info">
                                            <?php if (!empty($user['profile_image'])): ?>
                                                <i class="fas fa-check-circle"></i> Foto de perfil subida
                                            <?php else: ?>
                                                <i class="fas fa-info-circle"></i> No tienes foto de perfil
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>

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
                        <p>Copyright © 2022<a href="index.html"> ThemeOri</a> Website by Barbex</p>
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
        function showSection(sectionName, clickedElement = null) {
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

            // Set active class on clicked element or find the corresponding nav link
            if (clickedElement) {
                clickedElement.classList.add('active');
            } else {
                // Find the nav link that corresponds to the section
                const navLink = document.querySelector(`.account-sidebar .nav-link[href="#${sectionName}"]`);
                if (navLink) {
                    navLink.classList.add('active');
                }
            }
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

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];

                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('El archivo es demasiado grande. Máximo 2MB.');
                    input.value = '';
                    return;
                }

                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Tipo de archivo no válido. Solo se permiten JPG, PNG y GIF.');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    // Update the profile image preview
                    const container = document.querySelector('.profile-image-container');
                    if (container.querySelector('.profile-image')) {
                        container.querySelector('.profile-image').src = e.target.result;
                    } else if (container.querySelector('.profile-placeholder')) {
                        // Replace placeholder with actual image
                        container.innerHTML = '<img src="' + e.target.result + '" alt="Vista previa" class="profile-image rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #667eea;">';
                    }
                };
                reader.readAsDataURL(file);
            }
        }

        // Show dashboard by default
        document.addEventListener('DOMContentLoaded', function() {
            showSection('dashboard');
        });
    </script>
</body>
</html>