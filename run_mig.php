<?php
require __DIR__ . '/config/db.php';
$sql = file_get_contents(__DIR__ . '/multi_course_migration.sql');
$pdo->exec($sql);
echo "Migrations executed successfully";
