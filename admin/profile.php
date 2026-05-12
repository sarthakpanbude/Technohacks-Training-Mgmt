<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, username = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $username, $user_id]);
        $_SESSION['full_name'] = $full_name; // Update session name
        header("Location: profile.php?msg=Profile settings updated successfully");
        exit;
    } catch (PDOException $e) {
        header("Location: profile.php?error=Username or Email already in use");
        exit;
    }
}

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (password_verify($current, $user['password'])) {
        if ($new === $confirm) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);
            header("Location: profile.php?msg=Security credentials updated successfully");
            exit;
        } else {
            $error = "New passwords do not match";
        }
    } else {
        $error = "Incorrect current password";
    }
}

// Handle Administrator Removal from Account Page
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_admin'])) {
    $admin_id = $_POST['admin_id'];
    if ($admin_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
            $stmt->execute([$admin_id]);
            $msg = "Administrator removed successfully!";
        } catch (Exception $e) {
            $error = "Error removing administrator: " . $e->getMessage();
        }
    }
}

// Fetch Administrators
$admins = $pdo->query("SELECT id, full_name, username, email FROM users WHERE role='admin' ORDER BY full_name ASC")->fetchAll();

$pageTitle = "Admin Account Settings";
$activePage = "account";

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100 p-4">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="fw-bold mb-1">Admin Account Control</h2>
            <p class="text-muted small mb-0">Manage your administrative identity and system access credentials.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-purple-light text-purple px-3 py-2 rounded-pill fw-bold">
                <i class="fas fa-shield-alt me-1"></i> System Administrator
            </span>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 animate__animated animate__fadeIn">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 animate__animated animate__shakeX">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Profile Form -->
        <div class="col-lg-8">
            <div class="stat-card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="p-4 bg-light border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0"><i class="fas fa-id-card me-2 text-purple"></i>Profile Information</h5>
                    <span class="text-muted x-small fw-bold">LAST UPDATED: <?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
                </div>
                <div class="p-4">
                    <form action="" method="POST">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Full Administrative Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-user"></i></span>
                                    <input type="text" name="full_name" class="form-control border-start-0 ps-0" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Login Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-at"></i></span>
                                    <input type="text" name="username" class="form-control border-start-0 ps-0" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted text-uppercase">Primary Recovery Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control border-start-0 ps-0" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <p class="text-muted x-small mt-2 mb-0"><i class="fas fa-info-circle me-1"></i> This email will be used for system notifications and account recovery.</p>
                            </div>
                            <div class="col-12 text-end">
                                <hr class="my-2 opacity-25">
                                <button type="submit" name="update_profile" class="btn btn-purple px-5 py-2 fw-bold shadow-sm rounded-pill">Update Profile</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Stats/Info below -->
            <div class="row g-4 mt-1">
                <div class="col-md-6">
                    <div class="stat-card border-0 shadow-sm p-4 text-center">
                        <div class="h3 fw-bold text-purple mb-1">Active</div>
                        <div class="text-muted small fw-bold text-uppercase">Account Status</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card border-0 shadow-sm p-4 text-center">
                        <div class="h3 fw-bold text-dark mb-1">Admin</div>
                        <div class="text-muted small fw-bold text-uppercase">Access Level</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Password Form -->
        <div class="col-lg-4">
            <div class="stat-card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="p-4 bg-dark text-white border-bottom">
                    <h5 class="fw-bold mb-0"><i class="fas fa-key me-2 text-warning"></i>Access Control</h5>
                </div>
                <div class="p-4">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Current Password</label>
                            <input type="password" name="current_password" class="form-control bg-light border-0 py-2" placeholder="••••••••" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">New Access Key</label>
                            <input type="password" name="new_password" class="form-control bg-light border-0 py-2" placeholder="••••••••" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Confirm New Key</label>
                            <input type="password" name="confirm_password" class="form-control bg-light border-0 py-2" placeholder="••••••••" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-dark w-100 py-2 fw-bold rounded-3 shadow-sm mb-3">
                            Change Password
                        </button>
                    </form>

                    <div class="alert alert-info border-0 py-2 x-small mb-0">
                        <i class="fas fa-info-circle me-1"></i> Use at least 8 characters with letters and numbers for better security.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Management Section (Moved from Settings) -->
    <div class="row g-4 mt-2">
        <div class="col-lg-12">
            <div class="stat-card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="p-4 bg-light border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="fas fa-users-cog me-2 text-purple"></i>Administrative Team</h5>
                        <p class="text-muted x-small mb-0">Manage system administrators and their access credentials.</p>
                    </div>
                    <button class="btn btn-purple btn-sm rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addQuickAdminModal">
                        <i class="fas fa-plus me-1"></i>Add New Admin
                    </button>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <?php foreach ($admins as $admin): ?>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-4 d-flex justify-content-between align-items-center border border-white h-100">
                                    <div class="d-flex align-items-center overflow-hidden">
                                        <div class="avatar bg-white text-purple rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 45px; height: 45px; font-weight: bold; flex-shrink: 0;">
                                            <?php echo strtoupper(substr($admin['full_name'], 0, 1)); ?>
                                        </div>
                                        <div class="overflow-hidden">
                                            <h6 class="mb-0 fw-bold small text-truncate"><?php echo htmlspecialchars($admin['full_name']); ?></h6>
                                            <p class="text-muted x-small mb-0 text-truncate">@<?php echo htmlspecialchars($admin['username']); ?></p>
                                        </div>
                                    </div>
                                    <div class="ms-2">
                                        <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle" data-bs-toggle="modal" data-bs-target="#deleteAdminModal<?php echo $admin['id']; ?>" title="Remove Administrator">
                                                <i class="fas fa-user-minus"></i>
                                            </button>

                                            <!-- Quick Delete Modal -->
                                            <div class="modal fade" id="deleteAdminModal<?php echo $admin['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                                        <div class="modal-body text-center py-4">
                                                            <div class="text-danger mb-3">
                                                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                                                            </div>
                                                            <h6 class="fw-bold mb-2">Remove Admin?</h6>
                                                            <p class="text-muted x-small mb-4">Are you sure you want to remove <strong><?php echo htmlspecialchars($admin['full_name']); ?></strong>?</p>
                                                            
                                                            <form method="POST">
                                                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                                <div class="d-flex gap-2 justify-content-center">
                                                                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="delete_admin" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm">Confirm Remove</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-purple-light text-purple x-small rounded-pill px-2">YOU</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Add Admin Modal -->
    <div class="modal fade" id="addQuickAdminModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Quick Register Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="users.php" method="POST">
                    <div class="modal-body py-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Full Administrative Name</label>
                            <input type="text" name="full_name" class="form-control rounded-3" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Access Username</label>
                                <input type="text" name="username" class="form-control rounded-3" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Access Password</label>
                                <input type="password" name="password" class="form-control rounded-3" required>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold text-muted text-uppercase">Administrative Email</label>
                            <input type="email" name="email" class="form-control rounded-3" required>
                            <input type="hidden" name="role" value="admin">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user" class="btn btn-purple rounded-pill px-4 shadow-sm">Register Admin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<style>
    .bg-purple-light { background-color: rgba(128, 0, 128, 0.08); }
    .text-purple { color: #800080; }
    .btn-purple { background-color: #800080; color: white; border: none; }
    .btn-purple:hover { background-color: #660066; color: white; }
    .stat-card { background: white; border-radius: 15px; transition: transform 0.3s ease; }
    .x-small { font-size: 0.65rem; letter-spacing: 0.5px; }
    .input-group-text { color: #800080; }
    .form-control:focus { border-color: #800080; box-shadow: 0 0 0 0.25rem rgba(128, 0, 128, 0.1); }
</style>

<?php include '../includes/footer.php'; ?>
