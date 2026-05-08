DELETE FROM courses WHERE name = 'All programming languages' AND course_type = 'Training';

INSERT INTO courses (course_type, name, duration, fees, level) VALUES 
('Training', 'Python Programming', '1.5 months', 5000, 'Small Course'),
('Training', 'Java Programming', '1.5 months', 5000, 'Small Course'),
('Training', 'C Programming', '1.5 months', 5000, 'Small Course'),
('Training', 'C++ Programming', '1.5 months', 5000, 'Small Course'),
('Training', 'JavaScript Programming', '1.5 months', 5000, 'Small Course'),
('Training', 'PHP Programming', '1.5 months', 5000, 'Small Course');
