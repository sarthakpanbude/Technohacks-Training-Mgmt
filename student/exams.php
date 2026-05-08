<?php
require_once '../includes/auth.php';
checkAuth('student');
require_once '../config/db.php';

$pageTitle = "My Exams";
$activePage = "exams";

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <h2 class="fw-bold mb-4">Upcoming Exams</h2>
    <div class="stat-card text-center py-5">
        <i class="fas fa-pen-nib fa-3x text-muted mb-3"></i>
        <h5 class="fw-bold">No Exams Scheduled</h5>
        <p class="text-muted">You have no upcoming exams or quizzes at the moment.</p>
    </div>
</main>
<?php include '../includes/footer.php'; ?>
