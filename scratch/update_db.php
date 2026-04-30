<?php
require_once 'config/db.php';
try {
    // Step 1: Update Batches (Add course_name and start_time if they don't exist)
    $pdo->exec("ALTER TABLE batches ADD COLUMN IF NOT EXISTS course_name VARCHAR(100)");
    $pdo->exec("ALTER TABLE batches ADD COLUMN IF NOT EXISTS start_time VARCHAR(50)");
    echo "Batches table updated.\n";

    // Step 2: Update Students (Add batch_id if it doesn't exist)
    $pdo->exec("ALTER TABLE students ADD COLUMN IF NOT EXISTS batch_id INT NULL");
    echo "Students table updated.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
