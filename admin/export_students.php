<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="students_list_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Enrollment No', 'Name', 'Phone', 'Email', 'Admission Status', 'Date']);

$query = "SELECT s.enrollment_no, u.full_name, s.phone, u.email, s.admission_status, s.created_at 
          FROM students s 
          JOIN users u ON s.user_id = u.id 
          ORDER BY s.created_at DESC";
$result = $pdo->query($query);

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}
fclose($output);
exit;
