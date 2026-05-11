<?php
require_once '../config/db.php';

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->beginTransaction();

    $students = $pdo->query("SELECT id, enrollment_no FROM students")->fetchAll();

    foreach ($students as $s) {
        $old_id = $s['enrollment_no'];
        
        if (strpos($old_id, 'STU') === 0) {
            do {
                $rand = rand(10000, 99999);
                $new_id = "T" . $rand;
                $check = $pdo->prepare("SELECT id FROM students WHERE enrollment_no = ?");
                $check->execute([$new_id]);
            } while ($check->fetch());

            // Update students table
            $pdo->prepare("UPDATE students SET enrollment_no = ? WHERE id = ?")->execute([$new_id, $s['id']]);

            // Update students_basic
            $pdo->prepare("UPDATE students_basic SET student_id = ? WHERE student_id = ?")->execute([$new_id, $old_id]);

            // Update student_fees
            $pdo->prepare("UPDATE student_fees SET student_id = ? WHERE student_id = ?")->execute([$new_id, $old_id]);

            // Update referral_bonuses
            $pdo->prepare("UPDATE referral_bonuses SET referrer_id = ? WHERE referrer_id = ?")->execute([$new_id, $old_id]);
            $pdo->prepare("UPDATE referral_bonuses SET referred_id = ? WHERE referred_id = ?")->execute([$new_id, $old_id]);

            // Update personal_details
            $pdo->prepare("UPDATE personal_details SET student_id = ? WHERE student_id = ?")->execute([$new_id, $old_id]);

            // Update education
            $pdo->prepare("UPDATE education SET student_id = ? WHERE student_id = ?")->execute([$new_id, $old_id]);

            // Update student_documents
            $pdo->prepare("UPDATE student_documents SET student_id = ? WHERE student_id = ?")->execute([$new_id, $old_id]);
            
            echo "Converted $old_id to $new_id\n";
        }
    }

    $pdo->commit();
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "All IDs converted successfully!";
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Conversion failed: " . $e->getMessage();
}
