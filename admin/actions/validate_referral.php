<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    echo json_encode(['valid' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT s.enrollment_no, u.full_name FROM students s JOIN users u ON s.user_id = u.id WHERE (s.referral_code = ? OR s.enrollment_no = ?) AND s.admission_status IN ('enrolled', 'active', 'completed')");
$stmt->execute([$code, $code]);
$referrer = $stmt->fetch();

if ($referrer) {
    echo json_encode([
        'valid' => true,
        'enrollment_no' => $referrer['enrollment_no'],
        'name' => $referrer['full_name']
    ]);
} else {
    echo json_encode(['valid' => false]);
}
