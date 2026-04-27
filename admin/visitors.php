<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Inquiry Management";
$activePage = "visitors";

// Handle Status Update
if (isset($_POST['update_visitor_status'])) {
    $id = $_POST['visitor_id'];
    $status = $_POST['update_visitor_status'];
    $stmt = $pdo->prepare("UPDATE visitors SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    header("Location: visitors.php?msg=Status Updated");
    exit;
}

// Fetch Visitors
$visitors = $pdo->query("SELECT * FROM visitors ORDER BY created_at DESC")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <?php include '../includes/topbar.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Student Inquiries</h4>
            <p class="text-muted small">Approve or Reject new student applications.</p>
        </div>
        <button class="btn btn-primary shadow-sm" onclick="window.print()"><i class="fas fa-print me-2"></i>Export List</button>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card border-start border-primary border-4 p-3 bg-white rounded-4 shadow-sm">
                <h6 class="text-muted small fw-bold mb-1">Total Inquiries</h6>
                <h3 class="fw-bold mb-0"><?php echo count($visitors); ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-warning border-4 p-3 bg-white rounded-4 shadow-sm">
                <h6 class="text-muted small fw-bold mb-1">New Applications</h6>
                <h3 class="fw-bold mb-0"><?php echo count(array_filter($visitors, fn($v) => $v['status'] == 'new')); ?></h3>
            </div>
        </div>
    </div>

    <div class="stat-card bg-white rounded-4 shadow-sm overflow-hidden border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 border-0">Student</th>
                        <th class="border-0">Gender/Age</th>
                        <th class="border-0">Domain</th>
                        <th class="border-0">Contact</th>
                        <th class="border-0">Status</th>
                        <th class="border-0 text-end px-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($visitors)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No inquiries found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($visitors as $v): ?>
                            <tr>
                                <td class="px-4">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <?php if ($v['photo']): ?>
                                                <img src="../<?php echo $v['photo']; ?>" class="rounded-circle border" width="45" height="45" style="object-fit: cover;">
                                            <?php else: ?>
                                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border" width="45" height="45">
                                                    <i class="fas fa-user text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo $v['name']; ?></div>
                                            <div class="text-muted small"><?php echo date('M d, Y', strtotime($v['created_at'])); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?php echo $v['gender']; ?></span>
                                    <span class="badge bg-light text-dark border"><?php echo $v['age']; ?> yrs</span>
                                </td>
                                <td>
                                    <div class="fw-medium text-primary small"><?php echo $v['course_interest']; ?></div>
                                </td>
                                <td>
                                    <div class="small fw-bold"><i class="fas fa-phone-alt me-1 text-muted"></i> <?php echo $v['phone']; ?></div>
                                    <div class="small text-muted"><?php echo $v['email'] ?: 'No Email'; ?></div>
                                </td>
                                <td>
                                    <?php
                                    $badge = 'bg-secondary';
                                    if ($v['status'] == 'new') $badge = 'bg-warning text-dark';
                                    if ($v['status'] == 'contacted') $badge = 'bg-primary';
                                    if ($v['status'] == 'converted') $badge = 'bg-success';
                                    if ($v['status'] == 'rejected') $badge = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badge; ?> rounded-pill text-capitalize small px-3"><?php echo $v['status']; ?></span>
                                </td>
                                <td class="text-end px-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <form action="" method="POST" class="d-inline">
                                            <input type="hidden" name="visitor_id" value="<?php echo $v['id']; ?>">
                                            <?php if ($v['status'] == 'new'): ?>
                                                <button type="submit" name="update_visitor_status" value="converted" class="btn btn-sm btn-success rounded-pill px-3">Approve</button>
                                                <button type="submit" name="update_visitor_status" value="rejected" class="btn btn-sm btn-outline-danger rounded-pill px-3">Reject</button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 disabled">Handled</button>
                                            <?php endif; ?>
                                        </form>
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