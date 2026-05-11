<?php
require_once '../../includes/auth.php';
checkAuth('admin');
require_once '../../config/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Security check: Only allow deleting if status is 'Cancelled Due To Refund' or 'Cancelled'
    $stmt = $pdo->prepare("SELECT status FROM referral_bonuses WHERE id = ?");
    $stmt->execute([$id]);
    $referral = $stmt->fetch();
    
    if ($referral && (strpos($referral['status'], 'Cancelled') !== false)) {
        $stmt = $pdo->prepare("DELETE FROM referral_bonuses WHERE id = ?");
        if ($stmt->execute([$id])) {
            header("Location: ../referrals.php?success=Cancelled referral deleted successfully");
            exit;
        }
    }
}

header("Location: ../referrals.php?error=Failed to delete referral");
exit;
