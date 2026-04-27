<?php
require_once '../includes/auth.php';
checkAuth('student');
require_once '../config/db.php';

$pageTitle = "My Fees";
$activePage = "fees";

$userId = $_SESSION['user_id'];
$student = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$student->execute([$userId]);
$s_data = $student->fetch();
$studentId = $s_data['id'] ?? 0;

$payments = $pdo->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date DESC");
$payments->execute([$studentId]);
$payments = $payments->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <h2 class="fw-bold mb-4">My Fee Payments</h2>

    <div class="stat-card">
        <?php if (empty($payments)): ?>
            <div class="text-center py-5">
                <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3"></i>
                <h5 class="fw-bold">No Payments Found</h5>
                <p class="text-muted">You have no payment history yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">Receipt No</th>
                            <th class="border-0">Amount</th>
                            <th class="border-0">Type</th>
                            <th class="border-0">Method</th>
                            <th class="border-0">Date</th>
                            <th class="border-0 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?php echo $p['receipt_no']; ?></td>
                            <td class="fw-bold">₹<?php echo number_format($p['amount'], 2); ?></td>
                            <td><span class="badge bg-info bg-opacity-10 text-info rounded-pill text-capitalize"><?php echo str_replace('_', ' ', $p['payment_type']); ?></span></td>
                            <td class="text-capitalize text-muted small"><?php echo $p['payment_method']; ?></td>
                            <td class="text-muted small"><?php echo date('d M Y, h:i A', strtotime($p['payment_date'])); ?></td>
                            <td class="text-end">
                                <a href="../admin/invoice.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank"><i class="fas fa-download me-1"></i>Receipt</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
