<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle actions
$message = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        // Add new product
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $category = trim($_POST['category']);
        $stock = intval($_POST['stock']);
        $sku = trim($_POST['sku']) ?: 'PRD-' . time();

        if (empty($name) || $price <= 0) {
            $message = '<div class="alert alert-danger">Nombre y precio son obligatorios</div>';
        } else {
            $query = "INSERT INTO products (name, description, price, category, stock, sku) VALUES (:name, :description, :price, :category, :stock, :sku)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":price", $price);
            $stmt->bindParam(":category", $category);
            $stmt->bindParam(":stock", $stock);
            $stmt->bindParam(":sku", $sku);

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Producto agregado exitosamente</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al agregar producto</div>';
            }
        }
    } elseif (isset($_POST['edit_product'])) {
        // Edit product
        $id = intval($_POST['product_id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $category = trim($_POST['category']);
        $stock = intval($_POST['stock']);
        $status = $_POST['status'];

        if (empty($name) || $price <= 0) {
            $message = '<div class="alert alert-danger">Nombre y precio son obligatorios</div>';
        } else {
            $query = "UPDATE products SET name = :name, description = :description, price = :price, category = :category, stock = :stock, status = :status WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":price", $price);
            $stmt->bindParam(":category", $category);
            $stmt->bindParam(":stock", $stock);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Producto actualizado exitosamente</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al actualizar producto</div>';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $query = "DELETE FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);

    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Producto eliminado exitosamente</div>';
    } else {
        $message = '<div class="alert alert-danger">Error al eliminar producto</div>';
    }
}

// Get products for listing
$query = "SELECT * FROM products ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get product for editing
$edit_product = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT * FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Productos - BarbeX Admin</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/all.css">
    <style>
        .admin-header { background: #333; color: white; padding: 20px 0; }
        .admin-sidebar { background: #f8f9fa; min-height: calc(100vh - 76px); border-right: 1px solid #dee2e6; }
        .admin-sidebar .nav-link { color: #333; padding: 12px 20px; border-radius: 0; }
        .admin-sidebar .nav-link:hover { background: #e9ecef; color: #667eea; }
        .admin-sidebar .nav-link.active { background: #667eea; color: white; }
        .content-area { padding: 30px; }
        .btn-action { margin: 0 2px; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4><i class="fas fa-box"></i> Gestionar Productos - BarbeX</h4>
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
                    <a class="nav-link active" href="products.php">
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
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-area">
                <?php echo $message; ?>

                <!-- Action Buttons -->
                <div class="mb-4">
                    <?php if ($action == 'list'): ?>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Agregar Producto
                        </a>
                    <?php else: ?>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a la Lista
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($action == 'add' || $action == 'edit'): ?>
                    <!-- Add/Edit Product Form -->
                    <div class="card">
                        <div class="card-header">
                            <h5><?php echo $action == 'add' ? 'Agregar Nuevo Producto' : 'Editar Producto'; ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <?php if ($action == 'edit'): ?>
                                    <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                                <?php endif; ?>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nombre *</label>
                                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">SKU</label>
                                            <input type="text" class="form-control" name="sku" value="<?php echo htmlspecialchars($edit_product['sku'] ?? ''); ?>" placeholder="Se generará automáticamente">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Precio *</label>
                                            <input type="number" step="0.01" class="form-control" name="price" value="<?php echo $edit_product['price'] ?? ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Stock</label>
                                            <input type="number" class="form-control" name="stock" value="<?php echo $edit_product['stock'] ?? 0; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Categoría</label>
                                            <input type="text" class="form-control" name="category" value="<?php echo htmlspecialchars($edit_product['category'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <?php if ($action == 'edit'): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Estado</label>
                                        <select class="form-control" name="status">
                                            <option value="active" <?php echo ($edit_product['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Activo</option>
                                            <option value="inactive" <?php echo ($edit_product['status'] ?? 'active') == 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <button type="submit" name="<?php echo $action == 'add' ? 'add_product' : 'edit_product'; ?>" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo $action == 'add' ? 'Guardar Producto' : 'Actualizar Producto'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Products List -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Lista de Productos</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Imagen</th>
                                            <th>Nombre</th>
                                            <th>SKU</th>
                                            <th>Categoría</th>
                                            <th>Precio</th>
                                            <th>Stock</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><?php echo $product['id']; ?></td>
                                            <td>
                                                <?php if ($product['image']): ?>
                                                    <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div style="width: 50px; height: 50px; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                                            <td><?php echo $product['stock']; ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                                    <?php echo ucfirst($product['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning btn-action">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('¿Estás seguro de eliminar este producto?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>