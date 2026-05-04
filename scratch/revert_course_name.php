<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=technohacks_erp', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("ALTER TABLE courses CHANGE name course_name varchar(100) NOT NULL");
    echo "Renamed name back to course_name.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
