<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Referral & Earn";
$activePage = "referrals";

// Check if Referral System is Enabled
$sys_settings = $pdo->query("SELECT referral_system_enabled FROM settings WHERE id=1")->fetch();
if (!($sys_settings['referral_system_enabled'] ?? 1)) {
    header("Location: dashboard.php");
    exit;
}

// Run automation logic on page load
include 'actions/referral_automation.php';

// Filter & Search Params
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Statistics
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_referrals,
        SUM(CASE WHEN status = 'Approved' THEN bonus_amount ELSE 0 END) as total_approved_bonus,
        SUM(CASE WHEN status = 'Approved' AND payout_status = 'Paid' THEN bonus_amount ELSE 0 END) as paid_bonus,
        SUM(CASE WHEN status = 'Approved' AND payout_status = 'Unpaid' THEN bonus_amount ELSE 0 END) as unpaid_bonus,
        COUNT(CASE WHEN status = 'Approved' AND payout_status = 'Unpaid' THEN 1 END) as approved_count,
        SUM(CASE WHEN status IN ('Pending', 'Pending Full Payment', 'Waiting Refund Period') THEN bonus_amount ELSE 0 END) as pending_bonus,
        COUNT(CASE WHEN status IN ('Pending', 'Pending Full Payment', 'Waiting Refund Period') THEN 1 END) as pending_count,
        COUNT(CASE WHEN status = 'Waiting Refund Period' THEN 1 END) as waiting_count
    FROM referral_bonuses
")->fetch();

// Build Query
$sql = "SELECT rb.*, u1.full_name as referrer_name, u2.full_name as referred_name, 
               s2.enrollment_no as referred_id_str, sf.pending_fee, s2.admission_status
        FROM referral_bonuses rb
        LEFT JOIN students s1 ON rb.referrer_id = s1.enrollment_no
        LEFT JOIN users u1 ON s1.user_id = u1.id
        LEFT JOIN students s2 ON rb.referred_id = s2.enrollment_no
        LEFT JOIN users u2 ON s2.user_id = u2.id
        LEFT JOIN student_fees sf ON s2.enrollment_no = sf.student_id
        WHERE 1=1";

$params = [];

if ($search) {
    $sql .= " AND (u1.full_name LIKE ? OR u2.full_name LIKE ? OR rb.referrer_id LIKE ? OR rb.referred_id LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($status_filter) {
    if ($status_filter == 'Waiting') {
        $sql .= " AND rb.status = 'Waiting Refund Period'";
    } elseif ($status_filter == 'Pending') {
        $sql .= " AND rb.status IN ('Pending', 'Pending Full Payment')";
    } else {
        $sql .= " AND rb.status = ?";
        $params[] = $status_filter;
    }
}

// Sorting
switch ($sort) {
    case 'oldest': $sql .= " ORDER BY rb.created_at ASC"; break;
    case 'bonus_high': $sql .= " ORDER BY rb.bonus_amount DESC"; break;
    case 'bonus_low': $sql .= " ORDER BY rb.bonus_amount ASC"; break;
    default: $sql .= " ORDER BY rb.created_at DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$referrals = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content p-4">
    <?php include '../includes/topbar.php'; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Referral & Earn</h2>
            <p class="text-muted small">Monitor student referrals and bonus payouts.</p>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_GET['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4 row-cols-1 row-cols-md-3 row-cols-lg-5">
        <div class="col">
            <div class="stat-card p-3 bg-white shadow-sm rounded-4 border-bottom border-primary border-4 h-100 transition-hover">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                        <i class="fas fa-users fs-5"></i>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill x-small">Growth</span>
                </div>
                <span class="text-muted small d-block mb-1">Total Referrals</span>
                <h3 class="fw-bold mb-0"><?php echo $stats['total_referrals']; ?></h3>
            </div>
        </div>
        <div class="col">
            <div class="stat-card p-3 bg-white shadow-sm rounded-4 border-bottom border-success border-4 h-100 transition-hover">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="bg-success bg-opacity-10 text-success p-2 rounded-3">
                        <i class="fas fa-hand-holding-usd fs-5"></i>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill x-small">Settled</span>
                </div>
                <span class="text-muted small d-block mb-1">Paid Bonus</span>
                <h3 class="fw-bold mb-0 text-success">₹<?php echo number_format($stats['paid_bonus'], 0); ?></h3>
            </div>
        </div>
        <div class="col">
            <div class="stat-card p-3 bg-white shadow-sm rounded-4 border-bottom border-primary border-4 h-100 transition-hover">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                        <i class="fas fa-check-double fs-5"></i>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill x-small">Eligible</span>
                </div>
                <span class="text-muted small d-block mb-1">To Be Paid</span>
                <h3 class="fw-bold mb-0 text-primary">₹<?php echo number_format($stats['unpaid_bonus'], 0); ?></h3>
                <small class="x-small text-muted"><?php echo $stats['approved_count']; ?> Payouts</small>
            </div>
        </div>
        <div class="col">
            <div class="stat-card p-3 bg-white shadow-sm rounded-4 border-bottom border-warning border-4 h-100 transition-hover">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="bg-warning bg-opacity-10 text-warning p-2 rounded-3">
                        <i class="fas fa-clock fs-5"></i>
                    </div>
                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill x-small">Pipeline</span>
                </div>
                <span class="text-muted small d-block mb-1">Pending Bonus</span>
                <h3 class="fw-bold mb-0 text-warning">₹<?php echo number_format($stats['pending_bonus'], 0); ?></h3>
                <small class="x-small text-muted"><?php echo $stats['pending_count']; ?> Pending</small>
            </div>
        </div>
        <div class="col">
            <div class="stat-card p-3 bg-white shadow-sm rounded-4 border-bottom border-info border-4 h-100 transition-hover">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="bg-info bg-opacity-10 text-info p-2 rounded-3">
                        <i class="fas fa-hourglass-half fs-5"></i>
                    </div>
                    <span class="badge bg-info bg-opacity-10 text-info rounded-pill x-small">Active</span>
                </div>
                <span class="text-muted small d-block mb-1">Waiting Period</span>
                <h3 class="fw-bold mb-0 text-info"><?php echo $stats['waiting_count']; ?></h3>
                <small class="x-small text-muted">Refund window</small>
            </div>
        </div>
    </div>

    <style>
    .transition-hover { transition: all 0.3s ease; border-top: 1px solid #eee; border-left: 1px solid #eee; border-right: 1px solid #eee; }
    .transition-hover:hover { transform: translateY(-5px); shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
    .x-small { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
    </style>

    <!-- Filters & Search -->
    <div class="stat-card p-3 bg-white shadow-sm rounded-4 mb-4" style="height: auto;">
        <form action="" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Search Referrer or Referred</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Name or Student ID..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Filter Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Approved" <?php echo $status_filter == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="Waiting" <?php echo $status_filter == 'Waiting' ? 'selected' : ''; ?>>Waiting Refund Period</option>
                    <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Payment Pending</option>
                    <option value="Cancelled Due To Refund" <?php echo $status_filter == 'Cancelled Due To Refund' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Sort By</label>
                <select name="sort" class="form-select">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="bonus_high" <?php echo $sort == 'bonus_high' ? 'selected' : ''; ?>>Bonus (High to Low)</option>
                    <option value="bonus_low" <?php echo $sort == 'bonus_low' ? 'selected' : ''; ?>>Bonus (Low to High)</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                    Apply <i class="fas fa-filter ms-1 small"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="stat-card border-0 shadow-sm rounded-4 overflow-hidden bg-white" style="height: auto;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 text-muted small text-uppercase fw-bold">Referrer</th>
                        <th class="py-3 text-muted small text-uppercase fw-bold">Referred Student</th>
                        <th class="py-3 text-muted small text-uppercase fw-bold">Amount Details</th>
                        <th class="py-3 text-muted small text-uppercase fw-bold text-center">Status Tracking</th>
                        <th class="py-3 text-muted small text-uppercase fw-bold text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($referrals)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No referrals match your criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($referrals as $r): ?>
                            <tr>
                                <td class="px-4">
                                    <div class="fw-bold"><?php echo htmlspecialchars($r['referrer_name'] ?? 'Deleted/Unknown'); ?></div>
                                    <div class="text-muted x-small"><?php echo $r['referrer_id']; ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($r['referred_name'] ?? 'Deleted Student'); ?></div>
                                    <div class="text-muted x-small"><?php echo $r['referred_id']; ?></div>
                                </td>
                                <td>
                                    <div class="small">Final Fee: ₹<?php echo number_format($r['final_fee'], 0); ?></div>
                                    <div class="fw-bold text-primary">Bonus: ₹<?php echo number_format($r['bonus_amount'], 0); ?></div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                                        <?php if ($r['status'] == 'Approved'): ?>
                                            <?php if ($r['payout_status'] == 'Paid'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2">
                                                    <i class="fas fa-check-double me-1"></i> Paid 
                                                    <small class="d-block x-small opacity-75 mt-1"><?php echo date('d M Y', strtotime($r['paid_at'])); ?></small>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2">
                                                    <i class="fas fa-check-circle me-1"></i> Eligible for Payout
                                                </span>
                                            <?php endif; ?>
                                        <?php elseif ($r['status'] == 'Cancelled Due To Refund' || $r['admission_status'] == 'cancelled' || $r['admission_status'] == 'refunded'): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-2">
                                                <i class="fas fa-times-circle me-1"></i> Cancelled
                                            </span>
                                        <?php else: ?>
                                            <?php if ($r['pending_fee'] > 0): ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-2">
                                                    <i class="fas fa-clock me-1"></i> Full Payment Pending
                                                </span>
                                            <?php endif; ?>

                                            <?php if (strtotime($r['refund_expiry_date']) > time()): ?>
                                                <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2">
                                                    <i class="fas fa-hourglass-half me-1"></i> Waiting Refund Period (Until <?php echo date('d M, Y', strtotime($r['refund_expiry_date'])); ?>)
                                                </span>
                                            <?php endif; ?>

                                            <?php if ($r['status'] == 'Pending' && $r['pending_fee'] <= 0 && strtotime($r['refund_expiry_date']) <= time()): ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-2">
                                                    <i class="fas fa-spinner fa-spin me-1"></i> Processing Approval
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <?php if ($r['status'] == 'Approved' && $r['payout_status'] == 'Unpaid'): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-primary rounded-pill px-3 mb-1"
                                                    onclick="openPayoutModal(<?php echo $r['id']; ?>, '<?php echo addslashes($r['referrer_name']); ?>', '<?php echo number_format($r['bonus_amount'], 2); ?>')">
                                                <i class="fas fa-hand-holding-usd me-1"></i> Pay Now
                                            </button>
                                        <?php endif; ?>

                                        <?php if (strpos($r['status'], 'Cancelled') !== false || $r['admission_status'] == 'cancelled' || $r['admission_status'] == 'refunded'): ?>
                                            <a href="actions/delete_referral.php?id=<?php echo $r['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                               onclick="return confirm('Are you sure you want to delete this cancelled referral record permanently?')">
                                                <i class="fas fa-trash-alt me-1"></i> Delete
                                            </a>
                                        <?php elseif ($r['payout_status'] == 'Paid'): ?>
                                            <small class="text-muted x-small d-block">Ref: <?php echo htmlspecialchars($r['payment_ref'] ?? '-'); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Payout Modal -->
<div class="modal fade" id="payoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary"><i class="fas fa-hand-holding-usd me-2"></i>Record Bonus Payout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/pay_bonus.php" method="POST">
                <div class="modal-body py-4">
                    <input type="hidden" name="referral_id" id="modal_referral_id">
                    
                    <div class="text-center mb-4">
                        <p class="text-muted mb-1">Paying bonus to:</p>
                        <h4 class="fw-bold mb-1" id="modal_referrer_name"></h4>
                        <div class="display-6 fw-bold text-success" id="modal_bonus_amount"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Payment Reference (Optional)</label>
                        <input type="text" name="payment_ref" class="form-control rounded-pill" placeholder="UPI ID, Transaction No, etc.">
                    </div>
                    <p class="x-small text-muted">Confirming this will mark the bonus as 'Paid' and record the current date.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow">Confirm Payout</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openPayoutModal(id, name, amount) {
    document.getElementById('modal_referral_id').value = id;
    document.getElementById('modal_referrer_name').innerText = name;
    document.getElementById('modal_bonus_amount').innerText = '₹' + amount;
    new bootstrap.Modal(document.getElementById('payoutModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
