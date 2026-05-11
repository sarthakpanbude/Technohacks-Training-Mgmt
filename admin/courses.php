<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Course Management";
$activePage = "courses";

// Handle Add Course
if (isset($_POST['add_course'])) {
    $course_type = $_POST['course_type'];
    $course_name = $_POST['course_name'];
    $desc        = $_POST['description'];
    $duration    = $_POST['duration'];
    $fees        = $_POST['fees'];
    $level       = $_POST['level'];

    $stmt = $pdo->prepare("INSERT INTO courses (course_type, course_name, description, duration, fees, level) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$course_type, $course_name, $desc, $duration, $fees, $level]);
    header("Location: courses.php?msg=Course Added Successfully");
    exit;
}

$courses     = $pdo->query("SELECT * FROM courses ORDER BY course_type ASC, id DESC")->fetchAll();
$internships = array_filter($courses, fn($c) => $c['course_type'] == 'Internship');
$trainings   = array_filter($courses, fn($c) => $c['course_type'] == 'Training');

$levelColors = [
    'Beginner'     => ['bg' => 'rgba(16,185,129,0.1)',  'color' => '#059669'],
    'Intermediate' => ['bg' => 'rgba(245,158,11,0.1)',  'color' => '#b45309'],
    'Advanced'     => ['bg' => 'rgba(239,68,68,0.1)',   'color' => '#dc2626'],
    'Full Course'  => ['bg' => 'rgba(99,102,241,0.1)',  'color' => '#4f46e5'],
    'Small Course' => ['bg' => 'rgba(14,165,233,0.1)',  'color' => '#0369a1'],
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <?php include '../includes/topbar.php'; ?>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert border-0 mb-4 d-flex align-items-center gap-2 rounded-3"
             style="background:rgba(16,185,129,0.1); color:#065f46; font-size:0.875rem;">
            <i class="fas fa-check-circle" style="color:var(--success);"></i>
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1">Course Management</h4>
            <p class="mb-0" style="font-size:0.85rem; color:var(--text-muted);">
                <span class="fw-semibold" style="color:var(--primary);"><?php echo count($courses); ?></span> total courses —
                <?php echo count($internships); ?> internships, <?php echo count($trainings); ?> trainings
            </p>
        </div>
        <button class="btn btn-primary rounded-pill px-4" style="font-size:0.875rem;" data-bs-toggle="modal" data-bs-target="#addCourseModal">
            <i class="fas fa-plus me-2"></i>Create Course
        </button>
    </div>

    <!-- Tab Nav -->
    <div class="d-flex gap-2 mb-4">
        <button class="tab-pill active" id="tab-internship" onclick="switchTab('internship')">
            <i class="fas fa-briefcase me-2"></i>Internship Programs
            <span class="tab-count"><?php echo count($internships); ?></span>
        </button>
        <button class="tab-pill" id="tab-training" onclick="switchTab('training')">
            <i class="fas fa-chalkboard-teacher me-2"></i>Training Courses
            <span class="tab-count"><?php echo count($trainings); ?></span>
        </button>
    </div>

    <!-- Internship Tab -->
    <div id="pane-internship" class="tab-pane-content">
        <div class="row g-4">
            <?php if (empty($internships)): ?>
                <div class="col-12">
                    <div class="stat-card text-center py-5">
                        <div style="width:56px;height:56px;border-radius:16px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                            <i class="fas fa-briefcase" style="font-size:1.4rem;color:#cbd5e1;"></i>
                        </div>
                        <div class="fw-semibold mb-1">No internship programs yet</div>
                        <div style="font-size:0.8rem;color:var(--text-muted);">Click "Create Course" to add one.</div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($internships as $c):
                    $lc = $levelColors[$c['level']] ?? ['bg'=>'rgba(99,102,241,0.1)','color'=>'#4f46e5'];
                ?>
                <div class="col-md-4">
                    <div class="stat-card course-card h-100" style="border-top:3px solid var(--primary);">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span style="font-size:0.72rem;font-weight:700;padding:4px 10px;border-radius:6px;background:<?php echo $lc['bg'];?>;color:<?php echo $lc['color'];?>;text-transform:uppercase;letter-spacing:0.05em;">
                                <?php echo htmlspecialchars($c['level']); ?>
                            </span>
                            <div class="dropdown">
                                <button class="btn p-0 border-0 text-muted" style="width:28px;height:28px;border-radius:8px;background:#f1f5f9;" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h" style="font-size:0.75rem;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow p-2" style="border-radius:12px;font-size:0.85rem;">
                                    <li><a class="dropdown-item rounded-2 py-2" href="#"><i class="fas fa-edit me-2 text-primary"></i>Edit</a></li>
                                    <li><a class="dropdown-item rounded-2 py-2 text-danger" href="#"><i class="fas fa-trash me-2"></i>Delete</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,rgba(99,102,241,0.15),rgba(79,70,229,0.06));display:flex;align-items:center;justify-content:center;margin-bottom:0.75rem;">
                                <i class="fas fa-briefcase" style="color:var(--primary);font-size:0.9rem;"></i>
                            </div>
                            <h6 class="fw-bold mb-1" style="font-size:0.95rem;"><?php echo htmlspecialchars($c['course_name']); ?></h6>
                            <p class="mb-0" style="font-size:0.8rem;color:var(--text-muted);line-height:1.5;">
                                <?php echo htmlspecialchars(substr($c['description'] ?? 'No description.', 0, 90)); ?>...
                            </p>
                        </div>
                        <div class="d-flex justify-content-between pt-3" style="border-top:1px solid var(--border);">
                            <div>
                                <div style="font-size:0.68rem;color:var(--text-light);text-transform:uppercase;font-weight:700;margin-bottom:2px;">Duration</div>
                                <div class="fw-bold" style="font-size:0.85rem;"><?php echo htmlspecialchars($c['duration'] ?? '–'); ?></div>
                            </div>
                            <div class="text-end">
                                <div style="font-size:0.68rem;color:var(--text-light);text-transform:uppercase;font-weight:700;margin-bottom:2px;">Fees</div>
                                <div class="fw-bold" style="font-size:0.85rem;color:var(--success);">₹<?php echo number_format($c['fees'], 0); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Training Tab -->
    <div id="pane-training" class="tab-pane-content" style="display:none;">
        <div class="row g-4">
            <?php if (empty($trainings)): ?>
                <div class="col-12">
                    <div class="stat-card text-center py-5">
                        <div style="width:56px;height:56px;border-radius:16px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                            <i class="fas fa-chalkboard-teacher" style="font-size:1.4rem;color:#cbd5e1;"></i>
                        </div>
                        <div class="fw-bold mb-1">No training courses yet</div>
                        <div style="font-size:0.8rem;color:var(--text-muted);">Click "Create Course" to add one.</div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($trainings as $c):
                    $lc = $levelColors[$c['level']] ?? ['bg'=>'rgba(16,185,129,0.1)','color'=>'#059669'];
                ?>
                <div class="col-md-4">
                    <div class="stat-card course-card h-100" style="border-top:3px solid var(--success);">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span style="font-size:0.72rem;font-weight:700;padding:4px 10px;border-radius:6px;background:<?php echo $lc['bg'];?>;color:<?php echo $lc['color'];?>;text-transform:uppercase;letter-spacing:0.05em;">
                                <?php echo htmlspecialchars($c['level']); ?>
                            </span>
                            <div class="dropdown">
                                <button class="btn p-0 border-0 text-muted" style="width:28px;height:28px;border-radius:8px;background:#f1f5f9;" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h" style="font-size:0.75rem;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow p-2" style="border-radius:12px;font-size:0.85rem;">
                                    <li><a class="dropdown-item rounded-2 py-2" href="#"><i class="fas fa-edit me-2 text-primary"></i>Edit</a></li>
                                    <li><a class="dropdown-item rounded-2 py-2 text-danger" href="#"><i class="fas fa-trash me-2"></i>Delete</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,rgba(16,185,129,0.15),rgba(16,185,129,0.06));display:flex;align-items:center;justify-content:center;margin-bottom:0.75rem;">
                                <i class="fas fa-chalkboard-teacher" style="color:var(--success);font-size:0.9rem;"></i>
                            </div>
                            <h6 class="fw-bold mb-1" style="font-size:0.95rem;"><?php echo htmlspecialchars($c['course_name']); ?></h6>
                            <p class="mb-0" style="font-size:0.8rem;color:var(--text-muted);line-height:1.5;">
                                <?php echo htmlspecialchars(substr($c['description'] ?? 'No description.', 0, 90)); ?>...
                            </p>
                        </div>
                        <div class="d-flex justify-content-between pt-3" style="border-top:1px solid var(--border);">
                            <div>
                                <div style="font-size:0.68rem;color:var(--text-light);text-transform:uppercase;font-weight:700;margin-bottom:2px;">Duration</div>
                                <div class="fw-bold" style="font-size:0.85rem;"><?php echo htmlspecialchars($c['duration'] ?? '–'); ?></div>
                            </div>
                            <div class="text-end">
                                <div style="font-size:0.68rem;color:var(--text-light);text-transform:uppercase;font-weight:700;margin-bottom:2px;">Fees</div>
                                <div class="fw-bold" style="font-size:0.85rem;color:var(--success);">₹<?php echo number_format($c['fees'], 0); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Create New Course</h5>
                        <p class="text-muted mb-0" style="font-size:0.8rem;">Fill in the details below</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.8rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Course Name</label>
                            <input type="text" name="course_name" class="form-control" placeholder="e.g. Full Stack Web Development" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:0.8rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Brief overview of the course..."></textarea>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label" style="font-size:0.8rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Duration</label>
                                <input type="text" name="duration" class="form-control" placeholder="e.g. 3 Months" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label" style="font-size:0.8rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Fees (INR)</label>
                                <input type="number" name="fees" class="form-control" placeholder="e.g. 15000" required>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label" style="font-size:0.8rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Course Type</label>
                                <select name="course_type" class="form-select">
                                    <option value="Internship">Internship Program</option>
                                    <option value="Training">Training Course</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label" style="font-size:0.8rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Level</label>
                                <select name="level" class="form-select">
                                    <option value="Beginner">Beginner</option>
                                    <option value="Intermediate">Intermediate</option>
                                    <option value="Advanced">Advanced</option>
                                    <option value="Full Course">Full Course</option>
                                    <option value="Small Course">Small Course</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn rounded-pill px-4" style="border:1px solid var(--border);color:var(--text-muted);" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_course" class="btn btn-primary rounded-pill px-5">
                            <i class="fas fa-plus me-2"></i>Create Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<style>
.tab-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0.5rem 1.25rem;
    border-radius: 50px;
    border: 1px solid var(--border);
    background: white;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s;
}
.tab-pill:hover { background: #f8fafc; color: var(--primary); border-color: var(--primary); }
.tab-pill.active { background: var(--primary); color: white; border-color: var(--primary); box-shadow: 0 4px 12px rgba(99,102,241,0.25); }
.tab-pill.active .tab-count { background: rgba(255,255,255,0.25); color: white; }
.tab-count { font-size: 0.7rem; font-weight: 700; background: #f1f5f9; color: var(--text-muted); padding: 2px 7px; border-radius: 20px; margin-left: 4px; }
.course-card { transition: all 0.25s ease; }
.course-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
</style>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-pane-content').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.tab-pill').forEach(b => b.classList.remove('active'));
    document.getElementById('pane-' + tab).style.display = 'block';
    document.getElementById('tab-' + tab).classList.add('active');
}
</script>

<?php include '../includes/footer.php'; ?>
