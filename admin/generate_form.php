<?php
require_once '../includes/auth.php';
checkAuth('admin');
require_once '../config/db.php';

$student_id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT sb.*, pd.*, e.*, sf.* FROM students_basic sb 
                      LEFT JOIN personal_details pd ON sb.student_id = pd.student_id 
                      LEFT JOIN education e ON sb.student_id = e.student_id 
                      LEFT JOIN student_fees sf ON sb.student_id = sf.student_id 
                      WHERE sb.student_id = ?");
$stmt->execute([$student_id]);
$s = $stmt->fetch();
if (!$s) exit("Not found");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admission Form - <?php echo $student_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 14px; }
        .receipt-container { border: 2px solid #333; padding: 30px; border-radius: 10px; max-width: 800px; margin: auto; }
        .logo { height: 60px; }
        .signature { margin-top: 50px; border-top: 1px solid #333; display: inline-block; width: 200px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body class="bg-light p-4">
    <div class="receipt-container bg-white shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <img src="../assets/img/logo.png" class="logo">
            <div class="text-end">
                <h4 class="fw-bold mb-0">TechnoHacks Solutions</h4>
                <p class="text-muted small">Training Institute & Placement Cell</p>
            </div>
        </div>
        
        <h5 class="text-center bg-dark text-white p-2 mb-4">APPLICATION FOR ADMISSION</h5>
        
        <div class="row mb-4">
            <div class="col-8">
                <p><strong>Enrollment No:</strong> <?php echo $s['student_id']; ?></p>
                <p><strong>Candidate Name:</strong> <?php echo $s['full_name']; ?></p>
                <p><strong>Father's Name:</strong> <?php echo $s['father_name']; ?></p>
                <p><strong>Date of Birth:</strong> <?php echo date('d M, Y', strtotime($s['dob'])); ?></p>
            </div>
            <div class="col-4 text-center">
                <div class="border" style="width: 120px; height: 150px; margin: auto; line-height: 150px;">PHOTO</div>
            </div>
        </div>
        
        <h6 class="fw-bold border-bottom pb-2">Academic Details</h6>
        <p><strong>Qualification:</strong> <?php echo $s['qualification']; ?> (<?php echo $s['status']; ?>)</p>
        <p><strong>College:</strong> <?php echo $s['college_name']; ?></p>
        
        <h6 class="fw-bold border-bottom pb-2 mt-4">Selected Course</h6>
        <p><strong>Course:</strong> <?php echo $s['course']; ?></p>
        
        <div class="mt-5 d-flex justify-content-between">
            <div class="text-center">
                <div class="signature"></div>
                <p class="small mt-1">Student Signature</p>
            </div>
            <div class="text-center">
                <div class="signature"></div>
                <p class="small mt-1">CEO Signature</p>
            </div>
        </div>
        
        <div class="mt-5 text-center no-print">
            <button onclick="window.print()" class="btn btn-primary btn-sm">Print Form / Save PDF</button>
        </div>
    </div>
</body>
</html>
