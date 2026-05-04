<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Referral Management";
$activePage = "referrals";

// Handle Actions
if (isset($_POST['action'])) {
    $id = $_POST['id'];
    $action = $_POST['action'];
    
    if ($action == 'paid') {
        $stmt = $pdo->prepare("UPDATE referral_bonus SET status = 'paid' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action == 'fee_adjust') {
        $stmt = $pdo->prepare("UPDATE referral_bonus SET payment_type = 'fee_adjust', status = 'paid' WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    header("Location: referrals.php?msg=Referral Updated");
    exit;
}

// Fetch Referrals
$referrals = $pdo->query("SELECT rb.*, u1.full_name as referrer_name, u2.full_name as referred_name, sf.paid_fee as referred_paid, c.course_name
                          FROM referral_bonus rb 
                          JOIN students s1 ON rb.referrer_id = s1.enrollment_no 
                          JOIN users u1 ON s1.user_id = u1.id 
                          JOIN students s2 ON rb.referred_student_id = s2.enrollment_no 
                          JOIN users u2 ON s2.user_id = u2.id 
                          LEFT JOIN student_fees sf ON s2.enrollment_no = sf.student_id
                          LEFT JOIN courses c ON s2.course = c.course_name
                          ORDER BY rb.created_at DESC")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Referral Management</h2>
            <p class="text-muted">Track and manage student referral bonuses.</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0">Referrer</th>
                        <th class="border-0">Referred Student</th>
                        <th class="border-0">Course</th>
                        <th class="border-0">Fee Paid?</th>
                        <th class="border-0">Bonus</th>
                        <th class="border-0">Type</th>
                        <th class="border-0">Status</th>
                        <th class="border-0">Date</th>
                        <th class="border-0 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($referrals)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No referrals found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($referrals as $r): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($r['referrer_name']); ?></div>
                                    <small class="text-muted"><?php echo $r['referrer_id']; ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($r['referred_name']); ?></div>
                                    <small class="text-muted"><?php echo $r['referred_student_id']; ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border rounded-pill px-3">
                                        <?php echo htmlspecialchars($r['course_name'] ?: 'General Admission'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($r['referred_paid'] > 0): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 small">₹<?php echo number_format($r['referred_paid'], 0); ?> Paid</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 small">No Payment</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-success">₹<?php echo number_format($r['bonus_amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3">
                                        <?php echo $r['payment_type'] == 'wallet' ? 'Wallet' : 'Fee Adjust'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($r['status'] == 'pending'): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3">Pending</span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Paid</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
                                <td class="text-end">
                                    <?php if ($r['status'] == 'pending'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                            <button type="submit" name="action" value="paid" class="btn btn-sm btn-success rounded-pill px-3 me-1">Mark Paid</button>
                                            <button type="submit" name="action" value="fee_adjust" class="btn btn-sm btn-outline-primary rounded-pill px-3">Fee Adjust</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">Completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
