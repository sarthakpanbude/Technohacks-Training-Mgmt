<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Inquiry Management";
$activePage = "visitors";

// Fetch from inquiries table
$inquiries_data = $pdo->query("SELECT id, name, mobile as phone, course, status, created_at, message, 'inquiry' as source FROM inquiries WHERE status NOT IN ('admitted', 'deleted') OR status IS NULL OR status = ''")->fetchAll();

// Fetch from visitors table
$visitors_data = $pdo->query("SELECT id, name, phone, course_interest as course, status, created_at, message, 'visitor' as source FROM visitors WHERE status NOT IN ('converted', 'rejected')")->fetchAll();

// Merge and sort
$inquiries = array_merge($inquiries_data, $visitors_data);
usort($inquiries, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});


include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Student Inquiries</h4>
    </div>

    <div class="stat-card bg-white rounded-4 shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 border-0">Student Details</th>
                        <th class="border-0">Course Interest</th>
                        <th class="border-0 text-center">Status</th>
                        <th class="border-0">Date</th>
                        <th class="text-end px-4 border-0">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inquiries)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                            <p class="mb-0">No pending inquiries found at the moment.</p>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($inquiries as $inq): 
                            $uniqueId = $inq['source'] . "_" . $inq['id'];
                        ?>
                        <tr>
                            <td class="px-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-weight: bold;">
                                        <?php echo strtoupper(substr($inq['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($inq['name']); ?></div>
                                        <div class="text-muted small"><i class="fas fa-phone-alt me-1"></i><?php echo htmlspecialchars($inq['phone']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 fw-medium">
                                    <?php echo htmlspecialchars($inq['course']); ?>
                                </span>
                                <br><small class="text-muted ms-2" style="font-size: 0.65rem; text-uppercase; letter-spacing: 0.5px;"><?php echo $inq['source']; ?> Lead</small>
                            </td>
                            <td class="text-center">
                                <?php if ($inq['status'] == 'new' || $inq['status'] == ''): ?>
                                    <span class="badge bg-warning text-white rounded-pill px-3 py-2">New Inquiry</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-2 text-capitalize"><?php echo htmlspecialchars($inq['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small">
                                <i class="far fa-calendar-alt me-1"></i><?php echo date('d M, Y', strtotime($inq['created_at'])); ?>
                            </td>
                            <td class="text-end px-4">
                                <div class="btn-group shadow-sm rounded-pill overflow-hidden border bg-white">
                                    <button class="btn btn-sm btn-white border-0 px-3 py-2" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $uniqueId; ?>" title="View Details">
                                        <i class="fas fa-eye text-primary"></i>
                                    </button>
                                    <a href="admit.php?id=<?php echo $inq['id']; ?>&source=<?php echo $inq['source']; ?>" class="btn btn-sm btn-white border-0 border-start px-3 py-2" title="Convert to Admission">
                                        <i class="fas fa-user-check text-success"></i>
                                    </a>
                                    <a href="actions/delete.php?id=<?php echo $inq['id']; ?>&source=<?php echo $inq['source']; ?>" class="btn btn-sm btn-white border-0 border-start px-3 py-2" onclick="return confirm('Are you sure you want to delete this lead?')" title="Delete Lead">
                                        <i class="fas fa-trash-alt text-danger"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modals Section (Outside Table) -->
    <?php foreach ($inquiries as $inq): 
        $uniqueId = $inq['source'] . "_" . $inq['id'];
    ?>
    <div class="modal fade" id="viewModal<?php echo $uniqueId; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                        <i class="fas fa-user-edit fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Inquiry Profile</h5>
                        <p class="text-muted small mb-0"><?php echo strtoupper($inq['source']); ?> Lead ID: #<?php echo $inq['id']; ?></p>
                    </div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="p-3 bg-light rounded-4">
                                <label class="small text-muted text-uppercase fw-bold mb-1 d-block" style="font-size: 0.65rem; letter-spacing: 0.5px;">Candidate Name</label>
                                <div class="fw-bold fs-5 text-dark"><?php echo htmlspecialchars($inq['name']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold mb-1 d-block" style="font-size: 0.65rem; letter-spacing: 0.5px;">Contact Number</label>
                            <div class="fw-medium"><i class="fas fa-phone-alt me-2 text-primary"></i><?php echo htmlspecialchars($inq['phone']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold mb-1 d-block" style="font-size: 0.65rem; letter-spacing: 0.5px;">Inquiry Date</label>
                            <div class="fw-medium"><i class="fas fa-calendar-check me-2 text-primary"></i><?php echo date('d M, Y', strtotime($inq['created_at'])); ?></div>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted text-uppercase fw-bold mb-1 d-block" style="font-size: 0.65rem; letter-spacing: 0.5px;">Selected Program</label>
                            <div class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill fs-6 fw-bold">
                                <?php echo htmlspecialchars($inq['course']); ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted text-uppercase fw-bold mb-1 d-block" style="font-size: 0.65rem; letter-spacing: 0.5px;">Message / Requirement Details</label>
                            <div class="p-3 bg-light rounded-4 border-start border-primary border-4">
                                <p class="mb-0 text-dark" style="line-height: 1.6;">
                                    <?php echo nl2br(htmlspecialchars($inq['message'] ?? 'No additional details provided by the candidate.')); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close Window</button>
                    <a href="admit.php?id=<?php echo $inq['id']; ?>&source=<?php echo $inq['source']; ?>" class="btn btn-primary rounded-pill px-4">Convert to Admission &rarr;</a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

</main>

<style>
    .btn-white:hover { background-color: #f8f9fa; }
    .avatar-sm { font-size: 1rem; }
    .modal-content { overflow: hidden; }
</style>

<?php include '../includes/footer.php'; ?>
