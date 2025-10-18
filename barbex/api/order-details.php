<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo 'Acceso denegado';
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['order_id'])) {
    http_response_code(400);
    echo 'ID de pedido requerido';
    exit();
}

$order_id = (int)$_GET['order_id'];

// Get order details
$query = "SELECT * FROM orders WHERE id = :order_id";
$stmt = $db->prepare($query);
$stmt->execute(['order_id' => $order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    echo 'Pedido no encontrado';
    exit();
}

// Get order items with product images
$items_query = "SELECT oi.*, p.image FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :order_id";
$items_stmt = $db->prepare($items_query);
$items_stmt->execute(['order_id' => $order_id]);
$order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-8">
        <h5>Información del Cliente</h5>
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Dirección:</strong><br>
                        <?php echo htmlspecialchars($order['customer_address']); ?><br>
                        <?php echo htmlspecialchars($order['customer_city']); ?>, <?php echo htmlspecialchars($order['customer_postcode']); ?><br>
                        <?php echo htmlspecialchars($order['customer_country']); ?></p>
                    </div>
                </div>
                <?php if ($order['notes']): ?>
                <div class="mt-3">
                    <strong>Notas del Cliente:</strong><br>
                    <?php echo htmlspecialchars($order['notes']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <h5>Productos del Pedido</h5>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-right">Precio</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?php echo htmlspecialchars('../' . $item['image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image me-3">
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                            </div>
                        </td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td class="text-right">$<?php echo number_format($item['product_price'], 2); ?></td>
                        <td class="text-right">$<?php echo number_format($item['total'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold">
                        <td colspan="3" class="text-right"><strong>Total del Pedido:</strong></td>
                        <td class="text-right"><strong>$<?php echo number_format($order['total'], 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="col-md-4">
        <h5>Información del Pedido</h5>
        <div class="card">
            <div class="card-body">
                <p><strong>Número de Pedido:</strong><br><?php echo htmlspecialchars($order['order_number']); ?></p>
                <p><strong>Fecha del Pedido:</strong><br><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                <p><strong>Estado del Pedido:</strong><br>
                    <span class="badge bg-<?php
                        echo $order['order_status'] == 'pending' ? 'warning' :
                             ($order['order_status'] == 'processing' ? 'info' :
                              ($order['order_status'] == 'shipped' ? 'primary' :
                               ($order['order_status'] == 'delivered' ? 'success' : 'secondary')));
                    ?>">
                        <?php echo ucfirst($order['order_status']); ?>
                    </span>
                </p>
                <p><strong>Estado del Pago:</strong><br>
                    <span class="badge bg-<?php
                        echo $order['payment_status'] == 'paid' ? 'success' :
                             ($order['payment_status'] == 'pending' ? 'warning' : 'danger');
                    ?>">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                </p>
                <p><strong>Método de Pago:</strong><br><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>

                <hr>
                <div class="row">
                    <div class="col-6">
                        <p class="mb-1"><strong>Subtotal:</strong></p>
                        <p class="mb-1"><strong>Impuestos:</strong></p>
                        <p class="mb-1"><strong>Envío:</strong></p>
                        <p class="mb-0"><strong>Total:</strong></p>
                    </div>
                    <div class="col-6 text-end">
                        <p class="mb-1">$<?php echo number_format($order['subtotal'], 2); ?></p>
                        <p class="mb-1">$<?php echo number_format($order['tax'], 2); ?></p>
                        <p class="mb-1">$<?php echo number_format($order['shipping'], 2); ?></p>
                        <p class="mb-0"><strong>$<?php echo number_format($order['total'], 2); ?></strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.product-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 5px;
}
</style>