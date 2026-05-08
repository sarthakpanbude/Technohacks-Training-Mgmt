<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Admin Dashboard";
$activePage = "dashboard";

// Fetch Stats
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$activeBatches = $pdo->query("SELECT COUNT(*) FROM batches WHERE status = 'active'")->fetchColumn();
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

$page = $_GET['page'] ?? 'dashboard';

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <form action="" method="GET" class="d-flex gap-2" style="width: 400px;">
            <input type="text" name="search_id" class="form-control form-control-sm border shadow-sm px-3" 
                   placeholder="Quick Student Search (ID)..." 
                   style="border-radius: 20px;"
                   value="<?php echo htmlspecialchars($_GET['search_id'] ?? ''); ?>">
            <button type="submit" class="btn btn-purple btn-sm rounded-pill px-3 shadow-sm">Search</button>
        </form>
        <div class="d-flex gap-2">
            <a href="export_students.php" class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-sm"><i class="fas fa-file-excel me-1"></i> Export Students</a>
            <a href="export_fees.php" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-sm"><i class="fas fa-file-pdf me-1"></i> Export Fees</a>
        </div>
    </div>

    <!-- Search Result Section - Overlay Style -->
    <?php if (isset($_GET['search_id']) && !empty($_GET['search_id'])): ?>
        <?php if ($searchStudent): ?>
            <div class="stat-card border-0 shadow-lg mb-4 animate__animated animate__fadeInDown" style="border-top: 4px solid #800080 !important; background: #fdfdfd; border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-purple"><i class="fas fa-id-card me-2"></i>STUDENT DATA: <?php echo htmlspecialchars($searchStudent['enrollment_no']); ?></h6>
                    <a href="dashboard.php" class="btn-close btn-sm"></a>
                </div>
                <div class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <div class="text-muted x-small fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Name</div>
                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($searchStudent['full_name']); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted x-small fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Course</div>
                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($searchStudent['course']); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted x-small fw-bold text-uppercase mb-1" style="font-size: 0.65rem;">Fees (Total/Pending)</div>
                        <div>
                            <span class="fw-bold">₹<?php echo number_format($searchStudent['total_fee'], 2); ?></span> / 
                            <span class="text-danger fw-bold">₹<?php echo number_format($searchStudent['pending_fee'], 2); ?></span>
                        </div>
                    </div>
                    <div class="col-md-3 text-end">
                        <a href="generate_form.php?id=<?php echo $searchStudent['enrollment_no']; ?>" class="btn btn-purple btn-sm px-3 shadow-sm">Form</a>
                        <a href="generate_receipt.php?id=<?php echo $searchStudent['id']; ?>" class="btn btn-outline-success btn-sm px-3 shadow-sm">Receipt</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning py-2 border-0 shadow-sm mb-4 d-flex justify-content-between align-items-center" style="border-radius: 10px;">
                <span><i class="fas fa-exclamation-circle me-2"></i> ID <strong><?php echo htmlspecialchars($_GET['search_id']); ?></strong> not found.</span>
                <a href="dashboard.php" class="btn-close btn-sm"></a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($page == 'dashboard'): ?>

        <?php if ($overdueCount > 0): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4 border-0 shadow-sm" style="border-radius: 12px; border-left: 5px solid var(--bs-danger) !important;">
            <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
            <div>
                <strong>Fees Due Reminder:</strong> <?php echo $overdueCount; ?> student installment(s) are overdue. 
                <a href="../fees/installments.php" class="alert-link ms-2">View Pending Installments &rarr;</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3 class="fw-bold"><?php echo $totalStudents; ?></h3>
                    <p class="text-muted small mb-0">Total Students</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon-box bg-success bg-opacity-10 text-success">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <h3 class="fw-bold"><?php echo $activeBatches; ?></h3>
                    <p class="text-muted small mb-0">Active Batches</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon-box bg-info bg-opacity-10 text-info">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3 class="fw-bold"><?php echo $totalCourses; ?></h3>
                    <p class="text-muted small mb-0">Total Courses</p>
                </div>
            </div>
        </div>

        <!-- Financial Overview Grid -->
        <h5 class="fw-bold mb-3">Financial Overview</h5>
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card border-bottom border-success border-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <p class="text-muted small fw-bold mb-0 text-uppercase">Collected Fees</p>
                        <i class="fas fa-rupee-sign text-success"></i>
                    </div>
                    <h4 class="fw-bold mb-0">₹<?php echo number_format($totalCollected, 2); ?></h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-bottom border-warning border-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <p class="text-muted small fw-bold mb-0 text-uppercase">Pending Fees</p>
                        <i class="fas fa-hourglass-half text-warning"></i>
                    </div>
                    <h4 class="fw-bold mb-0">₹<?php echo number_format($totalPendingAmt, 2); ?></h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-bottom border-danger border-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <p class="text-muted small fw-bold mb-0 text-uppercase">Overdue Count</p>
                        <i class="fas fa-exclamation-circle text-danger"></i>
                    </div>
                    <h4 class="fw-bold mb-0"><?php echo $overdueCount; ?></h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-bottom border-primary border-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <p class="text-muted small fw-bold mb-0 text-uppercase">Today's Txns</p>
                        <i class="fas fa-exchange-alt text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-0"><?php echo $recentTransactions; ?></h4>
                </div>
            </div>
        </div>

        <!-- Enrollment Chart -->
        <div class="stat-card mb-4">
            <h5 class="fw-bold mb-4">Enrollment Trends</h5>
            <div style="height: 300px;">
                <canvas id="enrollmentChart"></canvas>
            </div>
        </div>

    <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('enrollmentChart');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(37, 99, 235, 0.2)');
        gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Admissions',
                    data: <?php echo $chartData; ?>,
                    borderColor: '#2563eb',
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>

<style>
    .btn-purple { background-color: #800080; color: white; border: none; }
    .btn-purple:hover { background-color: #660066; color: white; }
    .text-purple { color: #800080; }
    .stat-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    .animate__animated { animation-duration: 0.5s; }
</style>
<?php include '../includes/footer.php'; ?>
