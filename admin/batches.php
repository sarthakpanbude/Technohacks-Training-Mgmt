<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Batch Management";
$activePage = "batches";

// Fetch Teachers & Courses for dropdowns
$teachers = $pdo->query("SELECT t.id, u.full_name FROM teachers t JOIN users u ON t.user_id = u.id")->fetchAll();
$courses = $pdo->query("SELECT id, course_name FROM courses")->fetchAll();

// Handle Add Batch
if (isset($_POST['add_batch'])) {
    $course_id = $_POST['course_id'];
    $teacher_id = $_POST['teacher_id'];
    $name = $_POST['batch_name'];
    $schedule = $_POST['schedule'];
    $start_time = $_POST['start_time'] ?? '';
    $capacity = $_POST['capacity'];
    $start_date = $_POST['start_date'];

    $stmt = $pdo->prepare("INSERT INTO batches (course_id, teacher_id, batch_name, schedule, start_time, capacity, start_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$course_id, $teacher_id, $name, $schedule, $start_time, $capacity, $start_date]);
    header("Location: batches.php?msg=Batch Created");
    exit;
}

$batches = $pdo->query("SELECT b.*, c.course_name, u.full_name as teacher_name FROM batches b JOIN courses c ON b.course_id = c.id JOIN teachers t ON b.teacher_id = t.id JOIN users u ON t.user_id = u.id ORDER BY b.id DESC")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Batches</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBatchModal"><i class="fas fa-plus me-2"></i>Create Batch</button>
    </div>

    <div class="stat-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0">Batch Name</th>
                        <th class="border-0">Course</th>
                        <th class="border-0">Teacher</th>
                        <th class="border-0">Schedule</th>
                        <th class="border-0">Timing</th>
                        <th class="border-0">Capacity</th>
                        <th class="border-0">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($batches)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No batches created yet.</td></tr>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No batches created yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($batches as $b): ?>
                        <tr>
                            <td class="fw-bold"><?php echo $b['batch_name']; ?></td>
                            <td><?php echo $b['course_name']; ?></td>
                            <td><?php echo $b['teacher_name']; ?></td>
                            <td><?php echo $b['schedule']; ?></td>
                            <td><?php echo $b['start_time'] ?: 'N/A'; ?></td>
                            <td>
                                <div class="progress" style="height: 6px; width: 100px;">
                                    <div class="progress-bar" style="width: 20%;"></div>
                                </div>
                                <span class="small text-muted">12 / <?php echo $b['capacity']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill text-capitalize"><?php echo $b['status']; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="addBatchModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">New Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Course</label>
                            <select name="course_id" class="form-select" required>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo $c['course_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Assign Teacher</label>
                            <select name="teacher_id" class="form-select" required>
                                <?php foreach ($teachers as $t): ?>
                                    <option value="<?php echo $t['id']; ?>"><?php echo $t['full_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Batch Name (e.g. WD-Morning-2024)</label>
                            <input type="text" name="batch_name" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold">Days (e.g. Mon-Fri)</label>
                                <input type="text" name="schedule" class="form-control" placeholder="Mon-Fri" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold">Timing (e.g. 10AM)</label>
                                <input type="text" name="start_time" class="form-control" placeholder="10:00 AM" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold">Capacity</label>
                                <input type="number" name="capacity" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="submit" name="add_batch" class="btn btn-primary w-100">Create Batch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
