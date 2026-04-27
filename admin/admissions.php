<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Admission Management";
$activePage = "admissions";

// Handle Actions
if (isset($_POST['action'])) {
    $id = $_POST['student_id'];
    $action = $_POST['action'];
    $status = ($action == 'approve') ? 'approved' : 'rejected';
    
    $stmt = $pdo->prepare("UPDATE students SET admission_status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    header("Location: admissions.php?msg=Application " . ucfirst($status));
    exit;
}

// Fetch Pending Admissions
$admissions = $pdo->query("SELECT s.*, u.full_name, u.email, u.created_at as applied_at FROM students s JOIN users u ON s.user_id = u.id WHERE s.admission_status = 'pending' ORDER BY u.created_at DESC")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Admission Management</h2>
            <p class="text-muted">Review and process new student applications.</p>
        </div>
        <div class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill fw-bold">
            <i class="fas fa-clock me-1"></i> <?php echo count($admissions); ?> Pending Applications
        </div>
    </div>

    <div class="row g-4">
        <?php if (empty($admissions)): ?>
            <div class="col-12 text-center py-5">
                <div class="stat-card bg-light border-0">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 class="fw-bold">All caught up!</h5>
                    <p class="text-muted">No new admission forms to review at the moment.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($admissions as $a): ?>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; font-weight: bold;">
                                <?php echo strtoupper(substr($a['full_name'], 0, 2)); ?>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold"><?php echo $a['full_name']; ?></h6>
                                <span class="small text-muted">Applied: <?php echo date('M d, H:i', strtotime($a['applied_at'])); ?></span>
                            </div>
                        </div>
                        <span class="badge bg-warning text-dark small">New Form</span>
                    </div>
                    
                    <div class="bg-light p-3 rounded-3 mb-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="small text-muted d-block">Email</label>
                                <span class="small fw-bold"><?php echo $a['email']; ?></span>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted d-block">Phone</label>
                                <span class="small fw-bold"><?php echo $a['phone'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="col-12 mt-2">
                                <label class="small text-muted d-block">Address</label>
                                <span class="small"><?php echo $a['address'] ?: 'N/A'; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <form action="" method="POST" class="flex-grow-1">
                            <input type="hidden" name="student_id" value="<?php echo $a['id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm w-100 fw-bold">
                                <i class="fas fa-check me-1"></i> Approve
                            </button>
                        </form>
                        <form action="" method="POST" class="flex-grow-1">
                            <input type="hidden" name="student_id" value="<?php echo $a['id']; ?>">
                            <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm w-100 fw-bold">
                                <i class="fas fa-times me-1"></i> Reject
                            </button>
                        </form>
                        <a href="view_student.php?id=<?php echo $a['id']; ?>" class="btn btn-light btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
