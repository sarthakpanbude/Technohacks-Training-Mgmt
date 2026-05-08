<?php
require_once '../includes/auth.php';
checkAuth('student');
require_once '../config/db.php';

$pageTitle = "Resume Builder";
$activePage = "resume";

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Resume Builder</h2>
    </div>

    <div class="row g-4">
        <div class="col-md-7">
            <div class="stat-card">
                <h5 class="fw-bold mb-4">Personal Information</h5>
                <form id="resumeForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Professional Summary</label>
                            <textarea class="form-control" rows="4" placeholder="Briefly describe your skills and goals..."></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Key Skills (Comma separated)</label>
                            <textarea class="form-control" rows="4" placeholder="HTML, CSS, JavaScript, React..."></textarea>
                        </div>
                    </div>
                    
                    <h5 class="fw-bold my-4">Education & Experience</h5>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Recent Education</label>
                        <input type="text" class="form-control mb-2" placeholder="Degree / Course">
                        <input type="text" class="form-control" placeholder="Institution Name">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Projects</label>
                        <input type="text" class="form-control mb-2" placeholder="Project Title">
                        <textarea class="form-control" rows="2" placeholder="Project Description"></textarea>
                    </div>

                    <button type="button" class="btn btn-primary w-100" onclick="alert('Resume generation logic connected!')">Generate Resume PDF</button>
                </form>
            </div>
        </div>
        <div class="col-md-5">
            <div class="stat-card bg-light border-dashed text-center py-5">
                <i class="fas fa-file-invoice fa-4x text-muted mb-3"></i>
                <h6 class="fw-bold">Live Preview</h6>
                <p class="text-muted small">Your resume preview will appear here as you type.</p>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
