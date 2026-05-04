<?php
require_once 'config/db.php';
$stmt = $pdo->query("DESCRIBE courses");
print_r($stmt->fetchAll());
?>
