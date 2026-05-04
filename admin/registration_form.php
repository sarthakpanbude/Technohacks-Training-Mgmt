<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$id = $_GET['id'] ?? 0;

// Fetch student details with user and course info
$stmt = $pdo->prepare("SELECT s.*, u.full_name, u.email, u.created_at as reg_date,
                              c.name as course_name, b.batch_name
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       LEFT JOIN enrollments e ON s.id = e.student_id
                       LEFT JOIN batches b ON e.batch_id = b.id
                       LEFT JOIN courses c ON b.course_id = c.id
                       WHERE s.id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student not found.");
}

$registration_id = "TH-REG-" . str_pad($student['id'], 5, '0', STR_PAD_LEFT);
$registration_date = date('d/m/Y', strtotime($student['reg_date'] ?: $student['created_at']));
$course_name = $student['course_name'] ?? 'Not Enrolled';
$batch_name = $student['batch_name'] ?? 'Not Assigned';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Registration Form - <?php echo $registration_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            background: #f0f2f5;
            font-family: 'Poppins', sans-serif;
            color: #333;
        }

        .bill-container {
            max-width: 850px;
            margin: 30px auto;
            background: #fff;
            padding: 0;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .bill-header {
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .bill-of-supply {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bill-of-supply span {
            font-weight: 600;
            font-size: 14px;
        }

        .original-tag {
            border: 1px solid #ccc;
            padding: 2px 8px;
            font-size: 11px;
            color: #777;
            text-transform: uppercase;
        }

        .slogan {
            font-size: 13px;
            font-weight: 500;
        }

        .company-section {
            padding: 0 40px 20px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .company-logo img {
            height: 60px;
        }

        .company-details h1 {
            color: #800080;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 2px;
        }

        .company-info p {
            margin: 0;
            font-size: 12px;
            color: #555;
            line-height: 1.4;
        }

        .invoice-details-bar {
            background: #e9ecef;
            padding: 12px 40px;
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .detail-item {
            font-size: 14px;
        }

        .detail-item strong {
            font-weight: 600;
        }

        .bill-to-section {
            padding: 0 40px;
            margin-bottom: 20px;
        }

        .bill-to-section h6 {
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .customer-name {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 2px;
        }

        .customer-phone {
            font-size: 13px;
            color: #555;
        }

        .services-table {
            width: 100%;
            margin-bottom: 0;
        }

        .services-table th {
            border-top: 2px solid #800080;
            border-bottom: 2px solid #800080;
            padding: 12px 40px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .services-table td {
            padding: 15px 40px;
            font-size: 14px;
            vertical-align: middle;
        }

        .subtotal-bar {
            background: #800080;
            color: #fff;
            padding: 8px 40px;
            display: flex;
            justify-content: space-between;
            font-weight: 600;
            font-size: 14px;
        }

        .totals-section {
            padding: 20px 40px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .system-note {
            font-size: 11px;
            color: #777;
            font-style: italic;
            margin-top: 20px;
        }

        .signature-section {
            padding: 40px 40px 60px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .signature-img {
            height: 60px;
            margin-bottom: 10px;
        }

        .signature-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            text-align: center;
        }

        .signature-company {
            font-size: 12px;
            text-align: center;
        }

        @media print {
            body {
                background: #fff;
                margin: 0;
                padding: 0;
            }

            .bill-container {
                box-shadow: none;
                margin: 0;
                width: 100%;
                max-width: 100%;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="container py-4 no-print">
        <div class="d-flex justify-content-center gap-2">
            <button onclick="window.print()" class="btn btn-primary px-4 shadow-sm">
                <i class="fas fa-print me-2"></i>Print Form
            </button>
            <a href="view_student.php?id=<?php echo $student['id']; ?>"
                class="btn btn-outline-secondary px-4 shadow-sm">
                <i class="fas fa-arrow-left me-2"></i>Back to Profile
            </a>
        </div>
    </div>

    <div class="bill-container">
        <div class="bill-header">
            <div class="bill-of-supply">
                <span>STUDENT REGISTRATION FORM</span>
                <div class="original-tag">OFFICIAL COPY</div>
            </div>
            <div class="slogan">Let's Grow Together...!!</div>
        </div>

        <div class="company-section">
            <div class="company-logo">
                <img src="../assets/img/logo.png" alt="TechnoHacks">
            </div>
            <div class="company-details">
                <h1>TechnoHacks EduTech</h1>
                <div class="company-info">
                    <p>Nashik, Maharashtra 422010, India, Nashik, Maharashtra, 422010</p>
                    <p><strong>Mobile:</strong> 8208937014</p>
                    <p><strong>Email:</strong> info@technohacks.co.in</p>
                </div>
            </div>
        </div>

        <div class="invoice-details-bar">
            <div class="detail-item"><strong>Reg No.:</strong> <?php echo $registration_id; ?></div>
            <div class="detail-item"><strong>Reg. Date:</strong> <?php echo $registration_date; ?></div>
            <div class="detail-item"><strong>Course:</strong> <?php echo htmlspecialchars($course_name); ?></div>
        </div>

        <div class="bill-to-section">
            <h6>STUDENT DETAILS</h6>
            <div class="customer-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
            <div class="customer-phone">Mobile: <?php echo $student['phone'] ?? 'N/A'; ?></div>
            <div class="customer-phone">Email: <?php echo $student['email'] ?? 'N/A'; ?></div>
            <div class="customer-phone">Enrollment: <?php echo $student['enrollment_no'] ?? 'N/A'; ?></div>
        </div>

        <table class="table services-table">
            <thead>
                <tr>
                    <th>PROGRAM DETAILS</th>
                    <th class="text-center">BATCH</th>
                    <th class="text-end">STATUS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo strtoupper($course_name); ?> ( OFFLINE )</td>
                    <td class="text-center"><?php echo strtoupper($batch_name); ?></td>
                    <td class="text-end"><?php echo strtoupper($student['admission_status']); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="subtotal-bar">
            <span>REGISTRATION SUMMARY</span>
            <div class="d-flex gap-5">
                <span>VERIFIED</span>
                <span>CONFIRMED</span>
            </div>
        </div>

        <div class="totals-section">
            <div class="customer-phone"><strong>Address:</strong>
                <?php echo htmlspecialchars($student['address'] ?: 'N/A'); ?></div>
            <div class="customer-phone"><strong>Date of Birth:</strong>
                <?php echo $student['dob'] ? date('d M Y', strtotime($student['dob'])) : 'N/A'; ?></div>

            <p class="system-note">"This is a system generated registration form. No signature required."</p>
        </div>

        <div class="signature-section">
            <div class="signature-placeholder">
                <img src="https://upload.wikimedia.org/wikipedia/commons/3/3a/Jon_Kirsch%27s_Signature.png"
                    class="signature-img" style="opacity: 0.7; filter: grayscale(1);">
            </div>
            <div class="signature-label">AUTHORISED SIGNATORY</div>
            <div class="signature-company">TechnoHacks EduTech</div>
        </div>
    </div>
</body>

</html>