<?php
require 'config/db.php';
$stmt = $pdo->query("SELECT id FROM students LIMIT 1");
$id = $stmt->fetchColumn();
echo $id;
