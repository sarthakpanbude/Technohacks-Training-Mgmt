<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$student_id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT sb.*, pd.*, e.*, sf.*, s.phone FROM students_basic sb 
                      LEFT JOIN personal_details pd ON sb.student_id = pd.student_id 
                      LEFT JOIN education e ON sb.student_id = e.student_id 
                      LEFT JOIN student_fees sf ON sb.student_id = sf.student_id 
                      LEFT JOIN students s ON sb.student_id = s.enrollment_no
                      WHERE sb.student_id = ?");
$stmt->execute([$student_id]);
$s = $stmt->fetch();
if (!$s)
    exit("Not found");

$doc_stmt = $pdo->prepare("SELECT file_path FROM student_documents WHERE student_id = ? AND doc_type = 'photo'");
$doc_stmt->execute([$student_id]);
$photo = $doc_stmt->fetchColumn();

// Fallback to user profile pic if not found in documents
if (!$photo && isset($s['email'])) {
    $user_stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE email = ?");
    $user_stmt->execute([$s['email']]);
    $user_pic = $user_stmt->fetchColumn();
    if ($user_pic && $user_pic != 'default.png') {
        $photo = 'uploads/profiles/' . $user_pic;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Admission Form - <?php echo htmlspecialchars($student_id); ?></title>
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

        .admission-details-bar {
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

        .section-header {
            padding: 0 40px;
            margin-bottom: 10px;
            margin-top: 20px;
        }

        .section-header h6 {
            font-weight: 700;
            font-size: 14px;
            color: #800080;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            text-transform: uppercase;
        }

        .content-padding {
            padding: 0 40px;
        }

        .table-details {
            width: 100%;
            margin-bottom: 20px;
        }

        .table-details th {
            width: 25%;
            background-color: #f8f9fa;
            font-weight: 600;
            font-size: 12px;
            color: #555;
            padding: 8px 12px;
            border: 1px solid #eee;
        }

        .table-details td {
            padding: 8px 12px;
            border: 1px solid #eee;
            font-size: 13px;
        }

        .declaration-box {
            background: #f8f9fa;
            border: 1px dashed #ccc;
            padding: 15px 40px;
            margin: 20px 40px;
            font-size: 11px;
            color: #666;
            line-height: 1.5;
        }

        .signature-section {
            padding: 20px 40px 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .sig-block {
            text-align: center;
        }

        .sig-line {
            width: 150px;
            border-top: 1px solid #333;
            margin-top: 40px;
            margin-bottom: 5px;
        }

        .sig-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .signature-img {
            height: 50px;
            margin-bottom: 5px;
        }

        .signature-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            text-align: right;
        }

        .signature-company {
            font-size: 11px;
            text-align: right;
            color: #777;
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
                <i class="fas fa-print me-2"></i>Print Admission Form
            </button>
            <a href="students.php" class="btn btn-outline-secondary px-4 shadow-sm">
                <i class="fas fa-arrow-left me-2"></i>Back to Students
            </a>
        </div>
    </div>

    <div class="bill-container">
        <div class="bill-header">
            <div class="bill-of-supply">
                <span>ADMISSION FORM</span>
                <div class="original-tag">STUDENT COPY</div>
            </div>
            <div class="slogan">Let's Grow Together...!!</div>
        </div>

        <div class="company-section">
            <div class="company-logo">
                <img src="../assets/img/logo.png" alt="TechnoHacks">
            </div>
            <div class="company-details">
                <h1>TechnoHacks Solutions</h1>
                <div class="company-info">
                    <p>Nashik, Maharashtra 422010, India, Nashik, Maharashtra, 422010</p>
                    <p><strong>Mobile:</strong> 8208937014</p>
                    <p><strong>Email:</strong> info@technohacks.co.in</p>
                </div>
            </div>
        </div>

        <div class="admission-details-bar">
            <div class="detail-item"><strong>Enrollment No.:</strong> <?php echo htmlspecialchars($s['student_id']); ?></div>
            <div class="detail-item"><strong>Date:</strong> <?php echo date('d/m/Y'); ?></div>
            <div class="detail-item"><strong>Course:</strong> <?php echo htmlspecialchars($s['course']); ?></div>
        </div>

        <div class="section-header">
            <h6>CANDIDATE INFORMATION</h6>
        </div>
        <div class="content-padding">
            <div class="row">
                <div class="col-9">
                    <table class="table-details">
                        <tr>
                            <th>Candidate Name</th>
                            <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Course Selected</th>
                            <td><strong><?php echo htmlspecialchars($s['course']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Date of Birth</th>
                            <td><?php echo $s['dob'] ? date('d M, Y', strtotime($s['dob'])) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <th>Gender</th>
                            <td><?php echo htmlspecialchars($s['gender'] ?? 'N/A'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-3 text-center">
                    <?php if ($photo && file_exists('../' . $photo)): ?>
                        <img src="../<?php echo htmlspecialchars($photo); ?>" alt="Student Photo"
                            style="width: 100px; height: 120px; object-fit: cover; border: 1px solid #ddd; padding: 2px;">
                    <?php else: ?>
                        <div class="border d-flex align-items-center justify-content-center text-muted"
                            style="width: 100px; height: 120px; margin: auto; background-color: #f8f9fa; font-size: 10px;">PHOTO</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h6>PERSONAL & CONTACT DETAILS</h6>
        </div>
        <div class="content-padding">
            <table class="table-details">
                <tr>
                    <th>Father's Name</th>
                    <td><?php echo htmlspecialchars($s['father_name'] ?? 'N/A'); ?></td>
                    <th>Mother's Name</th>
                    <td><?php echo htmlspecialchars($s['mother_name'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Email ID</th>
                    <td><?php echo htmlspecialchars($s['email'] ?? 'N/A'); ?></td>
                    <th>Mobile No</th>
                    <td><?php echo htmlspecialchars($s['phone'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Aadhaar Number</th>
                    <td><?php echo htmlspecialchars($s['aadhaar_number'] ?? 'N/A'); ?></td>
                    <th>Category</th>
                    <td><?php echo htmlspecialchars($s['category'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Current Address</th>
                    <td colspan="3"><?php echo htmlspecialchars($s['address'] ?? 'N/A'); ?>,
                        <?php echo htmlspecialchars($s['city'] ?? ''); ?>,
                        <?php echo htmlspecialchars($s['state'] ?? ''); ?></td>
                </tr>
            </table>
        </div>

        <div class="section-header">
            <h6>ACADEMIC DETAILS</h6>
        </div>
        <div class="content-padding">
            <table class="table-details">
                <tr>
                    <th>Qualification</th>
                    <td><?php echo htmlspecialchars($s['qualification'] ?? 'N/A'); ?></td>
                    <th>Passing Year</th>
                    <td><?php echo htmlspecialchars($s['passing_year'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>College Name</th>
                    <td colspan="3"><?php echo htmlspecialchars($s['college_name'] ?? 'N/A'); ?></td>
                </tr>
            </table>
        </div>

        <div class="section-header">
            <h6>FEE INFORMATION</h6>
        </div>
        <div class="content-padding">
            <table class="table-details">
                <tr>
                    <th>Total Course Fee</th>
                    <td>₹<?php echo number_format($s['total_fee'] ?? 0, 2); ?></td>
                    <th>Paid Amount</th>
                    <td>₹<?php echo number_format($s['paid_fee'] ?? 0, 2); ?></td>
                </tr>
                <tr>
                    <th>Pending Balance</th>
                    <td><strong class="text-danger">₹<?php echo number_format($s['pending_fee'] ?? 0, 2); ?></strong></td>
                    <th>Installments</th>
                    <td><?php echo htmlspecialchars($s['installments'] ?? '1'); ?></td>
                </tr>
            </table>
        </div>

        <div class="declaration-box">
            <strong>DECLARATION:</strong> I hereby declare that all the information provided by me in this application
            is true and correct to the best of my knowledge and belief. I agree to abide by the rules and regulations of
            the institute. I understand that the fee once paid is non-refundable.
        </div>

        <div class="signature-section">
            <div class="sig-block">
                <div class="sig-line"></div>
                <div class="sig-label">Student's Signature</div>
            </div>
            <div class="sig-block text-end">
                <div class="signature-placeholder">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/3/3a/Jon_Kirsch%27s_Signature.png"
                        class="signature-img" style="opacity: 0.7; filter: grayscale(1);">
                </div>
                <div class="signature-label">AUTHORISED SIGNATORY FOR</div>
                <div class="signature-company">TechnoHacks Solutions</div>
            </div>
        </div>
    </div>
</body>

</html>