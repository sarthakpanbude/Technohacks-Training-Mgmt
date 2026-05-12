<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Reports & Analytics";
$activePage = "reports";

// Handle Filters
$date_from = $_GET['date_from'] ?? date('Y-m-01', strtotime('-5 months'));
$date_to   = $_GET['date_to']   ?? date('Y-m-d');

// KPI Calculations
$sales_stmt = $pdo->prepare("SELECT SUM(sf.total_fee) FROM student_fees sf JOIN students s ON sf.student_id = s.enrollment_no WHERE DATE(s.created_at) BETWEEN ? AND ?");
$sales_stmt->execute([$date_from, $date_to]);
$totalSales = $sales_stmt->fetchColumn() ?: 0;

$rev_stmt = $pdo->prepare("SELECT SUM(amount) FROM invoices WHERE DATE(payment_date) BETWEEN ? AND ?");
$rev_stmt->execute([$date_from, $date_to]);
$totalRevenue = $rev_stmt->fetchColumn() ?: 0;

$pend_stmt = $pdo->prepare("SELECT SUM(sf.pending_fee) FROM student_fees sf JOIN students s ON sf.student_id = s.enrollment_no WHERE DATE(s.created_at) BETWEEN ? AND ?");
$pend_stmt->execute([$date_from, $date_to]);
$totalPending = $pend_stmt->fetchColumn() ?: 0;

$exp_stmt = $pdo->prepare("SELECT SUM(bonus_amount) FROM referral_bonuses WHERE payout_status = 'Paid' AND DATE(paid_at) BETWEEN ? AND ?");
$exp_stmt->execute([$date_from, $date_to]);
$totalExpenses = $exp_stmt->fetchColumn() ?: 0;

$totalProfit = $totalRevenue - $totalExpenses;

// Monthly Trend Data
$months = []; $revenueTrend = []; $salesTrend = []; $profitTrend = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $displayM = date('M Y', strtotime("-$i months"));
    $months[] = $displayM;
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM invoices WHERE DATE_FORMAT(payment_date, '%Y-%m') = ?");
    $stmt->execute([$m]);
    $rev = (float)($stmt->fetchColumn() ?: 0);
    $revenueTrend[] = $rev;
    $stmt = $pdo->prepare("SELECT SUM(sf.total_fee) FROM student_fees sf JOIN students s ON sf.student_id = s.enrollment_no WHERE DATE_FORMAT(s.created_at, '%Y-%m') = ?");
    $stmt->execute([$m]);
    $salesTrend[] = (float)($stmt->fetchColumn() ?: 0);
    $stmt = $pdo->prepare("SELECT SUM(bonus_amount) FROM referral_bonuses WHERE payout_status = 'Paid' AND DATE_FORMAT(paid_at, '%Y-%m') = ?");
    $stmt->execute([$m]);
    $exp = (float)($stmt->fetchColumn() ?: 0);
    $profitTrend[] = $rev - $exp;
}

// Course Distribution
$courseRows = $pdo->query("SELECT course, COUNT(*) as total FROM students WHERE course IS NOT NULL AND course != '' GROUP BY course ORDER BY total DESC LIMIT 5")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content pt-3">
    <?php include '../includes/topbar.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0">Reports & Analytics</h4>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-light border rounded-pill px-3 shadow-sm">
                <i class="fas fa-print me-1"></i>Print
            </button>
            <!-- Compact Filter Dropdown -->
            <div class="dropdown">
                <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                    <i class="fas fa-filter me-1"></i>Filter Date
                </button>
                <div class="dropdown-menu dropdown-menu-end p-3 shadow-lg border-0 rounded-4" style="width: 300px;">
                    <form method="GET">
                        <div class="mb-2">
                            <label class="form-label x-small fw-bold text-muted text-uppercase">Start Date</label>
                            <input type="date" name="date_from" class="form-control form-control-sm rounded-3" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label x-small fw-bold text-muted text-uppercase">End Date</label>
                            <input type="date" name="date_to" class="form-control form-control-sm rounded-3" value="<?php echo $date_to; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 rounded-3 fw-bold">Apply Filter</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN GRAPHS (Placed at Top) -->
    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <div class="stat-card bg-white p-3 rounded-4 shadow-sm border-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 small">Sales vs Revenue Trend</h6>
                    <div class="d-flex gap-2">
                        <span class="x-small text-muted"><i class="fas fa-circle text-primary me-1"></i> Sales</span>
                        <span class="x-small text-muted"><i class="fas fa-circle text-success me-1"></i> Revenue</span>
                    </div>
                </div>
                <div style="height: 220px;">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-white p-3 rounded-4 shadow-sm border-0">
                <h6 class="fw-bold mb-3 small">Monthly Profitability</h6>
                <div style="height: 220px;">
                    <canvas id="profitTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Row (Compact) -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="kpi-card bg-white p-3 rounded-4 shadow-sm border-start border-primary border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted x-small fw-bold text-uppercase d-block">Sales</span>
                        <h4 class="fw-bold mb-0"><?php echo $settings['currency'] ?? '₹'; ?><?php echo number_format($totalSales, 0); ?></h4>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3"><i class="fas fa-shopping-cart text-primary small"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card bg-white p-3 rounded-4 shadow-sm border-start border-success border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted x-small fw-bold text-uppercase d-block">Revenue</span>
                        <h4 class="fw-bold mb-0"><?php echo $settings['currency'] ?? '₹'; ?><?php echo number_format($totalRevenue, 0); ?></h4>
                    </div>
                    <div class="bg-success bg-opacity-10 p-2 rounded-3"><i class="fas fa-wallet text-success small"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card bg-white p-3 rounded-4 shadow-sm border-start border-info border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted x-small fw-bold text-uppercase d-block">Profit</span>
                        <h4 class="fw-bold mb-0"><?php echo $settings['currency'] ?? '₹'; ?><?php echo number_format($totalProfit, 0); ?></h4>
                    </div>
                    <div class="bg-info bg-opacity-10 p-2 rounded-3"><i class="fas fa-chart-line text-info small"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card bg-white p-3 rounded-4 shadow-sm border-start border-danger border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted x-small fw-bold text-uppercase d-block">Pending</span>
                        <h4 class="fw-bold mb-0"><?php echo $settings['currency'] ?? '₹'; ?><?php echo number_format($totalPending, 0); ?></h4>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-2 rounded-3"><i class="fas fa-clock text-danger small"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Collection Status (Compact) -->
        <div class="col-md-4">
            <div class="stat-card bg-white p-3 rounded-4 shadow-sm border-0 h-100">
                <h6 class="fw-bold mb-3 small text-center">Collection Status</h6>
                <div style="height: 180px;">
                    <canvas id="feeStatusChart"></canvas>
                </div>
                <div class="mt-3 pt-2 border-top d-flex justify-content-around text-center">
                    <div>
                        <span class="x-small text-muted d-block">Paid</span>
                        <span class="small fw-bold text-success">₹<?php echo number_format($totalRevenue, 0); ?></span>
                    </div>
                    <div>
                        <span class="x-small text-muted d-block">Pending</span>
                        <span class="small fw-bold text-danger">₹<?php echo number_format($totalPending, 0); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Courses (Compact) -->
        <div class="col-md-8">
            <div class="stat-card bg-white p-3 rounded-4 shadow-sm border-0 h-100">
                <h6 class="fw-bold mb-3 small">Top Domain Performance</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="bg-light text-uppercase x-small fw-bold text-muted">
                            <tr>
                                <th class="border-0 px-2">Domain</th>
                                <th class="border-0">Value</th>
                                <th class="border-0 text-end px-2">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courseRows as $course): 
                                $billed = $pdo->prepare("SELECT SUM(sf.total_fee) FROM student_fees sf JOIN students s ON sf.student_id = s.enrollment_no WHERE s.course = ?");
                                $billed->execute([$course['course']]);
                                $courseValue = $billed->fetchColumn() ?: 0;
                                $share = ($totalSales > 0) ? ($courseValue / $totalSales) * 100 : 0;
                            ?>
                            <tr>
                                <td class="px-2 fw-bold text-primary"><?php echo htmlspecialchars($course['course']); ?></td>
                                <td class="fw-bold">₹<?php echo number_format($courseValue, 0); ?></td>
                                <td class="text-end px-2">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <span class="x-small text-muted"><?php echo round($share, 0); ?>%</span>
                                        <div class="progress" style="width: 40px; height: 4px;">
                                            <div class="progress-bar bg-primary" style="width: <?php echo $share; ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;
    Chart.defaults.font.family = "'Outfit', sans-serif";
    Chart.defaults.color = '#94a3b8';

    const salesCtx = document.getElementById('salesTrendChart');
    if (salesCtx) {
        new Chart(salesCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [
                    {
                        label: 'Sales',
                        data: <?php echo json_encode($salesTrend); ?>,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.05)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3
                    },
                    {
                        label: 'Revenue',
                        data: <?php echo json_encode($revenueTrend); ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.05)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a' } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f8fafc' }, ticks: { font: { size: 10 }, callback: v => '₹' + (v >= 1000 ? (v/1000) + 'k' : v) } },
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                }
            }
        });
    }

    const profitCtx = document.getElementById('profitTrendChart');
    if (profitCtx) {
        new Chart(profitCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Profit',
                    data: <?php echo json_encode($profitTrend); ?>,
                    backgroundColor: '#0ea5e9',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a' } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f8fafc' }, ticks: { font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                }
            }
        });
    }

    const statusCtx = document.getElementById('feeStatusChart');
    if (statusCtx) {
        new Chart(statusCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Pending'],
                datasets: [{
                    data: [<?php echo $totalRevenue; ?>, <?php echo $totalPending; ?>],
                    backgroundColor: ['#10b981', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '80%',
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 10 } } } }
            }
        });
    }
});
</script>

<style>
.main-content { padding-top: 1rem !important; }
.kpi-card { transition: all 0.2s; border: 1px solid #f1f5f9 !important; }
.kpi-card h4 { font-size: 1.25rem; }
.x-small { font-size: 0.65rem; }
.stat-card { border: 1px solid #f1f5f9 !important; }
@media print {
    .sidebar, .topbar, .btn, .dropdown { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
}
</style>

<?php include '../includes/footer.php'; ?>
