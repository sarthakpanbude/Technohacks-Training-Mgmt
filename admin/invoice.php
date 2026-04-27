<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$id = $_GET['id'] ?? 0;
$payment = $pdo->prepare("SELECT p.*, u.full_name, u.email, s.phone, s.enrollment_no FROM payments p JOIN students s ON p.student_id = s.id JOIN users u ON s.user_id = u.id WHERE p.id = ?");
$payment->execute([$id]);
$payment = $payment->fetch();

if (!$payment) {
    header("Location: fees.php?msg=Payment Not Found");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $payment['receipt_no']; ?> | TechnoHacks Solutions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; }
        .invoice-box { max-width: 800px; margin: 40px auto; background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 50px; }
        @media print { body { background: #fff; } .invoice-box { box-shadow: none; margin: 0; } .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="d-flex justify-content-between align-items-start mb-5">
            <div>
                <h3 class="fw-bold" style="color: #4f46e5;"><i class="fas fa-microchip me-2"></i>TechnoHacks Solutions</h3>
                <p class="text-muted mb-0 small">Institute Management ERP</p>
            </div>
            <div class="text-end">
                <h4 class="fw-bold mb-1">INVOICE</h4>
                <p class="text-muted mb-0 small">#<?php echo $payment['receipt_no']; ?></p>
                <p class="text-muted mb-0 small"><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></p>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-md-6">
                <h6 class="text-muted fw-bold small text-uppercase">Billed To</h6>
                <h5 class="fw-bold"><?php echo $payment['full_name']; ?></h5>
                <p class="text-muted mb-0"><?php echo $payment['email']; ?></p>
                <p class="text-muted mb-0"><?php echo $payment['phone'] ?: ''; ?></p>
                <p class="text-muted mb-0">Enrollment: <?php echo $payment['enrollment_no'] ?? 'N/A'; ?></p>
            </div>
            <div class="col-md-6 text-end">
                <h6 class="text-muted fw-bold small text-uppercase">Payment Details</h6>
                <p class="mb-0"><strong>Method:</strong> <?php echo ucfirst($payment['payment_method']); ?></p>
                <p class="mb-0"><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $payment['payment_type'])); ?></p>
            </div>
        </div>

        <table class="table">
            <thead class="bg-light">
                <tr>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Course Fee Payment (<?php echo ucfirst(str_replace('_', ' ', $payment['payment_type'])); ?>)</td>
                    <td class="text-end">₹<?php echo number_format($payment['amount'], 2); ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="border-top">
                    <td class="fw-bold fs-5">Total</td>
                    <td class="text-end fw-bold fs-5 text-success">₹<?php echo number_format($payment['amount'], 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <?php if ($payment['remarks']): ?>
        <div class="bg-light p-3 rounded-3 mt-3">
            <strong class="small">Remarks:</strong> <span class="small text-muted"><?php echo $payment['remarks']; ?></span>
        </div>
        <?php endif; ?>

        <hr class="my-4">
        <p class="text-muted small text-center">This is a computer-generated invoice. No signature required.</p>

        <div class="d-flex gap-2 justify-content-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-2"></i>Print Invoice</button>
            <a href="fees.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
        </div>
    </div>
</body>
</html>
