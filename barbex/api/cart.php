<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../classes/Cart.php';

$database = new Database();
$db = $database->getConnection();
$cart = new Cart($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'GET':
        handleGet($cart, $action);
        break;
    case 'POST':
        handlePost($cart, $action);
        break;
    case 'PUT':
        handlePut($cart, $action);
        break;
    case 'DELETE':
        handleDelete($cart, $action);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}

function handleGet($cart, $action) {
    switch ($action) {
        case 'get':
            echo json_encode([
                'success' => true,
                'data' => $cart->getCartData()
            ]);
            break;
        case 'count':
            echo json_encode([
                'success' => true,
                'count' => $cart->getItemCount()
            ]);
            break;
        default:
            echo json_encode([
                'success' => true,
                'data' => $cart->getCartData()
            ]);
    }
}

function handlePost($cart, $action) {
    // Debug: Log what we receive
    $raw_input = file_get_contents('php://input');
    error_log('Raw input: ' . $raw_input);
    error_log('POST data: ' . print_r($_POST, true));
    error_log('REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
    error_log('CONTENT_TYPE: ' . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

    // Try to get data from both JSON and form data
    $data = json_decode($raw_input, true);

    // If JSON failed, try form data
    if (!$data) {
        $data = $_POST;
    }

    error_log('Final data: ' . print_r($data, true));

    switch ($action) {
        case 'add':
            // Check both isset and !empty to be sure
            if (!isset($data['product_id']) || empty($data['product_id'])) {
                error_log('product_id not set or empty in data: ' . print_r($data, true));
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
                return;
            }

            $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
            error_log('Calling addToCart with product_id: ' . $data['product_id'] . ', quantity: ' . $quantity);
            $result = $cart->addToCart($data['product_id'], $quantity);
            error_log('addToCart result: ' . print_r($result, true));
            echo json_encode($result);
            break;

        case 'clear':
            $result = $cart->clearCart();
            echo json_encode($result);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
}

function handlePut($cart, $action) {
    $data = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'update':
            if (!isset($data['product_id']) || !isset($data['quantity'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de producto y cantidad requeridos']);
                return;
            }

            $result = $cart->updateQuantity($data['product_id'], (int)$data['quantity']);
            echo json_encode($result);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
}

function handleDelete($cart, $action) {
    switch ($action) {
        case 'remove':
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['product_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
                return;
            }

            $result = $cart->removeFromCart($data['product_id']);
            echo json_encode($result);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
}
?>