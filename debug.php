<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT id, name, image, gallery_images FROM products ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Debug - Productos e Imágenes</h1>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Imagen</th><th>Galería</th><th>Imagen existe</th></tr>";

    foreach ($products as $product) {
        $image_exists = '';
        if (!empty($product['image'])) {
            $image_path = __DIR__ . '/' . $product['image'];
            $image_exists = file_exists($image_path) ? 'SÍ' : 'NO';
        }

        echo "<tr>";
        echo "<td>" . $product['id'] . "</td>";
        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
        echo "<td>" . htmlspecialchars($product['image']) . "</td>";
        echo "<td>" . htmlspecialchars($product['gallery_images'] ?? '') . "</td>";
        echo "<td>" . $image_exists . "</td>";
        echo "</tr>";
    }

    echo "</table>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>