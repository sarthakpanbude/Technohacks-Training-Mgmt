<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=technohacks_erp', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if column course_name exists to rename it
    $stmt = $pdo->query("SHOW COLUMNS FROM courses LIKE 'course_name'");
    if ($stmt->fetch()) {
        $pdo->exec("ALTER TABLE courses CHANGE course_name name varchar(100) NOT NULL");
        echo "Renamed course_name to name.\n";
    }

    // Check if column course_type exists to add it
    $stmt = $pdo->query("SHOW COLUMNS FROM courses LIKE 'course_type'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE courses ADD COLUMN course_type enum('Internship', 'Training') DEFAULT 'Training' AFTER id");
        echo "Added course_type column.\n";
    }

    // Modify level to be more flexible (as expected by the form)
    $pdo->exec("ALTER TABLE courses MODIFY level varchar(50) DEFAULT 'Beginner'");
    echo "Modified level column.\n";

    echo "Courses table successfully updated!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
