<?php
require_once '../includes/auth.php';
checkAuth('student');
require_once '../config/db.php';

$pageTitle = "Documents Locker";
$activePage = "documents";

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Documents Locker</h2>
        <button class="btn btn-primary"><i class="fas fa-cloud-upload-alt me-2"></i>Upload</button>
    </div>
    <div class="stat-card text-center py-5">
        <i class="fas fa-box-archive fa-3x text-muted mb-3"></i>
        <h5 class="fw-bold">Your Locker is Empty</h5>
        <p class="text-muted">Upload your Aadhar, PAN, or previous educational documents here safely.</p>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
