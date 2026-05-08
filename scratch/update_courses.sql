ALTER TABLE courses ADD COLUMN course_type ENUM('Internship', 'Training') DEFAULT 'Internship' AFTER id;
ALTER TABLE courses MODIFY COLUMN level VARCHAR(50) DEFAULT 'beginner';

-- Update all existing courses to be Internship
UPDATE courses SET course_type = 'Internship';

-- Insert new Training courses
INSERT INTO courses (course_type, name, duration, fees, level) VALUES 
('Training', 'Data Science with AI', '6 months', 35000, 'Advanced'),
('Training', 'Full Stack Development', '6 months', 35000, 'Advanced'),
('Training', 'Data Analytics', '3 to 4 months', 24000, 'Full Course'),
('Training', 'Digital Marketing', '3 to 4 months', 24000, 'Full Course'),
('Training', 'Cyber Security', '3 to 4 months', 24000, 'Full Course'),
('Training', 'All programming languages', '1 to 1.5 months', 5000, 'Small Course'),
('Training', 'Diploma industrial training', '1 to 1.5 months', 5000, 'Small Course');
