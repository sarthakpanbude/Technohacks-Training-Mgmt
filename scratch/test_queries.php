<?php
require 'config/db.php';
try {
    $pdo->query("SELECT id, name, mobile as phone, course, status, created_at, message, 'inquiry' as source FROM inquiries WHERE status NOT IN ('admitted', 'deleted') OR status IS NULL OR status = ''");
    echo "inquiries query OK\n";
    $pdo->query("SELECT id, name, phone, course_interest as course, status, created_at, message, 'visitor' as source FROM visitors WHERE status NOT IN ('converted', 'rejected')");
    echo "visitors query OK\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
