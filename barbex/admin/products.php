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

// Handle actions
$message = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        // Handle image uploads
        $image_path = '';
        $gallery_paths = [];

        // Upload main image
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
            $upload_dir = '../assets/img/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
            $file_name = 'product_' . time() . '_main.' . $file_extension;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_path)) {
                $image_path = 'assets/img/products/' . $file_name;
            }
        }

        // Upload gallery images
        if (isset($_FILES['gallery_files']) && !empty($_FILES['gallery_files']['name'][0])) {
            $upload_dir = '../assets/img/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            foreach ($_FILES['gallery_files']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['gallery_files']['error'][$key] == 0) {
                    $file_extension = pathinfo($_FILES['gallery_files']['name'][$key], PATHINFO_EXTENSION);
                    $file_name = 'product_' . time() . '_gallery_' . $key . '.' . $file_extension;
                    $target_path = $upload_dir . $file_name;

                    if (move_uploaded_file($tmp_name, $target_path)) {
                        $gallery_paths[] = 'assets/img/products/' . $file_name;
                    }
                }
            }
        }

        // Add new product
        $name = trim($_POST['name']);
        $short_description = trim($_POST['short_description']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $sale_price = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
        $category_id = intval($_POST['category_id']);
        $stock = intval($_POST['stock']);
        $sku = trim($_POST['sku']);
        $stock_status = $_POST['stock_status'] ?? 'instock';
        $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
        $dimensions = trim($_POST['dimensions']);
        $brand = trim($_POST['brand']);
        $tags = trim($_POST['tags']);
        $gallery_images = !empty($gallery_paths) ? json_encode($gallery_paths) : null;
        $featured = isset($_POST['featured']) ? 1 : 0;

        if (empty($name) || $price <= 0 || empty($sku)) {
            $message = '<div class="alert alert-danger">Nombre, precio y SKU son obligatorios</div>';
        } else {
            $query = "INSERT INTO products (name, short_description, description, price, sale_price, category_id, stock, sku, stock_status, weight, dimensions, brand, tags, image, gallery_images, featured) VALUES (:name, :short_description, :description, :price, :sale_price, :category_id, :stock, :sku, :stock_status, :weight, :dimensions, :brand, :tags, :image, :gallery_images, :featured)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":short_description", $short_description);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":price", $price);
            $stmt->bindParam(":sale_price", $sale_price);
            $stmt->bindParam(":category_id", $category_id);
            $stmt->bindParam(":stock", $stock);
            $stmt->bindParam(":sku", $sku);
            $stmt->bindParam(":stock_status", $stock_status);
            $stmt->bindParam(":weight", $weight);
            $stmt->bindParam(":dimensions", $dimensions);
            $stmt->bindParam(":brand", $brand);
            $stmt->bindParam(":tags", $tags);
            $stmt->bindParam(":image", $image_path);
            $stmt->bindParam(":gallery_images", $gallery_images);
            $stmt->bindParam(":featured", $featured);

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Producto agregado exitosamente</div>';
            } else {
                $message = '<div class="alert alert-danger">Error al agregar producto</div>';
            }
        }
    } elseif (isset($_POST['edit_product'])) {
        // Handle image uploads for edit
        $image_path = $edit_product['image'] ?? ''; // Keep existing image if no new one uploaded
        $gallery_paths = [];

        // Upload main image
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
            $upload_dir = '../assets/img/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
            $file_name = 'product_' . time() . '_main.' . $file_extension;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_path)) {
                $image_path = 'assets/img/products/' . $file_name;
            }
        }

        // Upload gallery images (append to existing)
        $existing_gallery = json_decode($edit_product['gallery_images'] ?? '[]', true) ?: [];
        if (isset($_FILES['gallery_files']) && !empty($_FILES['gallery_files']['name'][0])) {
            $upload_dir = '../assets/img/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            foreach ($_FILES['gallery_files']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['gallery_files']['error'][$key] == 0) {
                    $file_extension = pathinfo($_FILES['gallery_files']['name'][$key], PATHINFO_EXTENSION);
                    $file_name = 'product_' . time() . '_gallery_' . $key . '.' . $file_extension;
                    $target_path = $upload_dir . $file_name;

                    if (move_uploaded_file($tmp_name, $target_path)) {
                        $existing_gallery[] = 'assets/img/products/' . $file_name;
                    }
                }
            }
        }

        // Edit product
        $id = intval($_POST['product_id']);
        $name = trim($_POST['name']);
        $short_description = trim($_POST['short_description']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $sale_price = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
        $category_id = intval($_POST['category_id']);
        $stock = intval($_POST['stock']);
        $sku = trim($_POST['sku']);
        $stock_status = $_POST['stock_status'] ?? 'instock';
        $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
        $dimensions = trim($_POST['dimensions']);
        $brand = trim($_POST['brand']);
        $tags = trim($_POST['tags']);
        $gallery_images = !empty($existing_gallery) ? json_encode($existing_gallery) : null;
        $featured = isset($_POST['featured']) ? 1 : 0;
        $status = $_POST['status'];

        if (empty($name) || $price <= 0 || empty($sku)) {
            $message = '<div class="alert alert-danger">Nombre, precio y SKU son obligatorios</div>';
        } else {
            $query = "UPDATE products SET name = :name, short_description = :short_description, description = :description, price = :price, sale_price = :sale_price, category_id = :category_id, stock = :stock, sku = :sku, stock_status = :stock_status, weight = :weight, dimensions = :dimensions, brand = :brand, tags = :tags, image = :image, gallery_images = :gallery_images, featured = :featured, status = :status WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":short_description", $short_description);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":price", $price);
            $stmt->bindParam(":sale_price", $sale_price);
            $stmt->bindParam(":category_id", $category_id);
            $stmt->bindParam(":stock", $stock);
            $stmt->bindParam(":sku", $sku);
            $stmt->bindParam(":stock_status", $stock_status);
            $stmt->bindParam(":weight", $weight);
            $stmt->bindParam(":dimensions", $dimensions);
            $stmt->bindParam(":brand", $brand);
            $stmt->bindParam(":tags", $tags);
            $stmt->bindParam(":image", $image_path);
            $stmt->bindParam(":gallery_images", $gallery_images);
            $stmt->bindParam(":featured", $featured);
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
                            <form method="POST" enctype="multipart/form-data" id="productForm">
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
                                            <label class="form-label">SKU *</label>
                                            <input type="text" class="form-control" name="sku" value="<?php echo htmlspecialchars($edit_product['sku'] ?? 'PRD-' . time()); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Descripción Corta</label>
                                    <input type="text" class="form-control" name="short_description" value="<?php echo htmlspecialchars($edit_product['short_description'] ?? ''); ?>" placeholder="Breve descripción para mostrar en la tienda">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Descripción Completa</label>
                                    <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Precio Regular *</label>
                                            <input type="number" step="0.01" class="form-control" name="price" value="<?php echo $edit_product['price'] ?? ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Precio de Oferta</label>
                                            <input type="number" step="0.01" class="form-control" name="sale_price" value="<?php echo $edit_product['sale_price'] ?? ''; ?>" placeholder="Opcional">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Stock *</label>
                                            <input type="number" class="form-control" name="stock" value="<?php echo $edit_product['stock'] ?? 0; ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Categoría</label>
                                            <select class="form-control" name="category_id">
                                                <option value="">Seleccionar categoría</option>
                                                <?php
                                                // Get categories from database
                                                $cat_query = "SELECT id, name FROM categories WHERE status = 'active' ORDER BY name";
                                                $cat_stmt = $db->prepare($cat_query);
                                                $cat_stmt->execute();
                                                $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

                                                foreach ($categories as $cat) {
                                                    $selected = ($edit_product['category_id'] ?? '') == $cat['id'] ? 'selected' : '';
                                                    echo "<option value=\"{$cat['id']}\" {$selected}>{$cat['name']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Marca</label>
                                            <input type="text" class="form-control" name="brand" value="<?php echo htmlspecialchars($edit_product['brand'] ?? 'BarbeX'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Estado del Stock</label>
                                            <select class="form-control" name="stock_status">
                                                <option value="instock" <?php echo ($edit_product['stock_status'] ?? 'instock') == 'instock' ? 'selected' : ''; ?>>En Stock</option>
                                                <option value="outofstock" <?php echo ($edit_product['stock_status'] ?? 'instock') == 'outofstock' ? 'selected' : ''; ?>>Sin Stock</option>
                                                <option value="onbackorder" <?php echo ($edit_product['stock_status'] ?? 'instock') == 'onbackorder' ? 'selected' : ''; ?>>En Pedido</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Peso (kg)</label>
                                            <input type="number" step="0.01" class="form-control" name="weight" value="<?php echo $edit_product['weight'] ?? ''; ?>" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Dimensiones (LxWxH cm)</label>
                                            <input type="text" class="form-control" name="dimensions" value="<?php echo htmlspecialchars($edit_product['dimensions'] ?? ''); ?>" placeholder="10x5x15">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Tags</label>
                                            <input type="text" class="form-control" name="tags" value="<?php echo htmlspecialchars($edit_product['tags'] ?? ''); ?>" placeholder="tag1,tag2,tag3">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Imagen Principal</label>
                                            <input type="file" class="form-control" name="image_file" accept="image/*">
                                            <small class="form-text text-muted">Seleccionar imagen desde el PC</small>
                                            <?php if (!empty($edit_product['image'])): ?>
                                                <div class="mt-2">
                                                    <img src="../<?php echo htmlspecialchars($edit_product['image']); ?>" alt="Imagen actual" style="max-width: 100px; max-height: 100px;">
                                                    <p class="text-muted small">Imagen actual</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Imágenes de Galería</label>
                                            <input type="file" class="form-control" name="gallery_files[]" accept="image/*" multiple>
                                            <small class="form-text text-muted">Seleccionar múltiples imágenes (mantén Ctrl para seleccionar varias)</small>
                                            <?php if (!empty($edit_product['gallery_images'])): ?>
                                                <div class="mt-2">
                                                    <p class="text-muted small">Imágenes actuales:</p>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <?php
                                                        $gallery = json_decode($edit_product['gallery_images'], true);
                                                        if (is_array($gallery)) {
                                                            foreach ($gallery as $img) {
                                                                echo '<img src="../' . htmlspecialchars($img) . '" alt="" style="width: 50px; height: 50px; object-fit: cover;">';
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="featured" value="1" <?php echo ($edit_product['featured'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">
                                                    Producto Destacado
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Estado</label>
                                            <select class="form-control" name="status">
                                                <option value="active" <?php echo ($edit_product['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Activo</option>
                                                <option value="inactive" <?php echo ($edit_product['status'] ?? 'active') == 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                                            </select>
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
                                            <td>
                                                <?php
                                                if ($product['category_id']) {
                                                    $cat_query = "SELECT name FROM categories WHERE id = :id";
                                                    $cat_stmt = $db->prepare($cat_query);
                                                    $cat_stmt->execute(['id' => $product['category_id']]);
                                                    $cat = $cat_stmt->fetch(PDO::FETCH_ASSOC);
                                                    echo htmlspecialchars($cat['name'] ?? 'Sin categoría');
                                                } else {
                                                    echo htmlspecialchars($product['category'] ?? 'Sin categoría');
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                $<?php echo number_format($product['price'], 2); ?>
                                                <?php if ($product['sale_price']): ?>
                                                    <br><small class="text-muted"><del>$<?php echo number_format($product['sale_price'], 2); ?></del></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $product['stock']; ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                                    <?php echo ucfirst($product['status']); ?>
                                                </span>
                                                <?php if ($product['featured']): ?>
                                                    <br><small class="badge badge-warning">Destacado</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning btn-action" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger btn-action" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este producto?')">
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