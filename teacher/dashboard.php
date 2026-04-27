<?php
require_once '../includes/auth.php';
checkAuth('teacher');
require_once '../config/db.php';

$pageTitle = "Teacher Dashboard";
$activePage = "dashboard";

// Get Teacher ID
$userId = $_SESSION['user_id'];
$teacher = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$teacher->execute([$userId]);
$t_data = $teacher->fetch();
$teacherId = $t_data['id'];

// Stats
$batchCount = $pdo->prepare("SELECT COUNT(*) FROM batches WHERE teacher_id = ?");
$batchCount->execute([$teacherId]);
$totalBatches = $batchCount->fetchColumn();

$studentCount = $pdo->prepare("SELECT COUNT(DISTINCT student_id) FROM enrollments e JOIN batches b ON e.batch_id = b.id WHERE b.teacher_id = ?");
$studentCount->execute([$teacherId]);
$totalStudents = $studentCount->fetchColumn();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-layer-group"></i>
                </div>
                <h3 class="fw-bold"><?php echo $totalBatches; ?></h3>
                <p class="text-muted small mb-0">Assigned Batches</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="icon-box bg-success bg-opacity-10 text-success">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="fw-bold"><?php echo $totalStudents; ?></h3>
                <p class="text-muted small mb-0">Total Students</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="icon-box bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3 class="fw-bold">8</h3>
                <p class="text-muted small mb-0">Pending Assignments</p>
            </div>
        </div>
    </div>

    <h5 class="fw-bold mb-3">Today's Schedule</h5>
    <div class="stat-card">
        <div class="list-group list-group-flush">
            <div class="list-group-item px-0 py-3 bg-transparent border-bottom">
                <div class="d-flex align-items-center">
                    <div class="me-3 text-center" style="width: 60px;">
                        <h6 class="mb-0 fw-bold">10:00</h6>
                        <span class="small text-muted">AM</span>
                    </div>
                    <div class="border-start ps-3">
                        <h6 class="mb-0 fw-bold">Full Stack Web Development</h6>
                        <p class="text-muted small mb-0">Batch: WD-MOR-2024 • Module: ReactJS Hooks</p>
                    </div>
                    <button class="ms-auto btn btn-primary btn-sm rounded-pill px-3">Mark Attendance</button>
                </div>
            </div>
            <div class="list-group-item px-0 py-3 bg-transparent border-0">
                <div class="d-flex align-items-center">
                    <div class="me-3 text-center" style="width: 60px;">
                        <h6 class="mb-0 fw-bold">02:00</h6>
                        <span class="small text-muted">PM</span>
                    </div>
                    <div class="border-start ps-3">
                        <h6 class="mb-0 fw-bold">Python for Data Science</h6>
                        <p class="text-muted small mb-0">Batch: DS-EVE-2024 • Module: Pandas Dataframes</p>
                    </div>
                    <button class="ms-auto btn btn-outline-primary btn-sm rounded-pill px-3">View Details</button>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
