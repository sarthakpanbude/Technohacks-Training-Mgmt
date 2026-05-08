<?php
$role = $_SESSION['role'];
?>
<div class="sidebar">
    <div class="auth-logo mb-4 text-center mt-2">
        <img src="../assets/img/logo.png" alt="TechnoHacks Solutions" style="max-height: 80px; width: auto; max-width: 100%; object-fit: contain;">
    </div>
    
    <div class="sidebar-menu">
        <?php if ($role == 'admin'): ?>
            <a href="../admin/dashboard.php" class="sidebar-link <?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            
            <div class="sidebar-header">ADMISSION DETAILS</div>
            <a href="../admin/admit.php?new=1" class="sidebar-link <?php echo $activePage == 'visitors' ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i> New Admission
            </a>
            <a href="../admin/students.php" class="sidebar-link <?php echo $activePage == 'students' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i> Student Profile
            </a>

            <div class="sidebar-header">ACADEMIC MANAGEMENT</div>
            <a href="../admin/courses.php" class="sidebar-link <?php echo $activePage == 'courses' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> All Courses
            </a>
            <a href="../admin/batches.php" class="sidebar-link <?php echo $activePage == 'batches' ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> Active Batches
            </a>

            <div class="sidebar-header">FINANCE & ACCOUNT</div>
            <a href="../fees/installments.php" class="sidebar-link <?php echo $activePage == 'fees' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar"></i> Fees Management
            </a>
            <a href="../admin/reports.php" class="sidebar-link <?php echo $activePage == 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Reports and Analytics
            </a>

            <div class="sidebar-header">USER SETTINGS</div>
            <a href="../admin/settings.php" class="sidebar-link <?php echo $activePage == 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="../admin/profile.php" class="sidebar-link <?php echo $activePage == 'account' ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i> Account
            </a>

        <?php elseif ($role == 'teacher'): ?>
            <a href="../teacher/dashboard.php" class="sidebar-link <?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <div class="sidebar-header">CLASSROOM</div>
            <a href="../teacher/attendance.php" class="sidebar-link <?php echo $activePage == 'attendance' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Mark Attendance
            </a>
            <a href="../teacher/assignments.php" class="sidebar-link <?php echo $activePage == 'assignments' ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i> Assignments
            </a>
            <a href="../teacher/batches.php" class="sidebar-link <?php echo $activePage == 'batches' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> My Batches
            </a>

        <?php elseif ($role == 'student'): ?>
            <a href="../student/dashboard.php" class="sidebar-link <?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> My Portal
            </a>
            <div class="sidebar-header">EDUCATION</div>
            <a href="../student/learning.php" class="sidebar-link <?php echo $activePage == 'learning' ? 'active' : ''; ?>">
                <i class="fas fa-play-circle"></i> Learning Path
            </a>
            <a href="../student/assignments.php" class="sidebar-link <?php echo $activePage == 'assignments' ? 'active' : ''; ?>">
                <i class="fas fa-edit"></i> Assignments
            </a>
            <div class="sidebar-header">PERSONAL</div>
            <a href="../student/fees.php" class="sidebar-link <?php echo $activePage == 'fees' ? 'active' : ''; ?>">
                <i class="fas fa-wallet"></i> Fees & Receipts
            </a>
            <a href="../student/profile.php" class="sidebar-link <?php echo $activePage == 'profile' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Account
            </a>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer mt-auto pt-3">
        <hr class="text-muted opacity-25">
        <a href="../logout.php" class="sidebar-link text-danger fw-bold">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<style>
.sidebar-header {
    font-size: 0.65rem;
    font-weight: 800;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    margin: 25px 0 10px 15px;
}
</style>
