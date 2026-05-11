<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Reports & Analytics";
$activePage = "reports";

// Real data for charts
$feeRows = $pdo->query("
    SELECT DATE_FORMAT(payment_date, '%b') as month, SUM(amount) as total
    FROM invoices
    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY MONTH(payment_date), DATE_FORMAT(payment_date, '%b')
    ORDER BY MONTH(payment_date) ASC
")->fetchAll();

$feeLabels = json_encode(array_column($feeRows, 'month'));
$feeData   = json_encode(array_column($feeRows, 'total'));

$courseRows = $pdo->query("
    SELECT s.course, COUNT(*) as total
    FROM students s
    WHERE s.course IS NOT NULL AND s.course != ''
    GROUP BY s.course
    ORDER BY total DESC
    LIMIT 8
")->fetchAll();

$courseLabels = json_encode(array_column($courseRows, 'course'));
$courseData   = json_encode(array_column($courseRows, 'total'));

// Summary stats
$totalRevenue  = $pdo->query("SELECT SUM(amount) FROM invoices")->fetchColumn() ?: 0;
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$activeMonths  = $pdo->query("SELECT COUNT(DISTINCT DATE_FORMAT(payment_date,'%Y-%m')) FROM invoices")->fetchColumn() ?: 1;
$avgMonthly    = $totalRevenue / $activeMonths;

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <?php include '../includes/topbar.php'; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1">Reports &amp; Analytics</h4>
            <p class="mb-0" style="font-size:0.85rem;color:var(--text-muted);">Business performance at a glance</p>
        </div>
        <div class="d-flex gap-2">
            <a href="export_fees.php" class="btn rounded-pill px-4" style="font-size:0.85rem;border:1px solid var(--danger);color:var(--danger);background:rgba(239,68,68,0.06);">
                <i class="fas fa-file-pdf me-2"></i>Export PDF
            </a>
            <a href="export_students.php" class="btn rounded-pill px-4" style="font-size:0.85rem;border:1px solid var(--success);color:var(--success);background:rgba(16,185,129,0.06);">
                <i class="fas fa-file-excel me-2"></i>Export Excel
            </a>
        </div>
    </div>

    <!-- Summary KPI Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card h-100" style="border-top:3px solid var(--primary);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-xs fw-bold text-uppercase" style="color:var(--text-light);letter-spacing:0.06em;">Total Revenue</span>
                    <div style="width:30px;height:30px;border-radius:8px;background:rgba(99,102,241,0.12);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-rupee-sign" style="color:var(--primary);font-size:0.75rem;"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-0" style="color:var(--primary);">₹<?php echo number_format($totalRevenue, 0); ?></h4>
                <p class="mb-0 text-xs" style="color:var(--text-muted);">All time collections</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card h-100" style="border-top:3px solid var(--success);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-xs fw-bold text-uppercase" style="color:var(--text-light);letter-spacing:0.06em;">Avg / Month</span>
                    <div style="width:30px;height:30px;border-radius:8px;background:rgba(16,185,129,0.12);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-chart-line" style="color:var(--success);font-size:0.75rem;"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-0" style="color:var(--success);">₹<?php echo number_format($avgMonthly, 0); ?></h4>
                <p class="mb-0 text-xs" style="color:var(--text-muted);">Monthly average</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card h-100" style="border-top:3px solid var(--info);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-xs fw-bold text-uppercase" style="color:var(--text-light);letter-spacing:0.06em;">Total Students</span>
                    <div style="width:30px;height:30px;border-radius:8px;background:rgba(14,165,233,0.12);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-users" style="color:var(--info);font-size:0.75rem;"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-0" style="color:var(--info);"><?php echo $totalStudents; ?></h4>
                <p class="mb-0 text-xs" style="color:var(--text-muted);">Enrolled students</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card h-100" style="border-top:3px solid var(--warning);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-xs fw-bold text-uppercase" style="color:var(--text-light);letter-spacing:0.06em;">Active Months</span>
                    <div style="width:30px;height:30px;border-radius:8px;background:rgba(245,158,11,0.12);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-calendar-alt" style="color:var(--warning);font-size:0.75rem;"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-0" style="color:var(--warning);"><?php echo $activeMonths; ?></h4>
                <p class="mb-0 text-xs" style="color:var(--text-muted);">Months with revenue</p>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4">
        <!-- Fee Collection Bar Chart -->
        <div class="col-md-7">
            <div class="stat-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h6 class="fw-bold mb-0">Fee Collection</h6>
                        <p class="mb-0 text-xs" style="color:var(--text-muted);">Last 6 months revenue</p>
                    </div>
                    <span style="font-size:0.75rem;color:var(--text-muted);background:#f1f5f9;padding:4px 12px;border-radius:20px;">
                        <i class="fas fa-circle me-1" style="color:var(--primary);font-size:0.5rem;"></i>Revenue
                    </span>
                </div>
                <div style="height:260px;position:relative;">
                    <canvas id="feeReportChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Course-wise Donut -->
        <div class="col-md-5">
            <div class="stat-card h-100">
                <div class="mb-4">
                    <h6 class="fw-bold mb-0">Course Distribution</h6>
                    <p class="mb-0 text-xs" style="color:var(--text-muted);">Students per course</p>
                </div>
                <div style="height:260px;position:relative;">
                    <canvas id="courseReportChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const feeCtx = document.getElementById('feeReportChart');
    if (feeCtx) {
        const feeGrad = feeCtx.getContext('2d').createLinearGradient(0, 0, 0, 260);
        feeGrad.addColorStop(0, 'rgba(99,102,241,0.85)');
        feeGrad.addColorStop(1, 'rgba(79,70,229,0.5)');

        new Chart(feeCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $feeLabels ?: '["No data"]'; ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?php echo $feeData ?: '[0]'; ?>,
                    backgroundColor: feeGrad,
                    borderRadius: 8,
                    borderSkipped: false,
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
                        callbacks: { label: ctx => ' ₹' + ctx.parsed.y.toLocaleString('en-IN') }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: { font: { family: 'Outfit', size: 11 }, color: '#94a3b8', callback: v => '₹' + (v/1000) + 'k' }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { family: 'Outfit', size: 11 }, color: '#94a3b8' }
                    }
                }
            }
        });
    }

    const courseCtx = document.getElementById('courseReportChart');
    if (courseCtx) {
        new Chart(courseCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo $courseLabels ?: '["No data"]'; ?>,
                datasets: [{
                    data: <?php echo $courseData ?: '[1]'; ?>,
                    backgroundColor: ['#6366f1','#10b981','#f59e0b','#ef4444','#0ea5e9','#8b5cf6','#ec4899','#14b8a6'],
                    borderWidth: 3,
                    borderColor: 'white',
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { family: 'Outfit', size: 11 }, padding: 16, usePointStyle: true, pointStyleWidth: 8 }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 10,
                    }
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
