<?php
require_once '../includes/auth.php';
checkAuth('student');
require_once '../config/db.php';

$pageTitle = "My Referrals";
$activePage = "referrals";

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT enrollment_no FROM students WHERE user_id = ?");
$stmt->execute([$userId]);
$student = $stmt->fetch();
$enrollment_no = $student['enrollment_no'];

// Handle Fee Adjustment Request
if (isset($_POST['use_bonus'])) {
    $stmt = $pdo->prepare("UPDATE referral_bonus SET payment_type = 'fee_adjust', status = 'pending' WHERE referrer_id = ? AND status = 'pending'");
    $stmt->execute([$enrollment_no]);
    header("Location: referrals.php?msg=Request Sent");
    exit;
}

// Fetch Referral Stats
$stats = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(bonus_amount) as earned,
    SUM(CASE WHEN status = 'paid' THEN bonus_amount ELSE 0 END) as paid,
    SUM(CASE WHEN status = 'pending' THEN bonus_amount ELSE 0 END) as pending
    FROM referral_bonus WHERE referrer_id = ?");
$stats->execute([$enrollment_no]);
$s = $stats->fetch();

// Fetch History
$history = $pdo->prepare("SELECT rb.*, u.full_name as referred_name 
                          FROM referral_bonus rb 
                          JOIN students s ON rb.referred_student_id = s.enrollment_no 
                          JOIN users u ON s.user_id = u.id 
                          WHERE rb.referrer_id = ? 
                          ORDER BY rb.created_at DESC");
$history->execute([$enrollment_no]);
$referrals = $history->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Refer & Earn</h2>
            <p class="text-muted">Invite your friends and earn 10% bonus on their admission.</p>
        </div>
        <div class="bg-white p-3 rounded shadow-sm">
            <small class="text-muted d-block">Your Referral ID</small>
            <span class="fw-bold text-primary fs-5"><?php echo $enrollment_no; ?></span>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="ms-3">
                        <small class="text-muted d-block">Total Referrals</small>
                        <h4 class="fw-bold mb-0"><?php echo $s['total'] ?: 0; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="ms-3">
                        <small class="text-muted d-block">Total Earned</small>
                        <h4 class="fw-bold mb-0">₹<?php echo number_format($s['earned'] ?: 0, 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-success">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="ms-3">
                        <small class="text-muted d-block">Paid/Adjusted</small>
                        <h4 class="fw-bold mb-0">₹<?php echo number_format($s['paid'] ?: 0, 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-warning">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="ms-3">
                        <small class="text-muted d-block">Pending</small>
                        <h4 class="fw-bold mb-0">₹<?php echo number_format($s['pending'] ?: 0, 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="stat-card">
                <h6 class="fw-bold mb-4">Referral History</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0">Student Name</th>
                                <th class="border-0">Bonus</th>
                                <th class="border-0">Type</th>
                                <th class="border-0">Status</th>
                                <th class="border-0">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($referrals)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No referrals yet. Start inviting!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($referrals as $r): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['referred_name']); ?></td>
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
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-light border-0 shadow-none">
                <h6 class="fw-bold mb-3">Redeem Bonus</h6>
                <p class="small text-muted mb-4">You can use your pending bonus to pay your upcoming course fees.</p>
                
                <div class="p-3 bg-white rounded border mb-4">
                    <small class="text-muted d-block">Available for Adjustment</small>
                    <h3 class="fw-bold mb-0">₹<?php echo number_format($s['pending'] ?: 0, 2); ?></h3>
                </div>

                <form method="POST">
                    <button type="submit" name="use_bonus" class="btn btn-primary w-100 py-2 rounded-pill" 
                            <?php echo ($s['pending'] <= 0) ? 'disabled' : ''; ?>>
                        <i class="fas fa-hand-holding-usd me-2"></i>Use in Fee Adjustment
                    </button>
                </form>
                <small class="text-center d-block mt-3 text-muted">Admin approval required for adjustment.</small>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
