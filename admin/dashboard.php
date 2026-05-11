<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Admin Dashboard";
$activePage = "dashboard";

// Fetch Stats
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalReferrals = $pdo->query("SELECT COUNT(*) FROM students WHERE referral_id IS NOT NULL")->fetchColumn() ?: 0;
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();

// Fetch Fee Stats
$totalCollected = $pdo->query("SELECT SUM(amount) FROM invoices")->fetchColumn() ?: 0;
$totalPendingAmt = $pdo->query("SELECT SUM(amount) FROM installments WHERE status = 'Pending'")->fetchColumn() ?: 0;
$overdueCount = $pdo->query("SELECT COUNT(*) FROM installments WHERE status = 'Pending' AND due_date < CURDATE()")->fetchColumn() ?: 0;
$recentTransactions = $pdo->query("SELECT COUNT(*) FROM invoices WHERE DATE(payment_date) = CURDATE()")->fetchColumn() ?: 0;

// Fetch Monthly Enrollments for Chart
$monthlyEnrollments = array_fill(0, 12, 0);
$enrollmentQuery = $pdo->query("SELECT MONTH(enrollment_date) as m, COUNT(*) as c FROM enrollments WHERE YEAR(enrollment_date) = YEAR(CURDATE()) GROUP BY MONTH(enrollment_date)");
while($row = $enrollmentQuery->fetch()) {
    $monthlyEnrollments[$row['m'] - 1] = $row['c'];
}
$chartData = json_encode(array_values($monthlyEnrollments));

// Handle Student ID Search
$searchStudent = null;
if (isset($_GET['search_id']) && !empty($_GET['search_id'])) {
    $search_id = $_GET['search_id'];
    $stmt = $pdo->prepare("SELECT s.*, u.full_name, u.email, sf.total_fee, sf.pending_fee 
                          FROM students s 
                          JOIN users u ON s.user_id = u.id 
                          JOIN student_fees sf ON s.enrollment_no = sf.student_id 
                          WHERE s.enrollment_no = ?");
    $stmt->execute([$search_id]);
    $searchStudent = $stmt->fetch();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <?php include '../includes/topbar.php'; ?>

    <!-- Overdue Alert -->
    <?php if ($overdueCount > 0): ?>
    <div class="alert-banner mb-4" style="background:linear-gradient(135deg,#fef2f2,#fff5f5); border:1px solid #fecaca; border-radius:12px; padding:1rem 1.25rem; display:flex; align-items:center; gap:12px;">
        <div style="width:36px;height:36px;border-radius:10px;background:#ef4444;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-exclamation-triangle" style="color:white;font-size:0.85rem;"></i>
        </div>
        <div>
            <div class="fw-semibold" style="color:#991b1b;font-size:0.875rem;">Fees Due Reminder</div>
            <div style="color:#dc2626;font-size:0.8rem;"><?php echo $overdueCount; ?> student installment(s) are overdue. <a href="../fees/installments.php" style="color:#dc2626;font-weight:600;">View Pending &rarr;</a></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Search Bar + Action Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print gap-3 flex-wrap">
        <form action="" method="GET" class="d-flex gap-2 flex-grow-1" style="max-width:420px;">
            <div class="d-flex align-items-center gap-2 px-3 py-2 bg-white border rounded-pill shadow-sm flex-grow-1" style="border-color:var(--border)!important;">
                <i class="fas fa-search" style="color:var(--text-muted);font-size:0.8rem;"></i>
                <input type="text" name="search_id" class="border-0 outline-0 w-100 bg-transparent"
                       placeholder="Quick Student Search (ID)..."
                       style="font-size:0.875rem;outline:none;"
                       value="<?php echo htmlspecialchars($_GET['search_id'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-primary px-4 rounded-pill" style="font-size:0.875rem;">Search</button>
        </form>
        <div class="d-flex gap-2 flex-wrap">
            <a href="admit.php?new=1" class="btn btn-primary rounded-pill px-4" style="font-size:0.85rem;">
                <i class="fas fa-plus me-2"></i>New Admission
            </a>
            <a href="export_students.php" class="btn btn-sm rounded-pill px-3" style="font-size:0.85rem;border:1px solid var(--success);color:var(--success);background:rgba(16,185,129,0.06);">
                <i class="fas fa-file-excel me-1"></i>Export Students
            </a>
            <a href="export_fees.php" class="btn btn-sm rounded-pill px-3" style="font-size:0.85rem;border:1px solid var(--success);color:var(--success);background:rgba(16,185,129,0.06);">
                <i class="fas fa-file-excel me-1"></i>Export Fees
            </a>
        </div>
    </div>

    <!-- Search Result -->
    <?php if (isset($_GET['search_id']) && !empty($_GET['search_id'])): ?>
        <?php if ($searchStudent): ?>
            <div class="stat-card mb-4" style="border-left:4px solid var(--primary);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,rgba(99,102,241,0.15),rgba(79,70,229,0.08));display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-id-card" style="color:var(--primary);font-size:0.85rem;"></i>
                        </div>
                        <span class="fw-bold" style="color:var(--primary);font-size:0.9rem;">Student Found: <?php echo htmlspecialchars($searchStudent['enrollment_no']); ?></span>
                    </div>
                    <a href="dashboard.php" class="btn-close" style="font-size:0.8rem;"></a>
                </div>
                <div class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <div class="text-xs fw-bold text-uppercase mb-1" style="color:var(--text-light);">Full Name</div>
                        <div class="fw-bold"><?php echo htmlspecialchars($searchStudent['full_name']); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-xs fw-bold text-uppercase mb-1" style="color:var(--text-light);">Course</div>
                        <div class="fw-bold"><?php echo htmlspecialchars($searchStudent['course']); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-xs fw-bold text-uppercase mb-1" style="color:var(--text-light);">Fees (Total / Pending)</div>
                        <div>
                            <span class="fw-bold">₹<?php echo number_format($searchStudent['total_fee'], 2); ?></span>
                            <span class="text-muted mx-1">/</span>
                            <span class="fw-bold" style="color:var(--danger);">₹<?php echo number_format($searchStudent['pending_fee'], 2); ?></span>
                        </div>
                    </div>
                    <div class="col-md-3 text-end d-flex gap-2 justify-content-end">
                        <a href="generate_form.php?id=<?php echo $searchStudent['enrollment_no']; ?>" class="btn btn-primary btn-sm rounded-pill px-3">Form</a>
                        <a href="generate_receipt.php?id=<?php echo $searchStudent['id']; ?>" class="btn btn-sm rounded-pill px-3" style="border:1px solid var(--success);color:var(--success);">Receipt</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="d-flex align-items-center justify-content-between mb-4 p-3 rounded-3" style="background:#fffbeb;border:1px solid #fde68a;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-exclamation-circle" style="color:#f59e0b;"></i>
                    <span style="font-size:0.875rem;">ID <strong><?php echo htmlspecialchars($_GET['search_id']); ?></strong> not found.</span>
                </div>
                <a href="dashboard.php" class="btn-close" style="font-size:0.75rem;"></a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- KPI Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-xs fw-bold text-uppercase mb-2" style="color:var(--text-light);letter-spacing:0.06em;">Total Students</p>
                        <h2 class="fw-bold mb-1" style="font-size:2.25rem;"><?php echo $totalStudents; ?></h2>
                        <p class="mb-0 text-xs" style="color:var(--success);"><i class="fas fa-arrow-up me-1"></i>Active enrollments</p>
                    </div>
                    <div class="icon-box" style="background:linear-gradient(135deg,rgba(99,102,241,0.15),rgba(79,70,229,0.08)); color:var(--primary);">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
                <div class="mt-3 pt-3" style="border-top:1px solid var(--border);">
                    <a href="students.php" style="font-size:0.8rem;color:var(--primary);text-decoration:none;font-weight:600;">View all students <i class="fas fa-arrow-right ms-1" style="font-size:0.7rem;"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-xs fw-bold text-uppercase mb-2" style="color:var(--text-light);letter-spacing:0.06em;">Total Referrals</p>
                        <h2 class="fw-bold mb-1" style="font-size:2.25rem;"><?php echo $totalReferrals; ?></h2>
                        <p class="mb-0 text-xs" style="color:var(--info);"><i class="fas fa-share-alt me-1"></i>Referred enrollments</p>
                    </div>
                    <div class="icon-box" style="background:linear-gradient(135deg,rgba(14,165,233,0.15),rgba(14,165,233,0.06)); color:var(--info);">
                        <i class="fas fa-share-alt"></i>
                    </div>
                </div>
                <div class="mt-3 pt-3" style="border-top:1px solid var(--border);">
                    <a href="referrals.php" style="font-size:0.8rem;color:var(--info);text-decoration:none;font-weight:600;">Manage referrals <i class="fas fa-arrow-right ms-1" style="font-size:0.7rem;"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-xs fw-bold text-uppercase mb-2" style="color:var(--text-light);letter-spacing:0.06em;">Total Courses</p>
                        <h2 class="fw-bold mb-1" style="font-size:2.25rem;"><?php echo $totalCourses; ?></h2>
                        <p class="mb-0 text-xs" style="color:var(--success);"><i class="fas fa-check-circle me-1"></i>Live courses</p>
                    </div>
                    <div class="icon-box" style="background:linear-gradient(135deg,rgba(16,185,129,0.15),rgba(16,185,129,0.06)); color:var(--success);">
                        <i class="fas fa-book-open"></i>
                    </div>
                </div>
                <div class="mt-3 pt-3" style="border-top:1px solid var(--border);">
                    <a href="courses.php" style="font-size:0.8rem;color:var(--success);text-decoration:none;font-weight:600;">Manage courses <i class="fas fa-arrow-right ms-1" style="font-size:0.7rem;"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Overview -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="fw-bold mb-0">Financial Overview</h5>
            <p class="mb-0 text-xs" style="color:var(--text-muted);">Real-time fee collection summary</p>
        </div>
        <a href="reports.php" class="btn btn-sm rounded-pill px-3" style="font-size:0.8rem;border:1px solid var(--border);color:var(--text-muted);">Full Report <i class="fas fa-arrow-right ms-1"></i></a>
    </div>

    <div class="row g-3 mb-4">
        <!-- Collected Fees -->
        <div class="col-md-3">
            <div class="stat-card h-100" style="border-top:3px solid var(--success);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-xs fw-bold text-uppercase" style="color:var(--text-light);letter-spacing:0.06em;">Collected Fees</span>
                    <div style="width:30px;height:30px;border-radius:8px;background:rgba(16,185,129,0.12);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-rupee-sign" style="color:var(--success);font-size:0.75rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0" style="color:var(--success);font-size:1.5rem;">₹<?php echo number_format($totalCollected, 2); ?></h3>
            </div>
        </div>
        <!-- Pending Fees -->
        <div class="col-md-3">
            <div class="stat-card h-100" style="border-top:3px solid var(--warning);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-xs fw-bold text-uppercase" style="color:var(--text-light);letter-spacing:0.06em;">Pending Fees</span>
                    <div style="width:30px;height:30px;border-radius:8px;background:rgba(245,158,11,0.12);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-hourglass-half" style="color:var(--warning);font-size:0.75rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0" style="color:var(--warning);font-size:1.5rem;">₹<?php echo number_format($totalPendingAmt, 2); ?></h3>
            </div>
        </div>
        <!-- Overdue Count -->
        <div class="col-md-3">
            <div class="stat-card h-100" style="border-top:3px solid var(--danger);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-xs fw-bold text-uppercase" style="color:var(--text-light);letter-spacing:0.06em;">Overdue Count</span>
                    <div style="width:30px;height:30px;border-radius:8px;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-exclamation-circle" style="color:var(--danger);font-size:0.75rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0" style="color:var(--danger);font-size:1.5rem;"><?php echo $overdueCount; ?></h3>
            </div>
        </div>
        <!-- Today's Transactions -->
        <div class="col-md-3">
            <div class="stat-card h-100" style="border-top:3px solid var(--primary);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-xs fw-bold text-uppercase" style="color:var(--text-light);letter-spacing:0.06em;">Today's Txns</span>
                    <div style="width:30px;height:30px;border-radius:8px;background:rgba(99,102,241,0.12);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-exchange-alt" style="color:var(--primary);font-size:0.75rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0" style="color:var(--primary);font-size:1.5rem;"><?php echo $recentTransactions; ?></h3>
            </div>
        </div>
    </div>

    <!-- Enrollment Chart -->
    <div class="stat-card mb-4" style="height:auto;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-0">Enrollment Trends</h5>
                <p class="mb-0 text-xs" style="color:var(--text-muted);">Monthly admissions — <?php echo date('Y'); ?></p>
            </div>
            <div class="d-flex gap-2">
                <span style="font-size:0.75rem;color:var(--text-muted);background:#f1f5f9;padding:4px 12px;border-radius:20px;">
                    <i class="fas fa-circle me-1" style="color:var(--primary);font-size:0.5rem;"></i>Admissions
                </span>
            </div>
        </div>
        <div style="height:260px;">
            <canvas id="enrollmentChart"></canvas>
        </div>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('enrollmentChart');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.18)');
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                datasets: [{
                    label: 'Admissions',
                    data: <?php echo $chartData; ?>,
                    borderColor: '#6366f1',
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.45,
                    borderWidth: 2.5,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: 'white',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 10,
                        titleFont: { family: 'Outfit', size: 12 },
                        bodyFont: { family: 'Outfit', size: 13 },
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: { font: { family: 'Outfit', size: 11 }, color: '#94a3b8' }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { family: 'Outfit', size: 11 }, color: '#94a3b8' }
                    }
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
