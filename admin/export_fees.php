<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

// Fetch fee data including course and installments
$query = "SELECT s.id as sid, s.enrollment_no, s.permanent_id, u.full_name, sb.course, sf.total_fee, sf.paid_fee, sf.pending_fee, sf.payment_mode,
          (SELECT CONCAT(amount, ' (', status, ' - ', due_date, ')') FROM installments WHERE student_id = s.id AND installment_no = 1) as inst1,
          (SELECT CONCAT(amount, ' (', status, ' - ', due_date, ')') FROM installments WHERE student_id = s.id AND installment_no = 2) as inst2,
          (SELECT CONCAT(amount, ' (', status, ' - ', due_date, ')') FROM installments WHERE student_id = s.id AND installment_no = 3) as inst3,
          (SELECT CONCAT(amount, ' (', status, ' - ', due_date, ')') FROM installments WHERE student_id = s.id AND installment_no = 4) as inst4
          FROM student_fees sf 
          JOIN students_basic sb ON sf.student_id = sb.student_id
          JOIN students s ON sb.student_id = s.enrollment_no
          JOIN users u ON s.user_id = u.id
          ORDER BY s.enrollment_no ASC";

$fees = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Set headers for Excel (.xls) download
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Fees_Report_Full_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

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
        th { background-color: #f1f5f9; font-weight: bold; border: 1px solid #000; padding: 10px; }
        td { border: 1px solid #ccc; padding: 5px; vertical-align: top; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th style="background-color: #6366f1; color: #ffffff;">Enrollment No</th>
                <th style="background-color: #6366f1; color: #ffffff;">Student ID</th>
                <th style="background-color: #6366f1; color: #ffffff;">Student Name</th>
                <th style="background-color: #6366f1; color: #ffffff;">Enrolled Course</th>
                <th style="background-color: #6366f1; color: #ffffff;">Total Fee</th>
                <th style="background-color: #6366f1; color: #ffffff;">Paid</th>
                <th style="background-color: #6366f1; color: #ffffff;">Pending</th>
                <th style="background-color: #4f46e5; color: #ffffff;">Installment 1</th>
                <th style="background-color: #4f46e5; color: #ffffff;">Installment 2</th>
                <th style="background-color: #4f46e5; color: #ffffff;">Installment 3</th>
                <th style="background-color: #4f46e5; color: #ffffff;">Installment 4</th>
                <th style="background-color: #6366f1; color: #ffffff;">Payment Mode</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($fees as $f): ?>
            <tr>
                <td class="text"><?php echo $f['enrollment_no']; ?></td>
                <td class="text"><?php echo $f['permanent_id']; ?></td>
                <td><?php echo htmlspecialchars($f['full_name']); ?></td>
                <td><?php echo htmlspecialchars($f['course']); ?></td>
                <td class="num"><?php echo $f['total_fee']; ?></td>
                <td class="num"><?php echo $f['paid_fee']; ?></td>
                <td class="num"><?php echo $f['pending_fee']; ?></td>
                <td class="text"><?php echo $f['inst1'] ?: '-'; ?></td>
                <td class="text"><?php echo $f['inst2'] ?: '-'; ?></td>
                <td class="text"><?php echo $f['inst3'] ?: '-'; ?></td>
                <td class="text"><?php echo $f['inst4'] ?: '-'; ?></td>
                <td><?php echo htmlspecialchars($f['payment_mode']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
