<?php
$role = $_SESSION['role'];
?>
<nav class="sidebar d-flex flex-column">

    <!-- Logo Area -->
    <div class="sidebar-logo">
        <img src="../assets/img/logo.png" alt="TechnoHacks Solutions" style="max-height:56px; width:auto; max-width:100%; object-fit:contain;">
    </div>

    <!-- Navigation -->
    <div class="sidebar-nav flex-grow-1">
        <?php if ($role == 'admin'): ?>

            <a href="../admin/dashboard.php" class="sidebar-link <?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-th-large"></i></span>
                <span>Dashboard</span>
            </a>

            <div class="sidebar-section-label">Admission</div>

            <a href="../admin/admit.php?new=1" class="sidebar-link <?php echo $activePage == 'visitors' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-user-plus"></i></span>
                <span>New Admission</span>
            </a>
            <a href="../admin/students.php" class="sidebar-link <?php echo $activePage == 'students' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-user-graduate"></i></span>
                <span>Student Profile</span>
            </a>
            <a href="../admin/referrals.php" class="sidebar-link <?php echo $activePage == 'referrals' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-share-alt"></i></span>
                <span>Referral &amp; Earn</span>
            </a>

            <div class="sidebar-section-label">Academic</div>

            <a href="../admin/courses.php" class="sidebar-link <?php echo $activePage == 'courses' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-book-open"></i></span>
                <span>All Courses</span>
            </a>

            <div class="sidebar-section-label">Finance</div>

            <a href="../fees/installments.php" class="sidebar-link <?php echo $activePage == 'fees' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                <span>Fees Management</span>
            </a>
            <a href="../admin/reports.php" class="sidebar-link <?php echo $activePage == 'reports' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-chart-bar"></i></span>
                <span>Reports &amp; Analytics</span>
            </a>

            <div class="sidebar-section-label">Settings</div>

            <a href="../admin/settings.php" class="sidebar-link <?php echo $activePage == 'settings' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-cog"></i></span>
                <span>Settings</span>
            </a>
            <a href="../admin/profile.php" class="sidebar-link <?php echo $activePage == 'account' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-user-circle"></i></span>
                <span>Account</span>
            </a>

        <?php elseif ($role == 'teacher'): ?>

            <a href="../teacher/dashboard.php" class="sidebar-link <?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-th-large"></i></span>
                <span>Dashboard</span>
            </a>
            <div class="sidebar-section-label">Classroom</div>
            <a href="../teacher/assignments.php" class="sidebar-link <?php echo $activePage == 'assignments' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-tasks"></i></span>
                <span>Assignments</span>
            </a>

        <?php elseif ($role == 'student'): ?>

            <a href="../student/dashboard.php" class="sidebar-link <?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-th-large"></i></span>
                <span>My Portal</span>
            </a>
            <div class="sidebar-section-label">Education</div>
            <a href="../student/learning.php" class="sidebar-link <?php echo $activePage == 'learning' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-play-circle"></i></span>
                <span>Learning Path</span>
            </a>
            <a href="../student/assignments.php" class="sidebar-link <?php echo $activePage == 'assignments' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-edit"></i></span>
                <span>Assignments</span>
            </a>
            <div class="sidebar-section-label">Personal</div>
            <a href="../student/fees.php" class="sidebar-link <?php echo $activePage == 'fees' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-wallet"></i></span>
                <span>Fees &amp; Receipts</span>
            </a>
            <a href="../student/profile.php" class="sidebar-link <?php echo $activePage == 'profile' ? 'active' : ''; ?>">
                <span class="sidebar-link-icon"><i class="fas fa-user"></i></span>
                <span>Account</span>
            </a>

        <?php endif; ?>
    </div>

    <!-- Logout -->
    <div class="sidebar-footer">
        <a href="../logout.php" class="sidebar-link sidebar-logout">
            <span class="sidebar-link-icon"><i class="fas fa-sign-out-alt"></i></span>
            <span>Logout</span>
        </a>
    </div>

</nav>

<style>
.sidebar-logo {
    padding: 1.25rem 1.25rem 1rem;
    border-bottom: 1px solid var(--border);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sidebar-nav {
    overflow-y: auto;
    padding: 0 0.75rem;
}
.sidebar-nav::-webkit-scrollbar { width: 3px; }
.sidebar-nav::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }

.sidebar-section-label {
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    padding: 1.25rem 0.75rem 0.4rem;
}

.sidebar-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0.7rem 0.9rem;
    color: var(--text-muted);
    text-decoration: none !important;
    border-radius: 10px;
    margin-bottom: 2px;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    position: relative;
}

.sidebar-link-icon {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    flex-shrink: 0;
    background: transparent;
    color: var(--text-muted);
}

.sidebar-link:hover {
    background: #f1f5f9;
    color: var(--primary);
}
.sidebar-link:hover .sidebar-link-icon {
    background: rgba(99,102,241,0.1);
    color: var(--primary);
}

.sidebar-link.active {
    background: linear-gradient(135deg, rgba(99,102,241,0.12), rgba(79,70,229,0.06));
    color: var(--primary);
    font-weight: 600;
}
.sidebar-link.active .sidebar-link-icon {
    background: var(--primary-gradient);
    color: white;
    box-shadow: 0 2px 8px rgba(99,102,241,0.3);
}
.sidebar-link.active::before {
    content: '';
    position: absolute;
    left: 0; top: 50%;
    transform: translateY(-50%);
    width: 3px; height: 60%;
    background: var(--primary);
    border-radius: 0 4px 4px 0;
}

.sidebar-footer {
    padding: 0.75rem;
    border-top: 1px solid var(--border);
    margin-top: 0.5rem;
}

.sidebar-logout {
    color: #ef4444 !important;
}
.sidebar-logout:hover {
    background: rgba(239,68,68,0.08) !important;
    color: #ef4444 !important;
}
.sidebar-logout .sidebar-link-icon {
    color: #ef4444 !important;
}
.sidebar-logout:hover .sidebar-link-icon {
    background: rgba(239,68,68,0.1) !important;
    color: #ef4444 !important;
}
</style>
