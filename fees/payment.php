<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Process Payment";
$activePage = "fees";

$installment_id = $_GET['installment_id'] ?? 0;

$stmt = $pdo->prepare("SELECT i.*, u.full_name, s.enrollment_no, u.email 
                       FROM installments i 
                       JOIN students s ON i.student_id = s.id 
                       JOIN users u ON s.user_id = u.id 
                       WHERE i.id = ? AND i.status = 'Pending'");
$stmt->execute([$installment_id]);
$installment = $stmt->fetch();

if (!$installment) {
    header("Location: installments.php?msg=Installment not found or already paid.");
    exit;
}

$is_overdue = (strtotime($installment['due_date']) < strtotime(date('Y-m-d')));
$late_fee = $is_overdue ? 100.00 : 0.00; // Fixed late fee logic
$total_payable = $installment['amount'] + $late_fee;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $payment_mode = $_POST['payment_mode'];
    $amount_paid = $_POST['amount_paid'];
    
    // Auto Generate Receipt No: RCPT-YYYY-0001
    $year = date('Y');
    $last_receipt = $pdo->query("SELECT receipt_no FROM invoices WHERE receipt_no LIKE 'RCPT-$year-%' ORDER BY id DESC LIMIT 1")->fetchColumn();
    if ($last_receipt) {
        $num = intval(substr($last_receipt, -4)) + 1;
        $receipt_no = "RCPT-" . $year . "-" . str_pad($num, 4, '0', STR_PAD_LEFT);
    } else {
        $receipt_no = "RCPT-" . $year . "-0001";
    }

    try {
        $pdo->beginTransaction();

        // Update installment
        $upd = $pdo->prepare("UPDATE installments SET status = 'Paid', late_fee = ? WHERE id = ?");
        $upd->execute([$late_fee, $installment_id]);

        // Create Invoice
        $inv = $pdo->prepare("INSERT INTO invoices (student_id, receipt_no, amount, payment_mode) VALUES (?, ?, ?, ?)");
        $inv->execute([$installment['student_id'], $receipt_no, $amount_paid, $payment_mode]);
        $invoice_id = $pdo->lastInsertId();

        $pdo->commit();
        
        // Optional Email Notification logic can be added here
        
        header("Location: receipt.php?id=" . $invoice_id . "&msg=Payment Successful!");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Payment failed: " . $e->getMessage();
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Process Payment</h2>
        <a href="installments.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="stat-card">
                <h5 class="fw-bold border-bottom pb-2 mb-3">Installment Details</h5>
                <p><strong>Student:</strong> <?php echo htmlspecialchars($installment['full_name']); ?></p>
                <p><strong>Enrollment No:</strong> <?php echo $installment['enrollment_no'] ?? 'N/A'; ?></p>
                <p><strong>Installment No:</strong> <?php echo $installment['installment_no']; ?></p>
                <p><strong>Due Date:</strong> <?php echo date('d M Y', strtotime($installment['due_date'])); ?></p>
                
                <hr>
                
                <div class="d-flex justify-content-between mb-2">
                    <span>Base Amount:</span>
                    <span class="fw-bold">₹<?php echo number_format($installment['amount'], 2); ?></span>
                </div>
                <?php if ($is_overdue): ?>
                <div class="d-flex justify-content-between mb-2 text-danger">
                    <span>Late Fee:</span>
                    <span class="fw-bold">₹<?php echo number_format($late_fee, 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mt-3 pt-2 border-top fs-5 text-primary">
                    <strong>Total Payable:</strong>
                    <strong>₹<?php echo number_format($total_payable, 2); ?></strong>
                </div>
            </div>
        </div>
        
        <div class="col-md-7">
            <div class="stat-card">
                <h5 class="fw-bold border-bottom pb-2 mb-3">Make Payment</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Amount to Pay (₹)</label>
                        <input type="number" name="amount_paid" class="form-control fw-bold text-success fs-5" value="<?php echo $total_payable; ?>" readonly>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Payment Mode</label>
                        <select name="payment_mode" class="form-select form-select-lg" required>
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI</option>
                            <option value="Card">Credit/Debit Card</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">
                        <i class="fas fa-check-circle me-2"></i>Complete Payment
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
