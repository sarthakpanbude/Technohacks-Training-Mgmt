<?php
require_once '../includes/auth.php';
checkAuth('student');
require_once '../config/db.php';

$pageTitle = "My Certificates";
$activePage = "certificates";

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <h2 class="fw-bold mb-4">My Certificates</h2>
    <div class="stat-card text-center py-5">
        <i class="fas fa-award fa-3x text-warning mb-3"></i>
        <h5 class="fw-bold">No Certificates Yet</h5>
        <p class="text-muted">Complete your courses and exams to earn certificates!</p>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
