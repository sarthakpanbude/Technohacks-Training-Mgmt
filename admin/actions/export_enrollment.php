<?php
require_once '../../includes/auth.php';
checkAuth('admin');
require_once '../../config/db.php';

$format = $_GET['format'] ?? 'excel';

// Handle Filters (same logic as in enrollment_review.php)
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$where_clauses = [];
$params = [];

if ($search) {
    $where_clauses[] = "(u.full_name LIKE ? OR s.enrollment_no LIKE ? OR s.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status) {
    $where_clauses[] = "s.admission_status = ?";
    $params[] = $status;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$query = "
    SELECT 
        s.enrollment_no,
        u.full_name,
        u.email,
        s.phone,
        s.admission_status,
        sf.total_fee,
        sf.paid_fee,
        sf.pending_fee,
        (SELECT receipt_no FROM invoices WHERE student_id = s.id ORDER BY created_at DESC LIMIT 1) as receipt_no
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN student_fees sf ON sf.student_id = s.enrollment_no
    $where_sql
    ORDER BY s.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($format == 'excel') {
    $filename = "Enrollment_Export_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['Enrollment No', 'Full Name', 'Email', 'Phone', 'Receipt No', 'Total Fee', 'Paid Fee', 'Pending Fee', 'Status']);
    
    // Rows
    foreach ($data as $row) {
        fputcsv($output, [
            $row['enrollment_no'],
            $row['full_name'],
            $row['email'],
            $row['phone'],
            $row['receipt_no'] ?: 'N/A',
            $row['total_fee'],
            $row['paid_fee'],
            $row['pending_fee'],
            $row['admission_status']
        ]);
    }
    
    fclose($output);
    exit;
}
