<?php
require_once '../../includes/auth.php';
checkAuth('admin');
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['referral_id'])) {
    $id = $_POST['referral_id'];
    $ref = $_POST['payment_ref'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE referral_bonuses SET payout_status = 'Paid', paid_at = NOW(), payment_ref = ? WHERE id = ? AND status = 'Approved'");
        if ($stmt->execute([$ref, $id])) {
            header("Location: ../referrals.php?success=Bonus payment recorded successfully!");
            exit;
        }
    } catch (Exception $e) {
        header("Location: ../referrals.php?error=Error recording payment: " . $e->getMessage());
        exit;
    }
}

header("Location: ../referrals.php?error=Invalid request");
exit;
