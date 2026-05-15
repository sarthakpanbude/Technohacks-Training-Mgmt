<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Multi-Course Management";
$activePage = "enrollment_report";

// Handle Filters
$filter_course = $_GET['course_id'] ?? '';
$filter_status = $_GET['status'] ?? '';
$date_from     = $_GET['date_from'] ?? date('Y-m-01', strtotime('-1 month'));
$date_to       = $_GET['date_to']   ?? date('Y-m-d');

// Build query
$where = ["DATE(ce.enrollment_date) BETWEEN ? AND ?"];
$params = [$date_from, $date_to];

if (!empty($filter_course)) {
    $where[] = "ce.course_id = ?";
    $params[] = $filter_course;
}

if (!empty($filter_status)) {
    $where[] = "ce.status = ?";
    $params[] = $filter_status;
}

$whereClause = implode(" AND ", $where);

$query = "
    SELECT 
        ce.id as enrollment_id,
        s.id as student_id,
        u.full_name as student_name,
        c.name as course_name,
        ce.enrollment_date,
        ce.status,
        ef.total_fee,
        ef.paid_amount
    FROM course_enrollments ce
    JOIN students s ON ce.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN courses c ON ce.course_id = c.id
    LEFT JOIN enrollment_fees ef ON ce.id = ef.enrollment_id
    WHERE $whereClause
    ORDER BY ce.enrollment_date DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

// Fetch courses for filter dropdown
$courses = $pdo->query("SELECT id, name FROM courses ORDER BY name ASC")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content pt-3">
    <?php include '../includes/topbar.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0">Multi-Course Enrollments Report</h4>
            <p class="text-muted small mb-0">Track all extra course registrations</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-light border rounded-pill px-3 shadow-sm">
                <i class="fas fa-print me-1"></i>Print
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="stat-card bg-white p-3 rounded-4 shadow-sm border-0 mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label x-small fw-bold text-muted text-uppercase">Course</label>
                <select name="course_id" class="form-select form-select-sm rounded-3">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $filter_course == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label x-small fw-bold text-muted text-uppercase">Status</label>
                <select name="status" class="form-select form-select-sm rounded-3">
                    <option value="">All</option>
                    <option value="Active" <?php echo $filter_status == 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Completed" <?php echo $filter_status == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="Dropped" <?php echo $filter_status == 'Dropped' ? 'selected' : ''; ?>>Dropped</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label x-small fw-bold text-muted text-uppercase">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm rounded-3" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label x-small fw-bold text-muted text-uppercase">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm rounded-3" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm w-100 rounded-3 shadow-sm">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="stat-card bg-white rounded-4 shadow-sm border-0">
        <div class="table-responsive p-3">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase x-small fw-bold text-muted">
                    <tr>
                        <th class="border-0 px-3 py-3">Student Name</th>
                        <th class="border-0">Course Name</th>
                        <th class="border-0">Enrollment Date</th>
                        <th class="border-0">Status</th>
                        <th class="border-0">Total Fees</th>
                        <th class="border-0">Fees Paid</th>
                        <th class="border-0 text-end px-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($enrollments)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No enrollments found for the given criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($enrollments as $row): ?>
                            <tr>
                                <td class="px-3 fw-bold text-dark">
                                    <a href="view_student.php?id=<?php echo $row['student_id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($row['student_name']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                <td class="text-muted small"><?php echo date('d M Y', strtotime($row['enrollment_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $row['status'] == 'Active' ? 'success' : ($row['status'] == 'Dropped' ? 'danger' : 'primary'); ?> bg-opacity-10 text-<?php echo $row['status'] == 'Active' ? 'success' : ($row['status'] == 'Dropped' ? 'danger' : 'primary'); ?> rounded-pill px-3 py-2">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="fw-bold">₹<?php echo number_format($row['total_fee'] ?? 0, 2); ?></td>
                                <td class="text-success fw-bold">₹<?php echo number_format($row['paid_amount'] ?? 0, 2); ?></td>
                                <td class="text-end px-3">
                                    <a href="view_student.php?id=<?php echo $row['student_id']; ?>#multi-course-info" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<style>
.main-content { padding-top: 1rem !important; }
.x-small { font-size: 0.65rem; }
.stat-card { border: 1px solid #f1f5f9 !important; }
@media print {
    .sidebar, .topbar, .btn, form { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
}
</style>

<?php include '../includes/footer.php'; ?>
