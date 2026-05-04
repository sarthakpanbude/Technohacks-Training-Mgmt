<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT name FROM courses WHERE name LIKE '%Industrial Training%'");
print_r($stmt->fetchAll());
?>
