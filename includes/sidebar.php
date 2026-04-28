<?php
$role = $_SESSION['role'];
?>
<div class="sidebar">
    <div class="auth-logo mb-4 text-center mt-2">
        <img src="../assets/img/logo.png" alt="TechnoHacks Solutions" style="max-height: 80px; width: auto; max-width: 100%; object-fit: contain;">
    </div>
    
    <div class="sidebar-menu">
        <?php if ($role == 'admin'): ?>
            <a href="dashboard.php" class="sidebar-link <?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            
            <div class="sidebar-header small text-muted text-uppercase fw-bold mt-4 mb-2 px-3" style="font-size: 0.7rem; letter-spacing: 1px;">Admissions & Leads</div>
            <a href="visitors.php" class="sidebar-link <?php echo $activePage == 'visitors' ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i> New Inquiries
            </a>
            <a href="admissions.php" class="sidebar-link <?php echo $activePage == 'admissions' ? 'active' : ''; ?>">
                <i class="fas fa-check-double"></i> Enrollment Review
            </a>

            <div class="sidebar-header small text-muted text-uppercase fw-bold mt-4 mb-2 px-3" style="font-size: 0.7rem; letter-spacing: 1px;">Academic Mgmt</div>
            <a href="students.php" class="sidebar-link <?php echo $activePage == 'students' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i> Active Students
            </a>
            <a href="courses.php" class="sidebar-link <?php echo $activePage == 'courses' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> Course Catalog
            </a>
            <a href="batches.php" class="sidebar-link <?php echo $activePage == 'batches' ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> Batch Schedules
            </a>
<<<<<<< HEAD

            <div class="sidebar-header small text-muted text-uppercase fw-bold mt-4 mb-2 px-3" style="font-size: 0.7rem; letter-spacing: 1px;">Operations</div>
            <a href="fees.php" class="sidebar-link <?php echo $activePage == 'fees' ? 'active' : ''; ?>">
=======
            <a href="../fees/installments.php" class="sidebar-link <?php echo $activePage == 'fees' ? 'active' : ''; ?>">
>>>>>>> 7e34921 (my changes)
                <i class="fas fa-file-invoice-dollar"></i> Fees & Billing
            </a>
            <a href="attendance.php" class="sidebar-link <?php echo $activePage == 'attendance' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Attendance Tracking
            </a>
            <a href="reports.php" class="sidebar-link <?php echo $activePage == 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Performance Reports
            </a>
            <a href="settings.php" class="sidebar-link <?php echo $activePage == 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> System Settings
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
