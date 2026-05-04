<?php
require_once 'config/db.php';
$stmt = $pdo->query("DESCRIBE courses");
while($row = $stmt->fetch()) {
    echo $row['Field'] . " - " . $row['Type'] . "<br>";
}
?>
