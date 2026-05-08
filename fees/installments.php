<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Fees Management";
$activePage = "fees";

// Fetch Students with their fee details
$stmt = $pdo->query("SELECT s.id as student_real_id, s.enrollment_no, u.full_name, s.course, 
                            sf.total_fee, sf.pending_fee, sf.installments as total_installments,
                            (SELECT COUNT(*) FROM installments WHERE student_id = s.id AND status = 'Paid') as paid_count
                     FROM students s 
                     JOIN users u ON s.user_id = u.id 
                     JOIN student_fees sf ON s.enrollment_no = sf.student_id
                     ORDER BY s.created_at DESC");
$fees_data = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Fees Management</h2>
            <p class="text-muted small">Monitor student payments, pending balances, and installment plans.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="../admin/fees.php" class="btn btn-outline-primary rounded-pill px-4 shadow-sm">
                <i class="fas fa-history me-2"></i>Payment History
            </a>
        </div>
    </div>

    <div class="stat-card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 text-muted small text-uppercase fw-bold">Student ID</th>
                        <th class="py-3 text-muted small text-uppercase fw-bold">Student Name</th>
                        <th class="py-3 text-muted small text-uppercase fw-bold">Course Name</th>
                        <th class="py-3 text-muted small text-uppercase fw-bold">Total Fees</th>
                        <th class="py-3 text-muted small text-uppercase fw-bold">Remaining Fees</th>
                        <th class="py-3 text-muted small text-uppercase fw-bold">Installment</th>
                        <th class="px-4 py-3 text-muted small text-uppercase fw-bold text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fees_data)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-money-bill-wave d-block mb-2 h3 opacity-50"></i>
                                No fee records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fees_data as $f): 
                            $pending_color = ($f['pending_fee'] > 0) ? 'text-danger' : 'text-success';
                        ?>
                            <tr>
                                <td class="px-4">
                                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($f['enrollment_no']); ?></span>
                                </td>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($f['full_name']); ?></td>
                                <td>
                                    <span class="badge bg-purple-light text-purple rounded px-2 py-1 small">
                                        <?php echo htmlspecialchars($f['course'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td class="fw-bold">₹<?php echo number_format($f['total_fee'], 2); ?></td>
                                <td class="fw-bold <?php echo $pending_color; ?>">
                                    ₹<?php echo number_format($f['pending_fee'], 2); ?>
                                </td>
                                <td>
                                    <div class="small fw-bold text-muted">
                                        <?php echo $f['paid_count']; ?> / <?php echo $f['total_installments']; ?> Paid
                                    </div>
                                    <div class="progress mt-1" style="height: 6px; width: 80px;">
                                        <?php 
                                        $percent = ($f['total_installments'] > 0) ? ($f['paid_count'] / $f['total_installments']) * 100 : 0;
                                        ?>
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percent; ?>%"></div>
                                    </div>
                                </td>
                                <td class="px-4 text-center">
                                    <a href="../admin/generate_receipt.php?id=<?php echo $f['enrollment_no']; ?>" 
                                       class="btn btn-outline-success btn-sm rounded px-3 shadow-sm">
                                        <i class="fas fa-receipt me-2"></i>View Fees Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<style>
    .bg-purple-light { background-color: rgba(128, 0, 128, 0.08); }
    .text-purple { color: #800080; }
    .stat-card {
        background: white;
        border-radius: 15px;
        border: 1px solid #eee;
    }
    .table thead th {
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        background: #fdfdfd;
        border-bottom: 2px solid #eee !important;
    }
    .table tbody td {
        padding: 18px 15px !important;
        border-bottom: 1px solid #f0f0f0 !important;
    }
    .progress {
        background-color: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
    }
</style>

<?php include '../includes/footer.php'; ?>
