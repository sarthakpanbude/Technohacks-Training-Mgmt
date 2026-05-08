<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$pageTitle = "Assign Fees & Installments";
$activePage = "fees";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $installment_plan = (int)$_POST['installment_plan']; // 1, 2, or 3
    
    // Custom amounts
    $amt_1 = $_POST['amt_1'] ?? 0;
    $amt_2 = $_POST['amt_2'] ?? 0;
    $amt_3 = $_POST['amt_3'] ?? 0;

    // Custom dates
    $date_1 = $_POST['date_1'] ?? date('Y-m-d');
    $date_2 = $_POST['date_2'] ?? date('Y-m-d', strtotime("+1 months", strtotime($date_1)));
    $date_3 = $_POST['date_3'] ?? date('Y-m-d', strtotime("+2 months", strtotime($date_1)));

    try {
        $pdo->beginTransaction();
        
        // Remove existing installments if any (to reset)
        $pdo->prepare("DELETE FROM installments WHERE student_id = ? AND status = 'Pending'")->execute([$student_id]);

        $dates = [1 => $date_1, 2 => $date_2, 3 => $date_3];
        $amounts = [1 => $amt_1, 2 => $amt_2, 3 => $amt_3];

        for ($i = 1; $i <= $installment_plan; $i++) {
            $due_date = $dates[$i];
            $amount = $amounts[$i];
            $stmt = $pdo->prepare("INSERT INTO installments (student_id, installment_no, amount, due_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$student_id, $i, $amount, $due_date]);
        }
        
        $pdo->commit();
        $msg = "Fees and installments assigned successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error assigning fees: " . $e->getMessage();
    }
}

// Fetch all students for dropdown
$students = $pdo->query("SELECT s.id, u.full_name, s.enrollment_no FROM students s JOIN users u ON s.user_id = u.id")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content w-100">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Assign Fees & Installments</h2>
        <a href="../admin/dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
    </div>

    <?php if (isset($msg)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $msg; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="stat-card">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Select Student</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['full_name']) . ' (' . ($s['enrollment_no'] ?? 'N/A') . ')'; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Total Course Fees (₹) (Reference)</label>
                    <input type="number" id="total_fees" class="form-control" placeholder="Enter total fees to split automatically" min="1">
                </div>
                
                <div class="col-md-12 border-bottom pb-2 mt-4">
                    <h5 class="fw-bold mb-0">Installment Setup</h5>
                </div>
                
                <div class="col-md-12 mb-2">
                    <label class="form-label fw-bold small">Installment Plan</label>
                    <select name="installment_plan" id="installment_plan" class="form-select" required>
                        <option value="1">Full Payment (1 Installment)</option>
                        <option value="2">2 Installments</option>
                        <option value="3">3 Installments</option>
                    </select>
                </div>
                
                <div class="col-md-12">
                    <div class="row g-3">
                        <!-- Installment 1 -->
                        <div class="col-md-6" id="amt-1">
                            <label class="form-label fw-bold small text-primary">1st Installment Amount (₹)</label>
                            <input type="number" name="amt_1" id="input_amt_1" class="form-control border-primary" required min="1">
                        </div>
                        <div class="col-md-6" id="df-1">
                            <label class="form-label fw-bold small text-primary">1st Installment Due Date</label>
                            <input type="date" name="date_1" class="form-control border-primary" required value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <!-- Installment 2 -->
                        <div class="col-md-6 d-none" id="amt-2">
                            <label class="form-label fw-bold small text-success">2nd Installment Amount (₹)</label>
                            <input type="number" name="amt_2" id="input_amt_2" class="form-control border-success">
                        </div>
                        <div class="col-md-6 d-none" id="df-2">
                            <label class="form-label fw-bold small text-success">2nd Installment Due Date</label>
                            <input type="date" name="date_2" class="form-control border-success" value="<?php echo date('Y-m-d', strtotime('+1 months')); ?>">
                        </div>

                        <!-- Installment 3 -->
                        <div class="col-md-6 d-none" id="amt-3">
                            <label class="form-label fw-bold small text-warning">3rd Installment Amount (₹)</label>
                            <input type="number" name="amt_3" id="input_amt_3" class="form-control border-warning">
                        </div>
                        <div class="col-md-6 d-none" id="df-3">
                            <label class="form-label fw-bold small text-warning">3rd Installment Due Date</label>
                            <input type="date" name="date_3" class="form-control border-warning" value="<?php echo date('Y-m-d', strtotime('+2 months')); ?>">
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Assign Fees</button>
                    <a href="installments.php" class="btn btn-outline-primary ms-2"><i class="fas fa-list me-2"></i>View Installments</a>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const planSelect = document.getElementById('installment_plan');
    const totalFeesInput = document.getElementById('total_fees');
    
    const amt1 = document.getElementById('amt-1');
    const df1 = document.getElementById('df-1');
    const amt2 = document.getElementById('amt-2');
    const df2 = document.getElementById('df-2');
    const amt3 = document.getElementById('amt-3');
    const df3 = document.getElementById('df-3');
    
    const inputAmt1 = document.getElementById('input_amt_1');
    const inputAmt2 = document.getElementById('input_amt_2');
    const inputAmt3 = document.getElementById('input_amt_3');
    
    function updateDateFields() {
        let plan = parseInt(planSelect.value);
        let total = parseFloat(totalFeesInput.value) || 0;
        
        // Reset required attributes
        inputAmt2.required = false;
        document.querySelector('input[name="date_2"]').required = false;
        inputAmt3.required = false;
        document.querySelector('input[name="date_3"]').required = false;
        
        if (plan === 1) {
            amt2.classList.add('d-none'); df2.classList.add('d-none');
            amt3.classList.add('d-none'); df3.classList.add('d-none');
            if(total > 0) inputAmt1.value = total;
        } else if (plan === 2) {
            amt2.classList.remove('d-none'); df2.classList.remove('d-none');
            amt3.classList.add('d-none'); df3.classList.add('d-none');
            
            inputAmt2.required = true;
            document.querySelector('input[name="date_2"]').required = true;
            
            if(total > 0) {
                inputAmt1.value = (total / 2).toFixed(2);
                inputAmt2.value = (total / 2).toFixed(2);
            }
        } else if (plan === 3) {
            amt2.classList.remove('d-none'); df2.classList.remove('d-none');
            amt3.classList.remove('d-none'); df3.classList.remove('d-none');
            
            inputAmt2.required = true;
            document.querySelector('input[name="date_2"]').required = true;
            inputAmt3.required = true;
            document.querySelector('input[name="date_3"]').required = true;
            
            if(total > 0) {
                inputAmt1.value = (total / 3).toFixed(2);
                inputAmt2.value = (total / 3).toFixed(2);
                inputAmt3.value = (total / 3).toFixed(2);
            }
        }
    }
    
    planSelect.addEventListener('change', updateDateFields);
    totalFeesInput.addEventListener('input', updateDateFields);
    updateDateFields(); // Init on load
});
</script>

<?php include '../includes/footer.php'; ?>
