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
            
            <div class="sidebar-header small text-muted text-uppercase fw-bold mt-4 mb-2 px-3" style="font-size: 0.7rem; letter-spacing: 1px;">Admissions & Leads</div>
            <a href="../admin/visitors.php" class="sidebar-link <?php echo $activePage == 'visitors' ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i> New Inquiries
            </a>
            <a href="../admin/admissions.php" class="sidebar-link <?php echo $activePage == 'admissions' ? 'active' : ''; ?>">
                <i class="fas fa-check-double"></i> Enrollment Review
            </a>

            <div class="sidebar-header small text-muted text-uppercase fw-bold mt-4 mb-2 px-3" style="font-size: 0.7rem; letter-spacing: 1px;">Academic Management</div>
            <a href="../admin/students.php" class="sidebar-link <?php echo $activePage == 'students' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i> Active Students
            </a>
            <a href="../admin/courses.php" class="sidebar-link <?php echo $activePage == 'courses' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> All Courses
            </a>
            <a href="../admin/batches.php" class="sidebar-link <?php echo $activePage == 'batches' ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> Batch Schedules
            </a>
            <div class="sidebar-header small text-muted text-uppercase fw-bold mt-4 mb-2 px-3" style="font-size: 0.7rem; letter-spacing: 1px;">Operations</div>
            <a href="../fees/installments.php" class="sidebar-link <?php echo $activePage == 'fees' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar"></i> Fees & Billing
            </a>
            <a href="../admin/attendance.php" class="sidebar-link <?php echo $activePage == 'attendance' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Attendance Tracking
            </a>
            <a href="../admin/reports.php" class="sidebar-link <?php echo $activePage == 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Performance Reports
            </a>
            <a href="../admin/settings.php" class="sidebar-link <?php echo $activePage == 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> System Settings
            </a>

        <?php elseif ($role == 'teacher'): ?>
            <a href="../teacher/dashboard.php" class="sidebar-link <?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
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
            <a href="../student/learning.php" class="sidebar-link <?php echo $activePage == 'learning' ? 'active' : ''; ?>">
                <i class="fas fa-play-circle"></i> Learning Path
            </a>
            <a href="../student/assignments.php" class="sidebar-link <?php echo $activePage == 'assignments' ? 'active' : ''; ?>">
                <i class="fas fa-edit"></i> Assignments
            </a>
            <a href="../student/fees.php" class="sidebar-link <?php echo $activePage == 'fees' ? 'active' : ''; ?>">
                <i class="fas fa-wallet"></i> Fees & Receipts
            </a>
            <a href="../student/certificates.php" class="sidebar-link <?php echo $activePage == 'certificates' ? 'active' : ''; ?>">
                <i class="fas fa-award"></i> My Certificates
            </a>
            <a href="../student/resume.php" class="sidebar-link <?php echo $activePage == 'resume' ? 'active' : ''; ?>">
                <i class="fas fa-file-pdf"></i> Resume Builder
            </a>
            <a href="../student/profile.php" class="sidebar-link <?php echo $activePage == 'profile' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> My Profile
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
