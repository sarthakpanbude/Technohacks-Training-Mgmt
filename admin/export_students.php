<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

// Fetch student data
$query = "SELECT s.enrollment_no, s.permanent_id, u.full_name, s.course, s.phone, u.email, s.dob, s.address, s.referral_code, s.referral_id, s.admission_status, s.created_at 
          FROM students s 
          JOIN users u ON s.user_id = u.id 
          ORDER BY s.created_at DESC";
$students = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Set headers for Excel (.xls) download
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Students_Full_List_" . date('Y-m-d') . ".xls");
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
                    <x:Name>Students List</x:Name>
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
        th { background-color: #6366f1; color: #ffffff; font-weight: bold; border: 1px solid #000; padding: 10px; }
        td { border: 1px solid #ccc; padding: 5px; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>Enrollment No</th>
                <th>Student ID</th>
                <th>Full Name</th>
                <th>Enrolled Course</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Date of Birth</th>
                <th>Address</th>
                <th>Referral Code</th>
                <th>Referred By (ID)</th>
                <th>Admission Status</th>
                <th>Admission Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($students as $s): ?>
            <tr>
                <td class="text"><?php echo $s['enrollment_no']; ?></td>
                <td class="text"><?php echo $s['permanent_id']; ?></td>
                <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                <td><?php echo htmlspecialchars($s['course']); ?></td>
                <td class="text"><?php echo $s['phone']; ?></td>
                <td><?php echo htmlspecialchars($s['email']); ?></td>
                <td><?php echo $s['dob'] ? date('d M Y', strtotime($s['dob'])) : '-'; ?></td>
                <td><?php echo htmlspecialchars($s['address']); ?></td>
                <td class="text"><?php echo $s['referral_code']; ?></td>
                <td class="text"><?php echo $s['referral_id'] ?: '-'; ?></td>
                <td><?php echo ucfirst($s['admission_status']); ?></td>
                <td><?php echo date('d M Y', strtotime($s['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
