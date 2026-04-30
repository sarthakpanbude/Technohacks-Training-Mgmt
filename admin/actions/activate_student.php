<?php
require_once '../../includes/auth.php';
checkAuth('admin');
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'] ?? null;
    $batch_id = $_POST['batch_id'] ?? null;

    if ($student_id && $batch_id) {
        try {
            $pdo->beginTransaction();

            // 1. Check if enrollment already exists
            $check = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND batch_id = ?");
            $check->execute([$student_id, $batch_id]);
            
            if (!$check->fetch()) {
                // 2. Insert into enrollments
                $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, batch_id, status) VALUES (?, ?, 'active')");
                $stmt->execute([$student_id, $batch_id]);
            }

            // 3. Update student status to 'enrolled'
            $stmt = $pdo->prepare("UPDATE students SET admission_status = 'enrolled' WHERE id = ?");
            $stmt->execute([$student_id]);

            $pdo->commit();
            header("Location: ../enrollment_review.php?success=Student activated and assigned to batch successfully");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: ../enrollment_review.php?error=" . urlencode($e->getMessage()));
            exit;
        }
    }
}

header("Location: ../enrollment_review.php");
exit;
