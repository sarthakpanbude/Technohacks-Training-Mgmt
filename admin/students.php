<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Active Students";
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

// Fetch Students - Simplified for professional view
$students = $pdo->query("SELECT s.*, u.full_name as display_name 
                        FROM students s 
                        LEFT JOIN users u ON s.user_id = u.id 
                        ORDER BY s.created_at DESC")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Active Students</h2>
            <p class="text-muted small">Manage enrollment records and generate admission forms.</p>
        </div>
        <a href="admit.php?new=1" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="fas fa-plus me-2"></i>New Admission
        </a>
    </div>

    <div class="stat-card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 text-muted small text-uppercase fw-bold">Student ID</th>
                        <th class="py-3 text-muted small text-uppercase fw-bold">Full Name</th>
                        <th class="py-3 text-muted small text-uppercase fw-bold">Course Selected</th>
                        <th class="py-3 text-muted small text-uppercase fw-bold">Joining Date</th>
                        <th class="px-4 py-3 text-muted small text-uppercase fw-bold text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-user-slash d-block mb-2 h3 opacity-50"></i>
                                No active students found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td class="px-4">
                                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($s['enrollment_no'] ?? 'N/A'); ?></span>
                                </td>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($s['display_name']); ?></td>
                                <td>
                                    <span class="badge bg-purple-light text-purple rounded px-2 py-1 small">
                                        <?php echo htmlspecialchars($s['course'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-muted small">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        <?php echo date('d M, Y', strtotime($s['created_at'])); ?>
                                    </div>
                                </td>
                                <td class="px-4 text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="generate_form.php?id=<?php echo $s['enrollment_no']; ?>" 
                                           class="btn btn-purple btn-sm rounded px-3 shadow-sm" title="View Admission Form">
                                            <i class="fas fa-file-pdf me-2"></i>View Form
                                        </a>
                                        
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm rounded border shadow-sm" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                <li>
                                                    <a class="dropdown-item py-2" href="view_student.php?id=<?php echo $s['id']; ?>">
                                                        <i class="fas fa-user me-2 text-primary"></i>View Profile
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider opacity-50"></li>
                                                <li>
                                                    <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete this admission?');">
                                                        <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                                                        <button type="submit" name="delete_student" class="dropdown-item py-2 text-danger">
                                                            <i class="fas fa-trash-alt me-2"></i>Delete Record
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
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

<style>
    .bg-purple-light { background-color: rgba(128, 0, 128, 0.08); }
    .text-purple { color: #800080; }
    .btn-purple { 
        background-color: #800080; 
        color: white; 
        border: none;
        transition: all 0.3s ease;
    }
    .btn-purple:hover { 
        background-color: #660066; 
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(128, 0, 128, 0.2) !important;
    }
    .stat-card {
        background: white;
        border-radius: 15px;
        border: 1px solid #eee;
    }
    .table thead th {
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        background: #fdfdfd;
        border-bottom: 2px solid #eee !important;
    }
    .table tbody td {
        padding: 20px 15px !important;
        border-bottom: 1px solid #f0f0f0 !important;
    }
    .table tbody tr:last-child td {
        border-bottom: none !important;
    }
</style>

<?php include '../includes/footer.php'; ?>