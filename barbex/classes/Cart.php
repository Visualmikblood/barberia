<?php
// No iniciar sesión aquí, se maneja en los archivos que usan esta clase

class Cart {
    private $db;
    private $sessionId;

    public function __construct($database) {
        $this->db = $database;
        $this->initSession();
    }

    private function initSession() {
        if (!isset($_SESSION['cart_session_id'])) {
            $_SESSION['cart_session_id'] = session_id();
        }
        $this->sessionId = $_SESSION['cart_session_id'];

        // Crear sesión en la base de datos si no existe
        $this->createSessionIfNotExists();
    }

    private function createSessionIfNotExists() {
        $query = "INSERT IGNORE INTO cart_sessions (session_id, user_id) VALUES (:session_id, :user_id)";
        $stmt = $this->db->prepare($query);
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $stmt->bindParam(":session_id", $this->sessionId);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
    }

    public function addToCart($productId, $quantity = 1) {
        // Verificar si el producto existe y está activo
        $productQuery = "SELECT id, name, price, stock FROM products WHERE id = :id AND status = 'active'";
        $stmt = $this->db->prepare($productQuery);
        $stmt->bindParam(":id", $productId);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'Producto no encontrado'];
        }

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar stock
        if ($product['stock'] < $quantity) {
            return ['success' => false, 'message' => 'Stock insuficiente'];
        }

        // Insertar o actualizar item en el carrito
        $query = "INSERT INTO cart_items (session_id, product_id, quantity)
                  VALUES (:session_id, :product_id, :quantity)
                  ON DUPLICATE KEY UPDATE quantity = quantity + :quantity";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":session_id", $this->sessionId);
        $stmt->bindParam(":product_id", $productId);
        $stmt->bindParam(":quantity", $quantity);

        if ($stmt->execute()) {
            $this->updateSessionTimestamp();
            return [
                'success' => true,
                'message' => 'Producto agregado al carrito',
                'product' => $product,
                'cart_count' => $this->getItemCount()
            ];
        }

        return ['success' => false, 'message' => 'Error al agregar producto'];
    }

    public function updateQuantity($productId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($productId);
        }

        // Verificar stock
        $stockQuery = "SELECT stock FROM products WHERE id = :id";
        $stmt = $this->db->prepare($stockQuery);
        $stmt->bindParam(":id", $productId);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product['stock'] < $quantity) {
            return ['success' => false, 'message' => 'Stock insuficiente'];
        }

        $query = "UPDATE cart_items SET quantity = :quantity, updated_at = NOW()
                  WHERE session_id = :session_id AND product_id = :product_id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":quantity", $quantity);
        $stmt->bindParam(":session_id", $this->sessionId);
        $stmt->bindParam(":product_id", $productId);

        if ($stmt->execute()) {
            $this->updateSessionTimestamp();
            return [
                'success' => true,
                'message' => 'Cantidad actualizada',
                'cart_count' => $this->getItemCount()
            ];
        }

        return ['success' => false, 'message' => 'Error al actualizar cantidad'];
    }

    public function removeFromCart($productId) {
        $query = "DELETE FROM cart_items WHERE session_id = :session_id AND product_id = :product_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":session_id", $this->sessionId);
        $stmt->bindParam(":product_id", $productId);

        if ($stmt->execute()) {
            $this->updateSessionTimestamp();
            return [
                'success' => true,
                'message' => 'Producto eliminado del carrito',
                'cart_count' => $this->getItemCount()
            ];
        }

        return ['success' => false, 'message' => 'Error al eliminar producto'];
    }

    public function getCartItems() {
        $query = "SELECT ci.*, p.name, p.price, p.image, p.stock
                  FROM cart_items ci
                  JOIN products p ON ci.product_id = p.id
                  WHERE ci.session_id = :session_id AND p.status = 'active'
                  ORDER BY ci.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":session_id", $this->sessionId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getItemCount() {
        $query = "SELECT SUM(quantity) as total FROM cart_items WHERE session_id = :session_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":session_id", $this->sessionId);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ? (int)$result['total'] : 0;
    }

    public function getTotal() {
        $query = "SELECT SUM(ci.quantity * p.price) as total
                  FROM cart_items ci
                  JOIN products p ON ci.product_id = p.id
                  WHERE ci.session_id = :session_id AND p.status = 'active'";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":session_id", $this->sessionId);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ? (float)$result['total'] : 0.0;
    }

    public function clearCart() {
        $query = "DELETE FROM cart_items WHERE session_id = :session_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":session_id", $this->sessionId);

        if ($stmt->execute()) {
            $this->updateSessionTimestamp();
            return ['success' => true, 'message' => 'Carrito vaciado'];
        }

        return ['success' => false, 'message' => 'Error al vaciar carrito'];
    }

    public function isEmpty() {
        return $this->getItemCount() == 0;
    }

    private function updateSessionTimestamp() {
        $query = "UPDATE cart_sessions SET updated_at = NOW() WHERE session_id = :session_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":session_id", $this->sessionId);
        $stmt->execute();
    }

    public function getCartData() {
        return [
            'items' => $this->getCartItems(),
            'item_count' => $this->getItemCount(),
            'total' => $this->getTotal(),
            'is_empty' => $this->isEmpty()
        ];
    }
}
?>