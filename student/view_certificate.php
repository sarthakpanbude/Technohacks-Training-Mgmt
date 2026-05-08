<?php
require_once '../includes/auth.php';
// Allow both admin and student to view certificates
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/db.php';

$id = $_GET['id'] ?? 0;

// Fetch certificate details
$stmt = $pdo->prepare("SELECT c.*, u.full_name, co.name as course_name, b.batch_name 
                       FROM certificates c 
                       JOIN enrollments e ON c.enrollment_id = e.id 
                       JOIN students s ON e.student_id = s.id 
                       JOIN users u ON s.user_id = u.id 
                       JOIN batches b ON e.batch_id = b.id 
                       JOIN courses co ON b.course_id = co.id 
                       WHERE c.id = ?");
$stmt->execute([$id]);
$cert = $stmt->fetch();

if (!$cert) {
    die("Certificate not found.");
}

// Security: Students can only view their own certificates
if ($_SESSION['role'] == 'student') {
    $studentCheck = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $studentCheck->execute([$_SESSION['user_id']]);
    $student = $studentCheck->fetch();
    
    // We need to check if this certificate belongs to this student
    $ownerCheck = $pdo->prepare("SELECT e.student_id FROM certificates c JOIN enrollments e ON c.enrollment_id = e.id WHERE c.id = ?");
    $ownerCheck->execute([$id]);
    $owner = $ownerCheck->fetch();
    
    if ($owner['student_id'] != $student['id']) {
        die("Unauthorized access.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?php echo $cert['certificate_no']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@400;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --cert-gold: #d4af37;
            --cert-navy: #0a192f;
        }
        body {
            background: #f8fafc;
            padding: 50px 0;
            font-family: 'Montserrat', sans-serif;
        }
        .certificate-container {
            width: 1000px;
            height: 700px;
            background: #fff;
            margin: 0 auto;
            position: relative;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            border: 20px solid var(--cert-navy);
            background-image: url('https://www.transparenttextures.com/patterns/white-paper.png');
        }
        .certificate-inner {
            border: 5px solid var(--cert-gold);
            height: 100%;
            padding: 60px;
            text-align: center;
            position: relative;
        }
        .cert-logo {
            max-height: 80px;
            margin-bottom: 30px;
        }
        .cert-title {
            font-family: 'Playfair Display', serif;
            font-size: 54px;
            color: var(--cert-navy);
            text-transform: uppercase;
            letter-spacing: 5px;
            margin-bottom: 10px;
        }
        .cert-subtitle {
            font-size: 20px;
            color: var(--cert-gold);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 40px;
            font-weight: 700;
        }
        .presented-to {
            font-size: 18px;
            font-style: italic;
            margin-bottom: 10px;
        }
        .student-name {
            font-family: 'Great Vibes', cursive;
            font-size: 64px;
            color: var(--cert-navy);
            margin-bottom: 20px;
        }
        .cert-text {
            font-size: 18px;
            line-height: 1.6;
            color: #444;
            max-width: 700px;
            margin: 0 auto 40px;
        }
        .course-name {
            color: var(--cert-navy);
            font-weight: 700;
            text-decoration: underline;
        }
        .cert-footer {
            position: absolute;
            bottom: 60px;
            left: 60px;
            right: 60px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .signature-box {
            width: 200px;
            border-top: 2px solid #333;
            padding-top: 10px;
        }
        .signature-name {
            font-weight: 700;
            font-size: 14px;
            color: var(--cert-navy);
        }
        .signature-title {
            font-size: 12px;
            color: #666;
        }
        .cert-no {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 10px;
            color: #999;
        }
        .qr-code-placeholder {
            width: 80px;
            height: 80px;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            border: 1px solid #ddd;
        }
        
        @media print {
            body { padding: 0; background: none; }
            .no-print { display: none; }
            .certificate-container { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>

    <div class="container text-center mb-4 no-print">
        <button onclick="window.print()" class="btn btn-primary px-4 py-2 rounded-pill fw-bold shadow">
            <i class="fas fa-print me-2"></i>Print Certificate
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-bold ms-2">
            Close
        </button>
    </div>

    <div class="certificate-container">
        <div class="certificate-inner">
            <img src="../assets/img/logo.png" alt="TechnoHacks Solutions" class="cert-logo">
            
            <div class="cert-title">Certificate</div>
            <div class="cert-subtitle">of Completion</div>
            
            <div class="presented-to">This certificate is proudly presented to</div>
            <div class="student-name"><?php echo $cert['full_name']; ?></div>
            
            <div class="cert-text">
                for successfully completing the professional training program in 
                <span class="course-name"><?php echo $cert['course_name']; ?></span> 
                conducted by TechnoHacks Solutions. This student has demonstrated exceptional dedication and proficiency throughout the course duration.
            </div>
            
            <div class="cert-footer">
                <div class="text-start">
                    <div class="qr-code-placeholder">
                        QR CODE<br>VERIFIED
                    </div>
                    <div class="mt-2 small text-muted">Issued: <?php echo date('d M Y', strtotime($cert['issued_date'])); ?></div>
                </div>
                
                <div class="signature-box text-center">
                    <div class="signature-name">Sandeep Panbude</div>
                    <div class="signature-title">CEO, TechnoHacks Solutions</div>
                </div>
            </div>
            
            <div class="cert-no">Certificate ID: <?php echo $cert['certificate_no']; ?></div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
