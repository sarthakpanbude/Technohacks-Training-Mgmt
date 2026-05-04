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
        <a href="admit.php?new=1" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="fas fa-plus me-2"></i>New Admission
        </a>
    </div>

    <div class="stat-card bg-white rounded-4 shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4">Name</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inquiries)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No pending inquiries found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($inquiries as $inq): 
                            $uniqueId = $inq['source'] . "_" . $inq['id'];
                        ?>
                        <tr>
                            <td class="px-4 fw-bold">
                                <?php echo htmlspecialchars($inq['name']); ?>
                                <br><small class="text-muted" style="font-size: 0.65rem; text-uppercase;"><?php echo $inq['source']; ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($inq['phone']); ?></td>
                            <td><span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3"><?php echo htmlspecialchars($inq['course']); ?></span></td>
                            <td>
                                <?php if ($inq['status'] == 'new' || $inq['status'] == ''): ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3">New</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3"><?php echo htmlspecialchars($inq['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?php echo date('d M, Y', strtotime($inq['created_at'])); ?></td>
                            <td class="text-end px-4">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-light border-0 rounded-pill px-3 me-2" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $uniqueId; ?>">View</button>
                                    <a href="admit.php?id=<?php echo $inq['id']; ?>&source=<?php echo $inq['source']; ?>" class="btn btn-sm btn-success rounded-pill px-3 me-2">Admit</a>
                                    <a href="actions/delete.php?id=<?php echo $inq['id']; ?>&source=<?php echo $inq['source']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Soft delete this inquiry?')">Delete</a>
                                </div>

                                <!-- View Modal -->
                                <div class="modal fade text-start" id="viewModal<?php echo $uniqueId; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                                            <div class="modal-header border-0">
                                                <h5 class="modal-title fw-bold">Inquiry Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="small text-muted text-uppercase fw-bold">Full Name</label>
                                                    <p class="mb-0"><?php echo htmlspecialchars($inq['name']); ?></p>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small text-muted text-uppercase fw-bold">Phone Number</label>
                                                    <p class="mb-0"><?php echo htmlspecialchars($inq['phone']); ?></p>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small text-muted text-uppercase fw-bold">Course Interest</label>
                                                    <p class="mb-0"><?php echo htmlspecialchars($inq['course']); ?></p>
                                                </div>
                                                <div class="mb-0">
                                                    <label class="small text-muted text-uppercase fw-bold">Message / Context</label>
                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($inq['message'] ?? 'No message provided.')); ?></p>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
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

<?php include '../includes/footer.php'; ?>
