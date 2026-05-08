<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "System Settings";
$activePage = "settings";

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <h2 class="fw-bold mb-4">Settings</h2>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="stat-card">
                <h5 class="fw-bold mb-4">Institute Details</h5>
                <form>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Institute Name</label>
                        <input type="text" class="form-control" value="TechnoHacks Solutions">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Contact Email</label>
                        <input type="email" class="form-control" value="info@technohacks.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Institute Logo</label>
                        <input type="file" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Settings</button>
                </form>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card">
                <h5 class="fw-bold mb-4">System Utilities</h5>
                <div class="d-grid gap-3">
                    <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold">Database Backup</h6>
                            <p class="text-muted small mb-0">Last backup: 2 days ago</p>
                        </div>
                        <button class="btn btn-dark btn-sm rounded-pill px-3">Backup Now</button>
                    </div>
                    <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold">Role Management</h6>
                            <p class="text-muted small mb-0">Configure permissions</p>
                        </div>
                        <button class="btn btn-outline-primary btn-sm rounded-pill px-3">Manage</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
