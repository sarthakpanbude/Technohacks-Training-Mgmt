<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$activePage = 'admissions';
$pageTitle = "Admission Management";
include '../includes/header.php';
include '../includes/sidebar.php';

// Fetch inquiries pending review (Successfully Submitted)
$stmt = $pdo->query("SELECT * FROM inquiries WHERE status = 'Successfully Submitted' ORDER BY id DESC");
$pending_inquiries = $stmt->fetchAll();
$pending_count = count($pending_inquiries);
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Admission Management</h3>
            <p class="text-muted small">Review and process new student applications.</p>
        </div>
        <div class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill">
            <i class="fas fa-clock me-1"></i> <?php echo $pending_count; ?> Pending Applications
        </div>
    </div>

    <?php if ($pending_count == 0): ?>
        <!-- "All Caught Up" UI from user image -->
        <div class="d-flex justify-content-center align-items-center" style="min-height: 400px;">
            <div class="stat-card bg-white rounded-4 shadow-sm p-5 text-center border-0 w-75">
                <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 d-inline-block mb-4" style="width: 80px; height: 80px;">
                    <i class="fas fa-check fa-2x mt-2"></i>
                </div>
                <h3 class="fw-bold mb-2">All caught up!</h3>
                <p class="text-muted">No new admission forms to review at the moment.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="stat-card bg-white rounded-4 shadow-sm p-4 border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 rounded-start">Student Details</th>
                            <th class="border-0">Course</th>
                            <th class="border-0">Date</th>
                            <th class="border-0 text-center">Status</th>
                            <th class="border-0 rounded-end text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pending_inquiries as $s): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3">
                                        <i class="fas fa-user-edit"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo $s['name']; ?></div>
                                        <div class="text-muted small"><?php echo $s['mobile']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill"><?php echo $s['course']; ?></span></td>
                            <td><?php echo date('d M, Y', strtotime($s['created_at'])); ?></td>
                            <td class="text-center">
                                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill"><?php echo $s['status']; ?></span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="admit.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-light border" title="Print Bill">
                                        <i class="fas fa-file-invoice text-primary"></i>
                                    </a>
                                    <a href="mailto:?subject=Admission Inquiry&body=Hello <?php echo $s['name']; ?>..." class="btn btn-sm btn-light border" title="Share via Email">
                                        <i class="fas fa-envelope text-danger"></i>
                                    </a>
                                    <a href="https://wa.me/<?php echo $s['mobile']; ?>?text=Hello <?php echo $s['name']; ?>..." target="_blank" class="btn btn-sm btn-light border" title="Share via WhatsApp">
                                        <i class="fab fa-whatsapp text-success"></i>
                                    </a>
                                    <a href="admit.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-primary px-3 rounded-pill">Admit Now</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
