<?php
require_once __DIR__ . '/../config/db.php';
try {
    $stmt = $pdo->query("SHOW DATABASES LIKE 'technohacks_erp'");
    $res = $stmt->fetchAll();
    if (count($res) > 0) {
        echo "Database exists.\n";
    } else {
        echo "Database does not exist.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
