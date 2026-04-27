<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Fees & Billing";
$activePage = "fees";

// Handle Payment Entry
if (isset($_POST['add_payment'])) {
    $student_id = $_POST['student_id'];
    $amount = $_POST['amount'];
    $type = $_POST['payment_type'];
    $receipt = "REC-" . time();

    $stmt = $pdo->prepare("INSERT INTO payments (student_id, amount, payment_type, receipt_no) VALUES (?, ?, ?, ?)");
    $stmt->execute([$student_id, $amount, $type, $receipt]);
    header("Location: fees.php?msg=Payment Recorded");
    exit;
}

$payments = $pdo->query("SELECT p.*, u.full_name FROM payments p JOIN students s ON p.student_id = s.id JOIN users u ON s.user_id = u.id ORDER BY p.payment_date DESC")->fetchAll();
$students = $pdo->query("SELECT s.id, u.full_name FROM students s JOIN users u ON s.user_id = u.id")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Fees & Billing</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal"><i class="fas fa-plus me-2"></i>Record Payment</button>
    </div>

    <div class="stat-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0">Receipt #</th>
                        <th class="border-0">Student</th>
                        <th class="border-0">Amount</th>
                        <th class="border-0">Type</th>
                        <th class="border-0">Date</th>
                        <th class="border-0">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No payments recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?php echo $p['receipt_no']; ?></td>
                            <td><?php echo $p['full_name']; ?></td>
                            <td>₹<?php echo number_format($p['amount'], 2); ?></td>
                            <td><span class="badge bg-info bg-opacity-10 text-info rounded-pill"><?php echo $p['payment_type']; ?></span></td>
                            <td class="text-muted small"><?php echo date('d M Y', strtotime($p['payment_date'])); ?></td>
                            <td>
                                <a href="invoice.php?id=<?php echo $p['id']; ?>" class="btn btn-light btn-sm rounded-pill px-3">
                                    <i class="fas fa-download me-1"></i> PDF
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Student</label>
                            <select name="student_id" class="form-select" required>
                                <?php foreach ($students as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo $s['full_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Amount (INR)</label>
                            <input type="number" name="amount" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Payment Type</label>
                            <select name="payment_type" class="form-select">
                                <option value="full">Full Payment</option>
                                <option value="installment">Installment</option>
                                <option value="late_fee">Late Fee</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="submit" name="add_payment" class="btn btn-primary w-100">Save Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
