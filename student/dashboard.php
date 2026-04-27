<?php
require_once '../includes/auth.php';
checkAuth('student');
require_once '../config/db.php';

$pageTitle = "Student Dashboard";
$activePage = "dashboard";

$userId = $_SESSION['user_id'];
$student = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$student->execute([$userId]);
$s_data = $student->fetch();
$studentId = $s_data['id'];

// Get Enrollment Data
$enrollment = $pdo->prepare("SELECT e.*, b.batch_name, c.name as course_name FROM enrollments e JOIN batches b ON e.batch_id = b.id JOIN courses c ON b.course_id = c.id WHERE e.student_id = ? AND e.status = 'active'");
$enrollment->execute([$studentId]);
$activeEnrollment = $enrollment->fetch();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <header class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">My Learning Dashboard</h2>
            <p class="text-muted">Keep pushing your limits, <?php echo explode(' ', $_SESSION['full_name'])[0]; ?>!</p>
        </div>
        <div class="d-flex align-items-center">
            <div class="text-end me-3">
                <h6 class="mb-0 fw-bold"><?php echo $_SESSION['full_name']; ?></h6>
                <span class="small text-muted">ID: <?php echo $s_data['enrollment_no'] ?? 'N/A'; ?></span>
            </div>
            <img src="../assets/img/default.png" class="rounded-circle border" width="45" height="45">
        </div>
    </header>

    <?php if ($activeEnrollment): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="stat-card bg-primary text-white" style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%) !important;">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <span class="badge bg-white text-primary mb-2">Current Course</span>
                        <h3 class="fw-bold mb-3"><?php echo $activeEnrollment['course_name']; ?></h3>
                        <p class="opacity-75 small">Batch: <?php echo $activeEnrollment['batch_name']; ?></p>
                        <div class="progress bg-white bg-opacity-20 mt-4" style="height: 10px; border-radius: 5px;">
                            <div class="progress-bar bg-white" style="width: 65%;"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2 small">
                            <span>Progress: 65%</span>
                            <span>12/18 Modules</span>
                        </div>
                    </div>
                    <div class="col-md-4 text-center d-none d-md-block">
                        <i class="fas fa-graduation-cap fa-5x opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <h6 class="fw-bold mb-3">Attendance Score</h6>
                <div class="text-center py-2">
                    <div class="position-relative d-inline-block">
                        <canvas id="attendanceRing" width="120" height="120"></canvas>
                        <div class="position-absolute top-50 start-50 translate-middle">
                            <h4 class="fw-bold mb-0">88%</h4>
                        </div>
                    </div>
                </div>
                <p class="text-center text-muted small mt-2">Excellent! Keep it up.</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="stat-card h-100">
                <h6 class="fw-bold mb-3">Pending Tasks</h6>
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 py-2 bg-transparent border-bottom">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="t1">
                            <label class="form-check-label small" for="t1">Submit React Assignment</label>
                        </div>
                    </div>
                    <div class="list-group-item px-0 py-2 bg-transparent border-bottom">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="t2">
                            <label class="form-check-label small" for="t2">Complete MCQ Quiz #4</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="stat-card">
                <h6 class="fw-bold mb-4">Skills Gained</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-bold">HTML/CSS</span>
                            <span class="small text-muted">90%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 90%;"></div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-bold">JavaScript</span>
                            <span class="small text-muted">75%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: 75%;"></div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-bold">ReactJS</span>
                            <span class="small text-muted">45%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-warning" style="width: 45%;"></div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-bold">NodeJS</span>
                            <span class="small text-muted">20%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-info" style="width: 20%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="stat-card text-center py-5">
        <i class="fas fa-clock fa-3x text-warning mb-3"></i>
        <h4 class="fw-bold">Admission Pending</h4>
        <p class="text-muted">Your admission is currently under review. Please check back later or contact support.</p>
    </div>
    <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('attendanceRing').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [88, 12],
                backgroundColor: ['#2563eb', '#f1f5f9'],
                borderWidth: 0,
                cutout: '80%'
            }]
        },
        options: {
            responsive: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
