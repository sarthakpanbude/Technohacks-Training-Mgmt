<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Support Tickets";
$activePage = "tickets";

// Handle Status Update
if (isset($_POST['update_ticket'])) {
    $id = $_POST['ticket_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    header("Location: tickets.php?msg=Ticket Updated");
    exit;
}

$tickets = $pdo->query("SELECT t.*, u.full_name FROM tickets t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Support Tickets</h2>
    </div>

    <div class="stat-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0">User</th>
                        <th class="border-0">Subject</th>
                        <th class="border-0">Priority</th>
                        <th class="border-0">Status</th>
                        <th class="border-0">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No tickets found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td><?php echo $t['full_name']; ?></td>
                            <td>
                                <h6 class="mb-0 fw-bold small"><?php echo $t['subject']; ?></h6>
                                <p class="text-muted mb-0 small"><?php echo substr($t['description'], 0, 50); ?>...</p>
                            </td>
                            <td>
                                <?php 
                                $pColor = 'secondary';
                                if ($t['priority'] == 'high') $pColor = 'danger';
                                if ($t['priority'] == 'medium') $pColor = 'warning';
                                ?>
                                <span class="badge bg-<?php echo $pColor; ?> bg-opacity-10 text-<?php echo $pColor; ?> rounded-pill text-capitalize"><?php echo $t['priority']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info rounded-pill text-capitalize"><?php echo $t['status']; ?></span>
                            </td>
                            <td>
                                <form action="" method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="ticket_id" value="<?php echo $t['id']; ?>">
                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="open" <?php echo $t['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                                        <option value="in_progress" <?php echo $t['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="resolved" <?php echo $t['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        <option value="closed" <?php echo $t['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                    <input type="hidden" name="update_ticket" value="1">
                                </form>
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
