<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT inv.*, u.full_name, s.enrollment_no, u.email 
                       FROM invoices inv 
                       JOIN students s ON inv.student_id = s.id 
                       JOIN users u ON s.user_id = u.id 
                       WHERE inv.id = ?");
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    die("Invoice not found.");
}

// DomPDF integration placeholder (Requires composer require dompdf/dompdf)
$download = $_GET['download'] ?? false;
if ($download) {
    // Basic logic for pdf download if dompdf is present
    // require_once 'vendor/autoload.php';
    // $dompdf = new Dompdf\Dompdf();
    // $dompdf->loadHtml($html);
    // $dompdf->render();
    // $dompdf->stream("Receipt_".$invoice['receipt_no'].".pdf");
    // exit;
    // For now we will rely on print to PDF feature via browser
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?php echo $invoice['receipt_no']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
        .receipt-card { max-width: 700px; margin: 40px auto; background: #fff; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-radius: 12px; }
        @media print {
            body { background: #fff; }
            .receipt-card { box-shadow: none; margin: 0; padding: 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success text-center mt-3 no-print fw-bold">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <div class="receipt-card">
            <div class="text-center mb-4 border-bottom pb-4">
                <h2 class="fw-bold text-primary mb-1">TechnoHacks Solutions</h2>
                <p class="text-muted small mb-0">Fastest-growing IT company in Nashik</p>
                <p class="text-muted small mb-0">CIN: U62099MH2024PTC424756</p>
            </div>

            <div class="row mb-4">
                <div class="col-sm-6">
                    <h6 class="fw-bold text-muted text-uppercase small">Payment Receipt</h6>
                    <h5 class="fw-bold mb-0">#<?php echo $invoice['receipt_no']; ?></h5>
                </div>
                <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                    <p class="mb-0"><strong>Date:</strong> <?php echo date('d M Y, h:i A', strtotime($invoice['payment_date'])); ?></p>
                    <p class="mb-0"><strong>Status:</strong> <span class="badge bg-success">PAID</span></p>
                </div>
            </div>

            <div class="row mb-4 bg-light p-3 rounded">
                <div class="col-12">
                    <p class="mb-1"><strong>Student Name:</strong> <?php echo htmlspecialchars($invoice['full_name']); ?></p>
                    <p class="mb-1"><strong>Enrollment No:</strong> <?php echo $invoice['enrollment_no'] ?? 'N/A'; ?></p>
                    <p class="mb-0"><strong>Payment Mode:</strong> <?php echo $invoice['payment_mode']; ?></p>
                </div>
            </div>

            <table class="table table-bordered mb-4">
                <thead class="table-light">
                    <tr>
                        <th>Description</th>
                        <th class="text-end" width="150">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Installment Payment</td>
                        <td class="text-end">₹<?php echo number_format($invoice['amount'], 2); ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="text-end fw-bold">Total Paid:</td>
                        <td class="text-end fw-bold fs-5 text-success">₹<?php echo number_format($invoice['amount'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>

            <div class="text-center text-muted small mt-5 pt-3 border-top">
                <p>This is a computer-generated receipt and does not require a physical signature.</p>
                <p class="mb-0">Thank you for choosing TechnoHacks Solutions!</p>
            </div>
        </div>

        <div class="text-center mb-5 no-print gap-2 d-flex justify-content-center">
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-2"></i>Print Receipt</button>
            <button onclick="window.print()" class="btn btn-outline-danger"><i class="fas fa-file-pdf me-2"></i>Download PDF</button>
            <a href="../admin/dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-home me-2"></i>Dashboard</a>
        </div>
    </div>
</body>
</html>
