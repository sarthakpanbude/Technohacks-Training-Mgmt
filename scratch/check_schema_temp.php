<?php
$pdo = new PDO('mysql:host=localhost;dbname=technohacks_erp', 'root', '');
echo "STUDENTS_BASIC:\n";
$stmt = $pdo->query('DESCRIBE students_basic');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "\nSTUDENT_FEES:\n";
$stmt = $pdo->query('DESCRIBE student_fees');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
