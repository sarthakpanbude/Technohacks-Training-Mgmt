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

$page = $_GET['page'] ?? 'dashboard';
if ($page == 'inquiry' || $page == 'add_inquiry') $activePage = 'visitors';

// Handle Manual Inquiry Submission
if (isset($_POST['add_manual_inquiry'])) {
    $stmt = $pdo->prepare("INSERT INTO visitors (name, phone, email, gender, age, course_interest, type, mode, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new')");
    $stmt->execute([$_POST['name'], $_POST['phone'], $_POST['email'], $_POST['gender'], $_POST['age'], $_POST['domain'], $_POST['type'], $_POST['mode']]);
    header("Location: dashboard.php?page=inquiry&msg=Inquiry Added");
    exit;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>
    
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="export_students.php" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="fas fa-file-excel me-1"></i> Export Students</a>
        <a href="export_fees.php" class="btn btn-sm btn-outline-danger rounded-pill px-3"><i class="fas fa-file-pdf me-1"></i> Export Fees</a>
    </div>

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

    <?php elseif ($page == 'inquiry'): ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Inquiry Management</h4>
            <div class="d-flex gap-2">
                <a href="dashboard.php?page=add_inquiry" class="btn btn-primary rounded-pill px-4"><i class="fas fa-plus me-2"></i>Add New Inquiry</a>
                <form class="d-flex gap-2" method="GET">
                    <input type="hidden" name="page" value="inquiry">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?php echo $_GET['search'] ?? ''; ?>" style="border-radius: 8px;">
                    <button type="submit" class="btn btn-primary btn-sm px-3 rounded-pill">Search</button>
                </form>
            </div>
        </div>
        
        <div class="stat-card bg-white rounded-4 shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4">Name</th>
                            <th>Mobile</th>
                            <th>Course</th>
                            <th>Date</th>
                            <th class="text-end px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $search = $_GET['search'] ?? '';
                        $query = "SELECT * FROM visitors WHERE status != 'rejected'";
                        $params = [];
                        if ($search) {
                            $query .= " AND (name LIKE ? OR phone LIKE ? OR course_interest LIKE ?)";
                            $params = ["%$search%", "%$search%", "%$search%"];
                        }
                        $query .= " ORDER BY created_at DESC";
                        $inquiries = $pdo->prepare($query);
                        $inquiries->execute($params);
                        foreach ($inquiries->fetchAll() as $inq):
                        ?>
                        <tr>
                            <td class="px-4 fw-bold"><?php echo $inq['name']; ?></td>
                            <td><?php echo $inq['phone']; ?></td>
                            <td><span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3"><?php echo $inq['course_interest']; ?></span></td>
                            <td class="small text-muted"><?php echo date('d M, Y', strtotime($inq['created_at'])); ?></td>
                            <td class="text-end px-4">
                                <a href="admit.php?id=<?php echo $inq['id']; ?>&new=1" class="btn btn-sm btn-success rounded-pill px-3">Admit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($page == 'add_inquiry'): ?>
        <div class="mb-4">
            <h4 class="fw-bold mb-0">Add New Inquiry</h4>
            <p class="text-muted small">Enter student details manually.</p>
        </div>
        <div class="stat-card bg-white rounded-4 shadow-sm border-0 p-4" style="max-width: 800px;">
            <form action="" method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Age</label>
                        <input type="number" name="age" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Select Domain</label>
                        <select name="domain" class="form-select" required>
                            <?php
                            $courses = $pdo->query("SELECT course_name FROM courses ORDER BY course_name ASC")->fetchAll();
                            foreach($courses as $c) echo "<option value='{$c['course_name']}'>{$c['course_name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Inquiry Type</label>
                        <select name="type" class="form-select" required>
                            <option value="Course">Course Admission</option>
                            <option value="Internship">Internship Program</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Preferred Mode</label>
                        <select name="mode" class="form-select" required>
                            <option value="Online">Online (Virtual)</option>
                            <option value="Offline">Offline (At Center)</option>
                        </select>
                    </div>
                    <div class="col-12 mt-4 pt-3 border-top">
                        <button type="submit" name="add_manual_inquiry" class="btn btn-primary px-5 rounded-pill">Save Inquiry</button>
                        <a href="dashboard.php?page=inquiry" class="btn btn-light px-4 border rounded-pill">Cancel</a>
                    </div>
                </div>
            </form>
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

<?php include '../includes/footer.php'; ?>
