<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Active Students";
$activePage = "students";

// Handle Student Deletion
if (isset($_POST['delete_student'])) {
    $id = $_POST['student_id'];
    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    if ($student) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$student['user_id']]);
    }
    header("Location: students.php?msg=Admission Deleted");
    exit;
}

// Search and Filters
$search = $_GET['search'] ?? '';
$course_filter = $_GET['course'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$sql = "SELECT MAX(s.id) as id, s.user_id, s.permanent_id, u.full_name as display_name, 
               GROUP_CONCAT(DISTINCT s.course SEPARATOR ', ') as courses
        FROM students s 
        LEFT JOIN users u ON s.user_id = u.id 
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (s.enrollment_no LIKE ? OR s.permanent_id LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($course_filter) {
    $sql .= " AND s.course = ?";
    $params[] = $course_filter;
}

$sql .= " GROUP BY s.user_id";

switch ($sort) {
    case 'oldest':    $sql .= " ORDER BY MIN(s.created_at) ASC"; break;
    case 'name_asc':  $sql .= " ORDER BY u.full_name ASC"; break;
    case 'name_desc': $sql .= " ORDER BY u.full_name DESC"; break;
    default:          $sql .= " ORDER BY MAX(s.created_at) DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$courses = $pdo->query("SELECT DISTINCT course FROM students WHERE course IS NOT NULL AND course != '' ORDER BY course ASC")->fetchAll(PDO::FETCH_COLUMN);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <?php include '../includes/topbar.php'; ?>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert border-0 mb-4 d-flex align-items-center gap-2 rounded-3"
             style="background:rgba(16,185,129,0.1); color:#065f46; font-size:0.875rem;">
            <i class="fas fa-check-circle" style="color:var(--success);"></i>
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="color:var(--text-main);">Active Students</h4>
            <p class="mb-0" style="font-size:0.85rem; color:var(--text-muted);">
                <span class="fw-semibold" style="color:var(--primary);"><?php echo count($students); ?></span> records found
                <?php if($search || $course_filter): ?>· filtered results<?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="admit.php?new=1" class="btn btn-primary rounded-pill px-4" style="font-size:0.85rem;">
                <i class="fas fa-plus me-2"></i>New Admission
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="stat-card mb-4" style="padding:1rem 1.25rem; height:auto;">
        <form action="" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="d-flex align-items-center gap-2 px-3 py-2 bg-white border rounded-pill" style="border-color:#e2e8f0!important;">
                    <i class="fas fa-search" style="color:var(--text-muted);font-size:0.8rem;"></i>
                    <input type="text" name="search" class="border-0 bg-transparent w-100" placeholder="Search by ID or name..."
                           style="font-size:0.875rem;outline:none;"
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="course" class="form-select rounded-pill" style="font-size:0.875rem;" onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $course_filter == $c ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="sort" class="form-select rounded-pill" style="font-size:0.875rem;" onchange="this.form.submit()">
                    <option value="newest" <?php echo $sort=='newest'?'selected':''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $sort=='oldest'?'selected':''; ?>>Oldest First</option>
                    <option value="name_asc" <?php echo $sort=='name_asc'?'selected':''; ?>>Name A–Z</option>
                    <option value="name_desc" <?php echo $sort=='name_desc'?'selected':''; ?>>Name Z–A</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill flex-grow-1" style="font-size:0.85rem;">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <?php if ($search || $course_filter || $sort != 'newest'): ?>
                    <a href="students.php" class="btn rounded-pill" style="font-size:0.85rem;border:1px solid var(--border);color:var(--text-muted);" title="Clear">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Students Table -->
    <div class="custom-table-container table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th style="padding:1rem 1.5rem;">#</th>
                    <th style="padding:1rem 1.25rem;">Student</th>
                    <th style="padding:1rem 1.25rem;">Student ID</th>
                    <th style="padding:1rem 1.25rem;">Enrolled Courses</th>
                    <th style="padding:1rem 1.5rem; text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5" style="color:var(--text-muted);">
                            <div style="width:60px;height:60px;border-radius:16px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                                <i class="fas fa-user-slash" style="font-size:1.5rem;color:#cbd5e1;"></i>
                            </div>
                            <div class="fw-semibold mb-1">No students found</div>
                            <div style="font-size:0.8rem;">Try adjusting your search or filters</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $i => $s): ?>
                        <tr style="transition:background 0.15s;">
                            <td style="padding:1rem 1.5rem; color:var(--text-muted); font-size:0.8rem; font-weight:600;">
                                <?php echo $i + 1; ?>
                            </td>
                            <td style="padding:1rem 1.25rem;">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,rgba(99,102,241,0.15),rgba(79,70,229,0.08));display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);font-size:0.95rem;flex-shrink:0;">
                                        <?php echo strtoupper(substr($s['display_name'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size:0.875rem;"><?php echo htmlspecialchars($s['display_name'] ?? 'Unknown'); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:1rem 1.25rem;">
                                <span style="font-size:0.8rem;font-weight:600;background:#f1f5f9;color:var(--text-muted);padding:3px 10px;border-radius:6px;font-family:monospace;">
                                    <?php echo htmlspecialchars($s['permanent_id'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td style="padding:1rem 1.25rem;">
                                <?php 
                                $courseList = explode(', ', $s['courses']);
                                foreach($courseList as $course): 
                                ?>
                                    <span style="font-size:0.78rem;font-weight:600;background:rgba(99,102,241,0.1);color:var(--primary);padding:4px 10px;border-radius:6px;margin-right:4px;display:inline-block;margin-bottom:4px;">
                                        <?php echo htmlspecialchars($course); ?>
                                    </span>
                                <?php endforeach; ?>
                            </td>
                            <td style="padding:1rem 1.5rem; text-align:center;">
                                <a href="view_student.php?id=<?php echo $s['id']; ?>"
                                   class="btn btn-sm btn-primary rounded-pill px-3"
                                   style="font-size:0.8rem;">
                                    <i class="fas fa-eye me-1"></i>View Profile
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if (!empty($students)): ?>
            <div class="px-4 py-3 d-flex align-items-center justify-content-between" style="border-top:1px solid var(--border);background:#fafafa;">
                <span style="font-size:0.8rem;color:var(--text-muted);">Showing <strong><?php echo count($students); ?></strong> student<?php echo count($students)!=1?'s':'';?></span>
                <span style="font-size:0.75rem;color:var(--text-light);">TechnoHacks ERP v2.0</span>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.custom-table-container tbody tr:hover { background: #fafbff; }
.form-select, .form-control {
    border-color: var(--border);
    font-size: 0.875rem;
    color: var(--text-main);
}
.form-select:focus, .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}
</style>

<?php include '../includes/footer.php'; ?>
