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

// Handle category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $status = $_POST['status'] ?? 'active';

    if (!empty($name)) {
        $query = "INSERT INTO categories (name, description, status, created_at) VALUES (:name, :description, :status, NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'status' => $status
        ]);

        header("Location: categories.php?created=1");
        exit();
    }
}

// Handle category update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $id = (int)$_POST['category_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $status = $_POST['status'] ?? 'active';

    if (!empty($name)) {
        $query = "UPDATE categories SET name = :name, description = :description, status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'status' => $status
        ]);

        header("Location: categories.php?updated=1");
        exit();
    }
}

// Handle category deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Check if category has products
    $check_query = "SELECT COUNT(*) as count FROM products WHERE category_id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute(['id' => $id]);
    $product_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($product_count > 0) {
        header("Location: categories.php?error=cannot_delete");
        exit();
    }

    $query = "DELETE FROM categories WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute(['id' => $id]);

    header("Location: categories.php?deleted=1");
    exit();
}

// Get all categories
$query = "SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $query = "SELECT * FROM categories WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute(['id' => $edit_id]);
    $edit_category = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías - BarbeX</title>
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
        .table-responsive {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8rem;
        }
        .category-form {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4><i class="fas fa-tags"></i> Gestión de Categorías - BarbeX</h4>
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
                    <a class="nav-link active" href="categories.php">
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
            <div class="col-md-10 py-4">
                <?php if (isset($_GET['created'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Categoría creada correctamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Categoría actualizada correctamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Categoría eliminada correctamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['error']) && $_GET['error'] === 'cannot_delete'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> No se puede eliminar la categoría porque tiene productos asociados.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Create/Edit Category Form -->
                <div class="category-form">
                    <h5><?php echo $edit_category ? 'Editar Categoría' : 'Crear Nueva Categoría'; ?></h5>
                    <form method="POST">
                        <?php if ($edit_category): ?>
                        <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                        <input type="hidden" name="update_category" value="1">
                        <?php else: ?>
                        <input type="hidden" name="create_category" value="1">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre de la Categoría *</label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?php echo ($edit_category['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Activa</option>
                                    <option value="inactive" <?php echo ($edit_category['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactiva</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $edit_category ? 'Actualizar' : 'Crear'; ?> Categoría
                            </button>
                            <?php if ($edit_category): ?>
                            <a href="categories.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Categories Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Productos</th>
                                <th>Estado</th>
                                <th>Fecha de Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($category['description'] ?? '', 0, 50)) . (strlen($category['description'] ?? '') > 50 ? '...' : ''); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $category['product_count']; ?> productos</span>
                                </td>
                                <td>
                                    <span class="badge status-badge bg-<?php
                                        echo $category['status'] == 'active' ? 'success' : 'secondary';
                                    ?>">
                                        <?php echo $category['status'] == 'active' ? 'Activa' : 'Inactiva'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($category['created_at'])); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $category['id']; ?>" class="btn btn-sm btn-warning me-1">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <?php if ($category['product_count'] == 0): ?>
                                    <a href="?delete=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger"
                                       onclick="return confirm('¿Estás seguro de eliminar esta categoría?')">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled title="No se puede eliminar porque tiene productos">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (empty($categories)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay categorías</h5>
                        <p class="text-muted">Crea tu primera categoría usando el formulario de arriba.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>