<?php
require_once '../includes/auth.php';
// Allow both admin and student to view invoice
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../config/db.php';

$id = $_GET['id'] ?? 0;

// Fetch payment details with student, user, and course info
$stmt = $pdo->prepare("SELECT p.*, u.full_name, u.email, s.phone, s.enrollment_no, s.id as student_id,
                              c.name as course_name, c.fees as course_total_fees,
                              (SELECT SUM(amount) FROM payments WHERE student_id = s.id AND id <= p.id) as total_received
                       FROM payments p 
                       JOIN students s ON p.student_id = s.id 
                       JOIN users u ON s.user_id = u.id 
                       LEFT JOIN enrollments e ON s.id = e.student_id
                       LEFT JOIN batches b ON e.batch_id = b.id
                       LEFT JOIN courses c ON b.course_id = c.id
                       WHERE p.id = ?");
$stmt->execute([$id]);
$payment = $stmt->fetch();

if (!$payment) {
    die("Invoice not found.");
}

// Fetch pending installments for this student
$stmt_inst = $pdo->prepare("SELECT * FROM installments WHERE student_id = ? AND status = 'Pending' ORDER BY due_date ASC");
$stmt_inst->execute([$payment['student_id']]);
$pending_installments = $stmt_inst->fetchAll();


// Security check: Students can only view their own invoices
if ($_SESSION['role'] === 'student') {
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
    if ($payment['student_id'] != $student['id']) {
        die("Unauthorized access.");
    }
}

// Function to convert number to words (Indian Rupees style)
function numberToWords($number)
{
    $no = (int) floor($number);
    $point = round($number - $no, 2) * 100;
    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        '0' => '',
        '1' => 'One',
        '2' => 'Two',
        '3' => 'Three',
        '4' => 'Four',
        '5' => 'Five',
        '6' => 'Six',
        '7' => 'Seven',
        '8' => 'Eight',
        '9' => 'Nine',
        '10' => 'Ten',
        '11' => 'Eleven',
        '12' => 'Twelve',
        '13' => 'Thirteen',
        '14' => 'Fourteen',
        '15' => 'Fifteen',
        '16' => 'Sixteen',
        '17' => 'Seventeen',
        '18' => 'Eighteen',
        '19' => 'Nineteen',
        '20' => 'Twenty',
        '30' => 'Thirty',
        '40' => 'Forty',
        '50' => 'Fifty',
        '60' => 'Sixty',
        '70' => 'Seventy',
        '80' => 'Eighty',
        '90' => 'Ninety'
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
            $str[] = ($number < 21) ? $words[$number] .
                " " . $digits[$counter] . $plural . " " . $hundred
                :
                $words[floor($number / 10) * 10]
                . " " . $words[$number % 10] . " "
                . $digits[$counter] . $plural . " " . $hundred;
        } else
            $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    $points = ($point) ?
        "." . $words[$point / 10] . " " .
        $words[$point = $point % 10] : '';
    return $result . "Rupees Only";
}

$course_name = $payment['course_name'] ?? 'Training Course';
$course_fees = $payment['course_total_fees'] ?? $payment['amount'];
$total_received = $payment['total_received'];
$balance = $course_fees - $total_received;
$invoice_date = date('d/m/Y', strtotime($payment['payment_date']));
$due_date = date('d/m/Y', strtotime($payment['payment_date'] . ' + 30 days'));

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Bill of Supply - <?php echo $payment['receipt_no']; ?></title>
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
            align-items: flex-end;
        }

        .total-row {
            display: flex;
            justify-content: flex-end;
            width: 100%;
            max-width: 300px;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .total-label {
            width: 180px;
            text-align: right;
            padding-right: 20px;
            font-weight: 600;
            color: #555;
        }

        .total-value {
            width: 120px;
            text-align: right;
            font-weight: 600;
        }

        .grand-total {
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
            padding: 5px 0;
            margin: 5px 0;
        }

        .amount-in-words {
            text-align: right;
            font-size: 12px;
            margin-top: 15px;
        }

        .amount-in-words p {
            margin: 0;
        }

        .amount-in-words .words {
            font-weight: 600;
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
                <i class="fas fa-print me-2"></i>Print Bill
            </button>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="fees.php" class="btn btn-outline-secondary px-4 shadow-sm">
                    <i class="fas fa-arrow-left me-2"></i>Back to Fees
                </a>
            <?php else: ?>
                <a href="../student/fees.php" class="btn btn-outline-secondary px-4 shadow-sm">
                    <i class="fas fa-arrow-left me-2"></i>Back to My Fees
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="bill-container">
        <div class="bill-header">
            <div class="bill-of-supply">
                <span>BILL OF SUPPLY</span>
                <div class="original-tag">ORIGINAL FOR RECIPIENT</div>
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

        <div class="invoice-details-bar">
            <div class="detail-item"><strong>Invoice No.:</strong> <?php echo $payment['receipt_no']; ?></div>
            <div class="detail-item"><strong>Invoice Date:</strong> <?php echo $invoice_date; ?></div>
            <div class="detail-item"><strong>Due Date:</strong> <?php echo $due_date; ?></div>
        </div>

        <div class="bill-to-section">
            <h6>BILL TO</h6>
            <div class="customer-name"><?php echo htmlspecialchars($payment['full_name']); ?></div>
            <div class="customer-phone">Mobile: <?php echo $payment['phone'] ?? 'N/A'; ?></div>
        </div>

        <table class="table services-table">
            <thead>
                <tr>
                    <th>SERVICES</th>
                    <th class="text-end">DISC.</th>
                    <th class="text-end">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo strtoupper($course_name); ?> ( OFFLINE )</td>
                    <td class="text-end">0.00</td>
                    <td class="text-end"><?php echo number_format($payment['amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="subtotal-bar">
            <span>SUBTOTAL</span>
            <div class="d-flex gap-5">
                <span>₹ 0.00</span>
                <span>₹ <?php echo number_format($payment['amount'], 2); ?></span>
            </div>
        </div>

        <div class="totals-section">
            <div class="total-row">
                <div class="total-label">TAXABLE AMOUNT</div>
                <div class="total-value">₹ <?php echo number_format($payment['amount'], 2); ?></div>
            </div>
            <div class="total-row grand-total">
                <div class="total-label">TOTAL AMOUNT</div>
                <div class="total-value">₹ <?php echo number_format($payment['amount'], 2); ?></div>
            </div>
            <div class="total-row">
                <div class="total-label">Received Amount</div>
                <div class="total-value">₹ <?php echo number_format($total_received, 2); ?></div>
            </div>
            <div class="total-row text-danger">
                <div class="total-label">Pending Balance</div>
                <div class="total-value">₹ <?php echo number_format($balance, 2); ?></div>
            </div>

            <div class="amount-in-words">
                <p>Total Amount (in words)</p>
                <p class="words"><?php echo numberToWords($payment['amount']); ?></p>
            </div>
        </div>

        <?php if (!empty($pending_installments)): ?>
            <div class="installments-section px-5 mb-4">
                <h6 class="fw-bold text-uppercase border-bottom pb-2 mb-3" style="font-size: 13px; color: #800080;">Upcoming
                    Installment Schedule</h6>
                <div class="row g-3">
                    <?php foreach ($pending_installments as $inst): ?>
                        <div class="col-6">
                            <div class="p-3 border rounded bg-light">
                                <div class="small text-muted mb-1">Installment #<?php echo $inst['installment_no']; ?></div>
                                <div class="fw-bold fs-5">₹ <?php echo number_format($inst['amount'], 2); ?></div>
                                <div class="small text-primary">Due: <?php echo date('d M Y', strtotime($inst['due_date'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="signature-section">

            <div class="signature-placeholder">
                <!-- Signature Image Placeholder -->
                <img src="https://upload.wikimedia.org/wikipedia/commons/3/3a/Jon_Kirsch%27s_Signature.png"
                    class="signature-img" style="opacity: 0.7; filter: grayscale(1);">
            </div>
            <div class="signature-label">AUTHORISED SIGNATORY FOR</div>
            <div class="signature-company">TechnoHacks Solutions</div>
        </div>
    </div>
</body>

</html>