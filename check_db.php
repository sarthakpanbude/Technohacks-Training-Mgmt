<?php
require_once 'config/db.php';
$tables = ['student_fees', 'payments', 'invoices'];
foreach($tables as $table) {
    try {
        echo "--- $table ---<br>";
        $stmt = $pdo->query("DESCRIBE $table");
        while($row = $stmt->fetch()) {
            echo $row['Field'] . " - " . $row['Type'] . "<br>";
        }
    } catch(Exception $e) {
        echo "Error describing $table: " . $e->getMessage() . "<br>";
    }
}
?>
