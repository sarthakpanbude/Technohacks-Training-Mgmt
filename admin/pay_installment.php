<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$inst_id = $_GET['id'] ?? null;
$student_main_id = $_GET['student_id'] ?? null;

if (!$inst_id || !$student_main_id) {
    header("Location: students.php");
    exit;
}

// Fetch Installment Details
$stmt = $pdo->prepare("SELECT i.*, s.enrollment_no, u.full_name FROM installments i JOIN students s ON i.student_id = s.id JOIN users u ON s.user_id = u.id WHERE i.id = ?");
$stmt->execute([$inst_id]);
$installment = $stmt->fetch();

if (!$installment) {
    header("Location: view_student.php?id=$student_main_id&error=Installment not found");
    exit;
}

// Handle Payment
if (isset($_POST['confirm_payment'])) {
    try {
        $pdo->beginTransaction();

        $amount = $installment['amount'];
        $receipt = "REC-INST-" . time();
        $payment_mode = $_POST['payment_mode'];
        $transaction_id = $_POST['transaction_id'] ?? null;
        $notes = $_POST['notes'] ?? null;
        $now = date('Y-m-d H:i:s');

        // 1. Update Installment Status
        $stmt = $pdo->prepare("UPDATE installments SET status = 'Paid', payment_date = ?, transaction_id = ?, notes = ? WHERE id = ?");
        $stmt->execute([$now, $transaction_id, $notes, $inst_id]);

        // 2. Record in Payments Table
        $stmt = $pdo->prepare("INSERT INTO payments (student_id, amount, payment_type, receipt_no, payment_date) VALUES (?, ?, 'Installment', ?, ?)");
        $stmt->execute([$student_main_id, $amount, $receipt, $now]);

        // 3. Update Student Fees Summary
        $stmt = $pdo->prepare("UPDATE student_fees SET paid_fee = paid_fee + ?, pending_fee = pending_fee - ? WHERE student_id = ?");
        $stmt->execute([$amount, $amount, $installment['enrollment_no']]);

        $pdo->commit();
        header("Location: view_student.php?id=$student_main_id&success=Installment paid successfully!");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

$pageTitle = "Pay Installment";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Record Installment Payment</h3>
            <p class="text-muted small">Confirming payment for <?php echo htmlspecialchars($installment['full_name']); ?></p>
        </div>
        <a href="view_student.php?id=<?php echo $student_main_id; ?>" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fas fa-arrow-left me-2"></i>Back to Profile
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="text-center mb-4">
                    <div class="avatar-lg bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Installment #<?php echo $installment['installment_no']; ?></h5>
                    <p class="text-muted small">Due Date: <?php echo date('d M Y', strtotime($installment['due_date'])); ?></p>
                    <hr class="my-4 opacity-25">
                </div>

                <div class="mb-4">
                    <div class="p-3 bg-light rounded-3 mb-3 text-center">
                        <span class="text-muted small d-block mb-1">Amount to Pay:</span>
                        <h2 class="fw-bold text-success mb-0">₹<?php echo number_format($installment['amount'], 2); ?></h2>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Payment Mode</label>
                            <select name="payment_mode" class="form-select rounded-pill py-2" required>
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI/Online</option>
                                <option value="Card">Credit/Debit Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Transaction ID / Reference (Optional)</label>
                            <input type="text" name="transaction_id" class="form-control rounded-pill py-2" placeholder="UPI ID, Check No, etc.">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Notes (Optional)</label>
                            <textarea name="notes" class="form-control rounded-4" rows="2" placeholder="Any additional details..."></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="confirm_payment" class="btn btn-primary btn-lg rounded-pill py-3 fw-bold shadow">
                                Confirm Payment <i class="fas fa-check-circle ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
