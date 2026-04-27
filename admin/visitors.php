<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Visitor Management";
$activePage = "visitors";

// Handle Status Update
if (isset($_POST['update_visitor_status'])) {
    $id = $_POST['visitor_id'];
    $status = $_POST['status'];
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

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Visitor Management</h2>
            <p class="text-muted">Track and manage potential student enquiries.</p>
        </div>
        <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print me-2"></i>Export Leads</button>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card border-start border-primary border-4">
                <h6 class="text-muted small fw-bold">Total Enquiries</h6>
                <h3 class="fw-bold"><?php echo count($visitors); ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-warning border-4">
                <h6 class="text-muted small fw-bold">New Leads</h6>
                <h3 class="fw-bold"><?php
                echo count(array_filter($visitors, fn($v) => $v['status'] == 'new'));
                ?></h3>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0">Date</th>
                        <th class="border-0">Visitor Name</th>
                        <th class="border-0">Contact Details</th>
                        <th class="border-0">Interested In</th>
                        <th class="border-0">Status</th>
                        <th class="border-0">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($visitors)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No visitor enquiries yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($visitors as $v): ?>
                            <tr>
                                <td class="small text-muted"><?php echo date('M d, Y', strtotime($v['created_at'])); ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo $v['name']; ?></div>
                                    <div class="text-muted small">
                                        <?php echo $v['message'] ? '"' . substr($v['message'], 0, 30) . '..."' : ''; ?></div>
                                </td>
                                <td>
                                    <div class="small"><i class="fas fa-phone-alt me-1 text-primary"></i>
                                        <?php echo $v['phone']; ?></div>
                                    <div class="small"><i class="fas fa-envelope me-1 text-muted"></i>
                                        <?php echo $v['email'] ?: 'N/A'; ?></div>
                                </td>
                                <td><span
                                        class="badge bg-info bg-opacity-10 text-info"><?php echo $v['course_interest']; ?></span>
                                </td>
                                <td>
                                    <?php
                                    $badge = 'bg-secondary';
                                    if ($v['status'] == 'new')
                                        $badge = 'bg-warning text-dark';
                                    if ($v['status'] == 'contacted')
                                        $badge = 'bg-primary';
                                    if ($v['status'] == 'converted')
                                        $badge = 'bg-success';
                                    if ($v['status'] == 'rejected')
                                        $badge = 'bg-danger';
                                    ?>
                                    <span
                                        class="badge <?php echo $badge; ?> rounded-pill text-capitalize"><?php echo $v['status']; ?></span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm rounded-circle" type="button"
                                            data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                            <li>
                                                <form action="" method="POST">
                                                    <input type="hidden" name="visitor_id" value="<?php echo $v['id']; ?>">
                                                    <button type="submit" name="update_visitor_status" value="contacted"
                                                        class="dropdown-item small">Mark Contacted</button>
                                                    <button type="submit" name="update_visitor_status" value="converted"
                                                        class="dropdown-item small text-success">Converted to Admission</button>
                                                    <button type="submit" name="update_visitor_status" value="rejected"
                                                        class="dropdown-item small text-danger">Rejected</button>
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