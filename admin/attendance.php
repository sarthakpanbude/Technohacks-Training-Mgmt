<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Attendance Management";
$activePage = "attendance";

// Handle Mark Attendance
if (isset($_POST['mark_attendance'])) {
    $batch_id = $_POST['batch_id'];
    $date = $_POST['date'];
    $students = $_POST['student_ids'] ?? [];
    $statuses = $_POST['statuses'] ?? [];

    foreach ($students as $i => $sid) {
        $status = $statuses[$i] ?? 'present';
        // Check if already marked
        $check = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND batch_id = ? AND date = ?");
        $check->execute([$sid, $batch_id, $date]);
        if ($check->fetch()) {
            $stmt = $pdo->prepare("UPDATE attendance SET status = ? WHERE student_id = ? AND batch_id = ? AND date = ?");
            $stmt->execute([$status, $sid, $batch_id, $date]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, batch_id, date, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sid, $batch_id, $date, $status]);
        }
    }
    header("Location: attendance.php?msg=Attendance Saved&batch_id=" . $batch_id);
    exit;
}

// Fetch batches
$batches = $pdo->query("SELECT b.id, b.batch_name, c.name as course_name FROM batches b JOIN courses c ON b.course_id = c.id WHERE b.status = 'active' ORDER BY b.batch_name")->fetchAll();

$selected_batch = $_GET['batch_id'] ?? '';
$selected_date = $_GET['date'] ?? date('Y-m-d');
$students = [];
$attendance_data = [];

if ($selected_batch) {
    $students = $pdo->prepare("SELECT s.id, u.full_name FROM enrollments e JOIN students s ON e.student_id = s.id JOIN users u ON s.user_id = u.id WHERE e.batch_id = ? AND e.status = 'active' ORDER BY u.full_name");
    $students->execute([$selected_batch]);
    $students = $students->fetchAll();

    foreach ($students as $s) {
        $att = $pdo->prepare("SELECT status FROM attendance WHERE student_id = ? AND batch_id = ? AND date = ?");
        $att->execute([$s['id'], $selected_batch, $selected_date]);
        $row = $att->fetch();
        $attendance_data[$s['id']] = $row ? $row['status'] : '';
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Attendance</h2>
            <p class="text-muted">Mark and track daily student attendance.</p>
        </div>
    </div>

    <div class="stat-card mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Select Batch</label>
                <select name="batch_id" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Choose Batch --</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?php echo $b['id']; ?>" <?php echo $selected_batch == $b['id'] ? 'selected' : ''; ?>>
                            <?php echo $b['batch_name'] . ' (' . $b['course_name'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo $selected_date; ?>"
                    onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <?php if ($selected_batch && !empty($students)): ?>
        <div class="stat-card">
            <form method="POST">
                <input type="hidden" name="batch_id" value="<?php echo $selected_batch; ?>">
                <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0">#</th>
                                <th class="border-0">Student Name</th>
                                <th class="border-0">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $i => $s): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td class="fw-bold"><?php echo $s['full_name']; ?></td>
                                    <td>
                                        <input type="hidden" name="student_ids[]" value="<?php echo $s['id']; ?>">
                                        <?php $curr = $attendance_data[$s['id']] ?? ''; ?>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="statuses[<?php echo $i; ?>]"
                                                value="present" id="p<?php echo $s['id']; ?>" <?php echo ($curr == 'present' || $curr == '') ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-success"
                                                for="p<?php echo $s['id']; ?>">Present</label>

                                            <input type="radio" class="btn-check" name="statuses[<?php echo $i; ?>]"
                                                value="absent" id="a<?php echo $s['id']; ?>" <?php echo $curr == 'absent' ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-danger" for="a<?php echo $s['id']; ?>">Absent</label>

                                            <input type="radio" class="btn-check" name="statuses[<?php echo $i; ?>]"
                                                value="late" id="l<?php echo $s['id']; ?>" <?php echo $curr == 'late' ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-warning" for="l<?php echo $s['id']; ?>">Late</label>

                                            <input type="radio" class="btn-check" name="statuses[<?php echo $i; ?>]"
                                                value="leave" id="lv<?php echo $s['id']; ?>" <?php echo $curr == 'leave' ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-info" for="lv<?php echo $s['id']; ?>">Leave</label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" name="mark_attendance" class="btn btn-primary mt-3">
                    <i class="fas fa-save me-2"></i>Save Attendance
                </button>
            </form>
        </div>
    <?php elseif ($selected_batch && empty($students)): ?>
        <div class="stat-card text-center py-5">
            <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
            <h5 class="fw-bold">No Students Enrolled</h5>
            <p class="text-muted">No active students found in this batch.</p>
        </div>
    <?php elseif (!$selected_batch): ?>
        <div class="stat-card text-center py-5">
            <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
            <h5 class="fw-bold">Select a Batch</h5>
            <p class="text-muted">Choose a batch above to start marking attendance.</p>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>