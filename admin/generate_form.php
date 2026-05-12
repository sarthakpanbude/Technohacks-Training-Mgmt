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

// Fetch Referral Discount
$stmt = $pdo->prepare("SELECT discount_amount FROM referral_bonuses WHERE referred_id = ?");
$stmt->execute([$student_id]);
$referral_discount = $stmt->fetchColumn() ?: 0;

// Fetch Institute Settings
$settings = $pdo->query("SELECT * FROM settings WHERE id=1")->fetch();
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
            margin: 0 auto;
            background: #fff;
            padding: 5px 40px;
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
            width: 150px;
            display: flex;
            flex-direction: column;
            align-items: flex-start; /* Logo on left */
        }
        
        .header-side.right {
            align-items: flex-end; /* Photo on right */
        }

        .logo-box {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
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
            font-size: 28px;
            color: #800080;
            margin: 0;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .photo-frame {
            width: 90px;
            height: 100px;
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
            margin: 4px 0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 400;
            color: #333;
            margin-bottom: 5px;
            padding: 0 5px;
        }

        /* Section Styles */
        .section-header {
            margin-top: 8px;
            margin-bottom: 5px;
        }

        .section-header h6 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 12px;
            color: #ffffff;
            background: #1a1a1a;
            padding: 6px 12px;
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
            font-size: 10px;
            color: #800080;
            margin: 8px 0 5px 5px;
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
            margin-bottom: 5px;
            border-collapse: collapse;
            background: #fff;
        }

        .table-details th {
            width: 20%;
            background-color: #fcfcfc;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 10px;
            color: #666;
            padding: 8px 12px;
            border: 1px solid #eeeeee;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .table-details td {
            padding: 8px 12px;
            border: 1px solid #eeeeee;
            font-size: 12px;
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
            font-size: 9.5px;
            color: #444;
            margin-top: 5px;
            padding: 8px;
            background: #fff;
            border: 1px solid #e0e0e0;
            line-height: 1.3;
        }

        .terms-conditions-box h6 {
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-size: 10px;
        }

        .signature-section {
            margin-top: 10px;
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
                padding: 10px;
                border: none;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="container py-4 no-print text-center">
        <div class="d-flex justify-content-center gap-2">
            <button onclick="window.print()" class="btn btn-primary px-4 shadow-sm">
                <i class="fas fa-print me-2"></i>Print Form
            </button>
            <button onclick="downloadPDF()" class="btn btn-success px-4 shadow-sm">
                <i class="fas fa-file-pdf me-2"></i>Download PDF
            </button>
            <a href="students.php" class="btn btn-outline-secondary px-4 shadow-sm">
                <i class="fas fa-arrow-left me-2"></i>Back to Students
            </a>
        </div>
    </div>

    <?php
    $copies = ["ADMISSION FORM"];
    foreach ($copies as $index => $copy_type):
        ?>
        <div class="bill-container" id="printable-area">
            

            <header class="form-header">
                <div class="header-top-row">
                    <div class="header-side left">
                        <div class="logo-box">
                            <img src="../<?php echo $settings['institute_logo'] ?: 'assets/img/logo.png'; ?>" alt="<?php echo htmlspecialchars($settings['institute_name']); ?>">
                        </div>
                    </div>

                    <div class="company-info-box">
                        <h2 class="form-title">Admission Form</h2>
                        <h1 class="company-name"><?php echo htmlspecialchars($settings['institute_name']); ?></h1>
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
            <h6>PERSONAL DETAILS</h6>
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
                        <th>Address</th>
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
            <h6>COURSE & FEE DETAILS</h6>
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

                <th>Start Date</th>
                <td><?php echo $s['start_date'] ? date('d/m/Y', strtotime($s['start_date'])) : 'N/A'; ?></td>
            </tr>
        </table>

        <div class="sub-header">FEE INFORMATION</div>
        <table class="table-details">
            <?php 
            $calc_std_fee = ($s['standard_fee'] > 0) ? $s['standard_fee'] : (($s['total_fee'] ?? 0) + ($s['other_discount'] ?? 0) + $referral_discount);
            ?>
            <tr>
                <th>Standard Fee</th>
                <td><?php echo $settings['currency'] ?? '₹'; ?><?php echo number_format($calc_std_fee, 2); ?></td>
                <th>Payment Mode</th>
                <td><?php echo htmlspecialchars($s['payment_mode'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <th>Discounts Applied</th>
                <td colspan="3">
                    <div style="display: flex; gap: 15px; font-size: 11px;">
                        <span>• Special Discount: <strong>-<?php echo $settings['currency'] ?? '₹'; ?><?php echo number_format($s['other_discount'] ?? 0, 2); ?></strong></span>
                        <span>• Referral Discount: <strong>-<?php echo $settings['currency'] ?? '₹'; ?><?php echo number_format($referral_discount, 2); ?></strong></span>
                    </div>
                </td>
            </tr>
            <tr>
                <th>Final Payable Fee</th>
                <td colspan="3"><strong style="font-size: 16px; color: #800080;"><?php echo $settings['currency'] ?? '₹'; ?><?php echo number_format($s['total_fee'] ?? 0, 2); ?></strong></td>
            </tr>
        </table>



            <!-- Terms & Conditions -->
            <?php 
            $refund_date = ($referral_discount > 0) 
                ? date('Y-m-d', strtotime(($s['created_at'] ?? 'now') . ' + 15 days')) 
                : date('Y-m-d', strtotime(($s['created_at'] ?? 'now') . ' + 7 days'));
            
            // Re-fetch from DB if available to be exact
            $stmt_ref = $pdo->prepare("SELECT refund_expiry_date FROM referral_bonuses WHERE referred_id = ?");
            $stmt_ref->execute([$student_id]);
            $db_refund_date = $stmt_ref->fetchColumn();
            if ($db_refund_date) $refund_date = $db_refund_date;
            ?>
            <div class="terms-conditions-box">
                <h6>Terms & Conditions</h6>
                <ol style="padding-left: 15px; margin-bottom: 0; line-height: 1.5; font-size: 9px;">
                    <?php 
                    $terms_str = $settings['terms_conditions'] ?? "";
                    if (empty(trim($terms_str))) {
                        $terms = [
                            "Fees once paid are non-refundable and non-transferable under any circumstances.",
                            "Minimum 75% attendance is mandatory to be eligible for course certification.",
                            "The institute reserves the right to modify batch timings or curriculum if necessary.",
                            "Students are required to carry their ID cards at all times within institute premises.",
                            "Any damage to institute property by the student will be charged to the student.",
                            "Placement assistance is subject to student performance, attendance, and conduct.",
                            "All disputes are subject to the jurisdiction of local courts only."
                        ];
                    } else {
                        $terms = explode("\n", $terms_str);
                    }
                    
                    foreach($terms as $term):
                        if(trim($term)):
                    ?>
                        <li><?php echo trim($term); ?></li>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </ol>
            </div>

            <!-- Updated Declaration -->
            <div class="declaration-box">
                <strong>DECLARATION:</strong> I confirm that the information provided by me is true and correct. I have read and agree to the above Terms & Conditions and institute rules.
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
                    <div class="signature-company"><?php echo htmlspecialchars($settings['institute_name']); ?></div>
                </div>
            </div>

    <?php endforeach; ?>

    <!-- PDF Generation Script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.getElementById('printable-area');
            const studentId = '<?php echo htmlspecialchars($student_id); ?>'.replace(/[^a-z0-9]/gi, '_');
            const opt = {
                margin:       [5, 5, 5, 5],
                filename:     `Admission_Form_${studentId}.pdf`,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>

</html>
