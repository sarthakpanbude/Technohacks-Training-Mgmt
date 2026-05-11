<?php
require_once 'config/db.php';
try {
    // Check if columns exist
    $stmt = $pdo->query("DESCRIBE referral_bonuses");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('paid_at', $columns)) {
        $pdo->exec("ALTER TABLE referral_bonuses ADD COLUMN paid_at DATETIME NULL");
        echo "Added paid_at column.\n";
    }
    if (!in_array('payment_ref', $columns)) {
        $pdo->exec("ALTER TABLE referral_bonuses ADD COLUMN payment_ref VARCHAR(255) NULL");
        echo "Added payment_ref column.\n";
    }
    if (!in_array('payout_status', $columns)) {
        $pdo->exec("ALTER TABLE referral_bonuses ADD COLUMN payout_status ENUM('Unpaid', 'Paid') DEFAULT 'Unpaid'");
        echo "Added payout_status column.\n";
    }
    
    echo "Migration completed successfully.";
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage();
}
