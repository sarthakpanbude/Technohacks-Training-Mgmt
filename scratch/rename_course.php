<?php
require_once 'config/db.php';
try {
    // 1. Update courses table
    $stmt1 = $pdo->prepare("UPDATE courses SET name = 'Industrial Training' WHERE name = 'Diploma industrial training'");
    $stmt1->execute();
    echo "Courses updated: " . $stmt1->rowCount() . "\n";

    // 2. Update inquiries table
    $stmt2 = $pdo->prepare("UPDATE inquiries SET course = 'Industrial Training' WHERE course = 'Diploma industrial training'");
    $stmt2->execute();
    echo "Inquiries updated: " . $stmt2->rowCount() . "\n";

    // 3. Update visitors table
    $stmt3 = $pdo->prepare("UPDATE visitors SET course_interest = 'Industrial Training' WHERE course_interest = 'Diploma industrial training'");
    $stmt3->execute();
    echo "Visitors updated: " . $stmt3->rowCount() . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
