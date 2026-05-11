<?php
require_once 'config/db.php';
$id = 'T32986';
$stmt = $pdo->prepare("SELECT enrollment_no, referral_code, admission_status FROM students WHERE enrollment_no = ? OR referral_code = ?");
$stmt->execute([$id, $id]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($result);
