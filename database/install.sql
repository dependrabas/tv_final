CREATE DATABASE IF NOT EXISTS tchs_info_board CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tchs_info_board;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(160) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','editor') NOT NULL DEFAULT 'editor',
    status TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS announcements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(220) NOT NULL,
    description TEXT NOT NULL,
    publish_date DATE NOT NULL,
    expiry_date DATE NULL,
    priority ENUM('normal','important','urgent') NOT NULL DEFAULT 'normal',
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    display_order INT NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ann_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ann_public (status, publish_date, expiry_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS news (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(220) NOT NULL,
    description TEXT NOT NULL,
    featured_image VARCHAR(255) NULL,
    publication_date DATE NOT NULL,
    expiry_date DATE NULL,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    display_order INT NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_news_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_news_public (status, publication_date, expiry_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS staff (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    position VARCHAR(150) NOT NULL,
    department VARCHAR(150) NULL,
    photo VARCHAR(255) NULL,
    email VARCHAR(160) NULL,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS staff_leave (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id INT UNSIGNED NOT NULL,
    leave_type VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    note VARCHAR(255) NULL,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_leave_staff FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    INDEX idx_leave_public (status, start_date, end_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS student_statistics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    total_students INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS programmes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    abbreviation VARCHAR(30) NOT NULL,
    duration VARCHAR(80) NOT NULL,
    description TEXT NOT NULL,
    programme_status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    publication_status ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS staff_statistics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teaching_staff INT UNSIGNED NOT NULL DEFAULT 0,
    admin_support_staff INT UNSIGNED NOT NULL DEFAULT 0,
    recorded_date DATE NOT NULL,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ticker_notices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message VARCHAR(500) NOT NULL,
    priority ENUM('normal','important','urgent') NOT NULL DEFAULT 'normal',
    start_date DATE NOT NULL,
    expiry_date DATE NULL,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ticker_public (status, start_date, expiry_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS media_slides (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(220) NOT NULL,
    caption VARCHAR(500) NULL,
    media_type ENUM('image','video') NOT NULL DEFAULT 'image',
    media_path VARCHAR(255) NOT NULL,
    duration_seconds INT UNSIGNED NOT NULL DEFAULT 8,
    start_date DATE NULL,
    expiry_date DATE NULL,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id INT UNSIGNED NULL,
    details VARCHAR(500) NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_log_created (created_at)
) ENGINE=InnoDB;

INSERT IGNORE INTO users (id, name, username, email, password, role) VALUES
(1, 'System Administrator', 'admin', 'admin@tchs.edu.bt', '$2y$12$xIMsNJr1Bd8RIR.eiAB/PuUQI0K1zB25JHCefiz/VfMVGSZXvZg02', 'super_admin');

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('college_name', 'Trongsa College of Heritage and Contemporary Studies'),
('university_name', 'Royal University of Bhutan'),
('short_name', 'TCHS'),
('logo_path', 'assets/images/college-logo.png'),
('vision', 'A premier institution that advances education, research and innovation for the promotion of Bhutanese cultural and linguistic heritage.'),
('mission', 'To provide transformative education, advance scholarship, preserve cultural heritage, and prepare compassionate graduates to serve Bhutan and the wider world.'),
('primary_colour', '#123d2c'),
('accent_colour', '#d29d2b'),
('refresh_interval', '180'),
('default_slide_duration', '10'),
('welcome_enabled', '1'),
('welcome_kicker', 'Kuzuzangpo la'),
('welcome_title', 'Welcome to Trongsa College'),
('welcome_message', 'A place where Bhutanese heritage, curiosity, and purposeful learning come together.'),
('welcome_duration', '10'),
('footer_text', 'Knowledge • Culture • Service'),
('contact_email', 'info@tchs.edu.bt'),
('contact_phone', '+975 03 521 xxx');

INSERT INTO announcements (title, description, publish_date, expiry_date, priority, status, display_order, created_by)
SELECT 'Semester Registration Opens', 'All continuing students are requested to complete semester registration with their respective programme offices.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'important', 'published', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM announcements LIMIT 1);
INSERT INTO announcements (title, description, publish_date, expiry_date, priority, status, display_order, created_by)
SELECT 'Emergency Evacuation Drill', 'A college-wide safety drill will take place at 2:00 PM. Follow instructions from the safety coordinators.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'urgent', 'published', 0, 1
WHERE (SELECT COUNT(*) FROM announcements) = 1;
INSERT INTO announcements (title, description, publish_date, expiry_date, priority, status, display_order, created_by)
SELECT 'Library Extended Hours', 'The library will remain open until 9:00 PM on weekdays during the examination preparation period.', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'normal', 'published', 2, 1
WHERE (SELECT COUNT(*) FROM announcements) = 2;

/* Remaining sample data is inserted only when each target table is empty. */
INSERT INTO news (title, description, publication_date, expiry_date, status, display_order, created_by)
SELECT 'Students Celebrate Heritage Week', 'A week of exhibitions, oral history, traditional arts, and student-led cultural programmes brought the campus together.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'published', 1, 1 WHERE NOT EXISTS (SELECT 1 FROM news LIMIT 1);
INSERT INTO news (title, description, publication_date, expiry_date, status, display_order, created_by)
SELECT 'TCHS Research Seminar Series Launched', 'The new seminar series connects researchers, practitioners, and communities around Bhutanese heritage and social change.', DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'published', 2, 1 WHERE (SELECT COUNT(*) FROM news)=1;
INSERT INTO news (title, description, publication_date, expiry_date, status, display_order, created_by)
SELECT 'College Team Wins Inter-College Debate', 'The TCHS debate team earned first place with a thoughtful performance on technology and cultural continuity.', DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'published', 3, 1 WHERE (SELECT COUNT(*) FROM news)=2;

INSERT INTO staff (name, position, department, email, status)
SELECT 'Sonam Choden','Lecturer','History and Culture','sonam.choden@tchs.edu.bt','published' WHERE NOT EXISTS (SELECT 1 FROM staff LIMIT 1);
INSERT INTO staff (name, position, department, email, status)
SELECT 'Karma Wangchuk','ICT Officer','Administration','karma.wangchuk@tchs.edu.bt','published' WHERE (SELECT COUNT(*) FROM staff)=1;
INSERT INTO staff (name, position, department, email, status)
SELECT 'Pema Dema','Assistant Lecturer','Language and Literature','pema.dema@tchs.edu.bt','published' WHERE (SELECT COUNT(*) FROM staff)=2;

INSERT INTO staff_leave (staff_id, leave_type, start_date, end_date, note, status)
SELECT (SELECT id FROM staff ORDER BY id LIMIT 1),'Official Duty',DATE_SUB(CURDATE(),INTERVAL 1 DAY),DATE_ADD(CURDATE(),INTERVAL 3 DAY),'Attending academic workshop','published' WHERE NOT EXISTS (SELECT 1 FROM staff_leave LIMIT 1);
INSERT INTO staff_leave (staff_id, leave_type, start_date, end_date, note, status)
SELECT (SELECT id FROM staff ORDER BY id LIMIT 1 OFFSET 1),'Annual Leave',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 5 DAY),'For urgent ICT matters contact the administration office','published' WHERE (SELECT COUNT(*) FROM staff_leave)=1;

INSERT INTO student_statistics (academic_year,total_students,status)
SELECT '2026–2027',684,'published' WHERE NOT EXISTS (SELECT 1 FROM student_statistics LIMIT 1);
INSERT INTO staff_statistics (teaching_staff,admin_support_staff,recorded_date,status)
SELECT 48,36,CURDATE(),'published' WHERE NOT EXISTS (SELECT 1 FROM staff_statistics LIMIT 1);

INSERT INTO programmes (name,abbreviation,duration,description,programme_status,publication_status,display_order)
SELECT 'Bachelor of Arts in Bhutan Studies and Global Perspectives','BA BSGP','4 years','Explores Bhutanese society, culture, development, and global perspectives.','active','published',1 WHERE NOT EXISTS (SELECT 1 FROM programmes WHERE name='Bachelor of Arts in Bhutan Studies and Global Perspectives');
INSERT INTO programmes (name,abbreviation,duration,description,programme_status,publication_status,display_order)
SELECT 'Bachelor of Arts in Language and Heritage Studies','BA LHS','4 years','Connects language, literature, culture, and the study of Bhutanese heritage.','active','published',2 WHERE NOT EXISTS (SELECT 1 FROM programmes WHERE name='Bachelor of Arts in Language and Heritage Studies');
INSERT INTO programmes (name,abbreviation,duration,description,programme_status,publication_status,display_order)
SELECT 'Bachelor of Cultural Innovation and Entrepreneurship','BCIE','4 years','Combines cultural knowledge, creativity, innovation, and entrepreneurial practice.','active','published',3 WHERE NOT EXISTS (SELECT 1 FROM programmes WHERE name='Bachelor of Cultural Innovation and Entrepreneurship');
INSERT INTO programmes (name,abbreviation,duration,description,programme_status,publication_status,display_order)
SELECT 'Bachelor of Arts in Psychology and Mindfulness Studies','BA PMS','4 years','Studies human behaviour, wellbeing, and contemplative approaches to mindfulness.','active','published',4 WHERE NOT EXISTS (SELECT 1 FROM programmes WHERE name='Bachelor of Arts in Psychology and Mindfulness Studies');
INSERT INTO programmes (name,abbreviation,duration,description,programme_status,publication_status,display_order)
SELECT 'Bachelor of Arts in History and Global Affairs','BA HGA','4 years','Examines history, international relations, and contemporary global affairs.','active','published',5 WHERE NOT EXISTS (SELECT 1 FROM programmes WHERE name='Bachelor of Arts in History and Global Affairs');

INSERT INTO ticker_notices (message,priority,start_date,expiry_date,status,display_order)
SELECT 'Emergency evacuation drill at 2:00 PM — please follow the marked assembly routes.','urgent',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 2 DAY),'published',0 WHERE NOT EXISTS (SELECT 1 FROM ticker_notices LIMIT 1);
INSERT INTO ticker_notices (message,priority,start_date,expiry_date,status,display_order)
SELECT 'Semester registration closes in 20 days. Contact your programme office for support.','important',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 20 DAY),'published',1 WHERE (SELECT COUNT(*) FROM ticker_notices)=1;
INSERT INTO ticker_notices (message,priority,start_date,expiry_date,status,display_order)
SELECT 'Keep the campus clean and use the labelled waste collection points.','normal',CURDATE(),NULL,'published',2 WHERE (SELECT COUNT(*) FROM ticker_notices)=2;

INSERT INTO media_slides (title,caption,media_type,media_path,duration_seconds,status,display_order)
SELECT 'Learning rooted in place','TCHS brings heritage, scholarship, and contemporary practice together.','image','assets/images/placeholder.svg',10,'published',1 WHERE NOT EXISTS (SELECT 1 FROM media_slides LIMIT 1);
INSERT INTO media_slides (title,caption,media_type,media_path,duration_seconds,status,display_order)
SELECT 'A community of inquiry','Students learn through dialogue, fieldwork, research, and service.','image','assets/images/placeholder.svg',10,'published',2 WHERE (SELECT COUNT(*) FROM media_slides)=1;

/* Legacy bulk seed block retained below is disabled. */
SET @skip_legacy_seed = 1;
/*
('Semester Registration Opens', 'All continuing students are requested to complete semester registration with their respective programme offices.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'important', 'published', 1, 1),
('Emergency Evacuation Drill', 'A college-wide safety drill will take place at 2:00 PM. Follow instructions from the safety coordinators.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'urgent', 'published', 0, 1),
('Library Extended Hours', 'The library will remain open until 9:00 PM on weekdays during the examination preparation period.', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'normal', 'published', 2, 1);

INSERT INTO news (title, description, publication_date, expiry_date, status, display_order, created_by) VALUES
('Students Celebrate Heritage Week', 'A week of exhibitions, oral history, traditional arts, and student-led cultural programmes brought the campus together.', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'published', 1, 1),
('TCHS Research Seminar Series Launched', 'The new seminar series connects researchers, practitioners, and communities around Bhutanese heritage and social change.', DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'published', 2, 1),
('College Team Wins Inter-College Debate', 'The TCHS debate team earned first place with a thoughtful performance on technology and cultural continuity.', DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'published', 3, 1);

INSERT INTO staff (name, position, department, email, status) VALUES
('Sonam Choden', 'Lecturer', 'History and Culture', 'sonam.choden@tchs.edu.bt', 'published'),
('Karma Wangchuk', 'ICT Officer', 'Administration', 'karma.wangchuk@tchs.edu.bt', 'published'),
('Pema Dema', 'Assistant Lecturer', 'Language and Literature', 'pema.dema@tchs.edu.bt', 'published');

INSERT INTO staff_leave (staff_id, leave_type, start_date, end_date, note, status) VALUES
(1, 'Official Duty', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Attending academic workshop', 'published'),
(2, 'Annual Leave', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'For urgent ICT matters contact the administration office', 'published');

INSERT INTO student_statistics (academic_year, total_students, status) VALUES
('2026–2027', 684, 'published');

INSERT INTO staff_statistics (teaching_staff, admin_support_staff, recorded_date, status) VALUES
(48, 36, CURDATE(), 'published');

INSERT INTO programmes (name, abbreviation, duration, description, programme_status, publication_status, display_order) VALUES
('Bachelor of Arts in History and Dzongkha', 'BA HD', '4 years', 'An interdisciplinary study of Bhutanese history, language, society, and cultural heritage.', 'active', 'published', 1),
('Bachelor of Arts in Language and Literature', 'BA LL', '4 years', 'Develops advanced communication, critical reading, creative practice, and literary scholarship.', 'active', 'published', 2),
('Bachelor of Arts in Sustainable Development', 'BA SD', '4 years', 'Connects policy, culture, community resilience, and environmentally responsible development.', 'active', 'published', 3),
('Postgraduate Diploma in Heritage Studies', 'PGD HS', '1 year', 'Professional preparation in heritage research, interpretation, conservation, and community practice.', 'active', 'published', 4);

INSERT INTO ticker_notices (message, priority, start_date, expiry_date, status, display_order) VALUES
('Emergency evacuation drill at 2:00 PM — please follow the marked assembly routes.', 'urgent', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'published', 0),
('Semester registration closes in 20 days. Contact your programme office for support.', 'important', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'published', 1),
('Keep the campus clean and use the labelled waste collection points.', 'normal', CURDATE(), NULL, 'published', 2);

INSERT INTO media_slides (title, caption, media_type, media_path, duration_seconds, status, display_order) VALUES
('Learning rooted in place', 'TCHS brings heritage, scholarship, and contemporary practice together.', 'image', 'assets/images/placeholder.svg', 8, 'published', 1),
('A community of inquiry', 'Students learn through dialogue, fieldwork, research, and service.', 'image', 'assets/images/placeholder.svg', 8, 'published', 2);
*/
