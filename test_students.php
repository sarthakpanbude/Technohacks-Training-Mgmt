<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Admin User';

ob_start();
try {
    include 'admin/students.php';
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " on line " . $e->getLine();
}
$html = ob_get_clean();

file_put_contents('scratch/students_output.html', $html);
echo "Output length: " . strlen($html);
