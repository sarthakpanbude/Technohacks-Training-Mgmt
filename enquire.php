<?php
require_once 'config/db.php';

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $course = $_POST['course'];
    $message = $_POST['message'];

    try {
        $stmt = $pdo->prepare("INSERT INTO visitors (name, email, phone, course_interest, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $course, $message]);
        $success = "Thank you for your interest! Our counselor will contact you soon.";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquire Now | TechnoHacks Solutions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-wrapper">

    <div class="auth-card animate-fade-in" style="max-width: 550px;">
        <div class="auth-logo">
            <i class="fas fa-microchip me-2"></i>TechnoHacks Solutions
        </div>
        <h4 class="text-center mb-4" style="font-weight: 700;">Visitor Enquiry Form</h4>
        
        <?php if ($success): ?>
            <div class="alert alert-success text-center">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
            <div class="text-center"><a href="index.php" class="btn btn-primary px-4">Back to Home</a></div>
        <?php else: ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter your name" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="your@email.com">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" placeholder="9876543210" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Course Interest</label>
                    <select name="course" class="form-control" required>
                        <option value="">Select a course</option>
                        <option value="Full Stack Web Development">Full Stack Web Development</option>
                        <option value="Data Science & ML">Data Science & ML</option>
                        <option value="Python Core & Advanced">Python Core & Advanced</option>
                        <option value="Java Enterprise Edition">Java Enterprise Edition</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold">Your Message (Optional)</label>
                    <textarea name="message" class="form-control" rows="3" placeholder="Tell us about your career goals..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                    <i class="fas fa-paper-plane me-2"></i>Submit Enquiry
                </button>
                <div class="text-center mt-3 small">
                    <span class="text-muted">Already decided?</span> 
                    <a href="student/apply.php" class="text-primary fw-bold text-decoration-none">Apply Directly</a>
                </div>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>
