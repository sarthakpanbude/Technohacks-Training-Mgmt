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
$pendingTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <header class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">System Overview</h2>
            <p class="text-muted">Welcome back, <?php echo $_SESSION['full_name']; ?></p>
        </div>
        <div class="d-flex align-items-center">
            <div class="dropdown me-3">
                <button class="btn btn-white border rounded-circle p-2" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-bell text-muted"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" style="padding: 0.35em 0.5em;">3</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2" style="width: 300px;">
                    <li><h6 class="dropdown-header">Notifications</h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item rounded mb-1" href="#">New student admission: Rahul Kumar</a></li>
                    <li><a class="dropdown-item rounded mb-1" href="#">Fee due for Batch B102</a></li>
                </ul>
            </div>
            <img src="../assets/img/default.png" class="rounded-circle border" width="40" height="40">
        </div>
    </header>

    <!-- Stats Grid -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3 class="fw-bold"><?php echo $totalStudents; ?></h3>
                <p class="text-muted small mb-0">Total Students</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon-box bg-success bg-opacity-10 text-success">
                    <i class="fas fa-layer-group"></i>
                </div>
                <h3 class="fw-bold"><?php echo $activeBatches; ?></h3>
                <p class="text-muted small mb-0">Active Batches</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon-box bg-info bg-opacity-10 text-info">
                    <i class="fas fa-book"></i>
                </div>
                <h3 class="fw-bold"><?php echo $totalCourses; ?></h3>
                <p class="text-muted small mb-0">Total Courses</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon-box bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h3 class="fw-bold"><?php echo $pendingTickets; ?></h3>
                <p class="text-muted small mb-0">Open Tickets</p>
            </div>
        </div>
    </div>

    <!-- Charts & Recent Activity -->
    <div class="row g-4">
        <div class="col-md-8">
            <div class="stat-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold">Enrollment Trends</h5>
                    <select class="form-select form-select-sm w-auto border-0 bg-light">
                        <option>This Year</option>
                        <option>Last Year</option>
                    </select>
                </div>
                <canvas id="enrollmentChart" height="280"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card h-100">
                <h5 class="fw-bold mb-4">Recent Admissions</h5>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 py-3 bg-transparent border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold;">RK</div>
                            <div>
                                <h6 class="mb-0 fw-bold">Rahul Kumar</h6>
                                <p class="text-muted small mb-0">Web Development • 2h ago</p>
                            </div>
                            <span class="ms-auto badge bg-success bg-opacity-10 text-success rounded-pill">Active</span>
                        </div>
                    </div>
                    <div class="list-group-item px-0 py-3 bg-transparent border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3 bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold;">AS</div>
                            <div>
                                <h6 class="mb-0 fw-bold">Anjali Singh</h6>
                                <p class="text-muted small mb-0">Data Science • 5h ago</p>
                            </div>
                            <span class="ms-auto badge bg-warning bg-opacity-10 text-warning rounded-pill">Pending</span>
                        </div>
                    </div>
                    <div class="list-group-item px-0 py-3 bg-transparent border-0">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3 bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold;">VM</div>
                            <div>
                                <h6 class="mb-0 fw-bold">Vikas Mishra</h6>
                                <p class="text-muted small mb-0">Python Core • 1d ago</p>
                            </div>
                            <span class="ms-auto badge bg-primary bg-opacity-10 text-primary rounded-pill">Enrolled</span>
                        </div>
                    </div>
                </div>
                <button class="btn btn-light w-100 mt-3 fw-bold small">View All Students</button>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('enrollmentChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Admissions',
                data: [45, 52, 38, 65, 48, 72, 85, 90, 68, 55, 42, 60],
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 0,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: { color: '#94a3b8' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
