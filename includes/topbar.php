<?php
// Topbar Component
$fullName = $_SESSION['full_name'] ?? 'User';
$firstLetter = strtoupper(substr($fullName, 0, 1));
$role = ucfirst($_SESSION['role'] ?? 'User');
?>
<header class="topbar d-flex justify-content-between align-items-center animate-fade-in mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="font-size:1.3rem; color:var(--text-main);"><?php echo $pageTitle ?? 'Dashboard'; ?></h4>
        <p class="mb-0" style="font-size:0.8rem; color:var(--text-muted);">TechnoHacks Training Management System</p>
    </div>
    <div class="d-flex align-items-center gap-3">

        <!-- Notification Bell -->
        <div class="dropdown">
            <button class="topbar-icon-btn position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                <i class="fas fa-bell" style="font-size:1rem;"></i>
                <span class="notif-dot"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2 mt-2" style="width:300px; border-radius:16px;">
                <li>
                    <div class="d-flex align-items-center justify-content-between px-3 py-2">
                        <span class="fw-bold" style="font-size:0.9rem;">Notifications</span>
                        <span class="badge rounded-pill" style="background:var(--primary-gradient); font-size:0.65rem;">3 New</span>
                    </div>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item rounded-3 py-2 px-3 mb-1" href="#" style="font-size:0.85rem;">
                        <div class="d-flex align-items-start gap-2">
                            <span class="notif-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-user-plus"></i></span>
                            <div><div class="fw-semibold" style="font-size:0.8rem;">New Admission</div><div class="text-muted" style="font-size:0.72rem;">Rahul Kumar enrolled</div></div>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item rounded-3 py-2 px-3 mb-1" href="#" style="font-size:0.85rem;">
                        <div class="d-flex align-items-start gap-2">
                            <span class="notif-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></span>
                            <div><div class="fw-semibold" style="font-size:0.8rem;">Fee Due</div><div class="text-muted" style="font-size:0.72rem;">Batch B102 payment pending</div></div>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item rounded-3 py-2 px-3" href="#" style="font-size:0.85rem;">
                        <div class="d-flex align-items-start gap-2">
                            <span class="notif-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle"></i></span>
                            <div><div class="fw-semibold" style="font-size:0.8rem;">Referral Approved</div><div class="text-muted" style="font-size:0.72rem;">Bonus ₹500 cleared</div></div>
                        </div>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Divider -->
        <div style="width:1px; height:28px; background:var(--border);"></div>

        <!-- Profile -->
        <div class="dropdown">
            <button class="btn p-0 border-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="profile-avatar-circle"><?php echo $firstLetter; ?></div>
                <div class="text-start d-none d-md-block">
                    <div class="fw-bold" style="font-size:0.85rem; line-height:1.2; color:var(--text-main);"><?php echo htmlspecialchars($fullName); ?></div>
                    <div style="font-size:0.72rem; color:var(--text-muted);"><?php echo $role; ?></div>
                </div>
                <i class="fas fa-chevron-down ms-1" style="font-size:0.65rem; color:var(--text-muted);"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2 mt-2" style="width:200px; border-radius:16px;">
                <li><h6 class="dropdown-header" style="font-size:0.7rem;">ACCOUNT</h6></li>
                <li><a class="dropdown-item rounded-3 py-2" href="profile.php" style="font-size:0.85rem;"><i class="fas fa-user-circle me-2 text-primary" style="width:16px;"></i>My Profile</a></li>
                <li><a class="dropdown-item rounded-3 py-2" href="settings.php" style="font-size:0.85rem;"><i class="fas fa-cog me-2 text-muted" style="width:16px;"></i>Settings</a></li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item rounded-3 py-2 text-danger" href="../logout.php" style="font-size:0.85rem;"><i class="fas fa-sign-out-alt me-2" style="width:16px;"></i>Logout</a></li>
            </ul>
        </div>

    </div>
</header>

<style>
.topbar-icon-btn {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: white;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}
.topbar-icon-btn:hover { background: var(--bg-main); color: var(--primary); border-color: var(--primary); }
.notif-dot {
    position: absolute;
    top: 6px; right: 6px;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--accent);
    border: 2px solid white;
}
.notif-icon {
    width: 30px; height: 30px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem;
    flex-shrink: 0;
}
.dropdown-item:hover { background: #f8fafc; }
</style>
