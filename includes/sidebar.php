<?php
$role = $_SESSION['role'];
?>
<div class="sidebar">
    <div class="auth-logo mb-4 text-start">
        <i class="fas fa-microchip me-2"></i>TechnoHacks
    </div>
    
    <div class="sidebar-menu">
        <?php if ($role == 'admin'): ?>
            <a href="dashboard.php" class="sidebar-link <?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="students.php" class="sidebar-link <?php echo $activePage == 'students' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a href="courses.php" class="sidebar-link <?php echo $activePage == 'courses' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> Courses
            </a>
            <a href="batches.php" class="sidebar-link <?php echo $activePage == 'batches' ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> Batches
            </a>
            <a href="fees.php" class="sidebar-link <?php echo $activePage == 'fees' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar"></i> Fees & Billing
            </a>
            <a href="attendance.php" class="sidebar-link <?php echo $activePage == 'attendance' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a href="reports.php" class="sidebar-link <?php echo $activePage == 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a href="tickets.php" class="sidebar-link <?php echo $activePage == 'tickets' ? 'active' : ''; ?>">
                <i class="fas fa-ticket-alt"></i> Tickets
            </a>
            <a href="settings.php" class="sidebar-link <?php echo $activePage == 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
        <?php elseif ($role == 'teacher'): ?>
            <a href="dashboard.php" class="sidebar-link <?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="batches.php" class="sidebar-link <?php echo $activePage == 'batches' ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> My Batches
            </a>
            <a href="attendance.php" class="sidebar-link <?php echo $activePage == 'attendance' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a href="assignments.php" class="sidebar-link <?php echo $activePage == 'assignments' ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i> Assignments
            </a>
            <a href="materials.php" class="sidebar-link <?php echo $activePage == 'materials' ? 'active' : ''; ?>">
                <i class="fas fa-folder-open"></i> Study Materials
            </a>
            <a href="doubts.php" class="sidebar-link <?php echo $activePage == 'doubts' ? 'active' : ''; ?>">
                <i class="fas fa-question-circle"></i> Doubts
            </a>
        <?php elseif ($role == 'student'): ?>
            <a href="dashboard.php" class="sidebar-link <?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            <a href="learning.php" class="sidebar-link <?php echo $activePage == 'learning' ? 'active' : ''; ?>">
                <i class="fas fa-graduation-cap"></i> Learning Path
            </a>
            <a href="assignments.php" class="sidebar-link <?php echo $activePage == 'assignments' ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i> Assignments
            </a>
            <a href="exams.php" class="sidebar-link <?php echo $activePage == 'exams' ? 'active' : ''; ?>">
                <i class="fas fa-pen-nib"></i> Exams
            </a>
            <a href="fees.php" class="sidebar-link <?php echo $activePage == 'fees' ? 'active' : ''; ?>">
                <i class="fas fa-wallet"></i> My Fees
            </a>
            <a href="documents.php" class="sidebar-link <?php echo $activePage == 'documents' ? 'active' : ''; ?>">
                <i class="fas fa-box-archive"></i> Doc Locker
            </a>
            <a href="resume.php" class="sidebar-link <?php echo $activePage == 'resume' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i> Resume Builder
            </a>
            <a href="certificates.php" class="sidebar-link <?php echo $activePage == 'certificates' ? 'active' : ''; ?>">
                <i class="fas fa-award"></i> Certificates
            </a>
        <?php endif; ?>
        
        <hr class="text-muted opacity-25">
        <a href="../logout.php" class="sidebar-link text-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>
