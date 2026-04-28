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

// Fetch Recent Inquiries
$recentInquiries = $pdo->query("SELECT * FROM visitors ORDER BY created_at DESC LIMIT 5")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>

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

    <!-- IT Institute Specific Sections -->
    <div class="row g-4">
        <!-- Placement & Trends -->
        <div class="col-md-8">
            <div class="stat-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold">Enrollment Trends</h5>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">2024</button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">2024</a></li>
                            <li><a class="dropdown-item" href="#">2023</a></li>
                        </ul>
                    </div>
                </div>
                <div style="height: 300px; position: relative;">
                    <canvas id="enrollmentChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Inquiries -->
        <div class="col-md-4">
            <div class="stat-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold">Recent Inquiries</h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">New</span>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($recentInquiries)): ?>
                        <div class="text-center py-4 text-muted small">No recent inquiries.</div>
                    <?php else: ?>
                        <?php foreach ($recentInquiries as $inq): ?>
                        <div class="list-group-item px-0 py-3 bg-transparent border-bottom">
                            <div class="d-flex align-items-center">
                                <div class="avatar me-3 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; font-size: 0.8rem;">
                                    <?php 
                                        $names = explode(' ', $inq['name']);
                                        echo strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                    ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 fw-bold small"><?php echo htmlspecialchars($inq['name']); ?></h6>
                                    <p class="text-muted extra-small mb-0"><?php echo htmlspecialchars($inq['course_interest']); ?></p>
                                </div>
                                <div class="ms-auto text-end">
                                    <span class="text-muted extra-small"><?php echo date('d M', strtotime($inq['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a href="visitors.php" class="btn btn-primary-light w-100 mt-3 fw-bold py-2 border-0 small" style="color: var(--primary);">View All Inquiries</a>
            </div>
        </div>

    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('enrollmentChart').getContext('2d');
    
    // Create Gradient
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
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#2563eb',
                pointBorderWidth: 2,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#2563eb',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.parsed.y + ' New Students';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: { 
                        color: '#94a3b8',
                        padding: 10,
                        stepSize: 1
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', padding: 10 }
                }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
