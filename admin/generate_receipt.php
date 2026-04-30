<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$student_id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT sb.full_name, sf.* FROM students_basic sb JOIN student_fees sf ON sb.student_id = sf.student_id WHERE sb.student_id = ?");
$stmt->execute([$student_id]);
$s = $stmt->fetch();
if (!$s) exit("Not found");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fee Receipt - <?php echo $student_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .receipt-container { border: 1px dashed #333; padding: 30px; max-width: 600px; margin: auto; background: #fff; }
        .logo { height: 50px; }
        .stamp { border: 2px solid #28a745; color: #28a745; transform: rotate(-15deg); display: inline-block; padding: 5px 10px; font-weight: bold; font-size: 20px; border-radius: 5px; opacity: 0.7; }
        @media print { body { background: none; } .no-print { display: none; } }
    </style>
</head>
<body class="bg-light p-4">
    <div class="receipt-container shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <img src="../assets/img/logo.png" class="logo">
            <div class="text-end">
                <h5 class="fw-bold mb-0 text-success">PAID RECEIPT</h5>
                <small class="text-muted">No: RCPT-<?php echo date('Y') . $s['id']; ?></small>
            </div>
        </div>
        
        <div class="mb-4 text-center">
            <h4 class="fw-bold mb-0">TechnoHacks Solutions</h4>
            <p class="small text-muted">Building Careers, Not Just Code</p>
        </div>
        
        <table class="table table-borderless small">
            <tr><td><strong>Student Name:</strong></td><td><?php echo $s['full_name']; ?></td></tr>
            <tr><td><strong>Enrollment ID:</strong></td><td><?php echo $s['student_id']; ?></td></tr>
            <tr><td><strong>Date:</strong></td><td><?php echo date('d M, Y'); ?></td></tr>
            <tr><td colspan="2"><hr></td></tr>
            <tr><td><strong>Total Course Fee:</strong></td><td class="text-end">₹<?php echo number_format($s['total_fee'], 2); ?></td></tr>
            <tr><td><strong>Paid Amount:</strong></td><td class="text-end text-success fw-bold">₹<?php echo number_format($s['paid_fee'], 2); ?></td></tr>
            <tr><td><strong>Payment Mode:</strong></td><td class="text-end"><?php echo $s['payment_mode']; ?></td></tr>
            <tr><td colspan="2"><hr></td></tr>
            <tr class="table-light"><td><strong>Pending Balance:</strong></td><td class="text-end text-danger fw-bold">₹<?php echo number_format($s['pending_fee'], 2); ?></td></tr>
            
            <?php if ($s['next_installment_amount'] > 0): ?>
            <tr><td><strong>2nd Installment:</strong></td><td class="text-end fw-bold">₹<?php echo number_format($s['next_installment_amount'], 2); ?></td></tr>
            <tr><td><small>Due Date:</small></td><td class="text-end small text-muted"><?php echo $s['next_installment_date'] ? date('d M, Y', strtotime($s['next_installment_date'])) : 'N/A'; ?></td></tr>
            <?php endif; ?>

            <?php if ($s['third_installment_amount'] > 0): ?>
            <tr><td><strong>3rd Installment:</strong></td><td class="text-end fw-bold">₹<?php echo number_format($s['third_installment_amount'], 2); ?></td></tr>
            <tr><td><small>Due Date:</small></td><td class="text-end small text-muted"><?php echo $s['third_installment_date'] ? date('d M, Y', strtotime($s['third_installment_date'])) : 'N/A'; ?></td></tr>
            <?php endif; ?>
        </table>
        
        <div class="mt-5 d-flex justify-content-between align-items-end">
            <div class="text-start">
                <p class="mb-1 small text-muted">Warm regards,</p>
                <div class="mt-2 mb-1">
                    <img src="../assets/img/signature.png" alt="Signature" style="max-height: 50px; display: block;" onerror="this.style.display='none'">
                    <div style="width: 150px; border-bottom: 1px solid #333; margin-bottom: 5px;" class="no-print-border"></div>
                </div>
                <h6 class="fw-bold mb-0" style="font-size: 1.1rem;">Sandip Gavit</h6>
                <p class="small mb-0 text-muted">Founder & CEO</p>
                <p class="small mb-0 text-muted">TechnoHacks Solutions Pvt. Ltd.</p>
            </div>
            <div class="text-center position-relative">
                <div class="company-stamp">
                    <img src="../assets/img/stamp.png" alt="Stamp" style="max-height: 100px; opacity: 0.8;" onerror="this.style.display='none'">
                    <div class="stamp-placeholder no-print" style="width: 100px; height: 100px; border: 2px dashed #ccc; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #ccc;">STAMP HERE</div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 text-center no-print border-top pt-3">
            <button onclick="window.print()" class="btn btn-success btn-sm">Print Receipt</button>
        </div>
    </div>
</body>
</html>
