<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../classes/Cart.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Debug: Log received data
error_log('Checkout API called');
$rawInput = file_get_contents('php://input');
error_log('Raw input: ' . $rawInput);

$data = json_decode($rawInput, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos - JSON malformado']);
    exit;
}

error_log('Decoded data: ' . print_r($data, true));

// Validar datos requeridos
$required_fields = ['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'postcode', 'country'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Campo requerido: $field"]);
        exit;
    }
}

try {
    // Check if cart data is provided in the request
    if (isset($data['cart']) && !empty($data['cart']['items'])) {
        // Use cart data from request
        $cartData = $data['cart'];
        $cartData['is_empty'] = empty($cartData['items']);
    } else {
        // Fallback to session cart
        $cart = new Cart($db);
        $cartData = $cart->getCartData();
    }

    if ($cartData['is_empty'] || empty($cartData['items'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
        exit;
    }

    // Generar número de orden único
    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));

    // Calcular totales
    $subtotal = isset($cartData['total']) ? $cartData['total'] : $cartData['subtotal'];
    $tax = $subtotal * 0.10; // 10% de impuesto
    $shipping = $subtotal > 50 ? 0 : 5.99; // Envío gratis para pedidos > $50
    $total = $subtotal + $tax + $shipping;

    // Iniciar transacción
    $db->beginTransaction();

    // Insertar orden
    $orderQuery = "INSERT INTO orders (
        user_id, session_id, order_number, customer_name, customer_email,
        customer_phone, customer_address, customer_city, customer_state,
        customer_postcode, customer_country, subtotal, tax, shipping, total,
        payment_method, notes
    ) VALUES (
        :user_id, :session_id, :order_number, :customer_name, :customer_email,
        :customer_phone, :customer_address, :customer_city, :customer_state,
        :customer_postcode, :customer_country, :subtotal, :tax, :shipping, :total,
        :payment_method, :notes
    )";

    $stmt = $db->prepare($orderQuery);
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $customerName = $data['first_name'] . ' ' . $data['last_name'];
    $paymentMethod = isset($data['payment_method']) ? $data['payment_method'] : 'cash_on_delivery';
    $notes = isset($data['notes']) ? $data['notes'] : '';

    $stmt->bindParam(":user_id", $userId);
    $stmt->bindParam(":session_id", $_SESSION['cart_session_id']);
    $stmt->bindParam(":order_number", $orderNumber);
    $stmt->bindParam(":customer_name", $customerName);
    $stmt->bindParam(":customer_email", $data['email']);
    $stmt->bindParam(":customer_phone", $data['phone']);
    $stmt->bindParam(":customer_address", $data['address']);
    $stmt->bindParam(":customer_city", $data['city']);
    $stmt->bindParam(":customer_state", $data['state']);
    $stmt->bindParam(":customer_postcode", $data['postcode']);
    $stmt->bindParam(":customer_country", $data['country']);
    $stmt->bindParam(":subtotal", $subtotal);
    $stmt->bindParam(":tax", $tax);
    $stmt->bindParam(":shipping", $shipping);
    $stmt->bindParam(":total", $total);
    $stmt->bindParam(":payment_method", $paymentMethod);
    $stmt->bindParam(":notes", $notes);

    $stmt->execute();
    $orderId = $db->lastInsertId();

    // Insertar items de la orden
    $itemQuery = "INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, total)
                  VALUES (:order_id, :product_id, :product_name, :product_price, :quantity, :total)";

    $stmt = $db->prepare($itemQuery);

    foreach ($cartData['items'] as $item) {
        $itemTotal = $item['price'] * $item['quantity'];

        $stmt->bindParam(":order_id", $orderId);
        $stmt->bindParam(":product_id", $item['id']); // Changed from product_id to id
        $stmt->bindParam(":product_name", $item['name']);
        $stmt->bindParam(":product_price", $item['price']);
        $stmt->bindParam(":quantity", $item['quantity']);
        $stmt->bindParam(":total", $itemTotal);

        $stmt->execute();

        // Actualizar stock del producto (only if using database cart)
        if (!isset($data['cart'])) {
            $stockQuery = "UPDATE products SET stock = stock - :quantity WHERE id = :product_id";
            $stockStmt = $db->prepare($stockQuery);
            $stockStmt->bindParam(":quantity", $item['quantity']);
            $stockStmt->bindParam(":product_id", $item['id']);
            $stockStmt->execute();
        }
    }

    // Limpiar carrito
    $cart->clearCart();

    // Confirmar transacción
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pedido realizado exitosamente',
        'order_number' => $orderNumber,
        'order_id' => $orderId,
        'total' => $total
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $db->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar el pedido: ' . $e->getMessage()
    ]);
}
?>