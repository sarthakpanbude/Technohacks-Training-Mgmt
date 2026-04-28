<?php
session_start();
require_once 'config/db.php';

$message = "";
$status = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $domain = $_POST['domain'];
    $type = $_POST['type'];
    $mode = $_POST['mode'];

    try {
        $stmt = $pdo->prepare("INSERT INTO visitors (name, phone, email, gender, age, course_interest, type, mode, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new')");
        $stmt->execute([$name, $phone, $email, $gender, $age, $domain, $type, $mode]);
        $message = "Your inquiry has been submitted successfully! Our team will contact you soon.";
        $status = "success";
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $status = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Inquiry Form | TechnoHacks Solutions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .inquiry-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .inquiry-card {
            background: white;
            border-radius: 24px;
            padding: 2.5rem;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            border-color: #4f46e5;
        }
    </style>
</head>
<body class="inquiry-wrapper">

    <div class="inquiry-card animate-fade-in">
        <div class="text-center mb-4">
            <img src="assets/img/logo.png" alt="Logo" style="max-height: 80px; margin-bottom: 1rem;">
            <h3 class="fw-bold">Course Inquiry Form</h3>
            <p class="text-muted">Fill in your details to start your career with TechnoHacks.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $status; ?> border-0 shadow-sm mb-4">
                <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
                <div class="mt-2 d-flex gap-2">
                    <a href="inquiry.php" class="btn btn-sm btn-<?php echo $status; ?> rounded-pill px-3">Submit Another</a>
                    <a href="index.php" class="btn btn-sm btn-outline-<?php echo $status; ?> rounded-pill px-3">Back to Login</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($status != "success"): ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" placeholder="+91 00000 00000" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="john@example.com">
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold">Gender</label>
                    <select name="gender" class="form-select" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold">Age</label>
                    <input type="number" name="age" class="form-control" placeholder="18" required>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-bold">Select Domain</label>
                    <select name="domain" class="form-select" required>
                        <option value="">Choose your course interest...</option>
                        <?php
                        $courses_query = $pdo->query("SELECT course_name FROM courses ORDER BY course_name ASC");
                        while($course = $courses_query->fetch()) {
                            echo "<option value=\"".htmlspecialchars($course['course_name'])."\">".htmlspecialchars($course['course_name'])."</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold">Inquiry Type</label>
                    <select name="type" class="form-select" required>
                        <option value="Course">Course Admission</option>
                        <option value="Internship">Internship Program</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold">Preferred Mode</label>
                    <select name="mode" class="form-select" required>
                        <option value="Online">Online (Virtual)</option>
                        <option value="Offline">Offline (At Center)</option>
                    </select>
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill shadow fw-bold">
                        SUBMIT INQUIRY <i class="fas fa-paper-plane ms-2"></i>
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <div class="text-center mt-4 pt-3 border-top">
            <a href="index.php" class="text-muted text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Back to Login
            </a>
        </div>
    </div>

</body>
</html>
