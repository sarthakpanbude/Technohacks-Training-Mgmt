<?php
require_once '../../includes/auth.php';
checkAuth('admin');
require_once '../../config/db.php';

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $pdo->beginTransaction();

        // 1. Fetch Inquiry
        $stmt = $pdo->prepare("SELECT * FROM inquiries WHERE id = ? AND status = 'pending'");
        $stmt->execute([$id]);
        $inquiry = $stmt->fetch();

        if ($inquiry) {
            // 2. Prevent duplicate student entry (check mobile)
            $check = $pdo->prepare("SELECT id FROM students WHERE phone = ?");
            $check->execute([$inquiry['mobile']]);
            if ($check->fetch()) {
                $pdo->rollBack();
                header("Location: ../inquiries.php?error=Student with this phone already exists");
                exit;
            }

            // 3. Create User Account (Check for duplicate email first)
            $username = "stu" . strtolower(preg_replace('/[^A-Za-z0-9]/', '', substr($inquiry['name'], 0, 5))) . rand(100, 999);
            $email = $inquiry['email'] ?? ($username . "@technohacks.com");
            
            $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmail->execute([$email]);
            $existingUser = $checkEmail->fetch();

            if (!$existingUser) {
                $password = password_hash($inquiry['mobile'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, full_name, status) VALUES (?, ?, 'student', ?, ?, 1)");
                $stmt->execute([$username, $password, $email, $inquiry['name']]);
                $user_id = $pdo->lastInsertId();
            } else {
                $user_id = $existingUser['id'];
            }

            // 4. Generate unique ID
            $student_id = "STU-" . date('Y') . "-" . rand(1000, 9999);

            // 5. Insert into students
            $stmt = $pdo->prepare("INSERT INTO students (user_id, enrollment_no, phone, admission_status) VALUES (?, ?, ?, 'active')");
            $stmt->execute([
                $user_id,
                $student_id,
                $inquiry['mobile']
            ]);

            // 6. Insert into students_basic for details
            $stmt = $pdo->prepare("INSERT INTO students_basic (student_id, full_name, email, course) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $student_id,
                $inquiry['name'],
                $email,
                $inquiry['course']
            ]);

            // 6. Update inquiry status
            $stmt = $pdo->prepare("UPDATE inquiries SET status = 'admitted' WHERE id = ?");
            $stmt->execute([$id]);

            $pdo->commit();
            header("Location: ../inquiries.php?msg=Student Admitted Successfully. User: $username");
            exit;
        } else {
            $pdo->rollBack();
            header("Location: ../inquiries.php?error=Inquiry not found or already admitted");
            exit;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: ../inquiries.php");
    exit;
}
?>
