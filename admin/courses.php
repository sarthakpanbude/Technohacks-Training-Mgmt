<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Course Management";
$activePage = "courses";

// Handle Add Course
if (isset($_POST['add_course'])) {
    $course_type = $_POST['course_type'];
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $duration = $_POST['duration'];
    $fees = $_POST['fees'];
    $level = $_POST['level'];

    $stmt = $pdo->prepare("INSERT INTO courses (course_type, name, description, duration, fees, level) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$course_type, $name, $desc, $duration, $fees, $level]);
    header("Location: courses.php?msg=Course Added");
    exit;
}

$courses = $pdo->query("SELECT * FROM courses ORDER BY course_type ASC, id DESC")->fetchAll();
$internships = array_filter($courses, function($c) { return $c['course_type'] == 'Internship'; });
$trainings = array_filter($courses, function($c) { return $c['course_type'] == 'Training'; });

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Courses</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal"><i class="fas fa-plus me-2"></i>Create Course</button>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active fw-bold" data-bs-toggle="tab" href="#internship">Internship Programs</a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold" data-bs-toggle="tab" href="#training">Training Courses</a>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="internship">
            <div class="row g-4">
                <?php if (empty($internships)): ?>
                    <div class="col-12 text-center py-5 text-muted">No internship programs available.</div>
                <?php else: ?>
                    <?php foreach ($internships as $c): ?>
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
                            <h5 class="fw-bold"><?php echo htmlspecialchars($c['name']); ?></h5>
                            <p class="text-muted small"><?php echo htmlspecialchars(substr($c['description'] ?? '', 0, 80)) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div>
                                    <span class="text-muted small">Fees</span>
                                    <h6 class="fw-bold mb-0">₹<?php echo number_format($c['fees'], 2); ?></h6>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted small">Duration</span>
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($c['duration']); ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="tab-pane fade" id="training">
            <div class="row g-4">
                <?php if (empty($trainings)): ?>
                    <div class="col-12 text-center py-5 text-muted">No training courses available.</div>
                <?php else: ?>
                    <?php foreach ($trainings as $c): ?>
                    <div class="col-md-4">
                        <div class="stat-card border-top border-4 border-success">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill text-capitalize"><?php echo $c['level']; ?></span>
                                <div class="dropdown">
                                    <i class="fas fa-ellipsis-h text-muted cursor-pointer" data-bs-toggle="dropdown"></i>
                                    <ul class="dropdown-menu border-0 shadow">
                                        <li><a class="dropdown-item small" href="#"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                        <li><a class="dropdown-item small text-danger" href="#"><i class="fas fa-trash me-2"></i>Delete</a></li>
                                    </ul>
                                </div>
                            </div>
                            <h5 class="fw-bold"><?php echo htmlspecialchars($c['name']); ?></h5>
                            <p class="text-muted small"><?php echo htmlspecialchars(substr($c['description'] ?? '', 0, 80)) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div>
                                    <span class="text-muted small">Fees</span>
                                    <h6 class="fw-bold mb-0">₹<?php echo number_format($c['fees'], 2); ?></h6>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted small">Duration</span>
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($c['duration']); ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
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
                            <label class="form-label small fw-bold">Course Type</label>
                            <select name="course_type" class="form-select">
                                <option value="Internship">Internship Program</option>
                                <option value="Training">Training Course</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Level / Category</label>
                            <select name="level" class="form-select">
                                <option value="Beginner">Beginner</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Advanced">Advanced (Level 1)</option>
                                <option value="Full Course">Full Course (Level 2)</option>
                                <option value="Small Course">Small Course (Level 3)</option>
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
