<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "User Management";
$activePage = "settings";

$success = "";
$error = "";

// Handle New User Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
        $error = "Please fill all fields.";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username already taken.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, full_name) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hashed_password, $role, $email, $full_name]);
                $success = "New user created successfully!";
            } catch (PDOException $e) {
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}

// Handle Role Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);
        $success = "User role updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating role: " . $e->getMessage();
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $user_id = $_POST['user_id'];
    $new_pass = $_POST['new_password'];
    
    try {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user_id]);
        $success = "User password changed successfully!";
    } catch (Exception $e) {
        $error = "Error changing password: " . $e->getMessage();
    }
}

// Handle User Deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    
    // Prevent self-deletion
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $success = "User account removed successfully!";
        } catch (Exception $e) {
            $error = "Error deleting user: " . $e->getMessage();
        }
    }
}

// Fetch all users (Admins and Teachers only, as students are managed in students.php)
$users = $pdo->query("SELECT * FROM users WHERE role IN ('admin', 'teacher') ORDER BY role ASC, full_name ASC")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Role Management</h2>
            <p class="text-muted small mb-0">Manage system users and their access levels</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus me-2"></i>Add New Admin
            </button>
            <a href="settings.php" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="fas fa-arrow-left me-2"></i>Back to Settings
            </a>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Register New Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body py-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Full Name</label>
                            <input type="text" name="full_name" class="form-control rounded-3" placeholder="e.g. John Doe" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Email Address</label>
                            <input type="email" name="email" class="form-control rounded-3" placeholder="admin@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Username</label>
                            <input type="text" name="username" class="form-control rounded-3" placeholder="Choose username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Password</label>
                            <input type="password" name="password" class="form-control rounded-3" placeholder="Create password" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold text-muted text-uppercase">Role</label>
                            <select name="role" class="form-select rounded-3">
                                <option value="admin">Administrator</option>
                                <option value="teacher">Teacher</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user" class="btn btn-primary rounded-pill px-4 shadow-sm">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="stat-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0 small text-uppercase">User</th>
                        <th class="border-0 small text-uppercase">Email</th>
                        <th class="border-0 small text-uppercase">Current Role</th>
                        <th class="border-0 small text-uppercase">Joined Date</th>
                        <th class="border-0 small text-uppercase text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-weight: bold;">
                                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                        <div class="text-muted x-small">@<?php echo htmlspecialchars($user['username']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge rounded-pill px-3 py-2 <?php 
                                    echo $user['role'] == 'admin' ? 'bg-danger bg-opacity-10 text-danger' : 
                                        ($user['role'] == 'teacher' ? 'bg-primary bg-opacity-10 text-primary' : 'bg-success bg-opacity-10 text-success'); 
                                ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light rounded-pill px-3 shadow-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-cog me-1"></i> Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 p-2">
                                            <li>
                                                <a class="dropdown-item rounded-3 mb-1" href="#" data-bs-toggle="modal" data-bs-target="#editRoleModal<?php echo $user['id']; ?>">
                                                    <i class="fas fa-user-tag me-2 text-primary"></i> Change Role
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item rounded-3 mb-1" href="#" data-bs-toggle="modal" data-bs-target="#changePassModal<?php echo $user['id']; ?>">
                                                    <i class="fas fa-key me-2 text-warning"></i> Reset Password
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle shadow-sm" style="width: 32px; height: 32px; padding: 0;" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?php echo $user['id']; ?>" title="Remove User">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle shadow-sm disabled" style="width: 32px; height: 32px; padding: 0;" title="Cannot delete yourself">
                                            <i class="fas fa-user-lock"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Role Modal -->
                                <div class="modal fade" id="editRoleModal<?php echo $user['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg rounded-4">
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="modal-title fw-bold">Update User Role</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body text-start py-4">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <p class="text-muted small mb-4">Change the access level for <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>.</p>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold text-muted text-uppercase">Select New Role</label>
                                                        <select name="role" class="form-select rounded-3">
                                                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                                            <option value="teacher" <?php echo $user['role'] == 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                                                            <option value="student" <?php echo $user['role'] == 'student' ? 'selected' : ''; ?>>Student</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-0 pt-0">
                                                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="update_role" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Password Modal -->
                                <div class="modal fade" id="changePassModal<?php echo $user['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg rounded-4">
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="modal-title fw-bold text-warning"><i class="fas fa-key me-2"></i>Reset Password</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body text-start py-4">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <p class="text-muted small mb-4">Set a new password for <strong><?php echo htmlspecialchars($user['full_name']); ?></strong> (@<?php echo htmlspecialchars($user['username']); ?>).</p>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold text-muted text-uppercase">New Password</label>
                                                        <input type="password" name="new_password" class="form-control rounded-3" placeholder="Enter new password" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-0 pt-0">
                                                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="change_password" class="btn btn-warning rounded-pill px-4 shadow-sm">Update Password</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteUserModal<?php echo $user['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered modal-sm">
                                        <div class="modal-content border-0 shadow-lg rounded-4">
                                            <div class="modal-body text-center py-4">
                                                <div class="text-danger mb-3">
                                                    <i class="fas fa-user-times fa-3x"></i>
                                                </div>
                                                <h5 class="fw-bold mb-2">Remove Account?</h5>
                                                <p class="text-muted small mb-4">Are you sure you want to delete <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>? This action cannot be undone.</p>
                                                
                                                <form method="POST">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="delete_user" class="btn btn-danger rounded-pill px-3 shadow-sm">Yes, Delete</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
