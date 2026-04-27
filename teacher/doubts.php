<?php
require_once '../includes/auth.php';
checkAuth('teacher');
require_once '../config/db.php';

$pageTitle = "Student Doubts";
$activePage = "doubts";

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <h2 class="fw-bold mb-4">Student Doubts</h2>

    <div class="stat-card text-center py-5">
        <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
        <h5 class="fw-bold">No Doubts Raised</h5>
        <p class="text-muted">All student queries will appear here.</p>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
