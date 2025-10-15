<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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
    $data = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'add':
            if (!isset($data['product_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
                return;
            }

            $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
            $result = $cart->addToCart($data['product_id'], $quantity);
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