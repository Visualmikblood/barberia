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
        handleGetProducts($db, $action);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}

function handleGetProducts($db, $action) {
    switch ($action) {
        case 'all':
            getAllProducts($db);
            break;
        case 'single':
            getSingleProduct($db);
            break;
        case 'category':
            getProductsByCategory($db);
            break;
        default:
            getAllProducts($db);
    }
}

function getAllProducts($db) {
    $query = "SELECT id, name, description, price, image, category, stock
              FROM products
              WHERE status = 'active'
              ORDER BY created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $products
    ]);
}

function getSingleProduct($db) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
        return;
    }

    $query = "SELECT * FROM products WHERE id = :id AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_GET['id']);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        return;
    }

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $product
    ]);
}

function getProductsByCategory($db) {
    if (!isset($_GET['category'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Categoría requerida']);
        return;
    }

    $query = "SELECT id, name, description, price, image, category, stock
              FROM products
              WHERE category = :category AND status = 'active'
              ORDER BY created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":category", $_GET['category']);
    $stmt->execute();

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $products
    ]);
}
?>