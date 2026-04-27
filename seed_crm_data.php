<?php
require_once 'config/db.php';

try {
    // 1. Seed Visitors
    $visitors = [
        ['Rahul Khanna', 'rahul@example.com', '9876543210', 'Full Stack Web Development', 'Looking for weekend batches.'],
        ['Priya Sharma', 'priya@example.com', '9876543211', 'Data Science & ML', 'Want to know about placement support.'],
        ['Amit Verma', 'amit@example.com', '9876543212', 'Python Core', 'Beginner friendly?']
    ];

    foreach ($visitors as $v) {
        $stmt = $pdo->prepare("INSERT INTO visitors (name, email, phone, course_interest, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute($v);
    }

    // 2. Seed Pending Admissions (Students with pending status)
    $admissions = [
        ['Suresh Kumar', 'suresh@example.com', 'suresh_student', '9876543220', 'Delhi, India'],
        ['Anjali Gupta', 'anjali@example.com', 'anjali_student', '9876543221', 'Mumbai, India']
    ];

    foreach ($admissions as $a) {
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, role, email, full_name) VALUES (?, ?, 'student', ?, ?)");
        $stmt->execute([$a[2], $password, $a[1], $a[0]]);
        $userId = $pdo->lastInsertId();
        
        if ($userId) {
            $stmt = $pdo->prepare("INSERT INTO students (user_id, phone, address, admission_status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$userId, $a[3], $a[4]]);
        }
    }

    echo "Visitor and Admission data seeded successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
