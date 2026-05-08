<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$student_id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT sb.*, pd.*, e.*, sf.*, s.phone, s.created_at, s.id as real_id FROM students_basic sb 
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

// Fetch Installments
$inst_stmt = $pdo->prepare("SELECT * FROM installments WHERE student_id = ? ORDER BY installment_no ASC");
$inst_stmt->execute([$s['real_id']]);
$installments_list = $inst_stmt->fetchAll();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Admission Form - <?php echo htmlspecialchars($student_id); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;600;700;800&family=Montserrat:wght@700;800&display=swap');

        body {
            background: #f0f2f5;
            font-family: 'Inter', sans-serif;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }

        .bill-container {
            max-width: 850px;
            margin: 10px auto;
            background: #fff;
            padding: 15px 40px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        /* Header Styles */
        .form-header {
            margin-bottom: 10px;
        }

        .header-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        /* Create equal sized containers on sides to force true center */
        .header-side {
            width: 120px;
            display: flex;
            align-items: center;
        }
        
        .header-side.right {
            justify-content: flex-end;
        }

        .logo-box {
            width: 80px;
            height: 80px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            background: #fff;
        }

        .logo-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .company-info-box {
            text-align: center;
            flex: 1;
        }

        .company-name {
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            font-size: 14px;
            color: #555;
            margin: 2px 0 0;
            letter-spacing: 0.5px;
            line-height: 1.2;
            text-align: center;
        }

        .form-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 38px;
            color: #800080;
            margin: 0;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .header-side {
            display: flex;
            flex-direction: column;
        }

        .photo-frame {
            width: 100px;
            height: 120px;
            border: 1.5px solid #333;
            background: #fdfdfd;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-placeholder {
            font-size: 8px;
            color: #999;
            text-align: center;
            padding: 5px;
            font-weight: 600;
        }

        .header-divider {
            width: 100%;
            height: 1.5px;
            background: #1a1a1a;
            margin: 8px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: 400;
            color: #333;
            margin-bottom: 15px;
            padding: 0 5px;
        }

        /* Section Styles */
        .section-header {
            margin-top: 15px;
            margin-bottom: 8px;
        }

        .section-header h6 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 13px;
            color: #ffffff;
            background: #1a1a1a;
            padding: 10px 15px;
            border-left: 6px solid #800080;
            letter-spacing: 1px;
            margin: 0;
            display: flex;
            align-items: center;
            border-radius: 0 4px 4px 0;
        }

        .sub-header {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 11px;
            color: #800080;
            margin: 15px 0 8px 5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sub-header::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(128, 0, 128, 0.2);
        }

        .table-details {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
            background: #fff;
        }

        .table-details th {
            width: 20%;
            background-color: #fcfcfc;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 11px;
            color: #666;
            padding: 12px 15px;
            border: 1px solid #eeeeee;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .table-details td {
            padding: 12px 15px;
            border: 1px solid #eeeeee;
            font-size: 13px;
            color: #111111;
            font-weight: 500;
        }

        .table-details td strong {
            color: #800080;
            font-weight: 700;
        }

        /* Footer Styles */
        .declaration-box {
            background: #fff;
            border: 1px solid #e0e0e0;
            padding: 10px 15px;
            margin-top: 10px;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
            text-align: justify;
        }

        .terms-conditions-box {
            font-size: 11.5px;
            color: #444;
            margin-top: 10px;
            padding: 12px;
            background: #fff;
            border: 1px solid #e0e0e0;
        }

        .terms-conditions-box h6 {
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-size: 11px;
        }

        .signature-section {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .sig-block {
            text-align: center;
        }

        .sig-line {
            width: 160px;
            border-top: 1.5px solid #333;
            margin-top: 30px;
            margin-bottom: 5px;
        }

        .sig-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .signature-company {
            font-size: 11px;
            text-align: right;
            color: #333;
            font-weight: 600;
        }


        .page-break {
            page-break-after: always;
            border-bottom: 2px dashed #ccc;
            margin: 30px 0;
        }

        .copy-tag-badge {
            position: absolute;
            top: 10px;
            left: 40px;
            font-size: 9px;
            font-weight: 700;
            color: #800080;
            border: 1px solid #800080;
            padding: 1px 8px;
            text-transform: uppercase;
            background: #fff;
        }

        @media print {
            @page {
                size: A4;
                margin: 5mm;
            }
            body {
                background: #fff;
            }

            .bill-container {
                box-shadow: none;
                margin: 0;
                width: 100%;
                max-width: 100%;
                padding: 20px;
                border: none;
            }

            .no-print {
                display: none !important;
            }

            .page-break {
                border-bottom: none;
            }

            .copy-tag-badge {
                top: 10px;
                left: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container py-4 no-print text-center">
        <div class="d-flex justify-content-center gap-2">
            <button onclick="window.print()" class="btn btn-primary px-4 shadow-sm">
                <i class="fas fa-print me-2"></i>Print Both Copies
            </button>
            <a href="students.php" class="btn btn-outline-secondary px-4 shadow-sm">
                <i class="fas fa-arrow-left me-2"></i>Back to Students
            </a>
        </div>
    </div>

    <?php
    $copies = ["STUDENT COPY", "INSTITUTE COPY"];
    foreach ($copies as $index => $copy_type):
        ?>
        <div class="bill-container">
            <div class="copy-tag-badge"><?php echo $copy_type; ?></div>

            <header class="form-header">
                <div class="header-top-row">
                    <div class="header-side left">
                        <div class="logo-box">
                            <img src="../assets/img/logo.png" alt="TechnoHacks">
                        </div>
                    </div>

                    <div class="company-info-box">
                        <h2 class="form-title">Admission Form</h2>
                        <h1 class="company-name">TechnoHacks Solutions Pvt. Ltd</h1>
                    </div>

                    <div class="header-side right">
                        <div class="photo-frame">
                            <?php if ($photo && file_exists('../' . $photo)): ?>
                                <img src="../<?php echo htmlspecialchars($photo); ?>" alt="Student Photo">
                            <?php else: ?>
                                <div class="photo-placeholder">PASTE PASSPORT SIZE<br>PHOTO HERE</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="header-divider"></div>

                <div class="info-row">
                    <div class="info-item"><strong>Student ID:</strong> <?php echo htmlspecialchars($s['student_id']); ?></div>
                    <div class="info-item"><strong>Joining Date:</strong> <?php echo date('d/m/Y', strtotime($s['created_at'] ?? 'now')); ?></div>
                </div>
                <div class="header-divider" style="margin-top: 5px;"></div>
            </header>

            <!-- Section 1 -->
        <div class="section-header">
            <h6>SECTION 1: PERSONAL DETAILS</h6>
        </div>
        <div class="section-flex">
            <div class="details-table-wrapper">
                <table class="table-details" style="margin-bottom: 0;">
                    <tr>
                        <th>Candidate Name</th>
                        <td colspan="3"><strong><?php echo htmlspecialchars($s['full_name']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Date of Birth</th>
                        <td><?php echo $s['dob'] ? date('d M, Y', strtotime($s['dob'])) : 'N/A'; ?></td>
                        <th>Gender</th>
                        <td><?php echo htmlspecialchars($s['gender'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Mobile Number</th>
                        <td><?php echo htmlspecialchars($s['phone'] ?? 'N/A'); ?></td>
                        <th>Email ID</th>
                        <td><?php echo htmlspecialchars($s['email'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Current Address</th>
                        <td colspan="3"><?php echo htmlspecialchars($s['address'] ?? 'N/A'); ?></td>
                    </tr>
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
        </div>

        <!-- Section 2 -->
        <div class="section-header">
            <h6>SECTION 2: COURSE & FEE DETAILS</h6>
        </div>
        
        <div class="sub-header">COURSE INFORMATION</div>
        <table class="table-details">
            <tr>
                <th>Course Selected</th>
                <td><strong><?php echo htmlspecialchars($s['course']); ?></strong></td>
                <th>Course Duration</th>
                <td><?php echo htmlspecialchars($s['duration'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <th>Mode (Online/Offline)</th>
                <td><?php echo htmlspecialchars($s['mode'] ?? 'Offline'); ?></td>
                <th>Start Date</th>
                <td><?php echo $s['start_date'] ? date('d/m/Y', strtotime($s['start_date'])) : 'N/A'; ?></td>
            </tr>
        </table>

        <div class="sub-header">FEE INFORMATION</div>
        <table class="table-details">
            <tr>
                <th>Total Course Fee</th>
                <td><strong>₹<?php echo number_format($s['total_fee'] ?? 0, 2); ?></strong></td>
                <th>Payment Mode</th>
                <td><?php echo htmlspecialchars($s['payment_mode'] ?? 'N/A'); ?></td>
            </tr>
        </table>



            <!-- Terms & Conditions -->
            <div class="terms-conditions-box">
                <h6>Terms & Conditions</h6>
                <ol style="padding-left: 15px; margin-bottom: 0; line-height: 1.5;">
                    <li><strong>Refund Policy:</strong> 100% refund if cancelled within 7 days (short courses) or 14 days
                        (full/advanced) from course start. After this period, no refund will be provided.</li>
                    <li>100% refund will be given if the syllabus is not completed due to the institute’s fault.</li>
                    <li><strong>Lifetime access:</strong> Students can join any existing batch again after course
                        completion, free of cost (same course only).</li>
                    <li>Fees must be paid as per plan. Delay may suspend access.</li>
                    <li>Certificate will be issued only after course completion and full fee payment.</li>
                    <li>Students must maintain regular attendance; missed sessions may not be repeated.</li>
                    <li>Batch timings, trainer, or schedule may change if required.</li>
                    <li>The institute is not responsible for any medical issues, injury, or health-related loss during the
                        course.</li>
                    <li>Misconduct or rule violation may lead to termination without refund.</li>
                    <li>Study material is for personal use only; sharing is prohibited.</li>
                </ol>
            </div>

            <!-- Updated Declaration -->
            <div class="declaration-box">
                <strong>DECLARATION:</strong> I hereby declare that the information provided by me is true and correct to
                the best of my knowledge. I agree to follow the rules, discipline, and code of conduct of the institute. I
                am committed to maintaining regular attendance, active participation, and a positive learning attitude
                throughout the training program.
            </div>

            <!-- Signatures -->
            <div class="signature-section">
                <div class="sig-block">
                    <div class="sig-line"></div>
                    <div class="sig-label">Student's Signature</div>
                </div>
                <div class="sig-block text-end">
                    <div class="sig-line"></div>
                    <div class="signature-label">AUTHORISED SIGNATORY FOR</div>
                    <div class="signature-company">TechnoHacks Solutions</div>
                </div>
            </div>

            </div>
        </div>
        <?php if ($index == 0): ?>
            <div class="page-break no-print"></div>
        <?php endif; ?>
    <?php endforeach; ?>
</body>

</html>