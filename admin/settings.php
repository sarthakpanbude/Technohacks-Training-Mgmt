<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "System Settings";
$activePage = "settings";

$success = "";
$error = "";

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $name = $_POST['institute_name'] ?? '';
    $email = $_POST['institute_email'] ?? '';
    $phone = $_POST['institute_phone'] ?? '';
    $address = $_POST['institute_address'] ?? '';
    $website = $_POST['institute_website'] ?? '';
    $slogan = $_POST['slogan'] ?? '';
    $terms = $_POST['terms_conditions'] ?? '';
    $discount_pct = $_POST['referral_discount_percent'] ?? 0;
    $bonus_pct = $_POST['referral_bonus_percent'] ?? 0;
    $referral_enabled = isset($_POST['referral_system_enabled']) ? 1 : 0;
    $refund_threshold = $_POST['referral_refund_threshold'] ?? 6000;
    $refund_days_short = $_POST['referral_refund_days_short'] ?? 7;
    $refund_days_long = $_POST['referral_refund_days_long'] ?? 14;
    
    try {
        $logo_path = null;
        if (isset($_FILES['institute_logo']) && $_FILES['institute_logo']['error'] == 0) {
            $target_dir = "../assets/img/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_ext = pathinfo($_FILES["institute_logo"]["name"], PATHINFO_EXTENSION);
            $logo_name = "logo_" . time() . "." . $file_ext;
            $target_file = $target_dir . $logo_name;
            
            if (move_uploaded_file($_FILES["institute_logo"]["tmp_name"], $target_file)) {
                $logo_path = "assets/img/" . $logo_name;
            }
        }

        $signature_path = null;
        if (isset($_FILES['signature_image']) && $_FILES['signature_image']['error'] == 0) {
            $target_dir = "../assets/img/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_ext = pathinfo($_FILES["signature_image"]["name"], PATHINFO_EXTENSION);
            $sig_name = "sig_" . time() . "." . $file_ext;
            $target_file = $target_dir . $sig_name;
            
            if (move_uploaded_file($_FILES["signature_image"]["tmp_name"], $target_file)) {
                $signature_path = "assets/img/" . $sig_name;
            }
        }

        $query = "UPDATE settings SET institute_name=?, institute_email=?, institute_phone=?, institute_address=?, institute_website=?, slogan=?, terms_conditions=?, referral_discount_percent=?, referral_bonus_percent=?, referral_system_enabled=?, referral_refund_threshold=?, referral_refund_days_short=?, referral_refund_days_long=?";
        $params = [$name, $email, $phone, $address, $website, $slogan, $terms, $discount_pct, $bonus_pct, $referral_enabled, $refund_threshold, $refund_days_short, $refund_days_long];

        if ($logo_path) {
            $query .= ", institute_logo=?";
            $params[] = $logo_path;
        }
        if ($signature_path) {
            $query .= ", signature_image=?";
            $params[] = $signature_path;
        }

        $query .= " WHERE id=1";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $success = "Settings updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Fetch Current Settings
$settings = $pdo->query("SELECT * FROM settings WHERE id=1")->fetch();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content pt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold mb-0">System Settings</h3>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row g-3">
            <div class="col-md-7">
                <div class="stat-card p-4" style="height: auto;">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-4 me-3">
                            <i class="fas fa-university text-primary h4 mb-0"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Institute Details</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Institute Name</label>
                            <input type="text" name="institute_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['institute_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Contact Email</label>
                            <input type="email" name="institute_email" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['institute_email'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Contact Phone</label>
                            <input type="text" name="institute_phone" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['institute_phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Slogan / Tagline</label>
                            <input type="text" name="slogan" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['slogan'] ?? ''); ?>" placeholder="e.g. Let's Grow Together">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Website URL</label>
                            <input type="url" name="institute_website" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['institute_website'] ?? ''); ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Address</label>
                            <textarea name="institute_address" class="form-control rounded-3" rows="2"><?php echo htmlspecialchars($settings['institute_address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-md-12 mt-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-warning bg-opacity-10 p-2 rounded-3 me-2">
                                    <i class="fas fa-file-contract text-warning"></i>
                                </div>
                                <h6 class="fw-bold mb-0">Documents & Policy</h6>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted text-uppercase">Admission Terms & Conditions</label>
                                <textarea name="terms_conditions" class="form-control rounded-3" rows="6" placeholder="Enter terms line by line"><?php 
                                    if (empty($settings['terms_conditions'])) {
                                        echo "1. Fees once paid are non-refundable and non-transferable.\n";
                                        echo "2. Minimum 75% attendance is mandatory for certification.\n";
                                        echo "3. Institute reserves the right to modify batch timings.\n";
                                        echo "4. Students must carry their ID cards at all times.\n";
                                        echo "5. Any damage to property will be charged to the student.\n";
                                        echo "6. Placement assistance is subject to performance and attendance.\n";
                                        echo "7. Disputes are subject to local jurisdiction only.";
                                    } else {
                                        echo htmlspecialchars($settings['terms_conditions']); 
                                    }
                                ?></textarea>
                                <small class="text-muted small">These will appear on Admission Forms and Receipts.</small>
                            </div>
                        </div>

                        <div class="col-md-12 mt-4">
                            <label class="form-label small fw-bold text-muted text-uppercase d-block">Institute Logo</label>
                            <div class="d-flex align-items-center gap-4 p-3 border rounded-4 bg-light">
                                <div class="settings-logo-preview">
                                    <img src="../<?php echo $settings['institute_logo'] ?: 'assets/img/logo.png'; ?>" id="logoPreview" class="rounded shadow-sm" style="max-height: 60px; max-width: 120px; object-fit: contain;">
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" name="institute_logo" id="logoInput" class="form-control form-control-sm rounded-pill" accept="image/*">
                                    <small class="text-muted x-small mt-2 d-block">Upload PNG/JPG. Recommended size: 200x200px</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-4">
                            <label class="form-label small fw-bold text-muted text-uppercase d-block">Authorised Signature</label>
                            <div class="d-flex align-items-center gap-4 p-3 border rounded-4 bg-light">
                                <div class="settings-logo-preview bg-white p-2 border">
                                    <img src="../<?php echo $settings['signature_image'] ?: 'https://upload.wikimedia.org/wikipedia/commons/3/3a/Jon_Kirsch%27s_Signature.png'; ?>" id="sigPreview" class="rounded" style="max-height: 60px; max-width: 150px; object-fit: contain;">
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" name="signature_image" id="sigInput" class="form-control form-control-sm rounded-pill" accept="image/*">
                                    <small class="text-muted x-small mt-2 d-block">Transparent PNG recommended. This will appear on receipts.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <!-- Combined Control Card -->
                <div class="stat-card border-0 shadow-sm rounded-4 overflow-hidden mb-3" style="height: auto;">
                    <div class="p-4 bg-light border-bottom d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                            <i class="fas fa-sliders-h text-primary"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Control Center</h5>
                    </div>
                    <div class="p-4">
                        <!-- Referral Controls -->
                        <div class="mb-4 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0 small text-muted text-uppercase">Referral & Earn System</h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="referral_system_enabled" id="refSystemToggle" <?php echo ($settings['referral_system_enabled'] ?? 1) ? 'checked' : ''; ?>>
                                </div>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label x-small fw-bold text-muted text-uppercase mb-1">Student Discount</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" name="referral_discount_percent" class="form-control rounded-start-3" value="<?php echo htmlspecialchars($settings['referral_discount_percent'] ?? '5.00'); ?>">
                                        <span class="input-group-text bg-white rounded-end-3">%</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label x-small fw-bold text-muted text-uppercase mb-1">Referrer Bonus</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" name="referral_bonus_percent" class="form-control rounded-start-3" value="<?php echo htmlspecialchars($settings['referral_bonus_percent'] ?? '5.00'); ?>">
                                        <span class="input-group-text bg-white rounded-end-3">%</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label x-small fw-bold text-muted text-uppercase mb-1">Fee Threshold for Long Period</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text bg-light rounded-start-3">₹</span>
                                        <input type="number" name="referral_refund_threshold" class="form-control rounded-end-3" value="<?php echo htmlspecialchars($settings['referral_refund_threshold'] ?? '6000'); ?>">
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label x-small fw-bold text-muted text-uppercase mb-1">Below Threshold</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="referral_refund_days_short" class="form-control rounded-start-3" value="<?php echo htmlspecialchars($settings['referral_refund_days_short'] ?? '7'); ?>">
                                                <span class="input-group-text bg-white rounded-end-3">Days</span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label x-small fw-bold text-muted text-uppercase mb-1">Above Threshold</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="referral_refund_days_long" class="form-control rounded-start-3" value="<?php echo htmlspecialchars($settings['referral_refund_days_long'] ?? '14'); ?>">
                                                <span class="input-group-text bg-white rounded-end-3">Days</span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-muted x-small mb-0 mt-2">Adjust periods based on the final fee amount.</p>
                                </div>
                            </div>
                        </div>

                        <!-- System Utilities -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3 small text-muted text-uppercase">System Utilities</h6>
                            <div class="p-3 bg-light rounded-4 d-flex justify-content-between align-items-center border border-white shadow-sm">
                                <div class="d-flex align-items-center">
                                    <div class="bg-white p-2 rounded-circle me-3 shadow-sm">
                                        <i class="fas fa-database text-dark small"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold small">Database Backup</h6>
                                        <p class="text-muted x-small mb-0">Secure your data now</p>
                                    </div>
                                </div>
                                <a href="actions/backup_db.php" class="btn btn-dark btn-sm rounded-pill px-3 shadow-sm">Backup Now</a>
                            </div>
                        </div>

                        <!-- Info Alert -->
                        <div class="stat-card bg-primary bg-opacity-10 border-0 p-3">
                            <div class="d-flex align-items-center">
                                <div class="text-primary me-3">
                                    <i class="fas fa-info-circle h4 mb-0"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-primary mb-1 small">Centralized System</h6>
                                    <p class="text-primary x-small mb-0 opacity-75">Changes here apply to all forms, receipts, and reports portal-wide.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" name="update_settings" class="btn btn-primary rounded-pill w-100 py-3 fw-bold shadow-lg">
                        <i class="fas fa-save me-2"></i>Save All System Settings
                    </button>
                </div>
            </div>
        </div>
    </form>
</main>

<script>
document.getElementById('logoInput').onchange = evt => {
  const [file] = evt.target.files
  if (file) {
    document.getElementById('logoPreview').src = URL.createObjectURL(file)
  }
}
document.getElementById('sigInput').onchange = evt => {
  const [file] = evt.target.files
  if (file) {
    document.getElementById('sigPreview').src = URL.createObjectURL(file)
  }
}
</script>

<?php include '../includes/footer.php'; ?>
