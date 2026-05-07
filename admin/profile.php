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
