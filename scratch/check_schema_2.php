<?php
require_once 'config/db.php';
echo "--- BATCHES ---\n";
try {
    $stmt = $pdo->query("DESCRIBE batches");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) { echo "Batches table error: " . $e->getMessage() . "\n"; }

echo "--- STUDENTS ---\n";
try {
    $stmt = $pdo->query("DESCRIBE students");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) { echo "Students table error: " . $e->getMessage() . "\n"; }
?>
