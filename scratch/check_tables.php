<?php
require_once '../config/db.php';
$stmt = $pdo->query("SHOW TABLES LIKE 'referral_bonus%'");
print_r($stmt->fetchAll());
