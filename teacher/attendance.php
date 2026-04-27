<?php
require_once '../includes/auth.php';
checkAuth('teacher');
require_once '../config/db.php';

$pageTitle = "Mark Attendance";
$activePage = "attendance";

$userId = $_SESSION['user_id'];
$teacher = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$teacher->execute([$userId]);
$teacherId = $teacher->fetchColumn();

// Fetch Batches
$batches = $pdo->prepare("SELECT id, batch_name FROM batches WHERE teacher_id = ?");
$batches->execute([$teacherId]);
$batchList = $batches->fetchAll();

$selectedBatch = $_GET['batch_id'] ?? ($batchList[0]['id'] ?? null);
$students = [];

if ($selectedBatch) {
    $stmt = $pdo->prepare("SELECT s.id, u.full_name FROM students s JOIN users u ON s.user_id = u.id JOIN enrollments e ON s.id = e.student_id WHERE e.batch_id = ?");
    $stmt->execute([$selectedBatch]);
    $students = $stmt->fetchAll();
}

// Handle Attendance Submission
if (isset($_POST['save_attendance'])) {
    $date = $_POST['date'];
    foreach ($_POST['attendance'] as $sId => $status) {
        $stmt = $pdo->prepare("INSERT INTO attendance (student_id, batch_id, date, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sId, $selectedBatch, $date, $status]);
    }
    header("Location: attendance.php?batch_id=$selectedBatch&msg=Attendance Saved");
    exit;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Attendance</h2>
        <form class="d-flex gap-2">
            <select name="batch_id" class="form-select w-auto" onchange="this.form.submit()">
                <?php foreach ($batchList as $b): ?>
                    <option value="<?php echo $b['id']; ?>" <?php echo $selectedBatch == $b['id'] ? 'selected' : ''; ?>><?php echo $b['batch_name']; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="stat-card">
        <form action="" method="POST">
            <div class="row mb-4 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Select Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">Student Name</th>
                            <th class="border-0">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="2" class="text-center py-4 text-muted">No students enrolled in this batch.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?php echo $s['full_name']; ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <input type="radio" class="btn-check" name="attendance[<?php echo $s['id']; ?>]" id="p<?php echo $s['id']; ?>" value="present" checked>
                                        <label class="btn btn-outline-success btn-sm px-3" for="p<?php echo $s['id']; ?>">P</label>

                                        <input type="radio" class="btn-check" name="attendance[<?php echo $s['id']; ?>]" id="a<?php echo $s['id']; ?>" value="absent">
                                        <label class="btn btn-outline-danger btn-sm px-3" for="a<?php echo $s['id']; ?>">A</label>

                                        <input type="radio" class="btn-check" name="attendance[<?php echo $s['id']; ?>]" id="l<?php echo $s['id']; ?>" value="late">
                                        <label class="btn btn-outline-warning btn-sm px-3" for="l<?php echo $s['id']; ?>">L</label>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($students)): ?>
            <div class="text-end mt-4">
                <button type="submit" name="save_attendance" class="btn btn-primary px-5">Save Attendance</button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
