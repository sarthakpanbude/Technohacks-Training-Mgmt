<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT * FROM student_documents ORDER BY id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
