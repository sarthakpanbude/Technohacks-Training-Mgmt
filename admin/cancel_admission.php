<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $cancel_type = $_POST['cancel_type']; // 'with_refund' or 'without_refund'
    $enrollment_no = $_POST['enrollment_no'];

    try {
        $pdo->beginTransaction();

        $status = ($cancel_type == 'with_refund') ? 'refunded' : 'cancelled';

        // 1. Update Student Status
        $stmt = $pdo->prepare("UPDATE students SET admission_status = ? WHERE id = ?");
        $stmt->execute([$status, $student_id]);

        // 2. Update Student Fees Status
        $stmt = $pdo->prepare("UPDATE student_fees SET pending_fee = 0 WHERE student_id = ?");
        $stmt->execute([$enrollment_no]);

        // 3. Mark all pending installments as cancelled
        $stmt = $pdo->prepare("UPDATE installments SET status = 'Cancelled' WHERE student_id = ? AND status = 'Pending'");
        $stmt->execute([$student_id]);

        // 4. Record the refund if applicable
        if ($cancel_type == 'with_refund') {
            // Fetch total paid so far
            $stmt = $pdo->prepare("SELECT paid_fee FROM student_fees WHERE student_id = ?");
            $stmt->execute([$enrollment_no]);
            $paid = $stmt->fetchColumn() ?: 0;

            if ($paid > 0) {
                // Record a negative payment as refund
                $receipt = "REF-" . time();
                $stmt = $pdo->prepare("INSERT INTO payments (student_id, amount, payment_type, receipt_no, payment_date) VALUES (?, ?, 'Refund', ?, NOW())");
                $stmt->execute([$student_id, -$paid, $receipt]);
                
                // Set paid_fee to 0
                $stmt = $pdo->prepare("UPDATE student_fees SET paid_fee = 0 WHERE student_id = ?");
                $stmt->execute([$enrollment_no]);
            }
        }

        // 5. Delete Profile if requested
        if (isset($_POST['delete_profile']) && $_POST['delete_profile'] == 'on') {
            // Get user_id first
            $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $user_id = $stmt->fetchColumn();

            // Systematic deletion
            $pdo->prepare("DELETE FROM installments WHERE student_id = ?")->execute([$student_id]);
            $pdo->prepare("DELETE FROM payments WHERE student_id = ?")->execute([$student_id]);
            $pdo->prepare("DELETE FROM invoices WHERE student_id = ?")->execute([$student_id]);
            $pdo->prepare("DELETE FROM student_fees WHERE student_id = ?")->execute([$enrollment_no]);
            $pdo->prepare("DELETE FROM student_documents WHERE student_id = ?")->execute([$enrollment_no]);
            $pdo->prepare("DELETE FROM education WHERE student_id = ?")->execute([$enrollment_no]);
            $pdo->prepare("DELETE FROM personal_details WHERE student_id = ?")->execute([$enrollment_no]);
            $pdo->prepare("DELETE FROM students_basic WHERE student_id = ?")->execute([$enrollment_no]);
            $pdo->prepare("DELETE FROM enrollments WHERE student_id = ?")->execute([$student_id]);
            $pdo->prepare("DELETE FROM students WHERE id = ?")->execute([$student_id]);
            if ($user_id) {
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
            }

            $pdo->commit();
            header("Location: students.php?success=Profile refunded and deleted successfully!");
            exit;
        }

        $pdo->commit();
        header("Location: view_student.php?id=$student_id&success=Admission $status successfully!");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: view_student.php?id=$student_id&error=Error: " . $e->getMessage());
        exit;
    }
}
?>
