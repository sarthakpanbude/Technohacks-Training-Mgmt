<?php
require_once 'config/db.php';
$stmt = $pdo->query("DESCRIBE student_documents");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
