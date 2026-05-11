<?php
require_once __DIR__ . '/../config/db.php';

try {
    // 1. Add referral_code to students table
    try {
        $pdo->exec("ALTER TABLE students ADD COLUMN referral_code VARCHAR(20) UNIQUE AFTER enrollment_no");
    } catch (Exception $e) {
        // Column might already exist
    }

    // 2. Create referral_bonuses table
    $pdo->exec("CREATE TABLE IF NOT EXISTS referral_bonuses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        referrer_id VARCHAR(50), 
        referred_id VARCHAR(50), 
        original_fee DECIMAL(10, 2),
        discount_amount DECIMAL(10, 2),
        final_fee DECIMAL(10, 2),
        bonus_amount DECIMAL(10, 2),
        status ENUM('Pending', 'Pending Full Payment', 'Waiting Refund Period', 'Approved', 'Rejected', 'Cancelled Due To Refund') DEFAULT 'Pending',
        refund_expiry_date DATE,
        is_fully_paid BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // 3. Update existing students with a referral code if they don't have one
    $students = $pdo->query("SELECT id, enrollment_no FROM students WHERE referral_code IS NULL OR referral_code = ''")->fetchAll();
    foreach ($students as $s) {
        $code = 'THR' . substr($s['enrollment_no'], -4) . rand(10, 99);
        try {
            $pdo->prepare("UPDATE students SET referral_code = ? WHERE id = ?")->execute([$code, $s['id']]);
        } catch (Exception $e) {
            // Might be duplicate code, skip or retry
        }
    }

    echo "Migration successful!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
