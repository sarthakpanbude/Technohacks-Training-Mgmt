<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

// Fetch fee data including course
$query = "SELECT s.enrollment_no, u.full_name, sb.course, sf.total_fee, sf.paid_fee, sf.pending_fee, sf.payment_mode 
          FROM student_fees sf 
          JOIN students_basic sb ON sf.student_id = sb.student_id
          JOIN students s ON sb.student_id = s.enrollment_no
          JOIN users u ON s.user_id = u.id
          ORDER BY s.enrollment_no ASC";

$fees = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Set headers for Excel (.xls) download
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Fees_Report_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Output the data as an HTML table which Excel recognizes as a native spreadsheet format
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Fees Report</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        .text { mso-number-format:"\@"; }
        .num { mso-number-format:"0\.00"; }
        th { background-color: #f1f5f9; font-weight: bold; border: 1px solid #000; }
        td { border: 1px solid #ccc; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th style="background-color: #6366f1; color: #ffffff;">Student ID</th>
                <th style="background-color: #6366f1; color: #ffffff;">Student Name</th>
                <th style="background-color: #6366f1; color: #ffffff;">Enrolled Course</th>
                <th style="background-color: #6366f1; color: #ffffff;">Total Fee (INR)</th>
                <th style="background-color: #6366f1; color: #ffffff;">Paid Amount (INR)</th>
                <th style="background-color: #6366f1; color: #ffffff;">Pending Amount (INR)</th>
                <th style="background-color: #6366f1; color: #ffffff;">Payment Mode</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($fees as $f): ?>
            <tr>
                <td class="text"><?php echo $f['enrollment_no']; ?></td>
                <td><?php echo htmlspecialchars($f['full_name']); ?></td>
                <td><?php echo htmlspecialchars($f['course']); ?></td>
                <td class="num"><?php echo $f['total_fee']; ?></td>
                <td class="num"><?php echo $f['paid_fee']; ?></td>
                <td class="num"><?php echo $f['pending_fee']; ?></td>
                <td><?php echo htmlspecialchars($f['payment_mode']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
