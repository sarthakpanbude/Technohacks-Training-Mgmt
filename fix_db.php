<?php
require_once 'config/db.php';

try {
    // Add email column to inquiries if it doesn't exist
    $pdo->exec("ALTER TABLE inquiries ADD COLUMN email VARCHAR(255) AFTER mobile");
    echo "Added email column to inquiries table successfully!<br>";
} catch (PDOException $e) {
    echo "Inquiries email column might already exist or error: " . $e->getMessage() . "<br>";
}

try {
    // Check visitors table too
    $pdo->exec("ALTER TABLE visitors ADD COLUMN email VARCHAR(255) AFTER phone");
    echo "Added email column to visitors table successfully!<br>";
} catch (PDOException $e) {
    echo "Visitors email column might already exist or error: " . $e->getMessage() . "<br>";
}

echo "Database fix completed.";
?>
