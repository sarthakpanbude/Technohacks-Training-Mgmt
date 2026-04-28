<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$fees = $pdo->query("SELECT s.enrollment_no, u.full_name, sf.total_fee, sf.paid_fee, sf.pending_fee, sf.payment_mode 
                     FROM student_fees sf 
                     JOIN students_basic sb ON sf.student_id = sb.student_id
                     JOIN students s ON sb.student_id = s.enrollment_no
                     JOIN users u ON s.user_id = u.id")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fees Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print { .no-print { display: none; } }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="p-5">
    <div class="text-center mb-5">
        <img src="../assets/img/logo.png" height="60" class="mb-3">
        <h2 class="fw-bold">TechnoHacks IT Solutions</h2>
        <p class="text-muted">Fees Collection Report - <?php echo date('M d, Y'); ?></p>
    </div>
    
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Total Fee</th>
                <th>Paid</th>
                <th>Pending</th>
                <th>Mode</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($fees as $f): ?>
            <tr>
                <td><?php echo $f['enrollment_no']; ?></td>
                <td><?php echo $f['full_name']; ?></td>
                <td>₹<?php echo number_format($f['total_fee'], 2); ?></td>
                <td>₹<?php echo number_format($f['paid_fee'], 2); ?></td>
                <td>₹<?php echo number_format($f['pending_fee'], 2); ?></td>
                <td><?php echo $f['payment_mode']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="mt-5 text-end no-print">
        <button onclick="window.print()" class="btn btn-primary">Print Report / Save PDF</button>
        <button onclick="window.close()" class="btn btn-light border">Close</button>
    </div>
    
    <script>window.onload = function() { // window.print(); }</script>
</body>
</html>
