<?php
// Setup script for BarbeX Shop Database
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Read and execute SQL file
    $sql = file_get_contents('sql/database.sql');

    // Split into individual statements and execute them
    $statements = explode(';', $sql);

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $db->exec($statement);
                echo "✅ Ejecutado: " . substr($statement, 0, 50) . "...<br>";
            } catch (PDOException $e) {
                // Skip errors for CREATE DATABASE IF NOT EXISTS and USE statements
                if (preg_match('/database exists|database doesn\'t exist/i', $e->getMessage())) {
                    echo "⚠️  Saltado (base de datos ya existe)<br>";
                    continue;
                }
                // Show more detailed error information
                echo "❌ Error en: " . substr($statement, 0, 50) . "...<br>";
                echo "Error: " . $e->getMessage() . "<br><br>";
                throw $e;
            }
        }
    }

    echo "<h2>✅ Base de datos configurada exitosamente</h2>";
    echo "<p>La tienda BarbeX está lista para usar.</p>";
    echo "<p><strong>Usuario admin:</strong> admin@barbex.com</p>";
    echo "<p><strong>Contraseña:</strong> password</p>";
    echo "<p><a href='index.html'>Ir al sitio web</a></p>";

} catch (PDOException $e) {
    echo "<h2>❌ Error al configurar la base de datos</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Asegúrate de que:</p>";
    echo "<ul>";
    echo "<li>MySQL esté ejecutándose</li>";
    echo "<li>Las credenciales en config/database.php sean correctas</li>";
    echo "<li>El usuario tenga permisos para crear bases de datos</li>";
    echo "</ul>";
}
?>