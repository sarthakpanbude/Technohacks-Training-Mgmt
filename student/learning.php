<?php
require_once '../includes/auth.php';
checkAuth('student');
require_once '../config/db.php';

$pageTitle = "Learning Path";
$activePage = "learning";

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <h2 class="fw-bold mb-4">My Learning Path</h2>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="stat-card border-primary">
                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-primary rounded-pill">Module 1</span>
                    <i class="fas fa-check-circle text-success"></i>
                </div>
                <h6 class="fw-bold">Web Foundations</h6>
                <p class="text-muted small">HTML5 Semantic structure and CSS3 Flexbox/Grid.</p>
                <div class="progress mt-3" style="height: 4px;">
                    <div class="progress-bar bg-success" style="width: 100%;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card border-primary">
                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-primary rounded-pill">Module 2</span>
                    <i class="fas fa-check-circle text-success"></i>
                </div>
                <h6 class="fw-bold">JavaScript Essentials</h6>
                <p class="text-muted small">ES6+ syntax, Async/Await, and DOM manipulation.</p>
                <div class="progress mt-3" style="height: 4px;">
                    <div class="progress-bar bg-success" style="width: 100%;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-secondary rounded-pill">Module 3</span>
                    <i class="fas fa-play-circle text-primary"></i>
                </div>
                <h6 class="fw-bold">React Development</h6>
                <p class="text-muted small">Hooks, State Management, and Component Architecture.</p>
                <div class="progress mt-3" style="height: 4px;">
                    <div class="progress-bar bg-primary" style="width: 40%;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 opacity-50">
            <div class="stat-card">
                <div class="d-flex justify-content-between mb-3">
                    <span class="badge bg-dark rounded-pill">Module 4</span>
                    <i class="fas fa-lock text-muted"></i>
                </div>
                <h6 class="fw-bold">Backend with Node.js</h6>
                <p class="text-muted small">Express, MongoDB, and API Design.</p>
                <div class="progress mt-3" style="height: 4px;">
                    <div class="progress-bar bg-secondary" style="width: 0%;"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
