<?php
require_once '../config/db.php';

try {
    $pdo->exec("UPDATE students SET referral_code = enrollment_no");
    echo "Referral IDs synced with Student IDs successfully!";
} catch (Exception $e) {
    echo "Sync failed: " . $e->getMessage();
}
