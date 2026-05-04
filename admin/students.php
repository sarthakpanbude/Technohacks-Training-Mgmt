<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Student Management";
$activePage = "students";

// Handle Student Deletion
if (isset($_POST['delete_student'])) {
    $id = $_POST['student_id'];

    // Get user_id to delete the user (which cascades to delete the student record)
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

// Fetch Students
$students = $pdo->query("SELECT s.*, u.full_name as display_name, u.email, GROUP_CONCAT(b.batch_name SEPARATOR ', ') as batch_name FROM students s LEFT JOIN users u ON s.user_id = u.id LEFT JOIN enrollments e ON e.student_id = s.id LEFT JOIN batches b ON e.batch_id = b.id GROUP BY s.id ORDER BY s.created_at DESC")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Students</h2>
        <a href="add_student.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>New Admission</a>
    </div>

    <div class="stat-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0">Enrollment #</th>
                        <th class="border-0">Name</th>
                        <th class="border-0">Email</th>
                        <th class="border-0">Batch</th>
                        <th class="border-0">Status</th>
                        <th class="border-0">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No students found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td class="fw-bold"><?php echo $s['enrollment_no'] ?? 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($s['display_name']); ?></td>
                                <td class="text-muted small"><?php echo htmlspecialchars($s['email'] ?? 'No Account'); ?></td>
                                <td class="small fw-bold <?php echo $s['batch_name'] ? 'text-primary' : 'text-muted'; ?>">
                                    <?php echo $s['batch_name'] ?: 'Not Assigned'; ?>
                                </td>
                                <td>
                                    <?php
                                    $badgeClass = 'bg-secondary';
                                    $statusText = $s['admission_status'];

                                    if ($s['admission_status'] == 'pending') {
                                        $badgeClass = 'bg-warning';
                                        $statusText = 'Pending';
                                    } elseif ($s['admission_status'] == 'admitted') {
                                        $badgeClass = 'bg-primary';
                                        $statusText = 'Admitted';
                                    } elseif ($s['admission_status'] == 'active' || $s['admission_status'] == 'enrolled') {
                                        $badgeClass = 'bg-success';
                                        $statusText = 'Active';
                                    }
                                    ?>
                                    <span
                                        class="badge <?php echo $badgeClass; ?> bg-opacity-10 text-<?php echo str_replace('bg-', '', $badgeClass); ?> rounded-pill text-capitalize">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm rounded-circle" type="button"
                                            data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                            <li><a class="dropdown-item" href="view_student.php?id=<?php echo $s['id']; ?>"><i
                                                        class="fas fa-eye me-2"></i>View Profile</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <form action="" method="POST"
                                                    onsubmit="return confirm('Are you sure you want to delete this admission? This action cannot be undone.');">
                                                    <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                                                    <button type="submit" name="delete_student"
                                                        class="dropdown-item small text-danger"><i
                                                            class="fas fa-trash-alt me-2"></i>Delete Admission</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>