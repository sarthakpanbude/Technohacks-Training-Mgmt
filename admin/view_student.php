<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Student Profile";
$activePage = "students";

$id = $_GET['id'] ?? 0;
$student = $pdo->prepare("SELECT s.*, u.full_name, u.email, u.created_at as joined_at FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$student->execute([$id]);
$student = $student->fetch();

if (!$student) {
    header("Location: students.php?msg=Student Not Found");
    exit;
}

// Fetch enrollments
$enrollments = $pdo->prepare("SELECT e.*, b.batch_name, c.name as course_name FROM enrollments e JOIN batches b ON e.batch_id = b.id JOIN courses c ON b.course_id = c.id WHERE e.student_id = ?");
$enrollments->execute([$id]);
$enrollments = $enrollments->fetchAll();

// Fetch payments
$payments = $pdo->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date DESC");
$payments->execute([$id]);
$payments = $payments->fetchAll();

$totalPaid = array_sum(array_column($payments, 'amount'));

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><?php echo $student['full_name']; ?></h2>
            <p class="text-muted mb-0">Enrollment: <?php echo $student['enrollment_no'] ?? 'N/A'; ?></p>
        </div>
        <a href="students.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>

    <div class="row g-4">
        <!-- Profile Info -->
        <div class="col-md-4">
            <div class="stat-card h-100">
                <div class="text-center mb-3">
                    <div class="avatar mx-auto bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">
                        <?php echo strtoupper(substr($student['full_name'], 0, 2)); ?>
                    </div>
                    <h5 class="fw-bold mb-1"><?php echo $student['full_name']; ?></h5>
                    <?php
                    $statusColor = 'secondary';
                    if (in_array($student['admission_status'], ['enrolled', 'active'])) $statusColor = 'primary';
                    if (in_array($student['admission_status'], ['approved', 'placed'])) $statusColor = 'success';
                    if ($student['admission_status'] == 'pending') $statusColor = 'warning';
                    ?>
                    <span class="badge bg-<?php echo $statusColor; ?> bg-opacity-10 text-<?php echo $statusColor; ?> rounded-pill text-capitalize">
                        <?php echo $student['admission_status']; ?>
                    </span>
                </div>
                <hr>
                <div class="mb-2"><i class="fas fa-envelope text-muted me-2"></i><?php echo $student['email']; ?></div>
                <div class="mb-2"><i class="fas fa-phone text-muted me-2"></i><?php echo $student['phone'] ?: 'N/A'; ?></div>
                <div class="mb-2"><i class="fas fa-birthday-cake text-muted me-2"></i><?php echo $student['dob'] ? date('d M Y', strtotime($student['dob'])) : 'N/A'; ?></div>
                <div class="mb-2"><i class="fas fa-map-marker-alt text-muted me-2"></i><?php echo $student['address'] ?: 'N/A'; ?></div>
                <div class="mb-2"><i class="fas fa-share-alt text-muted me-2"></i>Referral: <code><?php echo $student['referral_code'] ?? 'N/A'; ?></code></div>
                <div class="mb-0"><i class="fas fa-calendar text-muted me-2"></i>Joined: <?php echo date('d M Y', strtotime($student['joined_at'])); ?></div>
            </div>
        </div>

        <!-- Details -->
        <div class="col-md-8">
            <!-- Enrollments -->
            <div class="stat-card mb-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-layer-group me-2 text-primary"></i>Batch Enrollments</h5>
                <?php if (empty($enrollments)): ?>
                    <p class="text-muted">Not enrolled in any batch yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">Batch</th>
                                    <th class="border-0">Course</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0">Date</th>
                                    <th class="border-0 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrollments as $e): 
                                    // Check if certificate already issued
                                    $certCheck = $pdo->prepare("SELECT id FROM certificates WHERE enrollment_id = ?");
                                    $certCheck->execute([$e['id']]);
                                    $certificate = $certCheck->fetch();
                                ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $e['batch_name']; ?></td>
                                    <td><?php echo $e['course_name']; ?></td>
                                    <td><span class="badge bg-info bg-opacity-10 text-info rounded-pill text-capitalize"><?php echo $e['status']; ?></span></td>
                                    <td class="text-muted small"><?php echo date('d M Y', strtotime($e['enrollment_date'])); ?></td>
                                    <td class="text-end">
                                        <?php if ($certificate): ?>
                                            <a href="../student/view_certificate.php?id=<?php echo $certificate['id']; ?>" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                                <i class="fas fa-eye me-1"></i> View Cert
                                            </a>
                                        <?php else: ?>
                                            <a href="issue_certificate.php?enrollment_id=<?php echo $e['id']; ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                                <i class="fas fa-certificate me-1"></i> Issue
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payments -->
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-wallet me-2 text-success"></i>Payment History</h5>
                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-bold">Total: ₹<?php echo number_format($totalPaid, 2); ?></span>
                </div>
                <?php if (empty($payments)): ?>
                    <p class="text-muted">No payments recorded.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">Receipt</th>
                                    <th class="border-0">Amount</th>
                                    <th class="border-0">Type</th>
                                    <th class="border-0">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?php echo $p['receipt_no']; ?></td>
                                    <td>₹<?php echo number_format($p['amount'], 2); ?></td>
                                    <td><span class="badge bg-info bg-opacity-10 text-info rounded-pill"><?php echo $p['payment_type']; ?></span></td>
                                    <td class="text-muted small"><?php echo date('d M Y', strtotime($p['payment_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
