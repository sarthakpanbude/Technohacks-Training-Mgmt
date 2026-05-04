<?php
require_once 'config/db.php';
$stmt = $pdo->query("DESCRIBE batches");
print_r($stmt->fetchAll());
?>
