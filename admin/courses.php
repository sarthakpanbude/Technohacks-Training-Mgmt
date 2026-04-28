<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Course Management";
$activePage = "courses";

// Handle Add Course
if (isset($_POST['add_course'])) {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $duration = $_POST['duration'];
    $fees = $_POST['fees'];
    $level = $_POST['level'];

    $stmt = $pdo->prepare("INSERT INTO courses (course_name, description, duration, fees, level) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $desc, $duration, $fees, $level]);
    header("Location: courses.php?msg=Course Added");
    exit;
}

$courses = $pdo->query("SELECT * FROM courses ORDER BY id DESC")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Courses</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal"><i class="fas fa-plus me-2"></i>Create Course</button>
    </div>

    <div class="row g-4">
        <?php if (empty($courses)): ?>
            <div class="col-12 text-center py-5 text-muted">No courses available.</div>
        <?php else: ?>
            <?php foreach ($courses as $c): ?>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill text-capitalize"><?php echo $c['level']; ?></span>
                        <div class="dropdown">
                            <i class="fas fa-ellipsis-h text-muted cursor-pointer" data-bs-toggle="dropdown"></i>
                            <ul class="dropdown-menu border-0 shadow">
                                <li><a class="dropdown-item small" href="#"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                <li><a class="dropdown-item small text-danger" href="#"><i class="fas fa-trash me-2"></i>Delete</a></li>
                            </ul>
                        </div>
                    </div>
                    <h5 class="fw-bold"><?php echo $c['course_name']; ?></h5>
                    <p class="text-muted small"><?php echo substr($c['description'], 0, 80) . '...'; ?></p>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            <span class="text-muted small">Fees</span>
                            <h6 class="fw-bold mb-0">₹<?php echo number_format($c['fees'], 2); ?></h6>
                        </div>
                        <div class="text-end">
                            <span class="text-muted small">Duration</span>
                            <h6 class="fw-bold mb-0"><?php echo $c['duration']; ?></h6>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">New Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Course Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Duration (e.g. 3 Months)</label>
                                <input type="text" name="duration" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Fees (INR)</label>
                                <input type="number" name="fees" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Level</label>
                            <select name="level" class="form-select">
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="submit" name="add_course" class="btn btn-primary w-100">Create Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
