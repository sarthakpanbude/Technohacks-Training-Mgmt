<?php
// Topbar Component
$fullName = $_SESSION['full_name'] ?? 'User';
$firstLetter = strtoupper(substr($fullName, 0, 1));
?>
<header class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded-4 shadow-sm border animate-fade-in">
    <div>
        <h4 class="fw-bold mb-0"><?php echo $pageTitle ?? 'Dashboard'; ?></h4>
        <p class="text-muted small mb-0">TechnoHacks Training Management System</p>
    </div>
    <div class="d-flex align-items-center">
        <!-- Notifications -->
        <div class="dropdown me-3">
            <button class="btn btn-white border rounded-circle p-2 position-relative" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-bell text-muted"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" style="padding: 0.35em 0.5em;">3</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2" style="width: 300px;">
                <li><h6 class="dropdown-header">Notifications</h6></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item rounded mb-1" href="#"><i class="fas fa-user-plus me-2 text-primary"></i> New admission: Rahul Kumar</a></li>
                <li><a class="dropdown-item rounded mb-1" href="#"><i class="fas fa-clock me-2 text-warning"></i> Fee due for Batch B102</a></li>
            </ul>
        </div>

        <!-- Profile Dropdown -->
        <div class="dropdown">
            <button class="btn p-0 border-0 d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                <div class="profile-avatar-circle me-2">
                    <?php echo $firstLetter; ?>
                </div>
                <div class="text-start d-none d-md-block">
                    <div class="fw-bold small" style="line-height: 1;"><?php echo $fullName; ?></div>
                    <div class="text-muted" style="font-size: 0.75rem;"><?php echo ucfirst($_SESSION['role']); ?></div>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2 mt-2">
                <li><h6 class="dropdown-header">Account Settings</h6></li>
                <li><a class="dropdown-item rounded mb-1" href="profile.php"><i class="fas fa-user-circle me-2"></i>My Profile</a></li>
                <li><a class="dropdown-item rounded mb-1" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item rounded mb-1 text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</header>
