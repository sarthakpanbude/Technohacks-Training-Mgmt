USE technohacks_erp;
INSERT IGNORE INTO courses (name, duration, fees) VALUES ('Full Stack Web Development', '6 Months', 25000), ('Data Science with Python', '4 Months', 15000);
INSERT IGNORE INTO users (username, password, role, email, full_name) VALUES ('demo_student', '$2y$10$qyuP0Q.pVm/BsKsyuriMLeNQVu.hkbcW77QryN0FV4FCegj8T2vbW', 'student', 'studentdemo@technohacks.co.in', 'Rahul Sharma (Student)');
INSERT IGNORE INTO users (username, password, role, email, full_name) VALUES ('demo_teacher', '$2y$10$qyuP0Q.pVm/BsKsyuriMLeNQVu.hkbcW77QryN0FV4FCegj8T2vbW', 'teacher', 'teacherdemo@technohacks.co.in', 'Amit Sir (Teacher)');

INSERT IGNORE INTO students (user_id, enrollment_no, phone, admission_status) 
SELECT id, CONCAT('TH', id), '9999999999', 'approved' FROM users WHERE username = 'demo_student';

INSERT IGNORE INTO teachers (user_id) 
SELECT id FROM users WHERE username = 'demo_teacher';

INSERT IGNORE INTO batches (batch_name, course_id, teacher_id, schedule, capacity, status) 
SELECT 'FSWD Morning', (SELECT id FROM courses LIMIT 1), (SELECT id FROM teachers LIMIT 1), 'Morning 10 AM', 30, 'active' LIMIT 1;

INSERT IGNORE INTO enrollments (student_id, batch_id, status) 
SELECT (SELECT id FROM students WHERE user_id=(SELECT id FROM users WHERE username='demo_student')), (SELECT id FROM batches LIMIT 1), 'active';

INSERT IGNORE INTO payments (student_id, receipt_no, amount, payment_type, payment_method) 
SELECT (SELECT id FROM students WHERE user_id=(SELECT id FROM users WHERE username='demo_student')), 'RCP12345', 10000, 'admission_fee', 'upi';
