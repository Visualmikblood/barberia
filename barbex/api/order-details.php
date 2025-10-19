<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'No autorizado';
    exit();
}

$database = new Database();
$db = $database->getConnection();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    echo '<div class="alert alert-danger">ID de pedido no válido</div>';
    exit();
}

// Get order details
$query = "SELECT o.*, COUNT(oi.id) as item_count
          FROM orders o
          LEFT JOIN order_items oi ON o.id = oi.order_id
          WHERE o.id = :order_id
          GROUP BY o.id";
$stmt = $db->prepare($query);
$stmt->execute(['order_id' => $order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo '<div class="alert alert-danger">Pedido no encontrado</div>';
    exit();
}

// Get order items
$items_query = "SELECT oi.*, p.name as product_name, p.image
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :order_id
                ORDER BY oi.id";
$items_stmt = $db->prepare($items_query);
$items_stmt->execute(['order_id' => $order_id]);
$order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="order-details">
    <div class="row mb-4">
        <div class="col-md-6">
            <h6>Información del Pedido</h6>
            <p><strong>Número de Pedido:</strong> #<?php echo htmlspecialchars($order['order_number']); ?></p>
            <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
            <p><strong>Estado:</strong>
                <span class="badge bg-<?php
                    echo $order['order_status'] == 'pending' ? 'warning' :
                         ($order['order_status'] == 'processing' ? 'info' :
                         ($order['order_status'] == 'shipped' ? 'primary' :
                         ($order['order_status'] == 'delivered' ? 'success' : 'danger')));
                ?>">
                    <?php echo ucfirst($order['order_status']); ?>
                </span>
            </p>
        </div>
        <div class="col-md-6">
            <h6>Información de Envío</h6>
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
            <p><strong>Dirección:</strong><br>
                <?php echo htmlspecialchars($order['customer_address']); ?><br>
                <?php echo htmlspecialchars($order['customer_city']); ?><br>
                <?php if ($order['customer_postcode']): ?>
                    Código Postal: <?php echo htmlspecialchars($order['customer_postcode']); ?><br>
                <?php endif; ?>
                <?php echo htmlspecialchars($order['customer_country']); ?>
            </p>
        </div>
    </div>

    <h6>Productos</h6>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-end">Precio</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <?php if ($item['image']): ?>
                                <img src="../<?php echo htmlspecialchars($item['image']); ?>"
                                     alt="" style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
                            <?php endif; ?>
                            <div>
                                <strong><?php echo htmlspecialchars($item['product_name'] ?: $item['product_name']); ?></strong>
                            </div>
                        </div>
                    </td>
                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                    <td class="text-end">$<?php echo number_format($item['product_price'], 2); ?></td>
                    <td class="text-end">$<?php echo number_format($item['total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                    <td class="text-end">$<?php echo number_format($order['subtotal'], 2); ?></td>
                </tr>
                <?php if ($order['tax'] > 0): ?>
                <tr>
                    <td colspan="3" class="text-end"><strong>Impuestos:</strong></td>
                    <td class="text-end">$<?php echo number_format($order['tax'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($order['shipping'] > 0): ?>
                <tr>
                    <td colspan="3" class="text-end"><strong>Envío:</strong></td>
                    <td class="text-end">$<?php echo number_format($order['shipping'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="table-dark">
                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                    <td class="text-end"><strong>$<?php echo number_format($order['total'], 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if ($order['notes']): ?>
    <div class="mt-3">
        <h6>Notas del Pedido</h6>
        <p><?php echo htmlspecialchars($order['notes']); ?></p>
    </div>
    <?php endif; ?>

    <div class="mt-3">
        <h6>Información de Pago</h6>
        <p><strong>Método de Pago:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
        <p><strong>Estado del Pago:</strong>
            <span class="badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                <?php echo ucfirst($order['payment_status']); ?>
            </span>
        </p>
    </div>
</div>