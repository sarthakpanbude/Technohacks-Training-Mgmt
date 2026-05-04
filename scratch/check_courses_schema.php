<?php
$pdo = new PDO('mysql:host=localhost;dbname=technohacks_erp', 'root', '');
echo "COURSES:\n";
$stmt = $pdo->query('DESCRIBE courses');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
