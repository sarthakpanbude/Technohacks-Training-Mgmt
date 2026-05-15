<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$student_id = $_GET['id'] ?? null;
$inst_id = $_GET['inst_id'] ?? null;

// Use the students_basic and student_fees tables as per the existing logic in this file
$stmt = $pdo->prepare("SELECT sb.full_name, sb.course, sb.duration, sb.start_date, sf.*, s.id as main_id, s.permanent_id, s.created_at as admission_date, s.phone, u.email 
                      FROM students_basic sb 
                      JOIN student_fees sf ON sb.student_id = sf.student_id 
                      JOIN students s ON sb.student_id = s.enrollment_no 
                      LEFT JOIN users u ON s.user_id = u.id
                      WHERE sb.student_id = ?");
$stmt->execute([$student_id]);
$s = $stmt->fetch();

if (!$s) {
    die("Student or fee details not found.");
}

// Fetch Next Installment Date
$stmt = $pdo->prepare("SELECT due_date FROM installments WHERE student_id = ? AND status = 'Pending' ORDER BY due_date ASC LIMIT 1");
$stmt->execute([$s['main_id']]);
$nextInstallmentDate = $stmt->fetchColumn();

// Calculate Refund Expiry Date (Using same logic as admit.php: 7 days if fee < 6000, else 14 days from start_date)
$days = ($s['total_fee'] < 6000) ? 7 : 14;
$refundExpiryDate = date('Y-m-d', strtotime($s['start_date'] . " + $days days"));

// Fetch Discount info if available
$stmt = $pdo->prepare("SELECT * FROM referral_bonuses WHERE referred_id = ?");
$stmt->execute([$student_id]);
$discount_info = $stmt->fetch();

$installment_data = null;
if ($inst_id) {
    $stmt = $pdo->prepare("SELECT * FROM installments WHERE id = ?");
    $stmt->execute([$inst_id]);
    $installment_data = $stmt->fetch();
}

// Function to convert number to words (Indian Rupees style)
function numberToWords($number) {
    $no = (int)floor($number);
    $point = round($number - $no, 2) * 100;
    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        '0' => '', '1' => 'One', '2' => 'Two',
        '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six',
        '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
        '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve',
        '13' => 'Thirteen', '14' => 'Fourteen',
        '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen',
        '18' => 'Eighteen', '19' => 'Nineteen', '20' => 'Twenty',
        '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty',
        '60' => 'Sixty', '70' => 'Seventy',
        '80' => 'Eighty', '90' => 'Ninety'
    );
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number] .
                " " . $digits[$counter] . $plural . " " . $hundred
                :
                $words[floor($number / 10) * 10]
                . " " . $words[$number % 10] . " "
                . $digits[$counter] . $plural . " " . $hundred;
        } else $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    $points = ($point) ?
        "." . $words[$point / 10] . " " .
        $words[$point = $point % 10] : '';
    return $result . "Rupees Only";
}

$course_name = $s['course'] ?? 'Training Course';
$total_fees = $s['total_fee'];

if ($installment_data) {
    $paid_amount = $installment_data['amount'];
    $invoice_no = "RCPT-INST-" . $installment_data['id'];
    $invoice_date = date('d/m/Y', strtotime($installment_data['payment_date'] ?? 'now'));
    
    // Calculate balance at the time of this installment
    // We sum unique payments by receipt number to avoid double counting if a record exists in both tables
    $stmt = $pdo->prepare("
        SELECT SUM(amount) FROM (
            SELECT amount, receipt_no, payment_date FROM payments WHERE student_id = (SELECT id FROM students WHERE enrollment_no = ?)
            UNION
            SELECT amount, receipt_no, payment_date FROM invoices WHERE student_id = (SELECT id FROM students WHERE enrollment_no = ?)
        ) as unique_payments 
        WHERE payment_date <= ?
    ");
    $stmt->execute([$student_id, $student_id, $installment_data['payment_date'] ?? date('Y-m-d H:i:s')]);
    $accumulated_paid = $stmt->fetchColumn() ?: 0;
    
    $previous_paid = $accumulated_paid - $paid_amount;
    $balance = $total_fees - $accumulated_paid;
    $due_date = date('d/m/Y', strtotime($installment_data['due_date']));
} else {
    $paid_amount = $s['paid_fee'];
    $previous_paid = 0;
    $balance = $s['pending_fee'];
    $invoice_no = "RCPT-" . date('Y') . $s['id'];
    $invoice_date = date('d/m/Y');
    $due_date = $s['next_installment_date'] ? date('d/m/Y', strtotime($s['next_installment_date'])) : date('d/m/Y', strtotime('+30 days'));
}

// Fetch Institute Settings
$settings = $pdo->query("SELECT * FROM settings WHERE id=1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bill of Supply - <?php echo $invoice_no; ?></title>
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
            padding: 0 0 40px 0;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            position: relative;
        }

        .bill-header {
            padding: 15px 40px 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .bill-of-supply {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bill-of-supply span {
            font-weight: 700;
            font-size: 13px;
            color: #555;
            text-transform: uppercase;
        }

        .original-tag {
            border: 1px solid #4a148c;
            padding: 2px 8px;
            font-size: 11px;
            color: #4a148c;
            text-transform: uppercase;
            font-weight: 700;
        }

        .slogan {
            font-size: 13px;
            font-weight: 600;
            color: #4a148c;
        }

        .company-section {
            padding: 0 40px 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .company-logo img {
            height: 90px;
            width: auto;
            object-fit: contain;
        }

        .company-details h1 {
            color: #4a148c;
            font-weight: 800;
            font-size: 28px;
            margin-bottom: 2px;
            letter-spacing: -0.5px;
        }

        .company-info p {
            margin: 0;
            font-size: 11.5px;
            color: #666;
            line-height: 1.5;
        }

        .invoice-details-bar {
            background: #fdfbff;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            padding: 12px 40px;
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .detail-item {
            font-size: 14px;
            color: #333;
        }

        .detail-item strong {
            font-weight: 700;
            color: #4a148c;
        }

        .bill-to-section {
            padding: 0 40px;
            margin-bottom: 20px;
        }

        .bill-to-section h6 {
            font-weight: 800;
            font-size: 12px;
            color: #4a148c;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .customer-name {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 2px;
            color: #222;
        }

        .customer-phone {
            font-size: 13px;
            color: #555;
            font-weight: 500;
        }

        .services-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .services-table th {
            background: #4a148c;
            color: #fff;
            border: none;
            padding: 12px 40px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .services-table td {
            padding: 15px 40px;
            font-size: 14px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
            color: #333;
        }

        .subtotal-bar {
            background: #f3e5f5;
            color: #4a148c;
            padding: 12px 40px;
            display: flex;
            justify-content: space-between;
            font-weight: 800;
            font-size: 15px;
            text-transform: uppercase;
        }

        .totals-section {
            padding: 20px 40px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .total-row {
            display: flex;
            justify-content: flex-end;
            width: 100%;
            max-width: 380px;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .total-label {
            flex: 1;
            text-align: left;
            padding-right: 20px;
            font-weight: 600;
            color: #444;
        }

        .total-value {
            width: 120px;
            text-align: right;
            font-weight: 700;
            color: #222;
        }

        .grand-total {
            border-top: 2px solid #4a148c;
            border-bottom: 1px solid #eee;
            padding: 10px 0;
            margin: 8px 0;
        }
        
        .grand-total .total-label, .grand-total .total-value {
            font-weight: 800;
            color: #4a148c;
            font-size: 16px;
        }

        .received-amount {
            border-bottom: 1px solid #f0f0f0;
            padding: 8px 0;
        }
        
        .received-amount .total-value {
            color: #1b5e20; /* Deep Green for positive received amount */
        }
        
        .pending-balance {
            padding: 8px 0;
            font-weight: 800;
        }
        
        .pending-balance .total-value {
            color: #b71c1c; /* Urgent Red for pending balance */
        }

        .amount-in-words {
            text-align: right;
            font-size: 12px;
            margin-top: 12px;
            color: #666;
        }

        .amount-in-words p {
            margin: 0;
        }

        .amount-in-words .words {
            font-weight: 800;
            font-size: 15px;
            color: #4a148c;
        }

        .terms-info {
            font-size: 13px !important; /* Slightly larger for better readability */
            color: #444;
            line-height: 2.0;
        }
        
        .terms-info strong {
            color: #333;
        }

        .signature-section {
            padding: 10px 40px;
        }

        .signature-img {
            height: 75px;
            width: auto;
            object-fit: contain;
            margin-bottom: 5px;
        }

        .signature-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #4a148c;
        }

        .signature-company {
            font-size: 11px;
            color: #666;
        }

        .page-break {
            page-break-after: always;
            border-bottom: 2px dashed #ccc;
            margin: 20px 0;
        }

        @media print {
            body { background: #fff; margin: 0; padding: 0; }
            .bill-container { box-shadow: none; margin: 0; width: 100%; max-width: 100%; }
            .no-print { display: none !important; }
            .page-break { border-bottom: none; }
        }
    </style>
</head>
<body>
    <div class="container py-4 no-print text-center">
        <button onclick="window.print()" class="btn btn-primary px-4 shadow-sm">
            <i class="fas fa-print me-2"></i>Print Receipt
        </button>
        <button onclick="downloadPDF()" class="btn btn-success px-4 shadow-sm ms-2">
            <i class="fas fa-file-pdf me-2"></i>Download PDF
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary px-4 shadow-sm ms-2">
            <i class="fas fa-times me-2"></i>Close
        </button>
    </div>

    <?php 
    $copies = [
        "OFFICIAL RECEIPT"
    ];
    foreach ($copies as $index => $copy_type): 
    ?>
    <div class="bill-container" id="printable-area">
        <div class="bill-header">
            <div class="bill-of-supply">
                <span><?php echo $installment_data ? 'INSTALLMENT RECEIPT' : 'BILL OF SUPPLY'; ?></span>
            </div>
            <div class="slogan"><?php echo htmlspecialchars($settings['slogan'] ?? "Let's Grow Together...!!"); ?></div>
        </div>

        <div class="company-section">
            <div class="company-logo">
                <img src="../<?php echo $settings['institute_logo'] ?: 'assets/img/logo.png'; ?>" alt="<?php echo htmlspecialchars($settings['institute_name']); ?>">
            </div>
            <div class="company-details">
                <h1><?php echo htmlspecialchars($settings['institute_name']); ?></h1>
                <div class="company-info">
                    <p><?php echo nl2br(htmlspecialchars($settings['institute_address'])); ?></p>
                    <p><strong>Mobile:</strong> <?php echo htmlspecialchars($settings['institute_phone']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($settings['institute_email']); ?></p>
                </div>
            </div>
        </div>

        <div class="invoice-details-bar">
            <div class="detail-item"><strong><?php echo $installment_data ? 'Receipt No.:' : 'Invoice No.:'; ?></strong> <?php echo $invoice_no; ?></div>
            <div class="detail-item"><strong>Date:</strong> <?php echo $invoice_date; ?></div>
        </div>

        <div class="bill-to-section">
            <div class="d-flex justify-content-between">
                <div>
                    <h6>BILL TO</h6>
                    <div class="customer-name"><?php echo htmlspecialchars($s['full_name']); ?></div>
                    <div class="customer-phone" style="font-weight: 500;"><?php echo htmlspecialchars($s['phone']); ?> | <?php echo htmlspecialchars($s['email']); ?></div>
                    <div class="customer-phone">Student ID: <?php echo $s['permanent_id'] ?? $s['student_id']; ?></div>
                </div>
                <?php if ($installment_data && $installment_data['transaction_id']): ?>
                <div class="text-end">
                    <h6>REFERENCE</h6>
                    <div class="customer-name" style="font-size: 14px;"><?php echo htmlspecialchars($installment_data['transaction_id']); ?></div>
                    <div class="customer-phone">Transaction ID</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php 
        $calc_std_fee = ($s['standard_fee'] > 0) ? $s['standard_fee'] : ($total_fees + ($s['other_discount'] ?? 0) + ($discount_info['discount_amount'] ?? 0));
        ?>
        <table class="table services-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>DESCRIPTION</th>
                    <th class="text-center">DURATION</th>
                    <th class="text-end">AMOUNT (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td><?php echo strtoupper($course_name); ?> ( OFFLINE )</td>
                    <td class="text-center"><?php echo $s['duration'] ?: '-'; ?></td>
                    <td class="text-end"><?php echo number_format($calc_std_fee, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="subtotal-bar">
            <span>SUBTOTAL</span>
            <span><?php echo number_format($calc_std_fee, 2); ?></span>
        </div>

        <div class="totals-section">
            <div class="total-row">
                <div class="total-label">Standard Course Fee</div>
                <div class="total-value"><?php echo number_format($calc_std_fee, 2); ?></div>
            </div>
            <?php if (($s['other_discount'] ?? 0) > 0): ?>
            <div class="total-row" style="color: #2e7d32; font-weight: 600;">
                <div class="total-label" style="color: #2e7d32;">Special Discount</div>
                <div class="total-value">- <?php echo number_format($s['other_discount'], 2); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if (($discount_info['discount_amount'] ?? 0) > 0): ?>
            <div class="total-row" style="color: #2e7d32; font-weight: 600;">
                <div class="total-label" style="color: #2e7d32;">Referral Discount</div>
                <div class="total-value">- <?php echo number_format($discount_info['discount_amount'], 2); ?></div>
            </div>
            <?php endif; ?>
            <div class="total-row grand-total">
                <div class="total-label">TOTAL PAYABLE FEE</div>
                <div class="total-value"><?php echo number_format($total_fees, 2); ?></div>
            </div>
            <div class="total-row received-amount">
                <div class="total-label">Received Amount</div>
                <div class="total-value"><?php echo number_format($paid_amount, 2); ?></div>
            </div>
            <div class="total-row pending-balance">
                <div class="total-label">Pending Balance</div>
                <div class="total-value"><?php echo number_format($balance, 2); ?></div>
            </div>

            <div class="amount-in-words">
                <p>Total Amount (in words)</p>
                <p class="words"><?php echo numberToWords($total_fees); ?></p>
            </div>
        </div>

        <?php if ($s['next_installment_amount'] > 0 || $s['third_installment_amount'] > 0): ?>
        <div class="installments-section px-5 mb-3">
            <h6 class="fw-bold text-uppercase border-bottom pb-1 mb-2" style="font-size: 11px; color: #800080;">Upcoming Installment Schedule</h6>
            <div class="row g-2">
                <?php if ($s['next_installment_amount'] > 0): ?>
                <div class="col-6">
                    <div class="p-2 border rounded bg-light" style="font-size: 11px;">
                        <div class="small text-muted">2nd Installment</div>
                        <div class="fw-bold"><?php echo number_format($s['next_installment_amount'], 2); ?></div>
                        <div class="text-primary">Due: <?php echo date('d M Y', strtotime($s['next_installment_date'])); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($s['third_installment_amount'] > 0): ?>
                <div class="col-6">
                    <div class="p-2 border rounded bg-light" style="font-size: 11px;">
                        <div class="small text-muted">3rd Installment</div>
                        <div class="fw-bold"><?php echo number_format($s['third_installment_amount'], 2); ?></div>
                        <div class="text-primary">Due: <?php echo date('d M Y', strtotime($s['third_installment_date'])); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-end px-5 mb-4 mt-2">
            <div class="terms-info text-start" style="font-size: 11px; color: #555; line-height: 1.8;">
                <div class="mb-1">
                    <i class="fas fa-calendar-alt me-1" style="color: #4a148c;"></i> 
                    <strong>Next Installment Due:</strong> 
                    <span class="<?php echo $nextInstallmentDate ? 'text-danger fw-bold' : 'text-success fw-bold'; ?>">
                        <?php echo $nextInstallmentDate ? date('d M Y', strtotime($nextInstallmentDate)) : 'FULLY PAID'; ?>
                    </span>
                </div>
                <div>
                    <i class="fas fa-info-circle me-1 text-primary"></i> 
                    <strong>Refund Period:</strong> 
                    <span class="text-dark fw-bold">Valid until <?php echo date('d M Y', strtotime($refundExpiryDate)); ?></span>
                </div>
            </div>
            <div class="signature-section p-0 m-0">
                <div class="signature-placeholder text-center">
                    <img src="../<?php echo $settings['signature_image'] ?: 'https://upload.wikimedia.org/wikipedia/commons/3/3a/Jon_Kirsch%27s_Signature.png'; ?>" class="signature-img" style="opacity: 0.9; filter: contrast(1.1) brightness(0.9);">
                </div>
                <div class="signature-label fw-bold" style="color: #4a148c; font-size: 11px;">AUTHORISED SIGNATORY FOR</div>
                <div class="signature-company fw-bold" style="color: #555; font-size: 11px;"><?php echo htmlspecialchars($settings['institute_name']); ?></div>
            </div>
        </div>
        
    </div>
    <?php if ($index == 0): ?>
        <div class="page-break no-print"></div>
    <?php endif; ?>
    <?php endforeach; ?>

    <!-- PDF Generation Script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.getElementById('printable-area');
            const receiptNo = '<?php echo htmlspecialchars($invoice_no); ?>'.replace(/[^a-z0-9]/gi, '_');
            const opt = {
                margin:       [10, 10, 10, 10],
                filename:     `Fee_Receipt_${receiptNo}.pdf`,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>

