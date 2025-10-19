<?php
session_start();
require_once 'config/database.php';

echo "<h1>DEBUG: Profile Image Issue</h1>";
echo "<pre>";

// Check database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo "❌ Database connection failed\n";
    exit;
}

echo "✅ Database connected\n";

// Check which database we're connected to
$dbName = $db->query('SELECT DATABASE()')->fetchColumn();
echo "Connected to database: " . $dbName . "\n";

// Check users table structure
echo "\nUsers table columns:\n";
$columns = $db->query('SHOW COLUMNS FROM users');
$has_profile_image = false;
foreach ($columns as $col) {
    echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    if ($col['Field'] === 'profile_image') {
        $has_profile_image = true;
    }
}

if ($has_profile_image) {
    echo "\n✅ profile_image column EXISTS\n";
} else {
    echo "\n❌ profile_image column MISSING\n";
}

// Check current user data
if (isset($_SESSION['user_id'])) {
    echo "\nCurrent user ID: " . $_SESSION['user_id'] . "\n";
    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "User data:\n";
        foreach ($user as $key => $value) {
            echo "  $key: " . ($value ?? 'NULL') . "\n";
        }
    } else {
        echo "❌ User not found\n";
    }
} else {
    echo "\n❌ No user session\n";
}

// Test the exact query from my-account.php
echo "\nTesting the exact query from my-account.php...\n";
try {
    $query = 'UPDATE users SET name = :name, phone = :phone, address = :address, profile_image = :profile_image WHERE id = :id';
    $stmt = $db->prepare($query);
    $params = [
        'name' => 'Test User',
        'phone' => '123456789',
        'address' => 'Test Address',
        'profile_image' => 'test.jpg',
        'id' => $_SESSION['user_id'] ?? 1
    ];

    echo "Query: $query\n";
    echo "Params: " . json_encode($params) . "\n";

    $result = $stmt->execute($params);
    echo '✅ Query executed successfully: ' . ($result ? 'TRUE' : 'FALSE') . "\n";

    // Reset
    $db->exec('UPDATE users SET profile_image = NULL WHERE id = ' . ($_SESSION['user_id'] ?? 1));
    echo '✅ Test reset completed\n';
} catch (Exception $e) {
    echo '❌ Query failed: ' . $e->getMessage() . '\n';
}

echo "\n=== END DEBUG ===\n";
echo "</pre>";
?>