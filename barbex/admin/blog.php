<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Debug: Check database connection
if (!$db) {
    die('Database connection failed');
}

// Debug: Check current database
try {
    $result = $db->query('SELECT DATABASE() as db_name');
    $currentDb = $result->fetch(PDO::FETCH_ASSOC);
    error_log('Blog.php connected to database: ' . $currentDb['db_name']);
} catch (Exception $e) {
    error_log('Database check failed: ' . $e->getMessage());
    die('Database check failed: ' . $e->getMessage());
}

// Handle form submissions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $title = trim($_POST['title']);
                $content = $_POST['content'];
                $excerpt = trim($_POST['excerpt']);
                $category = trim($_POST['category']);
                $tags = trim($_POST['tags']);
                $status = $_POST['status'];

                if (empty($title) || empty($content)) {
                    $message = '<div class="alert alert-danger">Título y contenido son obligatorios</div>';
                } else {
                    // Generate slug from title
                    $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9\s]/', '', $title)));
                    $slug = preg_replace('/-+/', '-', $slug); // Remove multiple hyphens

                    // Handle file upload
                    $featured_image = '';
                    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = 'assets/img/blog/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }

                        $file_extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
                        $file_name = 'blog_' . time() . '.' . $file_extension;
                        $target_file = $upload_dir . $file_name;

                        if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                            $featured_image = 'assets/img/blog/' . $file_name;
                        }
                    }

                    try {
                        // Debug: Check if table exists
                        $checkQuery = "SHOW TABLES LIKE 'blog_posts'";
                        $checkStmt = $db->prepare($checkQuery);
                        $checkStmt->execute();
                        if ($checkStmt->rowCount() == 0) {
                            // Create the table if it doesn't exist
                            $createQuery = "CREATE TABLE blog_posts (
                                id INT PRIMARY KEY AUTO_INCREMENT,
                                title VARCHAR(255) NOT NULL,
                                slug VARCHAR(255) UNIQUE NOT NULL,
                                content TEXT NOT NULL,
                                excerpt TEXT,
                                featured_image VARCHAR(255),
                                author_id INT,
                                category VARCHAR(100),
                                tags VARCHAR(255),
                                status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
                                published_at TIMESTAMP NULL,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                            ) ENGINE=InnoDB";
                            $db->exec($createQuery);
                            error_log('Created blog_posts table');
                        }

                        $query = "INSERT INTO blog_posts (title, slug, content, excerpt, featured_image, author_id, category, tags, status, published_at)
                                 VALUES (:title, :slug, :content, :excerpt, :featured_image, :author_id, :category, :tags, :status,
                                         CASE WHEN :status = 'published' THEN NOW() ELSE NULL END)";

                        error_log('Blog create query: ' . $query);
                        error_log('Blog create params: ' . json_encode([
                            'title' => $title,
                            'slug' => $slug,
                            'content' => substr($content, 0, 100) . '...',
                            'excerpt' => $excerpt,
                            'featured_image' => $featured_image,
                            'author_id' => $_SESSION['user_id'],
                            'category' => $category,
                            'tags' => $tags,
                            'status' => $status
                        ]));

                        $stmt = $db->prepare($query);
                        $result = $stmt->execute([
                            'title' => $title,
                            'slug' => $slug,
                            'content' => $content,
                            'excerpt' => $excerpt,
                            'featured_image' => $featured_image,
                            'author_id' => $_SESSION['user_id'],
                            'category' => $category,
                            'tags' => $tags,
                            'status' => $status
                        ]);

                        if ($result) {
                            $message = '<div class="alert alert-success">Artículo creado exitosamente (ID: ' . $db->lastInsertId() . ')</div>';
                            error_log('Blog post created successfully with ID: ' . $db->lastInsertId());
                        } else {
                            $message = '<div class="alert alert-danger">Error: La consulta no se ejecutó correctamente</div>';
                        }
                    } catch (Exception $e) {
                        $message = '<div class="alert alert-danger">Error al crear artículo: ' . $e->getMessage() . '</div>';
                        error_log('Blog create error: ' . $e->getMessage());
                        error_log('Stack trace: ' . $e->getTraceAsString());
                    }
                }
                break;

            case 'update':
                $id = (int)$_POST['id'];
                $title = trim($_POST['title']);
                $content = $_POST['content'];
                $excerpt = trim($_POST['excerpt']);
                $category = trim($_POST['category']);
                $tags = trim($_POST['tags']);
                $status = $_POST['status'];

                if (empty($title) || empty($content)) {
                    $message = '<div class="alert alert-danger">Título y contenido son obligatorios</div>';
                } else {
                    // Handle file upload
                    $featured_image = $_POST['current_image'] ?? '';
                    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = '../assets/img/blog/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }

                        $file_extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
                        $file_name = 'blog_' . time() . '.' . $file_extension;
                        $target_file = $upload_dir . $file_name;

                        if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                            $featured_image = 'assets/img/blog/' . $file_name;
                        }
                    }

                    try {
                        $query = "UPDATE blog_posts SET
                                 title = :title, content = :content, excerpt = :excerpt,
                                 featured_image = :featured_image, category = :category, tags = :tags,
                                 status = :status, published_at = CASE WHEN :status = 'published' THEN COALESCE(published_at, NOW()) ELSE NULL END
                                 WHERE id = :id";

                        $stmt = $db->prepare($query);
                        $stmt->execute([
                            'title' => $title,
                            'content' => $content,
                            'excerpt' => $excerpt,
                            'featured_image' => $featured_image,
                            'category' => $category,
                            'tags' => $tags,
                            'status' => $status,
                            'id' => $id
                        ]);

                        $message = '<div class="alert alert-success">Artículo actualizado exitosamente</div>';
                    } catch (Exception $e) {
                        $message = '<div class="alert alert-danger">Error al actualizar artículo: ' . $e->getMessage() . '</div>';
                        error_log('Blog update error: ' . $e->getMessage());
                    }
                }
                break;

            case 'delete':
                try {
                    $id = (int)$_POST['id'];
                    $stmt = $db->prepare("DELETE FROM blog_posts WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    $message = '<div class="alert alert-success">Artículo eliminado exitosamente</div>';
                } catch (Exception $e) {
                    $message = '<div class="alert alert-danger">Error al eliminar artículo: ' . $e->getMessage() . '</div>';
                    error_log('Blog delete error: ' . $e->getMessage());
                }
                break;
        }
    }
}

// Get all blog posts
try {
    // Ensure blog_posts table exists
    $checkPostsQuery = "SHOW TABLES LIKE 'blog_posts'";
    $checkPostsStmt = $db->prepare($checkPostsQuery);
    $checkPostsStmt->execute();
    if ($checkPostsStmt->rowCount() == 0) {
        $createPostsQuery = "CREATE TABLE blog_posts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            content TEXT NOT NULL,
            excerpt TEXT,
            featured_image VARCHAR(255),
            author_id INT,
            category VARCHAR(100),
            tags VARCHAR(255),
            status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
            published_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB";
        $db->exec($createPostsQuery);
        error_log('Created blog_posts table in listing section');
    }

    $query = "SELECT bp.*, u.name as author_name
              FROM blog_posts bp
              LEFT JOIN users u ON bp.author_id = u.id
              ORDER BY bp.created_at DESC";
    $stmt = $db->query($query);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log('Blog query successful, found ' . count($posts) . ' posts');
} catch (Exception $e) {
    $posts = []; // Default to empty array if table doesn't exist
    error_log('Blog tables error: ' . $e->getMessage());
}

// Get categories for dropdown
try {
    // Ensure blog_categories table exists
    $checkCatsQuery = "SHOW TABLES LIKE 'blog_categories'";
    $checkCatsStmt = $db->prepare($checkCatsQuery);
    $checkCatsStmt->execute();
    if ($checkCatsStmt->rowCount() == 0) {
        $createCatsQuery = "CREATE TABLE blog_categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL UNIQUE,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB";
        $db->exec($createCatsQuery);

        // Insert default categories
        $db->exec("INSERT INTO blog_categories (name, slug, description, status) VALUES
            ('Tips de Belleza', 'tips-belleza', 'Consejos y tips para el cuidado personal', 'active'),
            ('Tendencias', 'tendencias', 'Últimas tendencias en barbería y belleza', 'active'),
            ('Productos', 'productos', 'Reseñas y recomendaciones de productos', 'active'),
            ('Consejos Profesionales', 'consejos-profesionales', 'Consejos de expertos en barbería', 'active')");
        error_log('Created blog_categories table and inserted default categories');
    }

    $categories_query = "SELECT * FROM blog_categories WHERE status = 'active' ORDER BY name";
    $categories_stmt = $db->query($categories_query);
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = []; // Default to empty array if table doesn't exist
    error_log('Blog categories error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Management - BarbeX Admin</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/all.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container { padding: 20px; }
        .admin-header { background: #f8f9fa; padding: 20px; margin: -20px -20px 20px -20px; border-bottom: 1px solid #dee2e6; }
        .btn-create { margin-bottom: 20px; }
        .table-responsive { margin-top: 20px; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .status-published { background: #28a745; color: white; }
        .status-draft { background: #ffc107; color: black; }
        .status-archived { background: #6c757d; color: white; }
        .excerpt-preview { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-blog"></i> Blog Management</h1>
                    <p class="mb-0">Manage blog posts and content</p>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <button class="btn btn-primary btn-create" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="fas fa-plus"></i> Create New Post
                    </button>
                </div>
            </div>
        </div>

        <?php echo $message; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?php echo $post['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                            <?php if ($post['excerpt']): ?>
                            <br><small class="text-muted excerpt-preview"><?php echo htmlspecialchars($post['excerpt']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($post['category']); ?></td>
                        <td><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $post['status']; ?>">
                                <?php echo ucfirst($post['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d', strtotime($post['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editPost(<?php echo $post['id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deletePost(<?php echo $post['id']; ?>, '<?php echo htmlspecialchars($post['title']); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Create New Blog Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="postId">

                        <div class="mb-3">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Excerpt</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="2" placeholder="Brief description of the post"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-control" id="category" name="category">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags</label>
                            <input type="text" class="form-control" id="tags" name="tags" placeholder="Comma separated tags">
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="featured_image" class="form-label">Featured Image</label>
                            <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
                            <input type="hidden" id="current_image" name="current_image">
                            <div id="current-image-preview" class="mt-2"></div>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Content *</label>
                            <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete "<span id="deleteTitle"></span>"?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.min.js"></script>
    <script>
        function editPost(id) {
            // Fetch post data and populate modal
            fetch(`../api/blog.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const post = data.post;
                        document.getElementById('formAction').value = 'update';
                        document.getElementById('postId').value = post.id;
                        document.getElementById('title').value = post.title;
                        document.getElementById('excerpt').value = post.excerpt || '';
                        document.getElementById('category').value = post.category;
                        document.getElementById('tags').value = post.tags || '';
                        document.getElementById('status').value = post.status;
                        document.getElementById('content').value = post.content;
                        document.getElementById('current_image').value = post.featured_image || '';

                        if (post.featured_image) {
                            document.getElementById('current-image-preview').innerHTML =
                                `<img src="../${post.featured_image}" style="max-width: 200px; max-height: 150px;">`;
                        }

                        document.getElementById('modalTitle').textContent = 'Edit Blog Post';
                        new bootstrap.Modal(document.getElementById('createModal')).show();
                    }
                });
        }

        function deletePost(id, title) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteTitle').textContent = title;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Reset modal when closed
        document.getElementById('createModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('formAction').value = 'create';
            document.getElementById('postId').value = '';
            document.getElementById('modalTitle').textContent = 'Create New Blog Post';
            document.getElementById('current-image-preview').innerHTML = '';
            document.querySelector('form').reset();
        });
    </script>
</body>
</html>