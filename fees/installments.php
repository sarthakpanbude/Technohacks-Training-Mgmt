<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Pending Installments";
$activePage = "fees";

// Detect pending installments where due_date < today
$stmt = $pdo->query("SELECT i.*, u.full_name, s.enrollment_no 
                     FROM installments i 
                     JOIN students s ON i.student_id = s.id 
                     JOIN users u ON s.user_id = u.id 
                     WHERE i.status = 'Pending' 
                     ORDER BY i.due_date ASC");
$installments = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Pending Installments</h2>
        <a href="add_fees.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Assign New Fees</a>
    </div>

    <div class="stat-card">
        <?php if (empty($installments)): ?>
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5 class="fw-bold">All clear!</h5>
                <p class="text-muted">No pending installments found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">Student</th>
                            <th class="border-0">Installment No</th>
                            <th class="border-0">Amount</th>
                            <th class="border-0">Due Date</th>
                            <th class="border-0">Status</th>
                            <th class="border-0 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($installments as $inst): 
                            $is_overdue = (strtotime($inst['due_date']) < strtotime(date('Y-m-d')));
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($inst['full_name']); ?></div>
                                <div class="small text-muted"><?php echo $inst['enrollment_no'] ?? 'N/A'; ?></div>
                            </td>
                            <td><?php echo $inst['installment_no']; ?></td>
                            <td class="fw-bold text-primary">₹<?php echo number_format($inst['amount'], 2); ?></td>
                            <td>
                                <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : 'text-muted'; ?>">
                                    <?php echo date('d M Y', strtotime($inst['due_date'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($is_overdue): ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill">Overdue</span>
                                <?php else: ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="payment.php?installment_id=<?php echo $inst['id']; ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-money-bill-wave me-1"></i>Pay Now
                                </a>
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
