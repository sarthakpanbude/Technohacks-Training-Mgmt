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

// Fetch Monthly Enrollments for Chart
$monthlyEnrollments = array_fill(0, 12, 0);
$enrollmentQuery = $pdo->query("SELECT MONTH(enrollment_date) as m, COUNT(*) as c FROM enrollments WHERE YEAR(enrollment_date) = YEAR(CURDATE()) GROUP BY MONTH(enrollment_date)");
while($row = $enrollmentQuery->fetch()) {
    $monthlyEnrollments[$row['m'] - 1] = $row['c'];
}
$chartData = json_encode(array_values($monthlyEnrollments));

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
            <div class="dropdown">
                <button class="btn p-0 border-0" type="button" data-bs-toggle="dropdown">
                    <img src="../assets/img/default.png" class="rounded-circle border" width="40" height="40">
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2">
                    <li><h6 class="dropdown-header"><?php echo $_SESSION['full_name']; ?></h6></li>
                    <li><a class="dropdown-item rounded mb-1" href="settings.php"><i class="fas fa-user-cog me-2"></i>Profile Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item rounded mb-1 text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
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

        <!-- Recent Placements -->
        <div class="col-md-4">
            <div class="stat-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold">Top Placements</h5>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Live Track</span>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 py-3 bg-transparent border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3 bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; font-weight: bold;">JD</div>
                            <div>
                                <h6 class="mb-0 fw-bold">John Doe</h6>
                                <p class="text-muted small mb-0">Google • Full Stack Dev</p>
                            </div>
                            <div class="ms-auto text-end">
                                <span class="fw-bold text-success">12 LPA</span>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item px-0 py-3 bg-transparent border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; font-weight: bold;">AS</div>
                            <div>
                                <h6 class="mb-0 fw-bold">Anjali Singh</h6>
                                <p class="text-muted small mb-0">Amazon • Data Analyst</p>
                            </div>
                            <div class="ms-auto text-end">
                                <span class="fw-bold text-success">10 LPA</span>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item px-0 py-3 bg-transparent border-0">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3 bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; font-weight: bold;">RV</div>
                            <div>
                                <h6 class="mb-0 fw-bold">Rohan Verma</h6>
                                <p class="text-muted small mb-0">Microsoft • UI/UX Designer</p>
                            </div>
                            <div class="ms-auto text-end">
                                <span class="fw-bold text-success">15 LPA</span>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary-light w-100 mt-3 fw-bold py-2 border-0" style="color: var(--primary);">View Success Stories</button>
            </div>
        </div>

        <!-- Upcoming Bootcamps -->
        <div class="col-12 mt-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold">Upcoming Workshops & Bootcamps</h5>
                    <a href="#" class="text-decoration-none small fw-bold">View Calendar <i class="fas fa-chevron-right ms-1"></i></a>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="p-3 border rounded-4 bg-light bg-opacity-50">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="badge bg-primary rounded-pill">12th May</span>
                                <i class="fas fa-microchip text-primary"></i>
                            </div>
                            <h6 class="fw-bold">React.js Advanced Patterns</h6>
                            <p class="text-muted small">Special bootcamp by industry experts on Hooks & Context API.</p>
                            <button class="btn btn-sm btn-outline-primary w-100">Enroll Students</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-4 bg-light bg-opacity-50">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="badge bg-info rounded-pill">15th May</span>
                                <i class="fas fa-database text-info"></i>
                            </div>
                            <h6 class="fw-bold">MySQL Query Optimization</h6>
                            <p class="text-muted small">Optimizing large scale databases for ERP systems.</p>
                            <button class="btn btn-sm btn-outline-info w-100">Notify Teachers</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-4 bg-light bg-opacity-50">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="badge bg-warning rounded-pill">20th May</span>
                                <i class="fas fa-shield-alt text-warning"></i>
                            </div>
                            <h6 class="fw-bold">Cybersecurity Fundamentals</h6>
                            <p class="text-muted small">Introduction to ethical hacking and network security.</p>
                            <button class="btn btn-sm btn-outline-warning w-100">Manage Batch</button>
                        </div>
                    </div>
                </div>
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
