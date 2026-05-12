<?php
require_once '../../includes/auth.php';
checkAuth('admin');
require_once '../../config/db.php';

// Database configuration from db.php might not have all info in a way we can use for mysqldump
// But since this is a local XAMPP, we can assume certain defaults or try to get them from the PDO connection if possible.
// However, the simplest way is to use PDO to fetch all tables and data.

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'technohacks_erp';

$tables = array();
$result = $pdo->query('SHOW TABLES');
while($row = $result->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

$return = "";
foreach($tables as $table) {
    $result = $pdo->query('SELECT * FROM '.$table);
    $num_fields = $result->columnCount();
    
    $return .= 'DROP TABLE IF EXISTS '.$table.';';
    $row2 = $pdo->query('SHOW CREATE TABLE '.$table)->fetch(PDO::FETCH_NUM);
    $return .= "\n\n".$row2[1].";\n\n";
    
    for ($i = 0; $i < $num_fields; $i++) {
        while($row = $result->fetch(PDO::FETCH_NUM)) {
            $return .= 'INSERT INTO '.$table.' VALUES(';
            for($j=0; $j<$num_fields; $j++) {
                $row[$j] = addslashes($row[$j]);
                $row[$j] = str_replace("\n","\\n",$row[$j]);
                if (isset($row[$j])) { $return .= '"'.$row[$j].'"' ; } else { $return .= '""'; }
                if ($j<($num_fields-1)) { $return .= ','; }
            }
            $return .= ");\n";
        }
    }
    $return .= "\n\n\n";
}

// Set headers to download the file
$filename = 'db-backup-' . date('Y-m-d-H-i-s') . '.sql';
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"" . $filename . "\"");
echo $return;
exit;
?>
