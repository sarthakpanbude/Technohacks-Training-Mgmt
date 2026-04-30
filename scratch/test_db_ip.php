<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
