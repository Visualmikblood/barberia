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

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $role = $_POST['role'] ?? 'customer';
    $status = $_POST['status'] ?? 'active';

    if (!empty($name) && !empty($email) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (name, email, password, phone, address, role, status) VALUES (:name, :email, :password, :phone, :address, :role, :status)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => $hashed_password,
            'phone' => $phone,
            'address' => $address,
            'role' => $role,
            'status' => $status
        ]);

        header("Location: users.php?created=1");
        exit();
    }
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id = (int)$_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $role = $_POST['role'] ?? 'customer';
    $status = $_POST['status'] ?? 'active';

    // Handle password change
    $password_update = '';
    if (!empty($_POST['password'])) {
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $password_update = ", password = :password";
    }

    if (!empty($name) && !empty($email)) {
        $query = "UPDATE users SET name = :name, email = :email, phone = :phone, address = :address, role = :role, status = :status{$password_update} WHERE id = :id";
        $stmt = $db->prepare($query);
        $params = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'role' => $role,
            'status' => $status
        ];

        if (!empty($_POST['password'])) {
            $params['password'] = $hashed_password;
        }

        $stmt->execute($params);

        header("Location: users.php?updated=1");
        exit();
    }
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Prevent deletion of the current admin user
    if ($id == $_SESSION['user_id']) {
        header("Location: users.php?error=cannot_delete_self");
        exit();
    }

    $query = "DELETE FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute(['id' => $id]);

    header("Location: users.php?deleted=1");
    exit();
}

// Get filter parameters
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($role_filter)) {
    $query .= " AND role = :role";
    $params['role'] = $role_filter;
}

if (!empty($status_filter)) {
    $query .= " AND status = :status";
    $params['status'] = $status_filter;
}

if (!empty($search)) {
    $query .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

$query .= " ORDER BY created_at DESC";

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$count_query = str_replace("SELECT * FROM users WHERE 1=1", "SELECT COUNT(*) as total FROM users WHERE 1=1", $query);
$count_query = str_replace(" ORDER BY created_at DESC", "", $count_query);

$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params);
$total_users = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_users / $per_page);

$query .= " LIMIT :limit OFFSET :offset";
$params['limit'] = $per_page;
$params['offset'] = $offset;

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key === 'limit' || $key === 'offset' ? $key : ":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user for editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute(['id' => $edit_id]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - BarbeX</title>
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
        .user-form {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .filters {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
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
                    <h4><i class="fas fa-users"></i> Gestión de Usuarios - BarbeX</h4>
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
                    <a class="nav-link" href="orders.php">
                        <i class="fas fa-shopping-cart"></i> Pedidos
                    </a>
                    <a class="nav-link active" href="users.php">
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
                    <i class="fas fa-check-circle"></i> Usuario creado correctamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Usuario actualizado correctamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Usuario eliminado correctamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['error']) && $_GET['error'] === 'cannot_delete_self'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> No puedes eliminar tu propia cuenta de administrador.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="filters">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Buscar</label>
                            <input type="text" name="search" class="form-control" placeholder="Nombre, email o teléfono"
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Rol</label>
                            <select name="role" class="form-select">
                                <option value="">Todos</option>
                                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Cliente</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select">
                                <option value="">Todos</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Activo</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                                <a href="users.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <a href="?action=add" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Nuevo Usuario
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if (isset($_GET['action']) && $_GET['action'] === 'add' || $edit_user): ?>
                    <!-- Create/Edit User Form -->
                    <div class="user-form">
                        <h5><?php echo $edit_user ? 'Editar Usuario' : 'Crear Nuevo Usuario'; ?></h5>
                        <form method="POST">
                            <?php if ($edit_user): ?>
                            <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                            <input type="hidden" name="update_user" value="1">
                            <?php else: ?>
                            <input type="hidden" name="create_user" value="1">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nombre Completo *</label>
                                    <input type="text" name="name" class="form-control" required
                                           value="<?php echo htmlspecialchars($edit_user['name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="email" class="form-control" required
                                           value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" name="phone" class="form-control"
                                           value="<?php echo htmlspecialchars($edit_user['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Rol</label>
                                    <select name="role" class="form-select">
                                        <option value="customer" <?php echo ($edit_user['role'] ?? 'customer') === 'customer' ? 'selected' : ''; ?>>Cliente</option>
                                        <option value="admin" <?php echo ($edit_user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Dirección</label>
                                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($edit_user['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Estado</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?php echo ($edit_user['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Activo</option>
                                        <option value="inactive" <?php echo ($edit_user['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><?php echo $edit_user ? 'Nueva Contraseña (opcional)' : 'Contraseña *'; ?></label>
                                    <input type="password" name="password" class="form-control"
                                           <?php echo !$edit_user ? 'required' : ''; ?> placeholder="<?php echo $edit_user ? 'Dejar vacío para mantener la actual' : ''; ?>">
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo $edit_user ? 'Actualizar' : 'Crear'; ?> Usuario
                                </button>
                                <a href="users.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Users List -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Fecha de Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php
                                            echo $user['role'] == 'admin' ? 'danger' : 'info';
                                        ?>">
                                            <?php echo $user['role'] == 'admin' ? 'Admin' : 'Cliente'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge status-badge bg-<?php
                                            echo $user['status'] == 'active' ? 'success' : 'secondary';
                                        ?>">
                                            <?php echo $user['status'] == 'active' ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="?edit=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning me-1" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger"
                                           onclick="return confirm('¿Estás seguro de eliminar este usuario?')" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php if (empty($users)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay usuarios</h5>
                            <p class="text-muted">No se encontraron usuarios con los filtros aplicados.</p>
                        </div>
                        <?php endif; ?>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="User pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>