<?php
require_once __DIR__ . '/../../config/db.php';

try {
    $pdo->beginTransaction();

    // 1. Update status from 'Pending Full Payment' to 'Waiting Refund Period' if fees are fully paid
    $stmt = $pdo->query("
        SELECT rb.id, sf.pending_fee 
        FROM referral_bonuses rb 
        JOIN student_fees sf ON rb.referred_id = sf.student_id 
        WHERE rb.status = 'Pending Full Payment'
    ");
    $to_update = $stmt->fetchAll();
    foreach ($to_update as $row) {
        if ($row['pending_fee'] <= 0) {
            $pdo->prepare("UPDATE referral_bonuses SET status = 'Waiting Refund Period', is_fully_paid = 1 WHERE id = ?")->execute([$row['id']]);
        }
    }

    // 2. Update status from 'Waiting Refund Period' to 'Approved' if refund period expired
    $stmt = $pdo->query("
        SELECT rb.id, s.admission_status 
        FROM referral_bonuses rb 
        JOIN students s ON rb.referred_id = s.enrollment_no 
        WHERE rb.status = 'Waiting Refund Period' AND rb.refund_expiry_date <= CURRENT_DATE
    ");
    $to_approve = $stmt->fetchAll();
    foreach ($to_approve as $row) {
        if ($row['admission_status'] != 'refunded' && $row['admission_status'] != 'cancelled') {
            $pdo->prepare("UPDATE referral_bonuses SET status = 'Approved' WHERE id = ?")->execute([$row['id']]);
        } else {
            $pdo->prepare("UPDATE referral_bonuses SET status = 'Cancelled Due To Refund' WHERE id = ?")->execute([$row['id']]);
        }
    }

    // 3. Handle sudden refunds for any non-approved referral
    $stmt = $pdo->query("
        SELECT rb.id 
        FROM referral_bonuses rb 
        JOIN students s ON rb.referred_id = s.enrollment_no 
        WHERE rb.status IN ('Pending', 'Pending Full Payment', 'Waiting Refund Period') 
        AND (s.admission_status = 'refunded' OR s.admission_status = 'cancelled')
    ");
    $to_cancel = $stmt->fetchAll();
    foreach ($to_cancel as $row) {
        $pdo->prepare("UPDATE referral_bonuses SET status = 'Cancelled Due To Refund' WHERE id = ?")->execute([$row['id']]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
}
