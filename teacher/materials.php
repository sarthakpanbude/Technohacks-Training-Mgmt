<?php
require_once '../includes/auth.php';
checkAuth('teacher');
require_once '../config/db.php';

$pageTitle = "Study Materials";
$activePage = "materials";

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Study Materials</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal"><i class="fas fa-upload me-2"></i>Upload Material</button>
    </div>

    <div class="stat-card text-center py-5">
        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
        <h5 class="fw-bold">No Materials Uploaded</h5>
        <p class="text-muted">Upload study materials for your batches.</p>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Upload Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Title</label>
                        <input type="text" class="form-control" placeholder="Material title">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">File</label>
                        <input type="file" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button class="btn btn-primary w-100">Upload</button>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
