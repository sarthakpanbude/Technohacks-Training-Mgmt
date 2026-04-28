-- TechnoHacks Solutions Institute Management System Database Schema

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

-- Table: users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin', 'teacher', 'student') NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `full_name` varchar(100) NOT NULL,
  `profile_pic` varchar(255) DEFAULT 'default.png',
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: students
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `enrollment_no` varchar(20) DEFAULT NULL UNIQUE,
  `dob` date DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `referral_code` varchar(10) DEFAULT NULL UNIQUE,
  `referred_by` varchar(10) DEFAULT NULL,
  `admission_status` enum('pending', 'verified', 'approved', 'enrolled', 'completed', 'placed', 'dropped') DEFAULT 'pending',
  `document_status` enum('pending', 'verified', 'rejected') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: teachers
CREATE TABLE `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: courses
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `fees` decimal(10,2) NOT NULL,
  `level` enum('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: batches
CREATE TABLE `batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `batch_name` varchar(50) NOT NULL,
  `schedule` varchar(100) DEFAULT NULL,
  `capacity` int(11) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('upcoming', 'active', 'completed') DEFAULT 'upcoming',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: enrollments
CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `enrollment_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active', 'completed', 'dropped') DEFAULT 'active',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`batch_id`) REFERENCES `batches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: payments
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `payment_type` enum('full', 'installment', 'late_fee') DEFAULT 'installment',
  `payment_method` varchar(50) DEFAULT 'offline',
  `receipt_no` varchar(50) NOT NULL UNIQUE,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: attendance
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present', 'absent', 'late', 'leave') DEFAULT 'present',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`batch_id`) REFERENCES `batches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: assignments
CREATE TABLE `assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`batch_id`) REFERENCES `batches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: submissions
CREATE TABLE `submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `github_link` varchar(255) DEFAULT NULL,
  `status` enum('submitted', 'reviewed', 'approved', 'rejected') DEFAULT 'submitted',
  `marks` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `submitted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: exams
CREATE TABLE `exams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) NOT NULL COMMENT 'in minutes',
  `total_marks` int(11) NOT NULL,
  `status` enum('pending', 'active', 'completed') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`batch_id`) REFERENCES `batches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: tickets
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('low', 'medium', 'high', 'critical') DEFAULT 'medium',
  `status` enum('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: notifications
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: certificates
CREATE TABLE `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) NOT NULL,
  `certificate_no` varchar(50) NOT NULL UNIQUE,
  `qr_code` varchar(255) DEFAULT NULL,
  `issued_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: placements
CREATE TABLE `placements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `company` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `status` enum('applied', 'interview', 'placed', 'rejected') DEFAULT 'applied',
  `package` varchar(50) DEFAULT NULL,
  `applied_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: visitors
CREATE TABLE `visitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `course_interest` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `mode` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'new',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: installments
CREATE TABLE `installments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `installment_no` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `late_fee` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: invoices
CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `receipt_no` varchar(50) NOT NULL UNIQUE,
  `amount` decimal(10,2) NOT NULL,
  `payment_mode` varchar(50) NOT NULL,
  `payment_date` timestamp DEFAULT current_timestamp(),
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dummy Admin Account (Password: admin123)
INSERT IGNORE INTO `users` (`username`, `password`, `role`, `email`, `full_name`) VALUES
('admin', '$2y$10$qyuP0Q.pVm/BsKsyuriMLeNQVu.hkbcW77QryN0FV4FCegj8T2vbW', 'admin', 'admin@technohacks.com', 'System Administrator');

-- Pre-fill Courses
INSERT IGNORE INTO courses (name, fees, level) VALUES 
('Networking & Linux', 15000, 'beginner'), 
('Prompt Engineering', 12000, 'intermediate'), 
('Software Testing', 15000, 'beginner'), 
('UI/UX Designing', 18000, 'beginner'), 
('Web Design & Development', 20000, 'beginner'), 
('Human Resource (HR)', 10000, 'beginner'), 
('Finance & Accounting', 12000, 'beginner'), 
('Python Development', 15000, 'beginner'), 
('App Development', 22000, 'intermediate'), 
('Blockchain', 25000, 'advanced'), 
('Business Analytics', 20000, 'intermediate'), 
('Business Development', 15000, 'beginner'), 
('C & C++ Programming', 10000, 'beginner'), 
('Cloud Computing', 20000, 'intermediate'), 
('Cybersecurity', 22000, 'intermediate'), 
('Data Analytics', 18000, 'intermediate'), 
('Data Science', 25000, 'advanced'), 
('DevOps', 22000, 'advanced'), 
('Digital Marketing', 15000, 'beginner'), 
('Full Stack Development', 30000, 'intermediate'), 
('Graphics Designing', 12000, 'beginner'), 
('Java Development', 18000, 'intermediate'), 
('Machine Learning', 28000, 'advanced');

COMMIT;
