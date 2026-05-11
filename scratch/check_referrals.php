<?php
require_once 'config/db.php';
try {
    echo "--- referral_bonuses ---\n";
    $stmt = $pdo->query("DESCRIBE referral_bonuses");
    while($row = $stmt->fetch()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
    echo "\n--- sample data ---\n";
    $stmt = $pdo->query("SELECT * FROM referral_bonuses LIMIT 5");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
