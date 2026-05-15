CREATE TABLE IF NOT EXISTS `course_enrollments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `course_id` INT(11) NOT NULL,
  `enrollment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Active', 'Completed', 'Dropped') DEFAULT 'Active',
  `batch` VARCHAR(100) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  INDEX `idx_student_course` (`student_id`, `course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `enrollment_fees` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` INT(11) NOT NULL,
  `total_fee` DECIMAL(10,2) NOT NULL,
  `paid_amount` DECIMAL(10,2) DEFAULT 0.00,
  `status` ENUM('Pending', 'Partial', 'Paid') DEFAULT 'Pending',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`enrollment_id`) REFERENCES `course_enrollments`(`id`) ON DELETE CASCADE,
  INDEX `idx_enrollment_fee` (`enrollment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
