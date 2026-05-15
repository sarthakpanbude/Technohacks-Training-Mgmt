<?php
require_once '../includes/auth.php';
checkAuth('student');
require_once '../config/db.php';

$pageTitle = "My Profile";
$activePage = "profile";

$userId = $_SESSION['user_id'];
$user = $pdo->prepare("SELECT u.*, s.enrollment_no, s.dob, s.phone, s.address, s.admission_status FROM users u JOIN students s ON u.id = s.user_id WHERE u.id = ?");
$user->execute([$userId]);
$userData = $user->fetch();

// Fetch ALL enrollments for this user
$enrollments = $pdo->prepare("SELECT * FROM students WHERE user_id = ? ORDER BY created_at DESC");
$enrollments->execute([$userId]);
$enrollments_list = $enrollments->fetchAll();

// Fetch Referral System Status
$sys_settings = $pdo->query("SELECT referral_system_enabled FROM settings WHERE id=1")->fetch();
$referral_enabled = $sys_settings['referral_system_enabled'] ?? 1;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $phone = $_POST['phone'];
    $dob = $_POST['dob'];
    $address = $_POST['address'];

    $stmt = $pdo->prepare("UPDATE students SET phone = ?, dob = ?, address = ? WHERE user_id = ?");
    $stmt->execute([$phone, $dob, $address, $userId]);
    
    header("Location: profile.php?msg=Profile Updated Successfully");
    exit;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">My Profile</h2>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="stat-card text-center h-100">
                <img src="../assets/img/default.png" alt="Profile" class="rounded-circle border mb-3" width="120" height="120">
                <h4 class="fw-bold"><?php echo htmlspecialchars($userData['full_name']); ?></h4>
                <p class="text-muted mb-1"><?php echo htmlspecialchars($userData['email']); ?></p>
                <span class="badge bg-primary rounded-pill mb-3">Student ID: <?php echo $enrollments_list[0]['permanent_id'] ?? 'Pending'; ?></span>
                
                <hr>
                
                <div class="text-start">
                    <p class="small mb-1"><i class="fas fa-calendar-check text-muted me-2"></i>Joined: <?php echo date('M d, Y', strtotime($userData['created_at'])); ?></p>
                    <p class="small mb-1"><i class="fas fa-info-circle text-muted me-2"></i>Status: <span class="text-capitalize text-success fw-bold"><?php echo $userData['admission_status']; ?></span></p>
                </div>

                <hr>

                <!-- Referral & Earn Section -->
                <?php if ($referral_enabled): ?>
                <div class="referral-section text-start p-3 bg-light rounded-4">
                    <h6 class="fw-bold mb-2 small"><i class="fas fa-gift text-primary me-2"></i>Referral & Earn</h6>
                    <div class="mb-3">
                        <label class="x-small text-muted fw-bold text-uppercase mb-1" style="font-size: 0.6rem;">Your Referral Code (Student ID)</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="refCode" class="form-control fw-bold text-center bg-white" value="<?php echo $enrollments_list[0]['permanent_id'] ?? 'N/A'; ?>" readonly>
                            <button class="btn btn-primary" onclick="copyRefCode()"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
<?php
$perm_id = $enrollments_list[0]['permanent_id'];
$bonusStats = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN status = 'Approved' THEN bonus_amount ELSE 0 END) as earned,
        SUM(CASE WHEN status IN ('Pending', 'Pending Full Payment', 'Waiting Refund Period') THEN bonus_amount ELSE 0 END) as pending
    FROM referral_bonuses 
    WHERE referrer_id = ?
");
$bonusStats->execute([$perm_id]);
$bStats = $bonusStats->fetch();
?>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="x-small text-muted d-block" style="font-size: 0.6rem;">Earned</span>
                            <span class="fw-bold text-success">₹<?php echo number_format($bStats['earned'] ?? 0, 2); ?></span>
                        </div>
                        <div class="text-end">
                            <span class="x-small text-muted d-block" style="font-size: 0.6rem;">Pending</span>
                            <span class="fw-bold text-warning">₹<?php echo number_format($bStats['pending'] ?? 0, 2); ?></span>
                        </div>
                    </div>
                </div>
                <script>
                    function copyRefCode() {
                        var copyText = document.getElementById("refCode");
                        copyText.select();
                        copyText.setSelectionRange(0, 99999);
                        navigator.clipboard.writeText(copyText.value);
                        alert("Code copied: " + copyText.value);
                    }
                </script>
                <?php endif; ?>

                <hr>
                <div class="text-start">
                    <h6 class="fw-bold mb-3 small"><i class="fas fa-graduation-cap text-primary me-2"></i>My Enrolled Courses</h6>
                    <?php foreach ($enrollments_list as $en): ?>
                        <div class="p-2 border rounded-3 mb-2 bg-white shadow-sm">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="fw-bold small"><?php echo htmlspecialchars($en['course']); ?></div>
                                <span class="badge bg-<?php echo ($en['admission_status'] == 'enrolled') ? 'success' : 'secondary'; ?> x-small">
                                    <?php echo ucfirst($en['admission_status']); ?>
                                </span>
                            </div>
                            <div class="x-small text-muted">Enrollment: <?php echo $en['enrollment_no']; ?></div>
                            <a href="dashboard.php?enrollment_id=<?php echo $en['id']; ?>" class="btn btn-sm btn-link p-0 x-small text-decoration-none">View Dashboard <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="stat-card h-100">
                <h5 class="fw-bold mb-4">Edit Profile Information</h5>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Full Name (Cannot be changed)</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($userData['full_name']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($userData['email']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date of Birth</label>
                            <input type="date" name="dob" class="form-control" value="<?php echo $userData['dob'] ?? ''; ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Address</label>
                            <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" name="update_profile" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
