<?php
require_once '../includes/auth.php';
checkAuth('teacher');
require_once '../config/db.php';

$pageTitle = "Study Materials";
$activePage = "materials";

$teacher = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$teacher->execute([$_SESSION['user_id']]);
$teacher_id = $teacher->fetchColumn();

// Handle File Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_material'])) {
    $title = $_POST['title'];
    $batch_id = $_POST['batch_id'];
    $description = $_POST['description'];

    // Handle File
    $file = $_FILES['material_file'];
    if ($file['error'] == 0) {
        $allowed_ext = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'rar'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed_ext)) {
            $new_name = time() . '_' . rand(1000, 9999) . '.' . $ext;
            $destination = '../uploads/materials/' . $new_name;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $stmt = $pdo->prepare("INSERT INTO study_materials (batch_id, teacher_id, title, description, file_path) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$batch_id, $teacher_id, $title, $description, $new_name]);
                $msg = "Material uploaded successfully!";
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Invalid file format. Allowed: " . implode(', ', $allowed_ext);
        }
    } else {
        $error = "File upload error.";
    }
}

// Fetch Teacher's Batches for Dropdown
$batches = $pdo->prepare("SELECT id, batch_name FROM batches WHERE teacher_id = ? AND status = 'active'");
$batches->execute([$teacher_id]);
$batches = $batches->fetchAll();

// Fetch Uploaded Materials
$materials = $pdo->prepare("SELECT m.*, b.batch_name FROM study_materials m JOIN batches b ON m.batch_id = b.id WHERE m.teacher_id = ? ORDER BY m.uploaded_at DESC");
$materials->execute([$teacher_id]);
$materials = $materials->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Study Materials</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-upload me-2"></i>Upload Material
        </button>
    </div>

    <?php if (isset($msg)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $msg; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if (empty($materials)): ?>
            <div class="col-12">
                <div class="stat-card text-center py-5">
                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                    <h5 class="fw-bold">No Materials Uploaded</h5>
                    <p class="text-muted">Upload study materials for your batches to see them here.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($materials as $m): ?>
            <div class="col-md-4">
                <div class="stat-card h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill"><?php echo $m['batch_name']; ?></span>
                        <a href="../uploads/materials/<?php echo $m['file_path']; ?>" class="text-muted" target="_blank" download><i class="fas fa-download"></i></a>
                    </div>
                    <h5 class="fw-bold text-truncate" title="<?php echo htmlspecialchars($m['title']); ?>">
                        <i class="fas fa-file-alt text-muted me-2"></i><?php echo htmlspecialchars($m['title']); ?>
                    </h5>
                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($m['description'] ?? ''); ?></p>
                    <div class="mt-auto border-top pt-3 d-flex justify-content-between small text-muted">
                        <span><i class="fas fa-clock me-1"></i><?php echo date('d M Y', strtotime($m['uploaded_at'])); ?></span>
                        <span class="text-uppercase"><?php echo pathinfo($m['file_path'], PATHINFO_EXTENSION); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Upload Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Title *</label>
                        <input type="text" name="title" class="form-control" placeholder="Material title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Target Batch *</label>
                        <select name="batch_id" class="form-select" required>
                            <option value="">-- Select Batch --</option>
                            <?php foreach ($batches as $b): ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo $b['batch_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">File (PDF, PPT, DOC, ZIP) *</label>
                        <input type="file" name="material_file" class="form-control" required accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.rar">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Optional notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" name="upload_material" class="btn btn-primary w-100 fw-bold">Upload</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
