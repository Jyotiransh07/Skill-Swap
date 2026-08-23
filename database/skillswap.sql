-- =================================================================
-- SkillSwap Campus - MySQL Database Schema & Seed Data
-- Database: skillswap
-- Target Platform: Windows / WAMP / phpMyAdmin / MySQL 5.7+ & 8.0+
-- =================================================================

CREATE DATABASE IF NOT EXISTS `skillswap` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `skillswap`;

-- Disable Foreign Key checks for clean import
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------
-- Drop Existing Tables & Objects
-- -----------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_after_review_insert`;
DROP TRIGGER IF EXISTS `trg_after_session_completed`;
DROP PROCEDURE IF EXISTS `sp_calculate_reputation`;
DROP PROCEDURE IF EXISTS `sp_get_smart_matches`;
DROP VIEW IF EXISTS `vw_student_reputation`;
DROP VIEW IF EXISTS `vw_skill_demand_supply`;

DROP TABLE IF EXISTS `reports`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `learning_requests`;
DROP TABLE IF EXISTS `user_skills`;
DROP TABLE IF EXISTS `skills`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------
-- Table 1: USERS
-- -----------------------------------------------------------------
CREATE TABLE `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `college_id` VARCHAR(50) NOT NULL UNIQUE,
  `department` VARCHAR(100) NOT NULL,
  `year` ENUM('1st Year', '2nd Year', '3rd Year', '4th Year', 'Postgraduate') NOT NULL DEFAULT '1st Year',
  `bio` TEXT DEFAULT NULL,
  `profile_image` VARCHAR(255) DEFAULT 'default-avatar.png',
  `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `reputation_score` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `chk_reputation_range` CHECK (`reputation_score` >= 0 AND `reputation_score` <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------
-- Table 2: SKILLS
-- -----------------------------------------------------------------
CREATE TABLE `skills` (
  `skill_id` INT AUTO_INCREMENT PRIMARY KEY,
  `skill_name` VARCHAR(100) NOT NULL UNIQUE,
  `category` VARCHAR(50) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------
-- Table 3: USER_SKILLS
-- -----------------------------------------------------------------
CREATE TABLE `user_skills` (
  `user_skill_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `skill_id` INT NOT NULL,
  `skill_type` ENUM('TEACH', 'LEARN') NOT NULL,
  `skill_level` ENUM('BEGINNER', 'INTERMEDIATE', 'ADVANCED', 'EXPERT') NOT NULL DEFAULT 'INTERMEDIATE',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE,
  UNIQUE KEY `uk_user_skill_type` (`user_id`, `skill_id`, `skill_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------
-- Table 4: LEARNING_REQUESTS
-- -----------------------------------------------------------------
CREATE TABLE `learning_requests` (
  `request_id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `skill_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('PENDING', 'ACCEPTED', 'REJECTED', 'CANCELLED', 'COMPLETED') NOT NULL DEFAULT 'PENDING',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE,
  CONSTRAINT `chk_sender_receiver_diff` CHECK (`sender_id` <> `receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------
-- Table 5: SESSIONS
-- -----------------------------------------------------------------
CREATE TABLE `sessions` (
  `session_id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_id` INT NOT NULL,
  `teacher_id` INT NOT NULL,
  `learner_id` INT NOT NULL,
  `skill_id` INT NOT NULL,
  `session_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `mode` ENUM('ONLINE', 'OFFLINE') NOT NULL DEFAULT 'ONLINE',
  `meeting_link` VARCHAR(255) DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('SCHEDULED', 'ONGOING', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'SCHEDULED',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`request_id`) REFERENCES `learning_requests` (`request_id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`learner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------
-- Table 6: REVIEWS
-- -----------------------------------------------------------------
CREATE TABLE `reviews` (
  `review_id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT NOT NULL,
  `reviewer_id` INT NOT NULL,
  `reviewee_id` INT NOT NULL,
  `rating` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewee_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `chk_rating_range` CHECK (`rating` >= 1 AND `rating` <= 5),
  UNIQUE KEY `uk_session_reviewer` (`session_id`, `reviewer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------
-- Table 7: NOTIFICATIONS
-- -----------------------------------------------------------------
CREATE TABLE `notifications` (
  `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('REQUEST', 'REQUEST_ACCEPTED', 'SESSION', 'REVIEW', 'SYSTEM') NOT NULL DEFAULT 'SYSTEM',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------
-- Table 8: REPORTS
-- -----------------------------------------------------------------
CREATE TABLE `reports` (
  `report_id` INT AUTO_INCREMENT PRIMARY KEY,
  `reported_by` INT NOT NULL,
  `reported_user` INT NOT NULL,
  `reason` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('PENDING', 'REVIEWED', 'RESOLVED', 'DISMISSED') NOT NULL DEFAULT 'PENDING',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`reported_user`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------
-- Indexes for High Efficiency Searching & Filtering
-- -----------------------------------------------------------------
CREATE INDEX `idx_user_skills_user` ON `user_skills` (`user_id`);
CREATE INDEX `idx_user_skills_skill` ON `user_skills` (`skill_id`);
CREATE INDEX `idx_user_skills_type` ON `user_skills` (`skill_type`);
CREATE INDEX `idx_requests_sender` ON `learning_requests` (`sender_id`);
CREATE INDEX `idx_requests_receiver` ON `learning_requests` (`receiver_id`);
CREATE INDEX `idx_requests_status` ON `learning_requests` (`status`);
CREATE INDEX `idx_sessions_teacher` ON `sessions` (`teacher_id`);
CREATE INDEX `idx_sessions_learner` ON `sessions` (`learner_id`);
CREATE INDEX `idx_sessions_status` ON `sessions` (`status`);
CREATE INDEX `idx_notifications_user` ON `notifications` (`user_id`, `is_read`);

-- -----------------------------------------------------------------
-- DBMS Feature 1: SQL VIEWS
-- -----------------------------------------------------------------

-- View 1: Student Reputation Summary
CREATE OR REPLACE VIEW `vw_student_reputation` AS
SELECT 
    u.user_id,
    u.name,
    u.email,
    u.department,
    u.year,
    u.reputation_score,
    COALESCE(AVG(r.rating), 0) AS avg_rating,
    COUNT(DISTINCT r.review_id) AS total_reviews,
    (SELECT COUNT(*) FROM sessions s WHERE s.teacher_id = u.user_id AND s.status = 'COMPLETED') AS completed_as_teacher,
    (SELECT COUNT(*) FROM sessions s WHERE s.learner_id = u.user_id AND s.status = 'COMPLETED') AS completed_as_learner,
    (SELECT COUNT(*) FROM learning_requests lr WHERE (lr.sender_id = u.user_id OR lr.receiver_id = u.user_id) AND lr.status = 'COMPLETED') AS total_exchanges
FROM users u
LEFT JOIN reviews r ON u.user_id = r.reviewee_id
WHERE u.role = 'student'
GROUP BY u.user_id;

-- View 2: Skill Demand and Supply Analysis
CREATE OR REPLACE VIEW `vw_skill_demand_supply` AS
SELECT 
    s.skill_id,
    s.skill_name,
    s.category,
    SUM(CASE WHEN us.skill_type = 'TEACH' THEN 1 ELSE 0 END) AS teachers_count,
    SUM(CASE WHEN us.skill_type = 'LEARN' THEN 1 ELSE 0 END) AS learners_count,
    COUNT(DISTINCT lr.request_id) AS total_requests
FROM skills s
LEFT JOIN user_skills us ON s.skill_id = us.skill_id
LEFT JOIN learning_requests lr ON s.skill_id = lr.skill_id
GROUP BY s.skill_id;

-- -----------------------------------------------------------------
-- DBMS Feature 2: STORED PROCEDURES
-- -----------------------------------------------------------------

-- Procedure 1: Recalculate User Reputation Score
DELIMITER //
CREATE PROCEDURE `sp_calculate_reputation`(IN p_user_id INT)
BEGIN
    DECLARE v_avg_rating DECIMAL(3,2) DEFAULT 0.00;
    DECLARE v_completed_sessions INT DEFAULT 0;
    DECLARE v_total_exchanges INT DEFAULT 0;
    DECLARE v_new_score DECIMAL(5,2) DEFAULT 50.00;

    -- Calculate average rating (Out of 5)
    SELECT COALESCE(AVG(rating), 0) INTO v_avg_rating
    FROM reviews
    WHERE reviewee_id = p_user_id;

    -- Calculate completed sessions as teacher or learner
    SELECT COUNT(*) INTO v_completed_sessions
    FROM sessions
    WHERE (teacher_id = p_user_id OR learner_id = p_user_id) AND status = 'COMPLETED';

    -- Calculate completed skill exchanges
    SELECT COUNT(*) INTO v_total_exchanges
    FROM learning_requests
    WHERE (sender_id = p_user_id OR receiver_id = p_user_id) AND status = 'COMPLETED';

    -- Formula:
    -- 40% from rating (Rating/5 * 40)
    -- 40% from completed sessions (capped at 10 sessions for max 40 points)
    -- 20% from skill exchanges (capped at 5 exchanges for max 20 points)
    SET v_new_score = (v_avg_rating / 5.0 * 40.0) 
                    + (LEAST(v_completed_sessions, 10) / 10.0 * 40.0) 
                    + (LEAST(v_total_exchanges, 5) / 5.0 * 20.0);

    IF v_new_score > 100 THEN SET v_new_score = 100.00; END IF;
    IF v_new_score < 0 THEN SET v_new_score = 0.00; END IF;

    UPDATE users 
    SET reputation_score = v_new_score 
    WHERE user_id = p_user_id;
END //
DELIMITER ;

-- Procedure 2: Get Smart Match Candidates for a Student
DELIMITER //
CREATE PROCEDURE `sp_get_smart_matches`(IN p_user_id INT)
BEGIN
    SELECT 
        u.user_id,
        u.name,
        u.department,
        u.year,
        u.profile_image,
        u.reputation_score,
        teach_s.skill_name AS can_teach_skill,
        learn_s.skill_name AS wants_learn_skill
    FROM users u
    -- Skill B teaches that User wants to learn
    JOIN user_skills us_teach ON u.user_id = us_teach.user_id AND us_teach.skill_type = 'TEACH'
    JOIN skills teach_s ON us_teach.skill_id = teach_s.skill_id
    JOIN user_skills my_learn ON my_learn.user_id = p_user_id AND my_learn.skill_type = 'LEARN' AND my_learn.skill_id = teach_s.skill_id
    -- Skill B wants to learn that User can teach
    LEFT JOIN user_skills us_learn ON u.user_id = us_learn.user_id AND us_learn.skill_type = 'LEARN'
    LEFT JOIN skills learn_s ON us_learn.skill_id = learn_s.skill_id
    LEFT JOIN user_skills my_teach ON my_teach.user_id = p_user_id AND my_teach.skill_type = 'TEACH' AND my_teach.skill_id = learn_s.skill_id
    WHERE u.user_id <> p_user_id AND u.status = 'active'
    GROUP BY u.user_id, teach_s.skill_id;
END //
DELIMITER ;

-- -----------------------------------------------------------------
-- DBMS Feature 3: TRIGGERS
-- -----------------------------------------------------------------

-- Trigger 1: Auto-recalculate reputation after review insertion
DELIMITER //
CREATE TRIGGER `trg_after_review_insert`
AFTER INSERT ON `reviews`
FOR EACH ROW
BEGIN
    CALL sp_calculate_reputation(NEW.reviewee_id);
END //
DELIMITER ;

-- Trigger 2: Auto-recalculate reputation after session is completed
DELIMITER //
CREATE TRIGGER `trg_after_session_completed`
AFTER UPDATE ON `sessions`
FOR EACH ROW
BEGIN
    IF NEW.status = 'COMPLETED' AND OLD.status <> 'COMPLETED' THEN
        CALL sp_calculate_reputation(NEW.teacher_id);
        CALL sp_calculate_reputation(NEW.learner_id);
    END IF;
END //
DELIMITER ;

-- -----------------------------------------------------------------
-- SEED DATA
-- Default Password Hash for "Student@123": $2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm
-- Default Password Hash for "Admin@123":   $2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm
-- -----------------------------------------------------------------

-- Insert Admin Account
INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `college_id`, `department`, `year`, `bio`, `profile_image`, `role`, `status`, `reputation_score`) VALUES
(1, 'System Administrator', 'admin@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'ADM-001', 'Administration', 'Postgraduate', 'Platform Administrator & Quality Monitor', 'default-avatar.png', 'admin', 'active', 100.00);

-- Insert 20 Demo Students
INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `college_id`, `department`, `year`, `bio`, `profile_image`, `role`, `status`, `reputation_score`) VALUES
(2, 'Rahul Sharma', 'rahul@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'CS202301', 'Computer Engineering', '3rd Year', 'Passionate developer loving Python, C++ & algorithms. Wanting to polish my UI design skills!', 'default-avatar.png', 'student', 'active', 91.00),
(3, 'Priya Mehta', 'priya@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'EC202305', 'Electronics', '3rd Year', 'UI/UX enthusiast, Figma designer and Arduino hacker. Eager to master Python for IoT scripts.', 'default-avatar.png', 'student', 'active', 94.50),
(4, 'Aman Patil', 'aman@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'CS202312', 'Computer Science', '2nd Year', 'Full-stack web dev wizard working with Java & PHP. Seeking guidance on mobile app design.', 'default-avatar.png', 'student', 'active', 88.00),
(5, 'Sneha Kulkarni', 'sneha@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'EC202318', 'Electronics', '4th Year', 'Robotics and IoT tinkerer. Can teach Arduino & ESP32 in exchange for C++ algorithmic problem solving.', 'default-avatar.png', 'student', 'active', 96.00),
(6, 'Rohan Verma', 'rohan@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'ME202302', 'Mechanical Engineering', '3rd Year', 'CAD/CAM & 3D Modeling tutor. Looking to learn Python for data analysis.', 'default-avatar.png', 'student', 'active', 82.00),
(7, 'Ananya Sen', 'ananya@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'IT202309', 'Information Technology', '2nd Year', 'Graphic Designer & Video Editor. Wanting to learn JavaScript and Web Development basics.', 'default-avatar.png', 'student', 'active', 89.00),
(8, 'Vikram Deshmukh', 'vikram@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'CS202322', 'Computer Engineering', '4th Year', 'Competitive programmer proficient in C++ & Data Structures. Wanting to pick up Figma.', 'default-avatar.png', 'student', 'active', 95.00),
(9, 'Neha Kapoor', 'neha@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'AI202304', 'Artificial Intelligence', '1st Year', 'Machine Learning beginner teaching Content Creation & Technical Writing.', 'default-avatar.png', 'student', 'active', 78.00),
(10, 'Karan Joshi', 'karan@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'EE202311', 'Electrical Engineering', '3rd Year', 'Circuit design specialist. Looking to learn Machine Learning & Python.', 'default-avatar.png', 'student', 'active', 84.00),
(11, 'Tanvi Rao', 'tanvi@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'DS202308', 'Data Science', '2nd Year', 'Python & SQL analyst. Seeking peer help in Graphic Design & Photoshop.', 'default-avatar.png', 'student', 'active', 86.50),
(12, 'Aditya Roy', 'aditya@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'CS202340', 'Computer Science', '3rd Year', 'Cybersecurity enthusiast & Linux guru. Wanting to learn Video Editing.', 'default-avatar.png', 'student', 'active', 92.00),
(13, 'Ishita Nair', 'ishita@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'IT202315', 'Information Technology', '4th Year', 'Web accessibility & HTML/CSS expert. Want to master Java backend frameworks.', 'default-avatar.png', 'student', 'active', 90.00),
(14, 'Siddharth Saxena', 'siddharth@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'CE202303', 'Civil Engineering', '2nd Year', 'AutoCAD expert. Learning Python & Data Visualization for research.', 'default-avatar.png', 'student', 'active', 80.00),
(15, 'Riya Choudhury', 'riya@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'EC202325', 'Electronics', '1st Year', 'Photography & Light Room expert. Looking for peer tutors in C++.', 'default-avatar.png', 'student', 'active', 76.00),
(16, 'Manish Bhatia', 'manish@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'CS202333', 'Computer Engineering', '3rd Year', 'Java & Android development. Learning UI/UX design concepts.', 'default-avatar.png', 'student', 'active', 87.00),
(17, 'Pooja Hegde', 'pooja@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'AI202310', 'Artificial Intelligence', '4th Year', 'PyTorch & Neural Networks. Wants to learn Public Speaking & Presentation.', 'default-avatar.png', 'student', 'active', 95.00),
(18, 'Varun Malhotra', 'varun@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'ME202314', 'Mechanical Engineering', '1st Year', 'Matlab & SolidWorks. Wants to learn Web Development.', 'default-avatar.png', 'student', 'active', 72.00),
(19, 'Kavya Pillai', 'kavya@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'DS202319', 'Data Science', '3rd Year', 'R Programming & Tableau. Wants to learn Figma & Prototyping.', 'default-avatar.png', 'student', 'active', 85.00),
(20, 'Yash Gupta', 'yash@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'IT202328', 'Information Technology', '2nd Year', 'Cloud Computing & Docker. Wants to learn Video Editing & Motion Graphics.', 'default-avatar.png', 'student', 'active', 88.50),
(21, 'Divya Agarwal', 'divya@skillswap.edu', '$2y$10$OLkggg9fhKbqIrYLUiunQ.laLfXqJMs7fXGJ/S..fTdmXWAPAbMEm', 'CS202350', 'Computer Science', '4th Year', 'Full Stack PHP & MySQL mentor. Wants to learn AI Prompt Engineering.', 'default-avatar.png', 'student', 'active', 97.00);

-- Insert 25 Popular Skills
INSERT INTO `skills` (`skill_id`, `skill_name`, `category`, `description`) VALUES
(1, 'Python', 'Programming', 'General purpose programming language widely used in AI, Data Science & Backend.'),
(2, 'C++', 'Programming', 'Object-oriented language famous for system programming, competitive coding & OS.'),
(3, 'Java', 'Programming', 'Enterprise applications and Android app development language.'),
(4, 'JavaScript', 'Programming', 'Dynamic scripting language powering web interactivity and modern frontends.'),
(5, 'Web Development', 'Programming', 'Full stack HTML, CSS, JavaScript, PHP & MySQL web applications.'),
(6, 'UI/UX Design', 'Design', 'User Interface and User Experience research, wireframing & usability.'),
(7, 'Figma', 'Design', 'Industry standard collaborative UI design and interactive prototyping software.'),
(8, 'Graphic Design', 'Design', 'Visual design using Photoshop, Illustrator, vector assets & branding.'),
(9, 'Arduino', 'Hardware', 'Open-source electronics platform based on easy-to-use hardware & software.'),
(10, 'ESP32', 'Hardware', 'Feature-rich Wi-Fi & Bluetooth microcontroller chip for IoT projects.'),
(11, 'Robotics', 'Hardware', 'Design, construction, operation, and application of autonomous robots.'),
(12, 'IoT (Internet of Things)', 'Hardware', 'Connecting physical sensors & microcontrollers to cloud platforms.'),
(13, 'Video Editing', 'Creative', 'Premiere Pro, DaVinci Resolve, video cuts, color grading & audio syncing.'),
(14, 'Photography', 'Creative', 'DSLR camera operation, composition, lighting, and Lightroom editing.'),
(15, 'Content Creation', 'Creative', 'Blogging, technical writing, copy writing, and social media media assets.'),
(16, 'SQL & Databases', 'Programming', 'Relational database design, queries, normalization, views & procedures.'),
(17, 'Machine Learning', 'Programming', 'Supervised/unsupervised algorithms, Scikit-Learn, and data modeling.'),
(18, 'AutoCAD', 'Design', '2D drafting and 3D design software for civil & mechanical engineering.'),
(19, '3D Modeling', 'Design', 'Blender & SolidWorks modeling, texturing, and rendering.'),
(20, 'Linux Systems', 'Programming', 'Bash scripting, server administration, permissions, and command line.'),
(21, 'Cybersecurity', 'Programming', 'Ethical hacking, network security, penetration testing basics & encryption.'),
(22, 'R Programming', 'Programming', 'Statistical computing and data visualization language.'),
(23, 'Tableau', 'Design', 'Data visualization and business intelligence dashboard creation.'),
(24, 'Public Speaking', 'Creative', 'Presentation skills, speech delivery, body language & confidence.'),
(25, 'Docker & Cloud', 'Programming', 'Containerization, microservices deployment & AWS/GCP basics.');

-- Insert User Skills (TEACH & LEARN relationships)
-- Rahul Sharma (User 2): Can Teach Python, C++, Web Dev | Wants UI/UX, Figma
INSERT INTO `user_skills` (`user_id`, `skill_id`, `skill_type`, `skill_level`) VALUES
(2, 1, 'TEACH', 'EXPERT'),
(2, 2, 'TEACH', 'ADVANCED'),
(2, 5, 'TEACH', 'INTERMEDIATE'),
(2, 6, 'LEARN', 'BEGINNER'),
(2, 7, 'LEARN', 'BEGINNER');

-- Priya Mehta (User 3): Can Teach UI/UX, Figma, Arduino | Wants Python, C++
INSERT INTO `user_skills` (`user_id`, `skill_id`, `skill_type`, `skill_level`) VALUES
(3, 6, 'TEACH', 'EXPERT'),
(3, 7, 'TEACH', 'EXPERT'),
(3, 9, 'TEACH', 'INTERMEDIATE'),
(3, 1, 'LEARN', 'INTERMEDIATE'),
(3, 2, 'LEARN', 'BEGINNER');

-- Aman Patil (User 4): Can Teach Java, Web Dev | Wants Figma, UI/UX
INSERT INTO `user_skills` (`user_id`, `skill_id`, `skill_type`, `skill_level`) VALUES
(4, 3, 'TEACH', 'ADVANCED'),
(4, 5, 'TEACH', 'ADVANCED'),
(4, 7, 'LEARN', 'BEGINNER'),
(4, 6, 'LEARN', 'BEGINNER');

-- Sneha Kulkarni (User 5): Can Teach Robotics, Arduino, ESP32 | Wants C++, Python
INSERT INTO `user_skills` (`user_id`, `skill_id`, `skill_type`, `skill_level`) VALUES
(5, 9, 'TEACH', 'EXPERT'),
(5, 10, 'TEACH', 'EXPERT'),
(5, 11, 'TEACH', 'ADVANCED'),
(5, 2, 'LEARN', 'INTERMEDIATE'),
(5, 1, 'LEARN', 'BEGINNER');

-- Additional User Skill Mappings for standard pool
INSERT INTO `user_skills` (`user_id`, `skill_id`, `skill_type`, `skill_level`) VALUES
(6, 18, 'TEACH', 'EXPERT'), (6, 19, 'TEACH', 'ADVANCED'), (6, 1, 'LEARN', 'BEGINNER'),
(7, 8, 'TEACH', 'ADVANCED'), (7, 13, 'TEACH', 'EXPERT'), (7, 5, 'LEARN', 'BEGINNER'), (7, 4, 'LEARN', 'BEGINNER'),
(8, 2, 'TEACH', 'EXPERT'), (8, 16, 'TEACH', 'ADVANCED'), (8, 7, 'LEARN', 'BEGINNER'),
(9, 15, 'TEACH', 'ADVANCED'), (9, 1, 'LEARN', 'BEGINNER'), (9, 17, 'LEARN', 'BEGINNER'),
(10, 12, 'TEACH', 'ADVANCED'), (10, 1, 'LEARN', 'BEGINNER'), (10, 17, 'LEARN', 'BEGINNER'),
(11, 1, 'TEACH', 'ADVANCED'), (11, 16, 'TEACH', 'EXPERT'), (11, 8, 'LEARN', 'BEGINNER'),
(12, 20, 'TEACH', 'EXPERT'), (12, 21, 'TEACH', 'ADVANCED'), (12, 13, 'LEARN', 'BEGINNER'),
(13, 5, 'TEACH', 'EXPERT'), (13, 3, 'LEARN', 'INTERMEDIATE'),
(14, 18, 'TEACH', 'EXPERT'), (14, 1, 'LEARN', 'BEGINNER'),
(15, 14, 'TEACH', 'EXPERT'), (15, 2, 'LEARN', 'BEGINNER'),
(16, 3, 'TEACH', 'ADVANCED'), (16, 6, 'LEARN', 'BEGINNER'),
(17, 17, 'TEACH', 'EXPERT'), (17, 24, 'LEARN', 'BEGINNER'),
(18, 19, 'TEACH', 'ADVANCED'), (18, 5, 'LEARN', 'BEGINNER'),
(19, 22, 'TEACH', 'EXPERT'), (19, 23, 'TEACH', 'ADVANCED'), (19, 7, 'LEARN', 'BEGINNER'),
(20, 25, 'TEACH', 'ADVANCED'), (20, 13, 'LEARN', 'BEGINNER'),
(21, 5, 'TEACH', 'EXPERT'), (21, 16, 'TEACH', 'EXPERT'), (21, 17, 'LEARN', 'BEGINNER');

-- Insert Sample Learning Requests
INSERT INTO `learning_requests` (`request_id`, `sender_id`, `receiver_id`, `skill_id`, `message`, `status`, `created_at`) VALUES
(1, 2, 3, 6, 'Hi Priya! I saw you excel at UI/UX and Figma. I can teach you Python or C++ algorithms in return!', 'ACCEPTED', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 3, 2, 1, 'Hey Rahul! I would love to learn Python scripts for microcontrollers. Excited to swap UI/UX lessons with you!', 'ACCEPTED', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(3, 4, 3, 7, 'Hello Priya, looking to learn Figma prototyping for my web projects.', 'PENDING', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 5, 8, 2, 'Hi Vikram, can we exchange Arduino robotics tips for C++ data structures?', 'ACCEPTED', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(5, 7, 2, 5, 'Hey Rahul, I can edit your project videos if you help me learn Web Dev fundamentals!', 'PENDING', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Insert Sample Sessions
INSERT INTO `sessions` (`session_id`, `request_id`, `teacher_id`, `learner_id`, `skill_id`, `session_date`, `start_time`, `end_time`, `mode`, `meeting_link`, `location`, `status`, `created_at`) VALUES
(1, 1, 3, 2, 6, DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY), '16:00:00', '17:30:00', 'ONLINE', 'https://meet.google.com/abc-skillswap-xyz', NULL, 'SCHEDULED', NOW()),
(2, 2, 2, 3, 1, DATE_SUB(CURRENT_DATE(), INTERVAL 2 DAY), '14:00:00', '15:30:00', 'ONLINE', 'https://meet.google.com/def-skillswap-uvw', NULL, 'COMPLETED', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 4, 8, 5, 2, DATE_SUB(CURRENT_DATE(), INTERVAL 6 DAY), '11:00:00', '12:30:00', 'OFFLINE', NULL, 'Central Library Study Room 3', 'COMPLETED', DATE_SUB(NOW(), INTERVAL 7 DAY));

-- Insert Sample Reviews
INSERT INTO `reviews` (`review_id`, `session_id`, `reviewer_id`, `reviewee_id`, `rating`, `comment`, `created_at`) VALUES
(1, 2, 3, 2, 5, 'Rahul is an outstanding tutor! He explained Python data types and functions using clear real-world examples. Highly recommended!', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 3, 5, 8, 5, 'Vikram has deep mastery over C++ memory management and pointers. Helped me solve my lab assignment effortlessly!', DATE_SUB(NOW(), INTERVAL 6 DAY));

-- Insert Sample Notifications
INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 2, 'Priya Mehta accepted your skill exchange request for UI/UX Design!', 'REQUEST_ACCEPTED', 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 2, 'Reminder: You have an upcoming UI/UX Design session with Priya tomorrow at 4:00 PM.', 'SESSION', 0, NOW()),
(3, 3, 'Rahul Sharma completed the Python session and gave you a 5-star rating!', 'REVIEW', 0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 3, 'Aman Patil sent you a skill exchange request for Figma.', 'REQUEST', 0, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Insert Sample Reports
INSERT INTO `reports` (`report_id`, `reported_by`, `reported_user`, `reason`, `description`, `status`, `created_at`) VALUES
(1, 11, 18, 'Inappropriate behavior', 'User did not show up to scheduled session twice and sent promotional spam messages.', 'PENDING', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Re-calculate initial sample reputation scores
CALL sp_calculate_reputation(2);
CALL sp_calculate_reputation(3);
CALL sp_calculate_reputation(5);
CALL sp_calculate_reputation(8);
