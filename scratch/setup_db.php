<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'technohacks_erp';

try {
    // Connect without DB first
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "✅ Database '$dbname' created/verified.\n";

    // Switch to DB
    $pdo->exec("USE `$dbname`");

    // Import main SQL files
    $sqlFiles = [
        __DIR__ . '/../database.sql',
        __DIR__ . '/../create_materials.sql',
    ];

    foreach ($sqlFiles as $file) {
        if (!file_exists($file)) {
            echo "⚠️  Skipping (not found): $file\n";
            continue;
        }
        $sql = file_get_contents($file);
        // Split by semicolon to run statements individually
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                try {
                    $pdo->exec($stmt);
                } catch (PDOException $e) {
                    // Ignore duplicate/already-exists errors
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        echo "⚠️  Stmt error: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        echo "✅ Imported: " . basename($file) . "\n";
    }

    // Verify tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "📋 Tables (" . count($tables) . "): " . implode(', ', $tables) . "\n";
    echo "\n🚀 Setup complete! Visit: http://localhost/Technohacks-Training-Mgmt/\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
