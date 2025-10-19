<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'GET':
        handleGet($db, $action);
        break;
    case 'POST':
        handlePost($db, $action);
        break;
    case 'PUT':
        handlePut($db, $action);
        break;
    case 'DELETE':
        handleDelete($db, $action);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}

function handleGet($db, $action) {
    switch ($action) {
        case 'get':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                return;
            }

            $query = "SELECT * FROM blog_posts WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute(['id' => (int)$_GET['id']]);

            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($post) {
                echo json_encode(['success' => true, 'post' => $post]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Post no encontrado']);
            }
            break;

        case 'list':
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            $category = isset($_GET['category']) ? $_GET['category'] : null;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

            $query = "SELECT bp.*, u.name as author_name, bc.name as category_name
                      FROM blog_posts bp
                      LEFT JOIN users u ON bp.author_id = u.id
                      LEFT JOIN blog_categories bc ON bp.category = bc.name";

            $conditions = [];
            $params = [];

            if ($status) {
                $conditions[] = "bp.status = :status";
                $params['status'] = $status;
            }

            if ($category) {
                $conditions[] = "bp.category = :category";
                $params['category'] = $category;
            }

            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }

            $query .= " ORDER BY bp.created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'posts' => $posts]);
            break;

        case 'categories':
            $query = "SELECT * FROM blog_categories WHERE status = 'active' ORDER BY name";
            $stmt = $db->query($query);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'categories' => $categories]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
}

function handlePost($db, $action) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        $data = $_POST;
    }

    switch ($action) {
        case 'create':
            if (!isset($data['title']) || !isset($data['content'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Título y contenido requeridos']);
                return;
            }

            // Generate slug
            $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9\s]/', '', $data['title'])));
            $slug = preg_replace('/-+/', '-', $slug);

            $query = "INSERT INTO blog_posts (title, slug, content, excerpt, featured_image, author_id, category, tags, status, published_at)
                     VALUES (:title, :slug, :content, :excerpt, :featured_image, :author_id, :category, :tags, :status,
                             CASE WHEN :status = 'published' THEN NOW() ELSE NULL END)";

            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                'title' => $data['title'],
                'slug' => $slug,
                'content' => $data['content'],
                'excerpt' => $data['excerpt'] ?? '',
                'featured_image' => $data['featured_image'] ?? '',
                'author_id' => $data['author_id'] ?? null,
                'category' => $data['category'] ?? '',
                'tags' => $data['tags'] ?? '',
                'status' => $data['status'] ?? 'draft'
            ]);

            if ($result) {
                $postId = $db->lastInsertId();
                echo json_encode(['success' => true, 'message' => 'Post creado', 'id' => $postId]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al crear post']);
            }
            break;

        case 'update':
            if (!isset($data['id']) || !isset($data['title']) || !isset($data['content'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID, título y contenido requeridos']);
                return;
            }

            $query = "UPDATE blog_posts SET
                     title = :title, content = :content, excerpt = :excerpt,
                     featured_image = :featured_image, category = :category, tags = :tags,
                     status = :status, published_at = CASE WHEN :status = 'published' THEN COALESCE(published_at, NOW()) ELSE NULL END
                     WHERE id = :id";

            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                'title' => $data['title'],
                'content' => $data['content'],
                'excerpt' => $data['excerpt'] ?? '',
                'featured_image' => $data['featured_image'] ?? '',
                'category' => $data['category'] ?? '',
                'tags' => $data['tags'] ?? '',
                'status' => $data['status'] ?? 'draft',
                'id' => (int)$data['id']
            ]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Post actualizado']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar post']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
}

function handlePut($db, $action) {
    $data = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'update':
            handlePost($db, 'update'); // Reuse the update logic
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
}

function handleDelete($db, $action) {
    switch ($action) {
        case 'delete':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                return;
            }

            $query = "DELETE FROM blog_posts WHERE id = :id";
            $stmt = $db->prepare($query);
            $result = $stmt->execute(['id' => (int)$_GET['id']]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Post eliminado']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al eliminar post']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
}
?>