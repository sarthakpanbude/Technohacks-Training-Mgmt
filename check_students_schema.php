<?php
require_once 'config/db.php';
$stmt = $pdo->query('DESCRIBE students_basic');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
unlink(__FILE__);
?>
