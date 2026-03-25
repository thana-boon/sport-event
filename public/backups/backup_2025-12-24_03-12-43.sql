-- Backup Database: school_app
-- Generated: 2025-12-24 03:12:43

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

DROP TABLE IF EXISTS `academic_years`;
CREATE TABLE `academic_years` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year_be` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `registration_is_open` tinyint(1) NOT NULL DEFAULT 0,
  `registration_start` datetime DEFAULT NULL,
  `registration_end` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_year_be` (`year_be`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `academic_years` VALUES ('1', '2568', 'ปีการศึกษา 2568', '1', '1', NULL, NULL, '2025-10-08 11:19:18');
INSERT INTO `academic_years` VALUES ('2', '2567', 'ปีการศึกษา 2567', '0', '0', NULL, NULL, '2025-10-08 11:19:26');
INSERT INTO `academic_years` VALUES ('3', '2569', 'ปีการศึกษา 2569', '0', '0', NULL, NULL, '2025-10-08 11:19:31');
INSERT INTO `academic_years` VALUES ('4', '2566', 'ปีการศึกษา 2566', '0', '0', NULL, NULL, '2025-10-08 11:21:22');

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `user_type` varchar(20) DEFAULT 'guest',
  `action` varchar(50) DEFAULT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`),
  KEY `idx_user_type` (`user_type`)
) ENGINE=InnoDB AUTO_INCREMENT=477 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `activity_logs` VALUES ('454', '10', 't00241', 'admin', 'DELETE', 'database', NULL, 'ลบไฟล์สำรองข้อมูล | ไฟล์: backup_2025-12-23_07-05-01.sql', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 13:05:35');
INSERT INTO `activity_logs` VALUES ('455', '10', 't00241', 'admin', 'DELETE', 'registrations', NULL, 'ลบการลงทะเบียนทั้งหมด: 1 รายการ | ปี 2568 | รายละเอียด: [วิ่ง 100 เมตร ชาย ป.1 สีเขียว (1 คน)]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 13:05:45');
INSERT INTO `activity_logs` VALUES ('456', '10', 't00241', 'admin', 'DELETE', 'database', NULL, 'ลบไฟล์สำรองข้อมูล | ไฟล์: backup_2025-12-23_07-07-54.sql', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 13:08:15');
INSERT INTO `activity_logs` VALUES ('457', '10', 't00241', 'admin', 'RESTORE', 'database', NULL, 'คืนค่าข้อมูลจากไฟล์สำรอง | ไฟล์: backup_2025-12-23_07-10-59.sql', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 13:11:04');
INSERT INTO `activity_logs` VALUES ('458', '10', 't00241', 'admin', 'DELETE', 'database', NULL, 'ลบไฟล์สำรองข้อมูล | ไฟล์: backup_2025-12-23_07-10-59.sql', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 13:11:09');
INSERT INTO `activity_logs` VALUES ('459', '10', 't00241', 'admin', 'UPDATE', 'registrations', '194', 'จัดทีม: วิ่ง 100 เมตร ชาย ป.1 | สีเขียว | จำนวน: 1/2 คน | ทีมเดิม: - | ทีมใหม่: [เด็กชายวรปรัชญ์ มูลทรัพย์] | ปีการศึกษา ID:1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 13:11:14');
INSERT INTO `activity_logs` VALUES ('460', '10', 't00241', 'admin', 'RESTORE', 'database', NULL, 'คืนค่าข้อมูลจากไฟล์สำรอง | ไฟล์: backup_2025-12-23_07-11-19.sql', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 13:13:38');
INSERT INTO `activity_logs` VALUES ('461', '10', 't00241', 'admin', 'DELETE', 'database', NULL, 'ลบไฟล์สำรองข้อมูล | ไฟล์: backup_2025-12-23_07-11-19.sql', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 13:13:42');
INSERT INTO `activity_logs` VALUES ('462', '10', 't00241', 'admin', 'DELETE', 'registrations', NULL, 'ลบการลงทะเบียนทั้งหมด: 1 รายการ | ปี 2568 | รายละเอียด: [วิ่ง 100 เมตร ชาย ป.1 สีเขียว (1 คน)]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 13:13:50');
INSERT INTO `activity_logs` VALUES ('463', '10', 't00241', 'admin', 'BACKUP', 'database', NULL, 'สร้างไฟล์สำรองข้อมูล | ไฟล์: backup_2025-12-23_07-15-08.sql | ขนาด: 412932 bytes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 13:15:08');
INSERT INTO `activity_logs` VALUES ('464', '10', 't00241', 'admin', 'DELETE', 'database', NULL, 'ลบไฟล์สำรองข้อมูล | ไฟล์: backup_2025-12-23_07-15-08.sql', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 13:15:21');
INSERT INTO `activity_logs` VALUES ('465', '10', 't00241', 'admin', 'BACKUP', 'database', NULL, 'สร้างไฟล์สำรองข้อมูล | ไฟล์: backup_2025-12-23_07-15-26.sql | ขนาด: 413686 bytes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 13:15:26');
INSERT INTO `activity_logs` VALUES ('466', '10', 't00241', 'admin', 'DELETE', 'database', NULL, 'ลบไฟล์สำรองข้อมูล | ไฟล์: backup_2025-12-23_07-15-26.sql', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 13:15:31');
INSERT INTO `activity_logs` VALUES ('467', '7', 'admin', 'admin', 'LOGIN', 'users', '7', 'เข้าสู่ระบบสำเร็จ (admin) | Display: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 14:30:16');
INSERT INTO `activity_logs` VALUES ('468', '7', 'admin', 'admin', 'LOGIN', 'users', '7', 'เข้าสู่ระบบสำเร็จ (admin) | Display: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 16:19:02');
INSERT INTO `activity_logs` VALUES ('469', '7', 'admin', 'admin', 'UPDATE', 'registrations', '194', 'จัดทีม: วิ่ง 100 เมตร ชาย ป.1 | สีเขียว | จำนวน: 2/2 คน | ทีมเดิม: - | ทีมใหม่: [เด็กชายธีรกานต์ สุดล้ำเลิศ, เด็กชายภูวณัฏฐ์ ธงชัย] | ปีการศึกษา ID:1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-23 16:19:35');
INSERT INTO `activity_logs` VALUES ('470', '7', 'admin', 'admin', 'LOGIN', 'users', '7', 'เข้าสู่ระบบสำเร็จ (admin) | Display: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 08:09:00');
INSERT INTO `activity_logs` VALUES ('471', '7', 'admin', 'admin', 'SUBSTITUTE', 'registrations', '33', 'เปลี่ยนตัวนักกีฬา | จาก: เด็กชายธีรกานต์ สุดล้ำเลิศ → เด็กชายวรปรัชญ์ มูลทรัพย์ | เหตุผล: -', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 08:58:43');
INSERT INTO `activity_logs` VALUES ('472', '7', 'admin', 'admin', 'SUBSTITUTE', 'registrations', '34', 'เปลี่ยนตัวนักกีฬา | จาก: เด็กชายภูวณัฏฐ์ ธงชัย → เด็กชายชาติอาชาไนย ฟักขำ | เหตุผล: -', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 09:00:24');
INSERT INTO `activity_logs` VALUES ('473', '7', 'admin', 'admin', 'UPDATE', 'academic_years', '3', 'ตั้งปีการศึกษาปัจจุบัน: ปีการศึกษา 2569 (พ.ศ. 2569) | ปิด: ปีการศึกษา 2568 (พ.ศ. 2568)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 09:02:32');
INSERT INTO `activity_logs` VALUES ('474', '7', 'admin', 'admin', 'UPDATE', 'academic_years', '1', 'ตั้งปีการศึกษาปัจจุบัน: ปีการศึกษา 2568 (พ.ศ. 2568) | ปิด: ปีการศึกษา 2569 (พ.ศ. 2569)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 09:02:38');
INSERT INTO `activity_logs` VALUES ('475', '7', 'admin', 'admin', 'SUBSTITUTE', 'registrations', '33', 'เปลี่ยนตัวนักกีฬา | จาก: เด็กชายวรปรัชญ์ มูลทรัพย์ → เด็กชายภูวณัฏฐ์ ธงชัย | เหตุผล: -', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 09:03:40');
INSERT INTO `activity_logs` VALUES ('476', '7', 'admin', 'admin', 'SUBSTITUTE', 'registrations', '33', 'เปลี่ยนตัวนักกีฬา | จาก: เด็กชายภูวณัฏฐ์ ธงชัย → เด็กชายวรปรัชญ์ มูลทรัพย์ | เหตุผล: -', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-24 09:03:55');

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admins` VALUES ('1', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin', '2025-10-08 10:51:50');

DROP TABLE IF EXISTS `athletics_events`;
CREATE TABLE `athletics_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year_id` int(10) unsigned NOT NULL,
  `sport_id` int(10) unsigned NOT NULL,
  `event_code` varchar(16) NOT NULL,
  `best_student_id` int(10) unsigned DEFAULT NULL,
  `best_time` varchar(32) DEFAULT NULL,
  `best_year_be` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_per_year` (`year_id`,`event_code`),
  KEY `idx_year_sport` (`year_id`,`sport_id`),
  KEY `fk_ath_ev_sport` (`sport_id`),
  KEY `fk_ath_ev_student` (`best_student_id`),
  CONSTRAINT `fk_ath_ev_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`),
  CONSTRAINT `fk_ath_ev_student` FOREIGN KEY (`best_student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `fk_ath_ev_year` FOREIGN KEY (`year_id`) REFERENCES `academic_years` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `athletics_events` VALUES ('8', '1', '164', '101', NULL, '8.2', NULL, 'เด็กชายยศกร ศรีสวัสดิ์', '2025-11-05 09:42:20');
INSERT INTO `athletics_events` VALUES ('9', '1', '165', '102', NULL, '9.3', NULL, 'เด็กหญิงชริสา ชัยยาภรณ์', '2025-11-06 14:47:37');
INSERT INTO `athletics_events` VALUES ('10', '1', '166', '103', NULL, '9.9', NULL, 'เด็กชายรัฐเจตน์ โกมารทัต', '2025-11-06 14:51:00');
INSERT INTO `athletics_events` VALUES ('11', '1', '167', '104', '647', '10.18', NULL, '', '2025-11-06 14:51:56');
INSERT INTO `athletics_events` VALUES ('12', '1', '168', '105', NULL, '9.78', NULL, 'เด็กชายศิริโชค ศรีสนิท', '2025-11-06 14:52:23');
INSERT INTO `athletics_events` VALUES ('13', '1', '169', '106', '340', '10.56', '2567', 'เด็กหญิงนนทกร จรดล', '2025-11-06 14:52:56');
INSERT INTO `athletics_events` VALUES ('14', '1', '170', '107', NULL, '10.94', NULL, 'เด็กชายอินทนันทน์ เซ่งสมหวัง', '2025-11-06 14:55:46');
INSERT INTO `athletics_events` VALUES ('15', '1', '171', '108', NULL, '11.56', NULL, 'เด็กหญิงปิ่นเหล้า จำปาวิทยาคุณ', '2025-11-06 14:56:22');
INSERT INTO `athletics_events` VALUES ('16', '1', '172', '109', NULL, '11.4', NULL, 'เด็กชายสุรเชษฐ์ โป้สมบูรณ์', '2025-11-06 15:00:28');
INSERT INTO `athletics_events` VALUES ('17', '1', '173', '110', NULL, '12.09', NULL, 'เด็กหญิงรสวิมน พะวงษ์', '2025-11-06 15:01:15');
INSERT INTO `athletics_events` VALUES ('18', '1', '174', '111', NULL, '11.94', '2567', 'เด็กชายอัฐพล บุญยอดนิล', '2025-11-06 15:12:14');
INSERT INTO `athletics_events` VALUES ('19', '1', '175', '112', NULL, '13.81', NULL, 'เด็กหญิงพัฒนวดี เหลืองไพรินทร์', '2025-11-06 15:16:32');
INSERT INTO `athletics_events` VALUES ('20', '1', '176', '113', NULL, '13.06', NULL, 'เด็กชายจตุรวิทย์ ชาชำนาญ', '2025-11-06 15:17:07');
INSERT INTO `athletics_events` VALUES ('21', '1', '177', '114', '284', '14.78', '2567', '', '2025-11-06 15:23:42');
INSERT INTO `athletics_events` VALUES ('22', '1', '178', '115', '1010', '14.62', NULL, '', '2025-11-06 15:26:22');
INSERT INTO `athletics_events` VALUES ('23', '1', '179', '116', '838', '15.62', NULL, '', '2025-11-06 15:27:20');
INSERT INTO `athletics_events` VALUES ('24', '1', '180', '117', NULL, '11.46', NULL, 'นายสุวัฒน์ มรรคเจริญ', '2025-11-06 15:33:22');
INSERT INTO `athletics_events` VALUES ('25', '1', '181', '118', NULL, '15.03', NULL, 'นางสาวธัชปรีดา ชะอุ่ม', '2025-11-06 15:34:25');
INSERT INTO `athletics_events` VALUES ('26', '1', '182', '119', NULL, '12.35', NULL, 'นายอภิรักษ์ ยวงใย', '2025-11-06 15:37:18');
INSERT INTO `athletics_events` VALUES ('27', '1', '183', '120', NULL, '15.69', NULL, 'นางสาวอิงครัตน์ กมลวชิรเศารษฐ์', '2025-11-06 15:38:26');
INSERT INTO `athletics_events` VALUES ('28', '1', '184', '121', NULL, '12.37', NULL, 'เด็กชายปรเมศวร์ ชุมพล', '2025-11-06 15:39:35');
INSERT INTO `athletics_events` VALUES ('29', '1', '185', '122', NULL, '15.15', NULL, 'เด็กหญิงณัฐริกา แย้มบางยาง', '2025-11-06 15:40:18');
INSERT INTO `athletics_events` VALUES ('30', '1', '186', '123', NULL, '13.09', NULL, 'เด็กชายณัฐวุฒิ เฮี้ยนชาศรี', '2025-11-06 15:42:57');
INSERT INTO `athletics_events` VALUES ('31', '1', '187', '124', NULL, '15.65', NULL, 'เด็กหญิงณัฐหทัย ศักดิ์ศรีสกุล', '2025-11-06 15:43:51');
INSERT INTO `athletics_events` VALUES ('32', '1', '188', '125', '1041', '14.37', NULL, '', '2025-11-06 15:44:26');
INSERT INTO `athletics_events` VALUES ('33', '1', '189', '126', NULL, '15.15', NULL, 'เด็กหญิงสุวภัทร สุดประเสริฐ', '2025-11-06 15:50:58');
INSERT INTO `athletics_events` VALUES ('34', '1', '190', '127', NULL, '16.15', NULL, 'เด็กชายธนกร เบ็ญจมฐารกุล', '2025-11-06 15:51:52');
INSERT INTO `athletics_events` VALUES ('35', '1', '191', '128', NULL, '16.59', NULL, 'เด็กหญิงปวริศา ตั้งประเสริฐชัย', '2025-11-06 15:52:25');
INSERT INTO `athletics_events` VALUES ('36', '1', '192', '129', '1046', '17.35', NULL, '', '2025-11-06 15:54:31');
INSERT INTO `athletics_events` VALUES ('37', '1', '193', '130', '984', '18.16', NULL, '', '2025-11-06 15:55:10');
INSERT INTO `athletics_events` VALUES ('38', '1', '194', '131', NULL, '18.47', NULL, 'เด็กชายศิริโชค ศรีสนิท', '2025-11-06 15:55:51');
INSERT INTO `athletics_events` VALUES ('39', '1', '195', '132', '906', '20.87', NULL, '', '2025-11-06 15:56:24');
INSERT INTO `athletics_events` VALUES ('40', '1', '196', '133', NULL, '25.00', NULL, 'นายพศวัต บุญเศรฐ', '2025-11-06 16:04:13');
INSERT INTO `athletics_events` VALUES ('41', '1', '197', '134', NULL, '32.82', NULL, 'นางสาวชญาณี สืบนาค', '2025-11-06 16:04:58');
INSERT INTO `athletics_events` VALUES ('42', '1', '198', '135', NULL, '26.54', NULL, 'นายพชร ปุลิสัจจะ', '2025-11-06 16:28:37');
INSERT INTO `athletics_events` VALUES ('43', '1', '199', '136', NULL, '35.25', NULL, 'นางสาววิภาดา เลี้ยงรักษา', '2025-11-06 16:29:13');
INSERT INTO `athletics_events` VALUES ('44', '1', '200', '137', NULL, '25.75', NULL, 'เด็กชายทนงศักดิ์ สุขตัว', '2025-11-06 16:30:01');
INSERT INTO `athletics_events` VALUES ('45', '1', '201', '138', NULL, '34.69', NULL, 'เด็กหญิงนาฏยากร ก้านเหลือง', '2025-11-06 16:30:34');
INSERT INTO `athletics_events` VALUES ('46', '1', '202', '139', '1042', '28.22', '2567', '', '2025-11-06 16:33:18');
INSERT INTO `athletics_events` VALUES ('47', '1', '203', '140', NULL, '35.43', NULL, 'เด็กหญิงรุ่งนภา คงมณี', '2025-11-06 16:36:15');
INSERT INTO `athletics_events` VALUES ('48', '1', '204', '141', NULL, '30.56', NULL, 'เด็กชายณัฐวุฒิ เฮี้ยนชาศรี', '2025-11-06 16:36:52');
INSERT INTO `athletics_events` VALUES ('49', '1', '205', '142', NULL, '35.19', NULL, 'เด็กหญิงธัญญลักษณ์ พรหมอยู่', '2025-11-06 16:37:41');
INSERT INTO `athletics_events` VALUES ('50', '1', '206', '143', NULL, '58.31', NULL, 'นายเมธา บุญอิ่มยิ่ง', '2025-11-06 16:44:39');
INSERT INTO `athletics_events` VALUES ('51', '1', '207', '144', NULL, '84.6', NULL, 'นางสาวพัฒนวดี เหลืองไพรินทร์', '2025-11-06 16:46:32');
INSERT INTO `athletics_events` VALUES ('52', '1', '208', '145', NULL, '61.25', NULL, 'นายติรพิพัฒน์ ชูกลิ่น', '2025-11-06 16:50:08');
INSERT INTO `athletics_events` VALUES ('53', '1', '209', '146', '1510', '100.75', '2567', '', '2025-11-06 16:51:49');
INSERT INTO `athletics_events` VALUES ('54', '1', '210', '147', NULL, '60.75', NULL, 'เด็กชายอารักษ์ โพธิ์ทองนาค', '2025-11-06 17:00:32');
INSERT INTO `athletics_events` VALUES ('55', '1', '211', '148', NULL, '65.02', NULL, 'เด็กหญิงวิภาดา เลี้ยงรักษา', '2025-11-06 17:01:23');
INSERT INTO `athletics_events` VALUES ('56', '1', '212', '149', NULL, '94.59', NULL, 'สีชมพู', '2025-11-06 17:02:55');
INSERT INTO `athletics_events` VALUES ('57', '1', '213', '150', NULL, '98.69', NULL, 'สีเขียว', '2025-11-06 17:03:36');
INSERT INTO `athletics_events` VALUES ('58', '1', '214', '151', NULL, '84.34', NULL, 'สีเขียว', '2025-11-06 17:48:42');
INSERT INTO `athletics_events` VALUES ('59', '1', '215', '152', NULL, '84.34', NULL, 'สีเขียว', '2025-11-06 17:50:03');
INSERT INTO `athletics_events` VALUES ('60', '1', '216', '153', NULL, '78.5', NULL, 'สีฟ้า', '2025-11-06 17:50:55');
INSERT INTO `athletics_events` VALUES ('61', '1', '217', '154', NULL, '81.06', NULL, 'สีฟ้า', '2025-11-06 17:51:51');
INSERT INTO `athletics_events` VALUES ('62', '1', '218', '155', NULL, '69.47', NULL, 'สีชมพู', '2025-11-06 17:52:34');
INSERT INTO `athletics_events` VALUES ('63', '1', '219', '156', NULL, '74', NULL, 'สีฟ้า', '2025-11-06 17:53:10');
INSERT INTO `athletics_events` VALUES ('64', '1', '220', '157', NULL, '90.72', NULL, 'สีเขียว', '2025-11-06 17:54:06');
INSERT INTO `athletics_events` VALUES ('65', '1', '221', '158', NULL, '95.69', NULL, 'สีส้ม', '2025-11-06 17:54:54');
INSERT INTO `athletics_events` VALUES ('66', '1', '222', '159', NULL, '77.16', NULL, 'สีฟ้า', '2025-11-06 17:55:35');
INSERT INTO `athletics_events` VALUES ('67', '1', '223', '160', NULL, '84.62', NULL, 'สีเขียว', '2025-11-06 17:56:22');
INSERT INTO `athletics_events` VALUES ('68', '1', '224', '161', NULL, '69.97', NULL, 'สีส้ม', '2025-11-06 17:57:04');
INSERT INTO `athletics_events` VALUES ('69', '1', '225', '162', NULL, '79.35', NULL, 'สีส้ม', '2025-11-06 17:57:41');
INSERT INTO `athletics_events` VALUES ('70', '1', '226', '163', NULL, '67.84', NULL, 'สีชมพู', '2025-11-06 17:58:14');
INSERT INTO `athletics_events` VALUES ('71', '1', '227', '164', NULL, '73.9', NULL, 'สีเขียว', '2025-11-06 17:58:52');
INSERT INTO `athletics_events` VALUES ('72', '1', '228', '165', NULL, '62.82', NULL, 'สีฟ้า', '2025-11-06 17:59:28');
INSERT INTO `athletics_events` VALUES ('73', '1', '229', '166', NULL, '71.85', NULL, 'สีเขียว', '2025-11-06 18:00:14');
INSERT INTO `athletics_events` VALUES ('74', '1', '230', '167', NULL, '51.1', NULL, 'สีชมพู', '2025-11-06 18:03:01');
INSERT INTO `athletics_events` VALUES ('75', '1', '231', '168', NULL, '68.35', NULL, 'สีฟ้า', '2025-11-06 18:03:28');
INSERT INTO `athletics_events` VALUES ('76', '1', '232', '169', NULL, '52.66', NULL, 'สีชมพู', '2025-11-06 18:04:51');
INSERT INTO `athletics_events` VALUES ('77', '1', '233', '170', NULL, '68.38', NULL, 'สีเขียว', '2025-11-06 18:05:30');
INSERT INTO `athletics_events` VALUES ('78', '1', '234', '171', NULL, '53.09', NULL, 'สีส้ม', '2025-11-06 18:06:02');
INSERT INTO `athletics_events` VALUES ('79', '1', '235', '172', NULL, '69.8', NULL, 'สีส้ม', '2025-11-06 18:06:45');
INSERT INTO `athletics_events` VALUES ('80', '1', '236', '173', NULL, '61.19', NULL, 'สีฟ้า', '2025-11-06 18:10:27');
INSERT INTO `athletics_events` VALUES ('81', '1', '237', '174', NULL, '70.5', NULL, 'สีชมพู', '2025-11-06 18:12:06');
INSERT INTO `athletics_events` VALUES ('82', '1', '238', '175', NULL, '66.75', NULL, 'สีฟ้า', '2025-11-06 18:12:55');
INSERT INTO `athletics_events` VALUES ('83', '1', '239', '176', NULL, '70.44', NULL, 'สีเขียว', '2025-11-06 18:14:20');
INSERT INTO `athletics_events` VALUES ('84', '1', '240', '177', NULL, '73.43', NULL, 'สีฟ้า', '2025-11-06 18:14:51');
INSERT INTO `athletics_events` VALUES ('85', '1', '241', '178', NULL, '77.82', NULL, 'สีส้ม', '2025-11-06 18:15:25');
INSERT INTO `athletics_events` VALUES ('86', '1', '242', '179', NULL, '79.91', NULL, 'สีชมพู', '2025-11-06 18:17:16');
INSERT INTO `athletics_events` VALUES ('87', '1', '243', '180', NULL, '85.82', NULL, 'สีฟ้า', '2025-11-06 18:17:49');
INSERT INTO `athletics_events` VALUES ('88', '1', '244', '181', NULL, '88.03', NULL, 'สีชมพู', '2025-11-06 18:18:53');
INSERT INTO `athletics_events` VALUES ('89', '1', '245', '182', NULL, '94.97', NULL, 'สีเขียว', '2025-11-06 18:19:33');
INSERT INTO `athletics_events` VALUES ('90', '1', '246', '183', NULL, '274.84', NULL, 'สีเขียว', '2025-11-06 18:21:01');
INSERT INTO `athletics_events` VALUES ('91', '1', '247', '184', NULL, '381.53', NULL, 'สีฟ้า', '2025-11-06 18:22:31');
INSERT INTO `athletics_events` VALUES ('92', '1', '248', '185', NULL, '248.97', NULL, 'สีเขียว', '2025-11-06 18:23:33');
INSERT INTO `athletics_events` VALUES ('93', '1', '249', '186', NULL, '388', NULL, 'สีชมพู', '2025-11-06 18:24:11');

DROP TABLE IF EXISTS `athletics_results`;
CREATE TABLE `athletics_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `heat_no` int(11) NOT NULL,
  `lane_no` int(11) NOT NULL,
  `registration_id` int(11) DEFAULT NULL,
  `time_sec` decimal(8,3) DEFAULT NULL,
  `rank` tinyint(4) DEFAULT NULL,
  `is_record` tinyint(1) NOT NULL DEFAULT 0,
  `record_by_name` varchar(120) DEFAULT NULL,
  `academic_year` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_result` (`event_id`,`heat_no`,`lane_no`,`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `category_year_settings`;
CREATE TABLE `category_year_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `max_per_student` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_year_category` (`year_id`,`category_id`),
  KEY `fk_cys_cat` (`category_id`),
  CONSTRAINT `fk_cys_cat` FOREIGN KEY (`category_id`) REFERENCES `sport_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cys_year` FOREIGN KEY (`year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `category_year_settings` VALUES ('1', '3', '1', '2', '1', '2025-10-08 12:26:40');
INSERT INTO `category_year_settings` VALUES ('2', '3', '2', '2', '1', '2025-10-08 12:26:40');
INSERT INTO `category_year_settings` VALUES ('3', '3', '3', '1', '1', '2025-10-08 12:26:40');
INSERT INTO `category_year_settings` VALUES ('4', '3', '4', '1', '1', '2025-10-08 12:26:40');
INSERT INTO `category_year_settings` VALUES ('8', '1', '2', '2', '1', '2025-10-08 12:45:39');

DROP TABLE IF EXISTS `competition_meta`;
CREATE TABLE `competition_meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year_id` int(11) NOT NULL,
  `edition_no` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_competition_meta_year` (`year_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `competition_meta` VALUES ('1', '1', '16', '2026-01-27', '2026-01-30', 'กีฬาราชพฤกษ์เกม', 'uploads/logo/logo_year_1_20251113_020454.png', '2025-10-15 08:50:10', '2025-11-13 08:04:54');

DROP TABLE IF EXISTS `match_pairs`;
CREATE TABLE `match_pairs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year_id` int(10) unsigned NOT NULL,
  `sport_id` int(10) unsigned NOT NULL,
  `round_name` varchar(100) NOT NULL DEFAULT 'รอบคัดเลือก',
  `round_no` int(10) unsigned NOT NULL DEFAULT 1,
  `match_no` int(10) unsigned NOT NULL,
  `match_date` date DEFAULT NULL,
  `match_time` time DEFAULT NULL,
  `venue` varchar(120) DEFAULT NULL,
  `side_a_label` varchar(120) NOT NULL,
  `side_a_color` enum('ส้ม','เขียว','ชมพู','ฟ้า') DEFAULT NULL,
  `side_b_label` varchar(120) NOT NULL,
  `side_b_color` enum('ส้ม','เขียว','ชมพู','ฟ้า') DEFAULT NULL,
  `winner` enum('A','B','BYE') DEFAULT NULL,
  `score_a` varchar(30) DEFAULT NULL,
  `score_b` varchar(30) DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_match` (`year_id`,`sport_id`,`round_no`,`match_no`),
  KEY `idx_sport_year` (`sport_id`,`year_id`),
  CONSTRAINT `fk_mp_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mp_year` FOREIGN KEY (`year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=519 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `match_results`;
CREATE TABLE `match_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_pair_id` int(11) NOT NULL,
  `score_a` int(11) DEFAULT NULL,
  `score_b` int(11) DEFAULT NULL,
  `winner_registration_id` int(11) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `academic_year` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_match_year` (`match_pair_id`,`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `player_substitutions`;
CREATE TABLE `player_substitutions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year_id` int(11) NOT NULL,
  `sport_id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL COMMENT 'ID ของการลงทะเบียนที่ถูกเปลี่ยน',
  `old_student_id` int(11) NOT NULL COMMENT 'นักเรียนเก่า (ก่อนเปลี่ยน)',
  `new_student_id` int(11) NOT NULL COMMENT 'นักเรียนใหม่ (หลังเปลี่ยน)',
  `color` varchar(20) NOT NULL,
  `substitution_date` datetime DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_year_sport` (`year_id`,`sport_id`),
  KEY `idx_registration` (`registration_id`),
  KEY `idx_students` (`old_student_id`,`new_student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `player_substitutions` VALUES ('1', '1', '194', '33', '23', '24', 'เขียว', '2025-12-24 08:58:43', '7', '');
INSERT INTO `player_substitutions` VALUES ('4', '1', '194', '34', '25', '26', 'เขียว', '2025-12-24 09:00:24', '7', '');
INSERT INTO `player_substitutions` VALUES ('5', '1', '194', '33', '24', '25', 'เขียว', '2025-12-24 09:03:40', '7', '');
INSERT INTO `player_substitutions` VALUES ('6', '1', '194', '33', '25', '24', 'เขียว', '2025-12-24 09:03:55', '7', '');

DROP TABLE IF EXISTS `referee_results`;
CREATE TABLE `referee_results` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year_id` int(10) unsigned NOT NULL,
  `sport_id` int(10) unsigned NOT NULL,
  `color` varchar(20) NOT NULL,
  `rank` tinyint(3) unsigned NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_year_sport_color` (`year_id`,`sport_id`,`color`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `registrations`;
CREATE TABLE `registrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year_id` int(10) unsigned NOT NULL,
  `sport_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `color` enum('ส้ม','เขียว','ชมพู','ฟ้า') NOT NULL,
  `registered_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_reg` (`year_id`,`sport_id`,`student_id`),
  KEY `idx_year_sport_color` (`year_id`,`sport_id`,`color`),
  KEY `fk_reg_sport` (`sport_id`),
  KEY `fk_reg_student` (`student_id`),
  CONSTRAINT `fk_reg_sport` FOREIGN KEY (`sport_id`) REFERENCES `sports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reg_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reg_year` FOREIGN KEY (`year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `registrations` VALUES ('33', '1', '194', '24', 'เขียว', '2025-12-23 16:19:35');
INSERT INTO `registrations` VALUES ('34', '1', '194', '26', 'เขียว', '2025-12-23 16:19:35');

DROP TABLE IF EXISTS `scoring_rules`;
CREATE TABLE `scoring_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `rank1` int(11) NOT NULL DEFAULT 5,
  `rank2` int(11) NOT NULL DEFAULT 3,
  `rank3` int(11) NOT NULL DEFAULT 2,
  `rank4` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_year_cat` (`year_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sport_categories`;
CREATE TABLE `sport_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `max_per_student` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_category_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sport_categories` VALUES ('1', 'กีฬาสากล', '', '2', '1', '2025-10-08 12:15:08');
INSERT INTO `sport_categories` VALUES ('2', 'กรีฑา', '', '2', '1', '2025-10-08 12:15:16');
INSERT INTO `sport_categories` VALUES ('3', 'กีฬาไทย', '', '1', '1', '2025-10-08 12:15:26');
INSERT INTO `sport_categories` VALUES ('4', 'กีฬาสาธิต', '', '1', '1', '2025-10-08 12:15:32');

DROP TABLE IF EXISTS `sports`;
CREATE TABLE `sports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `gender` enum('ช','ญ','รวม') NOT NULL DEFAULT 'รวม',
  `participant_type` enum('เดี่ยว','ทีม') NOT NULL DEFAULT 'เดี่ยว',
  `team_size` int(11) NOT NULL DEFAULT 1,
  `grade_levels` varchar(255) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_year_cat_name_gender_type` (`year_id`,`category_id`,`name`,`gender`,`participant_type`),
  KEY `idx_year_category` (`year_id`,`category_id`),
  KEY `fk_sports_category` (`category_id`),
  CONSTRAINT `fk_sports_category` FOREIGN KEY (`category_id`) REFERENCES `sport_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=250 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sports` VALUES ('34', '1', '1', 'เทเบิลเทนนิส มัธยมศึกษาตอนปลาย (ชายเดี่ยว)', 'ช', 'เดี่ยว', '1', 'ม4,ม5,ม6', '1', '2025-11-03 15:26:55');
INSERT INTO `sports` VALUES ('37', '1', '1', 'เทเบิลเทนนิส มัธยมศึกษาตอนปลาย (หญิงเดี่ยว)', 'ญ', 'เดี่ยว', '1', 'ม4,ม5,ม6', '1', '2025-11-03 15:26:55');
INSERT INTO `sports` VALUES ('38', '1', '1', 'เทเบิลเทนนิส ประถมศึกษาปีที่ 4 (ชายเดี่ยว)', 'ช', 'เดี่ยว', '1', 'ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('39', '1', '1', 'เทเบิลเทนนิส ประถมศึกษาปีที่ 5 (ชายเดี่ยว)', 'ช', 'เดี่ยว', '1', 'ป5', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('40', '1', '1', 'เทเบิลเทนนิส ประถมศึกษาปีที่ 6 (ชายเดี่ยว)', 'ช', 'เดี่ยว', '1', 'ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('41', '1', '1', 'เทเบิลเทนนิส ประถมศึกษาปีที่ 4 (หญิงเดี่ยว)', 'ญ', 'เดี่ยว', '1', 'ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('42', '1', '1', 'เทเบิลเทนนิส ประถมศึกษาปีที่ 5 (หญิงเดี่ยว)', 'ญ', 'เดี่ยว', '1', 'ป5', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('43', '1', '1', 'เทเบิลเทนนิส ประถมศึกษาปีที่ 6 (หญิงเดี่ยว)', 'ญ', 'เดี่ยว', '1', 'ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('44', '1', '1', 'เทเบิลเทนนิส มัธยมศึกษาปีที่ 1 (ชายเดี่ยว)', 'ช', 'เดี่ยว', '1', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('45', '1', '1', 'เทเบิลเทนนิส มัธยมศึกษาปีที่ 2-3 (ชายเดี่ยว)', 'ช', 'เดี่ยว', '1', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('47', '1', '1', 'เทเบิลเทนนิส มัธยมศึกษาปีที่ 1 (หญิงเดี่ยว)', 'ญ', 'เดี่ยว', '1', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('48', '1', '1', 'เทเบิลเทนนิส มัธยมศึกษาปีที่ 2-3 (หญิงเดี่ยว)', 'ญ', 'เดี่ยว', '1', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('50', '1', '1', 'เทเบิลเทนนิส มัธยมศึกษาปีที่ 2-3 (ชายคู่)', 'ช', 'ทีม', '2', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('51', '1', '1', 'เทเบิลเทนนิส มัธยมศึกษาตอนปลาย (ชายคู่)', 'ช', 'ทีม', '2', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('52', '1', '1', 'เทเบิลเทนนิส มัธยมศึกษาปีที่ 2-3 (หญิงคู่)', 'ญ', 'ทีม', '2', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('53', '1', '1', 'เทเบิลเทนนิส มัธยมศึกษาตอนปลาย (หญิงคู่)', 'ญ', 'ทีม', '2', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('54', '1', '1', 'แชร์บอล ประถมศึกษาปีที่ 4 - 6 (ชาย)', 'ช', 'ทีม', '10', 'ป4,ป5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('55', '1', '1', 'แชร์บอล ประถมศึกษาปีที่ 4 - 6 (หญิง)', 'ญ', 'ทีม', '10', 'ป4,ป5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('56', '1', '1', 'แชร์บอล มัธยมศึกษาปีที่ 1 (ชาย)', 'ช', 'ทีม', '10', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('57', '1', '1', 'แชร์บอล มัธยมศึกษาปีที่ 2-3 (ชาย)', 'ช', 'ทีม', '10', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('58', '1', '1', 'แชร์บอล มัธยมศึกษาปีที่ 1 (หญิง)', 'ญ', 'ทีม', '10', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('59', '1', '1', 'แชร์บอล มัธยมศึกษาปีที่ 2-3 (หญิง)', 'ญ', 'ทีม', '10', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('60', '1', '1', 'แชร์บอล มัธยมศึกษาตอนปลาย (หญิง)', 'ญ', 'ทีม', '10', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('61', '1', '1', 'บาสเก็ตบอล มัธยมศึกษาตอนต้น (ชาย)', 'ช', 'ทีม', '10', 'ม1,ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('62', '1', '1', 'บาสเก็ตบอล มัธยมศึกษาตอนปลาย (ชาย)', 'ช', 'ทีม', '10', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('63', '1', '1', 'บาสเก็ตบอล มัธยมศึกษาตอนต้น (หญิง)', 'ญ', 'ทีม', '10', 'ม1,ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('64', '1', '1', 'บาสเก็ตบอล มัธยมศึกษาตอนปลาย (หญิง)', 'ญ', 'ทีม', '10', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('65', '1', '1', 'ฟุตซอล ประถมศึกษาปีที่ 1-2 (ชาย)', 'ช', 'ทีม', '10', 'ป1,ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('66', '1', '1', 'ฟุตซอล ประถมศึกษาปีที่ 3-4 (ชาย)', 'ช', 'ทีม', '10', 'ป3,ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('67', '1', '1', 'ฟุตซอล ประถมศึกษาปีที่ 5-6 (ชาย)', 'ช', 'ทีม', '10', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('68', '1', '1', 'ฟุตซอล มัธยมศึกษาปีที่ 1-2 (ชาย)', 'ช', 'ทีม', '10', 'ม1,ม2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('69', '1', '1', 'ฟุตซอล มัธยมศึกษาปีที่ 3-4 (ชาย)', 'ช', 'ทีม', '10', 'ม3,ม4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('70', '1', '1', 'ฟุตซอล มัธยมศึกษาปีที่ 5-6 (ชาย)', 'ช', 'ทีม', '10', 'ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('71', '1', '1', 'ฟุตซอล มัธยมศึกษาตอนต้น-ปลาย (หญิง)', 'ญ', 'ทีม', '10', 'ม1,ม2,ม3,ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('72', '1', '1', 'เปตอง มัธยมศึกษาปีที่ 1 (ชายเดี่ยว)', 'ช', 'เดี่ยว', '1', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('73', '1', '1', 'เปตอง มัธยมศึกษาปีที่ 2-3 (ชายเดี่ยว)', 'ช', 'เดี่ยว', '1', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('74', '1', '1', 'เปตอง มัธยมศึกษาตอนปลาย (ชายเดี่ยว)', 'ช', 'เดี่ยว', '1', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('75', '1', '1', 'เปตอง มัธยมศึกษาปีที่ 1 (หญิงเดี่ยว)', 'ญ', 'เดี่ยว', '1', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('76', '1', '1', 'เปตอง มัธยมศึกษาปีที่ 2-3 (หญิงเดี่ยว)', 'ญ', 'เดี่ยว', '1', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('77', '1', '1', 'เปตอง มัธยมศึกษาตอนปลาย (หญิงเดี่ยว)', 'ญ', 'เดี่ยว', '1', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('78', '1', '1', 'เปตอง ประถมศึกษาปีที่ 4 (ชายทีม 3)', 'ช', 'ทีม', '3', 'ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('79', '1', '1', 'เปตอง ประถมศึกษาปีที่ 5 (ชายทีม 3)', 'ช', 'ทีม', '3', 'ป5', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('80', '1', '1', 'เปตอง ประถมศึกษาปีที่ 6 (ชายทีม 3)', 'ช', 'ทีม', '3', 'ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('81', '1', '1', 'เปตอง ประถมศึกษาปีที่ 4 (หญิงทีม 3)', 'ญ', 'ทีม', '3', 'ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('82', '1', '1', 'เปตอง ประถมศึกษาปีที่ 5 (หญิงทีม 3)', 'ญ', 'ทีม', '3', 'ป5', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('83', '1', '1', 'เปตอง ประถมศึกษาปีที่ 6 (หญิงทีม 3)', 'ญ', 'ทีม', '3', 'ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('84', '1', '1', 'เปตอง มัธยมศึกษาปีที่ 1 (ชายทีม 3)', 'ช', 'ทีม', '3', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('85', '1', '1', 'เปตอง มัธยมศึกษาปีที่ 2-3 (ชายทีม 3)', 'ช', 'ทีม', '3', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('86', '1', '1', 'เปตอง มัธยมศึกษาตอนปลาย (ชายทีม 3)', 'ช', 'ทีม', '3', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('87', '1', '1', 'เปตอง มัธยมศึกษาปีที่ 1 (หญิงทีม 3)', 'ญ', 'ทีม', '3', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('88', '1', '1', 'เปตอง มัธยมศึกษาปีที่ 2-3 (หญิงทีม 3)', 'ญ', 'ทีม', '3', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('89', '1', '1', 'เปตอง มัธยมศึกษาตอนปลาย (หญิงทีม 3)', 'ญ', 'ทีม', '3', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('90', '1', '1', 'ตะกร้อ มัธยมศึกษาตอนต้น (ชาย)', 'ช', 'ทีม', '4', 'ม1,ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('91', '1', '1', 'ตะกร้อ มัธยมศึกษาตอนปลาย (ชาย)', 'ช', 'ทีม', '4', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('92', '1', '1', 'วอลเลย์บอล มัธยมศึกษาตอนต้น (ชาย)', 'ช', 'ทีม', '10', 'ม1,ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('93', '1', '1', 'วอลเลย์บอล มัธยมศึกษาตอนปลาย (ชาย)', 'ช', 'ทีม', '10', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('94', '1', '1', 'วอลเลย์บอล มัธยมศึกษาตอนต้น (หญิง)', 'ญ', 'ทีม', '10', 'ม1,ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('95', '1', '1', 'วอลเลย์บอล มัธยมศึกษาตอนปลาย (หญิง)', 'ญ', 'ทีม', '10', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('96', '1', '4', 'ฟุตบอล ประถมศึกษาปีที่ 4-6 (ชาย)', 'ช', 'ทีม', '15', 'ป4,ป5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('97', '1', '4', 'ฟุตบอล มัธยมศึกษาตอนต้น (ชาย)', 'ช', 'ทีม', '15', 'ม1,ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('98', '1', '4', 'ฟุตบอล มัธยมศึกษาตอนปลาย (ชาย)', 'ช', 'ทีม', '15', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('99', '1', '3', 'ชักเย่อ ประถมศึกษาปีที่ 1', 'รวม', 'ทีม', '10', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('100', '1', '3', 'ชักเย่อ ประถมศึกษาปีที่ 2', 'รวม', 'ทีม', '10', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('101', '1', '3', 'ชักเย่อ ประถมศึกษาปีที่ 3', 'รวม', 'ทีม', '10', 'ป3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('102', '1', '3', 'ชักเย่อ ประถมศึกษาปีที่ 4', 'รวม', 'ทีม', '10', 'ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('103', '1', '3', 'ชักเย่อ ประถมศึกษาปีที่ 5', 'รวม', 'ทีม', '10', 'ป5', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('104', '1', '3', 'ชักเย่อ ประถมศึกษาปีที่ 6', 'รวม', 'ทีม', '10', 'ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('105', '1', '3', 'ชักเย่อ มัธยมศึกษาตอนต้น', 'รวม', 'ทีม', '10', 'ม1,ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('106', '1', '3', 'ชักเย่อ มัธยมศึกษาตอนปลาย', 'รวม', 'ทีม', '10', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('107', '1', '3', 'วิ่งเปี้ยว ประถมศึกษาปีที่ 1', 'รวม', 'ทีม', '10', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('108', '1', '3', 'วิ่งเปี้ยว ประถมศึกษาปีที่ 2', 'รวม', 'ทีม', '10', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('109', '1', '3', 'วิ่งเปี้ยว ประถมศึกษาปีที่ 3', 'รวม', 'ทีม', '10', 'ป3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('110', '1', '3', 'วิ่งเปี้ยว ประถมศึกษาปีที่ 4', 'รวม', 'ทีม', '10', 'ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('111', '1', '3', 'วิ่งเปี้ยว ประถมศึกษาปีที่ 5', 'รวม', 'ทีม', '10', 'ป5', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('112', '1', '3', 'วิ่งเปี้ยว ประถมศึกษาปีที่ 6', 'รวม', 'ทีม', '10', 'ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('113', '1', '4', 'E-Sport ROV ประถมศึกษาปีที่ 3-4 (เดี่ยว)', 'รวม', 'เดี่ยว', '1', 'ป3,ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('114', '1', '4', 'E-Sport ROV ประถมศึกษาปีที่ 5-6 (เดี่ยว)', 'รวม', 'เดี่ยว', '1', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('115', '1', '4', 'E-Sport ROV มัธยมศึกษาปีที่ 1-2 (เดี่ยว)', 'รวม', 'เดี่ยว', '1', 'ม1,ม2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('116', '1', '4', 'E-Sport ROV มัธยมศึกษาปีที่ 3-4 (เดี่ยว)', 'รวม', 'เดี่ยว', '1', 'ม3,ม4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('117', '1', '4', 'E-Sport ROV มัธยมศึกษาปีที่ 5-6 (เดี่ยว)', 'รวม', 'เดี่ยว', '1', 'ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('118', '1', '4', 'E-Sport ROV ประถมศึกษาปีที่ 3-4', 'รวม', 'ทีม', '5', 'ป3,ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('119', '1', '4', 'E-Sport ROV ประถมศึกษาปีที่ 5-6', 'รวม', 'ทีม', '5', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('120', '1', '4', 'E-Sport ROV มัธยมศึกษาปีที่ 1-2', 'รวม', 'ทีม', '5', 'ม1,ม2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('121', '1', '4', 'E-Sport ROV มัธยมศึกษาปีที่ 3-4', 'รวม', 'ทีม', '5', 'ม3,ม4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('122', '1', '4', 'E-Sport ROV มัธยมศึกษาปีที่ 5-6', 'รวม', 'ทีม', '5', 'ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('123', '1', '1', 'ว่ายน้ำ เกาะโฟมเตะขา 18 เมตร ชาย ป.1', 'ช', 'เดี่ยว', '1', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('124', '1', '1', 'ว่ายน้ำ เกาะโฟมเตะขา 18 เมตร หญิง ป.1', 'ญ', 'เดี่ยว', '1', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('125', '1', '1', 'ว่ายน้ำ เกาะโฟมเตะขา 18 เมตร ชาย ป.2', 'ช', 'เดี่ยว', '1', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('126', '1', '1', 'ว่ายน้ำ เกาะโฟมเตะขา 18 เมตร หญิง ป.2', 'ญ', 'เดี่ยว', '1', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('127', '1', '1', 'ว่ายน้ำ เกาะโฟมเตะขา 18 เมตร ชาย ป.3', 'ช', 'เดี่ยว', '1', 'ป3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('128', '1', '1', 'ว่ายน้ำ เกาะโฟมเตะขา 18 เมตร หญิง ป.3', 'ญ', 'เดี่ยว', '1', 'ป3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('129', '1', '1', 'ว่ายน้ำ ฟรีสไตล์ 25 เมตร ชาย ป.4', 'ช', 'เดี่ยว', '1', 'ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('130', '1', '1', 'ว่ายน้ำ ฟรีสไตล์ 25 เมตร หญิง ป.4', 'ญ', 'เดี่ยว', '1', 'ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('131', '1', '1', 'ว่ายน้ำ ฟรีสไตล์ 25 เมตร ชาย ป.5', 'ช', 'เดี่ยว', '1', 'ป5', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('132', '1', '1', 'ว่ายน้ำ ฟรีสไตล์ 25 เมตร หญิง ป.5', 'ญ', 'เดี่ยว', '1', 'ป5', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('133', '1', '1', 'ว่ายน้ำ ฟรีสไตล์ 25 เมตร ชาย ป.6', 'ช', 'เดี่ยว', '1', 'ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('134', '1', '1', 'ว่ายน้ำ ฟรีสไตล์ 25 เมตร หญิง ป.6', 'ญ', 'เดี่ยว', '1', 'ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('135', '1', '1', 'ว่ายน้ำ เกาะโฟมเตะขา 25 เมตร ชาย ป.4', 'ช', 'เดี่ยว', '1', 'ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('136', '1', '1', 'ว่ายน้ำ เกาะโฟมเตะขา 25 เมตร หญิง ป.4', 'ญ', 'เดี่ยว', '1', 'ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('137', '1', '1', 'ว่ายน้ำ เกาะโฟมเตะขา 25 เมตร ชาย ป.5', 'ช', 'เดี่ยว', '1', 'ป5', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('138', '1', '1', 'ว่ายน้ำ เกาะโฟมเตะขา 25 เมตร หญิง ป.5', 'ญ', 'เดี่ยว', '1', 'ป5', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('139', '1', '1', 'ว่ายน้ำ เกาะโฟมเตะขา 25 เมตร ชาย ป.6', 'ช', 'เดี่ยว', '1', 'ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('140', '1', '1', 'ว่ายน้ำ เกาะโฟมเตะขา 25 เมตร หญิง ป.6', 'ญ', 'เดี่ยว', '1', 'ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('141', '1', '1', 'ว่ายน้ำ ผลัดเกาะโฟม 18 เมตร ชาย ป.1', 'ช', 'ทีม', '2', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('142', '1', '1', 'ว่ายน้ำ ผลัดเกาะโฟม 18 เมตร หญิง ป.1', 'ญ', 'ทีม', '2', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('143', '1', '1', 'ว่ายน้ำ ผลัดเกาะโฟม 18 เมตร ชาย ป.2', 'ช', 'ทีม', '2', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('144', '1', '1', 'ว่ายน้ำ ผลัดเกาะโฟม 18 เมตร หญิง ป.2', 'ญ', 'ทีม', '2', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('145', '1', '1', 'ว่ายน้ำ ผลัดเกาะโฟม 18 เมตร ชาย ป.3', 'ช', 'ทีม', '2', 'ป3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('146', '1', '1', 'ว่ายน้ำ ผลัดเกาะโฟม 18 เมตร หญิง ป.3', 'ญ', 'ทีม', '2', 'ป3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('147', '1', '1', 'ว่ายน้ำ ผลัดเกาะโฟม 25 เมตร ชาย ป.4', 'ช', 'ทีม', '2', 'ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('148', '1', '1', 'ว่ายน้ำ ผลัดเกาะโฟม 25 เมตร หญิง ป.4', 'ญ', 'ทีม', '2', 'ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('149', '1', '1', 'ว่ายน้ำ ผลัดเกาะโฟม 25 เมตร ชาย ป.5', 'ช', 'ทีม', '2', 'ป5', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('150', '1', '1', 'ว่ายน้ำ ผลัดเกาะโฟม 25 เมตร หญิง ป.5', 'ญ', 'ทีม', '2', 'ป5', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('151', '1', '1', 'ว่ายน้ำ ผลัดเกาะโฟม 25 เมตร ชาย ป.6', 'ช', 'ทีม', '2', 'ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('152', '1', '1', 'ว่ายน้ำ ผลัดเกาะโฟม 25 เมตร หญิง ป.6', 'ญ', 'ทีม', '2', 'ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('153', '1', '4', 'แบดมินตัน (ชายเดี่ยว) ป.4-6', 'ช', 'เดี่ยว', '1', 'ป4,ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('154', '1', '4', 'แบดมินตัน (หญิงเดี่ยว) ป.4-6', 'ญ', 'เดี่ยว', '1', 'ป4,ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('155', '1', '4', 'แบดมินตัน (ชายเดี่ยว) ม.1-2', 'ช', 'เดี่ยว', '1', 'ม1,ม2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('156', '1', '4', 'แบดมินตัน (หญิงเดี่ยว) ม.1-2', 'ญ', 'เดี่ยว', '1', 'ม1,ม2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('157', '1', '4', 'แบดมินตัน (ชายเดี่ยว) ม.3-4', 'ช', 'เดี่ยว', '1', 'ม3,ม4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('158', '1', '4', 'แบดมินตัน (หญิงเดี่ยว) ม.3-4', 'ญ', 'เดี่ยว', '1', 'ม3,ม4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('159', '1', '4', 'แบดมินตัน (ชายเดี่ยว) ม.5-6', 'ช', 'เดี่ยว', '1', 'ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('160', '1', '4', 'แบดมินตัน (หญิงเดี่ยว) ม.5-6', 'ญ', 'เดี่ยว', '1', 'ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('161', '1', '4', 'แบดมินตัน (ชายคู่) มัธยมศึกษาตอนปลาย', 'ช', 'ทีม', '2', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('162', '1', '4', 'แบดมินตัน (หญิงคู่) มัธยมศึกษาตอนปลาย', 'ญ', 'ทีม', '2', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('163', '1', '4', 'แบดมินตัน (ชาย-หญิงคู่) มัธยมศึกษาตอนปลาย', 'รวม', 'ทีม', '2', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('164', '1', '2', 'วิ่ง 60 เมตร ชาย ป.5-6', 'ช', 'เดี่ยว', '2', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('165', '1', '2', 'วิ่ง 60 เมตร หญิง ป.5-6', 'ญ', 'เดี่ยว', '2', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('166', '1', '2', 'วิ่ง 60 เมตร ชาย ป.3-4', 'ช', 'เดี่ยว', '2', 'ป3,ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('167', '1', '2', 'วิ่ง 60 เมตร หญิง ป.3-4', 'ญ', 'เดี่ยว', '2', 'ป3,ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('168', '1', '2', 'วิ่ง 60 เมตร ชาย ป.2', 'ช', 'เดี่ยว', '2', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('169', '1', '2', 'วิ่ง 60 เมตร หญิง ป.2', 'ญ', 'เดี่ยว', '2', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('170', '1', '2', 'วิ่ง 60 เมตร ชาย ป.1', 'ช', 'เดี่ยว', '2', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('171', '1', '2', 'วิ่ง 60 เมตร หญิง ป.1', 'ญ', 'เดี่ยว', '2', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('172', '1', '2', 'วิ่ง 80 เมตร ชาย ป.5-6', 'ช', 'เดี่ยว', '2', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('173', '1', '2', 'วิ่ง 80 เมตร หญิง ป.5-6', 'ญ', 'เดี่ยว', '2', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('174', '1', '2', 'วิ่ง 80 เมตร ชาย ป.3-4', 'ช', 'เดี่ยว', '2', 'ป3,ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('175', '1', '2', 'วิ่ง 80 เมตร หญิง ป.3-4', 'ญ', 'เดี่ยว', '2', 'ป3,ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('176', '1', '2', 'วิ่ง 80 เมตร ชาย ป.2', 'ช', 'เดี่ยว', '2', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('177', '1', '2', 'วิ่ง 80 เมตร หญิง ป.2', 'ญ', 'เดี่ยว', '2', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('178', '1', '2', 'วิ่ง 80 เมตร ชาย ป.1', 'ช', 'เดี่ยว', '2', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('179', '1', '2', 'วิ่ง 80 เมตร หญิง ป.1', 'ญ', 'เดี่ยว', '2', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('180', '1', '2', 'วิ่ง 100 เมตร ชาย ม.5-6', 'ช', 'เดี่ยว', '2', 'ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('181', '1', '2', 'วิ่ง 100 เมตร หญิง ม.5-6', 'ญ', 'เดี่ยว', '2', 'ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('182', '1', '2', 'วิ่ง 100 เมตร ชาย ม.4', 'ช', 'เดี่ยว', '2', 'ม4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('183', '1', '2', 'วิ่ง 100 เมตร หญิง ม.4', 'ญ', 'เดี่ยว', '2', 'ม4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('184', '1', '2', 'วิ่ง 100 เมตร ชาย ม.2-3', 'ช', 'เดี่ยว', '2', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('185', '1', '2', 'วิ่ง 100 เมตร หญิง ม.2-3', 'ญ', 'เดี่ยว', '2', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('186', '1', '2', 'วิ่ง 100 เมตร ชาย ม.1', 'ช', 'เดี่ยว', '2', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('187', '1', '2', 'วิ่ง 100 เมตร หญิง ม.1', 'ญ', 'เดี่ยว', '2', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('188', '1', '2', 'วิ่ง 100 เมตร ชาย ป.5-6', 'ช', 'เดี่ยว', '2', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('189', '1', '2', 'วิ่ง 100 เมตร หญิง ป.5-6', 'ญ', 'เดี่ยว', '2', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('190', '1', '2', 'วิ่ง 100 เมตร ชาย ป.3-4', 'ช', 'เดี่ยว', '2', 'ป3,ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('191', '1', '2', 'วิ่ง 100 เมตร หญิง ป.3-4', 'ญ', 'เดี่ยว', '2', 'ป3,ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('192', '1', '2', 'วิ่ง 100 เมตร ชาย ป.2', 'ช', 'เดี่ยว', '2', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('193', '1', '2', 'วิ่ง 100 เมตร หญิง ป.2', 'ญ', 'เดี่ยว', '2', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('194', '1', '2', 'วิ่ง 100 เมตร ชาย ป.1', 'ช', 'เดี่ยว', '2', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('195', '1', '2', 'วิ่ง 100 เมตร หญิง ป.1', 'ญ', 'เดี่ยว', '2', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('196', '1', '2', 'วิ่ง 200 เมตร ชาย ม.5-6', 'ช', 'เดี่ยว', '2', 'ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('197', '1', '2', 'วิ่ง 200 เมตร หญิง ม.5-6', 'ญ', 'เดี่ยว', '2', 'ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('198', '1', '2', 'วิ่ง 200 เมตร ชาย ม.4', 'ช', 'เดี่ยว', '2', 'ม4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('199', '1', '2', 'วิ่ง 200 เมตร หญิง ม.4', 'ญ', 'เดี่ยว', '2', 'ม4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('200', '1', '2', 'วิ่ง 200 เมตร ชาย ม.2-3', 'ช', 'เดี่ยว', '2', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('201', '1', '2', 'วิ่ง 200 เมตร หญิง ม.2-3', 'ญ', 'เดี่ยว', '2', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('202', '1', '2', 'วิ่ง 200 เมตร ชาย ม.1', 'ช', 'เดี่ยว', '2', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('203', '1', '2', 'วิ่ง 200 เมตร หญิง ม.1', 'ญ', 'เดี่ยว', '2', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('204', '1', '2', 'วิ่ง 200 เมตร ชาย ป.5-6', 'ช', 'เดี่ยว', '2', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('205', '1', '2', 'วิ่ง 200 เมตร หญิง ป.5-6', 'ญ', 'เดี่ยว', '2', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('206', '1', '2', 'วิ่ง 400 เมตร ชาย ม.5-6', 'ช', 'เดี่ยว', '2', 'ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('207', '1', '2', 'วิ่ง 400 เมตร หญิง ม.5-6', 'ญ', 'เดี่ยว', '2', 'ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('208', '1', '2', 'วิ่ง 400 เมตร ชาย ม.4', 'ช', 'เดี่ยว', '2', 'ม4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('209', '1', '2', 'วิ่ง 400 เมตร หญิง ม.4', 'ญ', 'เดี่ยว', '2', 'ม4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('210', '1', '2', 'วิ่ง 400 เมตร ชาย ม.2-3', 'ช', 'เดี่ยว', '2', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('211', '1', '2', 'วิ่ง 400 เมตร หญิง ม.2-3', 'ญ', 'เดี่ยว', '2', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('212', '1', '2', 'วิ่งผลัด 8 x 50 เมตร ชาย ป.1', 'ช', 'ทีม', '8', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('213', '1', '2', 'วิ่งผลัด 8 x 50 เมตร หญิง ป.1', 'ญ', 'ทีม', '8', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('214', '1', '2', 'วิ่งผลัด 8 x 50 เมตร ชาย ป.2', 'ช', 'ทีม', '8', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('215', '1', '2', 'วิ่งผลัด 8 x 50 เมตร หญิง ป.2', 'ญ', 'ทีม', '8', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('216', '1', '2', 'วิ่งผลัด 8 x 50 เมตร ชาย ป.3-4', 'ช', 'ทีม', '8', 'ป3,ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('217', '1', '2', 'วิ่งผลัด 8 x 50 เมตร หญิง ป.3-4', 'ญ', 'ทีม', '8', 'ป3,ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('218', '1', '2', 'วิ่งผลัด 8 x 50 เมตร ชาย ป.5-6', 'ช', 'ทีม', '8', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('219', '1', '2', 'วิ่งผลัด 8 x 50 เมตร หญิง ป.5-6', 'ญ', 'ทีม', '8', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('220', '1', '2', 'วิ่งผลัด 5 x 80 เมตร ชาย ป.1', 'ช', 'ทีม', '5', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('221', '1', '2', 'วิ่งผลัด 5 x 80 เมตร หญิง ป.1', 'ญ', 'ทีม', '5', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('222', '1', '2', 'วิ่งผลัด 5 x 80 เมตร ชาย ป.2', 'ช', 'ทีม', '5', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('223', '1', '2', 'วิ่งผลัด 5 x 80 เมตร หญิง ป.2', 'ญ', 'ทีม', '5', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('224', '1', '2', 'วิ่งผลัด 5 x 80 เมตร ชาย ป.3-4', 'ช', 'ทีม', '5', 'ป3,ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('225', '1', '2', 'วิ่งผลัด 5 x 80 เมตร หญิง ป.3-4', 'ญ', 'ทีม', '5', 'ป3,ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('226', '1', '2', 'วิ่งผลัด 5 x 80 เมตร ชาย ป.5-6', 'ช', 'ทีม', '5', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('227', '1', '2', 'วิ่งผลัด 5 x 80 เมตร หญิง ป.5-6', 'ญ', 'ทีม', '5', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('228', '1', '2', 'วิ่งผลัด 5 x 80 เมตร ชาย ม.1', 'ช', 'ทีม', '5', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('229', '1', '2', 'วิ่งผลัด 5 x 80 เมตร หญิง ม.1', 'ญ', 'ทีม', '5', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('230', '1', '2', 'วิ่งผลัด 4 x 100 เมตร ชาย ม.5-6', 'ช', 'ทีม', '4', 'ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('231', '1', '2', 'วิ่งผลัด 4 x 100 เมตร หญิง ม.5-6', 'ญ', 'ทีม', '4', 'ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('232', '1', '2', 'วิ่งผลัด 4 x 100 เมตร ชาย ม.4', 'ช', 'ทีม', '4', 'ม4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('233', '1', '2', 'วิ่งผลัด 4 x 100 เมตร หญิง ม.4', 'ญ', 'ทีม', '4', 'ม4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('234', '1', '2', 'วิ่งผลัด 4 x 100 เมตร ชาย ม.2-3', 'ช', 'ทีม', '4', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('235', '1', '2', 'วิ่งผลัด 4 x 100 เมตร หญิง ม.2-3', 'ญ', 'ทีม', '4', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('236', '1', '2', 'วิ่งผลัด 4 x 100 เมตร ชาย ม.1', 'ช', 'ทีม', '4', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('237', '1', '2', 'วิ่งผลัด 4 x 100 เมตร หญิง ม.1', 'ญ', 'ทีม', '4', 'ม1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('238', '1', '2', 'วิ่งผลัด 4 x 100 เมตร ชาย ป.5-6', 'ช', 'ทีม', '4', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('239', '1', '2', 'วิ่งผลัด 4 x 100 เมตร หญิง ป.5-6', 'ญ', 'ทีม', '4', 'ป5,ป6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('240', '1', '2', 'วิ่งผลัด 4 x 100 เมตร ชาย ป.3-4', 'ช', 'ทีม', '4', 'ป3,ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('241', '1', '2', 'วิ่งผลัด 4 x 100 เมตร หญิง ป.3-4', 'ญ', 'ทีม', '4', 'ป3,ป4', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('242', '1', '2', 'วิ่งผลัด 4 x 100 เมตร ชาย ป.2', 'ช', 'ทีม', '4', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('243', '1', '2', 'วิ่งผลัด 4 x 100 เมตร หญิง ป.2', 'ญ', 'ทีม', '4', 'ป2', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('244', '1', '2', 'วิ่งผลัด 4 x 100 เมตร ชาย ป.1', 'ช', 'ทีม', '4', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('245', '1', '2', 'วิ่งผลัด 4 x 100 เมตร หญิง ป.1', 'ญ', 'ทีม', '4', 'ป1', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('246', '1', '2', 'วิ่งผลัด 4 x 400 เมตร ชาย ม.2-3', 'ช', 'ทีม', '4', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('247', '1', '2', 'วิ่งผลัด 4 x 400 เมตร หญิง ม.2-3', 'ญ', 'ทีม', '4', 'ม2,ม3', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('248', '1', '2', 'วิ่งผลัด 4 x 400 เมตร ชาย มัธยมปลาย', 'ช', 'ทีม', '4', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');
INSERT INTO `sports` VALUES ('249', '1', '2', 'วิ่งผลัด 4 x 400 เมตร หญิง มัธยมปลาย', 'ญ', 'ทีม', '4', 'ม4,ม5,ม6', '1', '2025-11-03 17:12:07');

DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year_id` int(10) unsigned NOT NULL,
  `student_code` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `class_level` varchar(10) NOT NULL,
  `class_room` int(11) NOT NULL,
  `number_in_room` int(11) NOT NULL,
  `color` enum('ส้ม','เขียว','ชมพู','ฟ้า') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_student_year_code` (`year_id`,`student_code`),
  KEY `idx_year_color` (`year_id`,`color`),
  KEY `idx_year_class` (`year_id`,`class_level`,`class_room`),
  CONSTRAINT `fk_students_year` FOREIGN KEY (`year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1757 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `students` VALUES ('23', '1', '6271', 'เด็กชายธีรกานต์', 'สุดล้ำเลิศ', 'ป.1', '1', '1', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('24', '1', '6555', 'เด็กชายวรปรัชญ์', 'มูลทรัพย์', 'ป.1', '1', '2', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('25', '1', '6702', 'เด็กชายภูวณัฏฐ์', 'ธงชัย', 'ป.1', '1', '3', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('26', '1', '6705', 'เด็กชายชาติอาชาไนย', 'ฟักขำ', 'ป.1', '1', '4', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('27', '1', '6707', 'เด็กชายพัชระ', 'กองสุข', 'ป.1', '1', '5', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('28', '1', '6710', 'เด็กชายชลกร', 'คงสงค์', 'ป.1', '1', '6', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('29', '1', '6718', 'เด็กชายพีรวิชญ์', 'อุณหทวีทรัพย์', 'ป.1', '1', '7', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('30', '1', '6719', 'เด็กชายพรพิพัฒน์', 'ภัทรทวีโชคชัย', 'ป.1', '1', '8', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('31', '1', '6728', 'เด็กชายภูดิส', 'นามนวล', 'ป.1', '1', '9', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('32', '1', '6978', 'เด็กชายปพัณกร', 'คชศาสตร์ศิลป์', 'ป.1', '1', '10', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('33', '1', '6979', 'เด็กชายณภควรรธน์', 'เหมันต์', 'ป.1', '1', '11', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('34', '1', '7460', 'เด็กชายกฤติน', 'ไข่ลือนาม', 'ป.1', '1', '12', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('35', '1', '7462', 'เด็กชายชนกันต์', 'คำคล้อย', 'ป.1', '1', '13', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('36', '1', '7464', 'เด็กชายณัฐภูมิข์', 'ปิ่นแก้ว', 'ป.1', '1', '14', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('37', '1', '7465', 'เด็กชายตรัยรัตน์', 'เลิศสกุล', 'ป.1', '1', '15', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('38', '1', '6563', 'เด็กหญิงรมย์นริน', 'ชูหา', 'ป.1', '1', '16', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('39', '1', '6564', 'เด็กหญิงรัญชิดา', 'คงศิริ', 'ป.1', '1', '17', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('40', '1', '6566', 'เด็กหญิงพิมพา', 'เจริญสุขใส', 'ป.1', '1', '18', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('41', '1', '6736', 'เด็กหญิงนีรนฎา', 'พวงเดช', 'ป.1', '1', '19', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('42', '1', '6740', 'เด็กหญิงแพรพลอย', 'หงษ์ศรีทอง', 'ป.1', '1', '20', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('43', '1', '6754', 'เด็กหญิงคณิศร', 'พันทะคุ', 'ป.1', '1', '21', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('44', '1', '6780', 'เด็กหญิงเอรียา', 'เธียรฐิติธัช', 'ป.1', '1', '22', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('45', '1', '6793', 'เด็กหญิงปวริศา', 'ทรัพย์มา', 'ป.1', '1', '23', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('46', '1', '6986', 'เด็กหญิงพิมพ์พิศา', 'เลิศเข็มนาค', 'ป.1', '1', '24', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('47', '1', '7479', 'เด็กหญิงญาณิศา', 'ไชยสาร', 'ป.1', '1', '25', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('48', '1', '7480', 'เด็กหญิงฐานิตา', 'รอบคอบ', 'ป.1', '1', '26', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('49', '1', '7481', 'เด็กหญิงอัญยา', 'ฤกษ์อจลานนท์', 'ป.1', '1', '27', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('50', '1', '7486', 'เด็กหญิงสุชญาพัฒน์', 'รุธิรบริสุทธิ์', 'ป.1', '1', '28', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('51', '1', '6261', 'เด็กชายรักษ์พงศ์', 'น้อยรักษา', 'ป.1', '2', '1', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('52', '1', '6272', 'เด็กชายกิตติ', 'ตั้งวัชโรบล', 'ป.1', '2', '2', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('53', '1', '6559', 'เด็กชายธนธรณ์', 'ปานเจริญ', 'ป.1', '2', '3', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('54', '1', '6703', 'เด็กชายนนท์กฤช', 'กิจชมภู', 'ป.1', '2', '4', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('55', '1', '6706', 'เด็กชายคุณภัทร', 'โพธิ์อุไร', 'ป.1', '2', '5', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('56', '1', '6711', 'เด็กชายอคิราห์', 'แก่นจันทร์', 'ป.1', '2', '6', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('57', '1', '6712', 'เด็กชายณัฐพัชร์', 'สรนันทน์สกุล', 'ป.1', '2', '7', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('58', '1', '6720', 'เด็กชายปวิช', 'แย้มสำรวล', 'ป.1', '2', '8', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('59', '1', '6723', 'เด็กชายณภัทร', 'อิสโร', 'ป.1', '2', '9', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('60', '1', '6725', 'เด็กชายภูวฤทธิ์', 'ตันหยง', 'ป.1', '2', '10', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('61', '1', '6792', 'เด็กชายภาคิน', 'ยามโนภาพ', 'ป.1', '2', '11', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('62', '1', '6982', 'เด็กชายพิชยพล', 'โชติดิฐสกุล', 'ป.1', '2', '12', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('63', '1', '6983', 'เด็กชายธนกฤต', 'เพ็งแจ่ม', 'ป.1', '2', '13', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('64', '1', '7461', 'เด็กชายกฤศกร', 'สกุลเมือง', 'ป.1', '2', '14', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('65', '1', '7466', 'เด็กชายภูวรินทร์', 'เส็งสมวงศ์', 'ป.1', '2', '15', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('66', '1', '7467', 'เด็กชายดวิน', 'วรวัฒนานนท์', 'ป.1', '2', '16', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('67', '1', '6562', 'เด็กหญิงณภัทร', 'สิทธิชัย', 'ป.1', '2', '17', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('68', '1', '6565', 'เด็กหญิงกัญญาดา', 'กล่อมสวัสดิ์', 'ป.1', '2', '18', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('69', '1', '6732', 'เด็กหญิงภคอร', 'วงศ์สุวรรณ', 'ป.1', '2', '19', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('70', '1', '6735', 'เด็กหญิงเฌอมาวีร์', 'ธานีประเสริฐ', 'ป.1', '2', '20', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('71', '1', '6738', 'เด็กหญิงณฐพร', 'จันทนะโสตถิ์', 'ป.1', '2', '21', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('72', '1', '6752', 'เด็กหญิงนรินทร์พร', 'บุตรรอด', 'ป.1', '2', '22', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('73', '1', '6753', 'เด็กหญิงชญาภา', 'พี่พานิช', 'ป.1', '2', '23', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('74', '1', '6775', 'เด็กหญิงรินรดา', 'ภัคไพโรจน์', 'ป.1', '2', '24', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('75', '1', '7226', 'เด็กหญิงลภัสรดา', 'ดาวนันท์', 'ป.1', '2', '25', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('76', '1', '7477', 'เด็กหญิงณัฐพร', 'รุ่มนุ่ม', 'ป.1', '2', '26', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('77', '1', '7484', 'เด็กหญิงณิชารัชท์', 'มีเขียว', 'ป.1', '2', '27', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('78', '1', '7485', 'เด็กหญิงธัญชนก', 'กุลโฮง', 'ป.1', '2', '28', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('79', '1', '7488', 'เด็กหญิงกชมล', 'ชอบงาม', 'ป.1', '2', '29', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('80', '1', '6557', 'เด็กชายภูดิศ', 'เจ้ยทองศรี', 'ป.1', '3', '1', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('81', '1', '6558', 'เด็กชายพีรพงศ์', 'น้อยพิทักษ์', 'ป.1', '3', '2', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('82', '1', '6708', 'เด็กชายธัญกฤษฏิ์', 'ถ่อนาวา', 'ป.1', '3', '3', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('83', '1', '6713', 'เด็กชายวงศธร', 'ศิริสัจจวัฒน์', 'ป.1', '3', '4', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('84', '1', '6715', 'เด็กชายปุณณพัฒน์', 'วาณิชย์กุล', 'ป.1', '3', '5', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('85', '1', '6727', 'เด็กชายพลวัฒน์', 'สุขจำเริญศรี', 'ป.1', '3', '6', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('86', '1', '6730', 'เด็กชายติณณภพ', 'มีบางยาง', 'ป.1', '3', '7', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('87', '1', '6766', 'เด็กชายชนุดม', 'วงศ์พินิจ', 'ป.1', '3', '8', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('88', '1', '6777', 'เด็กชายภูมิรพี', 'ตรีพงษ์พันธ์', 'ป.1', '3', '9', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('89', '1', '6981', 'เด็กชายชนกันต์', 'นะราแก้ว', 'ป.1', '3', '10', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('90', '1', '7459', 'เด็กชายธนภัทร', 'วิเชียรรัตน์', 'ป.1', '3', '11', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('91', '1', '7468', 'เด็กชายณัฏฐกิตติ์', 'ซ่อนกลิ่น', 'ป.1', '3', '12', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('92', '1', '7470', 'เด็กชายถิรวุษิ', 'ขาวแก้ว', 'ป.1', '3', '13', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('93', '1', '7471', 'เด็กชายธาวิน', 'ชุนถนอม', 'ป.1', '3', '14', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('94', '1', '6561', 'เด็กหญิงณัฐชยา', 'สีลาโคตร', 'ป.1', '3', '15', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('95', '1', '6733', 'เด็กหญิงณรัชต์หทัย', 'สุขสกุลวัฒน์', 'ป.1', '3', '16', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('96', '1', '6741', 'เด็กหญิงปิยธิดา', 'บุญสม', 'ป.1', '3', '17', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('97', '1', '6742', 'เด็กหญิงอภิษฎา', 'แก้วจรัส', 'ป.1', '3', '18', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('98', '1', '6745', 'เด็กหญิงสาริศา', 'กิตติพิชัย', 'ป.1', '3', '19', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('99', '1', '6746', 'เด็กหญิงปิ่นธิดา', 'ยิ้มสำราญ', 'ป.1', '3', '20', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('100', '1', '6747', 'เด็กหญิงณิชวรกาญจน์', 'คงทับทิม', 'ป.1', '3', '21', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('101', '1', '6984', 'เด็กหญิงญาติกา', 'ปรีเปรมโอน', 'ป.1', '3', '22', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('102', '1', '6985', 'เด็กหญิงกัญญ์กุลณัช', 'จันดี', 'ป.1', '3', '23', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('103', '1', '7227', 'เด็กหญิงธีรดา', 'เอื้อเศรษฐ์ถาวร', 'ป.1', '3', '24', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('104', '1', '7475', 'เด็กหญิงศศิมา', 'ทัศนิยม', 'ป.1', '3', '25', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('105', '1', '7482', 'เด็กหญิงณภาภัช', 'ปัญโญนันท์', 'ป.1', '3', '26', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('106', '1', '7483', 'เด็กหญิงพัชรินทร์', 'พวงมาลัย', 'ป.1', '3', '27', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('107', '1', '7578', 'เด็กหญิงกัญจน์กมล', 'สวัสดิ์เสริมศรี', 'ป.1', '3', '28', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('108', '1', '6721', 'เด็กชายสหรัฐ', 'วิริยะธนาศิริ', 'ป.1', '3', '29', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('109', '1', '6554', 'เด็กชายกฤษธนดล', 'จันแสงทอง', 'ป.1', '4', '1', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('110', '1', '6556', 'เด็กชายธนกร', 'สุภาพจันทร์', 'ป.1', '4', '2', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('111', '1', '6626', 'เด็กชายอคิณ', 'เกตุแก้ว', 'ป.1', '4', '3', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('112', '1', '6700', 'เด็กชายพีรัชชัย', 'ตัณฑ์พานิช', 'ป.1', '4', '4', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('113', '1', '6714', 'เด็กชายธนทัต', 'จันทร์เอี่ยม', 'ป.1', '4', '5', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('114', '1', '6722', 'เด็กชายเอื้ออังกูร', 'เย็นสุขใจชน', 'ป.1', '4', '6', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('115', '1', '6726', 'เด็กชายศักดิพัต', 'ตุ้งสวัสดิ์', 'ป.1', '4', '7', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('116', '1', '6774', 'เด็กชายณัทกร', 'รุ่งประกายพรรณ', 'ป.1', '4', '8', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('117', '1', '6980', 'เด็กชายกฤษกร', 'คูณคำ', 'ป.1', '4', '9', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('118', '1', '7225', 'เด็กชายปรัชญากรณ์', 'ท่าทราย', 'ป.1', '4', '10', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('119', '1', '7463', 'เด็กชายชญตว์', 'ภูพันนา', 'ป.1', '4', '11', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('120', '1', '7469', 'เด็กชายณปภัทร', 'จินดาเย็น', 'ป.1', '4', '12', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('121', '1', '7472', 'เด็กชายคเณศ', 'จุ่นหัวโทน', 'ป.1', '4', '13', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('122', '1', '7473', 'เด็กชายธณเดช', 'ธนัตพรกุล', 'ป.1', '4', '14', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('123', '1', '6560', 'เด็กหญิงอุรัสยา', 'คำสาลี', 'ป.1', '4', '15', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('124', '1', '6582', 'เด็กหญิงวาสนา', 'อยู่ยืนยง', 'ป.1', '4', '16', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('125', '1', '6734', 'เด็กหญิงณัชชา', 'พาหา', 'ป.1', '4', '17', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('126', '1', '6737', 'เด็กหญิงพลอยขวัญ', 'รัตนเขมากร', 'ป.1', '4', '18', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('127', '1', '6739', 'เด็กหญิงชลิตา', 'บุตรน้ำเพชร', 'ป.1', '4', '19', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('128', '1', '6744', 'เด็กหญิงปวีณ์ญาดา', 'กี่คงเดิม', 'ป.1', '4', '20', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('129', '1', '6749', 'เด็กหญิงสุขฤดู', 'กิติกรเศรษฐ์', 'ป.1', '4', '21', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('130', '1', '6987', 'เด็กหญิงพิสุทธิณัฏฐ์', 'สิงห์โต', 'ป.1', '4', '22', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('131', '1', '7228', 'เด็กหญิงกานต์ธีรา', 'เพ็ชรนิล', 'ป.1', '4', '23', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('132', '1', '7229', 'เด็กหญิงภควกานต์', 'เหลืองขมิ้น', 'ป.1', '4', '24', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('133', '1', '7474', 'เด็กหญิงปรียาดา', 'หุ่นรูปหล่อ', 'ป.1', '4', '25', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('134', '1', '7476', 'เด็กหญิงฐิดายุ', 'รัตนปัญญากุล', 'ป.1', '4', '26', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('135', '1', '7478', 'เด็กหญิงนันทัชพร', 'ล้อมวงษ์', 'ป.1', '4', '27', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('136', '1', '7487', 'เด็กหญิงหทัยกานต์', 'หทัยเกียรติกุล', 'ป.1', '4', '28', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('137', '1', '7593', 'เด็กชายพิริยชัช', 'ประดิษฐ', 'ป.1', '4', '29', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('138', '1', '6257', 'เด็กชายชนนันท์', 'ใจกล่ำ', 'ป.2', '1', '1', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('139', '1', '6259', 'เด็กชายวุฒิภัทร', 'สุวรรณโชติ', 'ป.2', '1', '2', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('140', '1', '6270', 'เด็กชายกวิภัฏ', 'ศิริบุญฤทธิ์', 'ป.2', '1', '3', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('141', '1', '6499', 'เด็กชายเปรมปิยะ', 'ทอมุด', 'ป.2', '1', '4', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('142', '1', '6501', 'เด็กชายวรางกูร', 'ใจสุดา', 'ป.2', '1', '5', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('143', '1', '6503', 'เด็กชายธีรภัทร', 'สุทธารมย์', 'ป.2', '1', '6', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('144', '1', '6510', 'เด็กชายพุฒิพงศ์', 'จีนประชา', 'ป.2', '1', '7', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('145', '1', '6517', 'เด็กชายธนิสร', 'สุมะโมสกุล', 'ป.2', '1', '8', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('146', '1', '6518', 'เด็กชายภัชวกรณ์', 'คงไพร', 'ป.2', '1', '9', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('147', '1', '6691', 'เด็กชายธนพัศพงศ์', 'รงค์จันทมานนท์', 'ป.2', '1', '10', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('148', '1', '6971', 'เด็กชายธนบดี', 'สืบสมุทร', 'ป.2', '1', '11', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('149', '1', '7194', 'เด็กชายนราวิชญ์', 'จินาอ่อน', 'ป.2', '1', '12', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('150', '1', '7200', 'เด็กชายธีรวัฒน์', 'เฮงจินดา', 'ป.2', '1', '13', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('151', '1', '7201', 'เด็กชายธนัชกฤศ', 'คำสิงห์', 'ป.2', '1', '14', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('152', '1', '7449', 'เด็กชายอัยยวัฒน์', 'เจ้ยทองศรี', 'ป.2', '1', '15', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('153', '1', '7455', 'เด็กชายก้าวชนุตม์', 'นัดสูงวงษ์', 'ป.2', '1', '16', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('154', '1', '6273', 'เด็กหญิงพัชรีวรรณ', 'เสถียรยานนท์', 'ป.2', '1', '17', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('155', '1', '6276', 'เด็กหญิงสาธิตา', 'กิติคุณ', 'ป.2', '1', '18', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('156', '1', '6278', 'เด็กหญิงเฌอภัทร', 'กิติกรเศรษฐ์', 'ป.2', '1', '19', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('157', '1', '6279', 'เด็กหญิงณัฐิดา', 'สามประทีป', 'ป.2', '1', '20', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('158', '1', '6523', 'เด็กหญิงธัญญาภรณ์', 'คงอาษา', 'ป.2', '1', '21', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('159', '1', '6528', 'เด็กหญิงปริตตา', 'โรจนอำพล', 'ป.2', '1', '22', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('160', '1', '6530', 'เด็กหญิงปัญยพัชร์', 'อิสริยฤทธานนท์', 'ป.2', '1', '23', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('161', '1', '6540', 'เด็กหญิงเพ็ญพิมล', 'ธนฤกษ์ชัย', 'ป.2', '1', '24', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('162', '1', '6542', 'เด็กหญิงผกามาศ', 'แสนรักษ์', 'ป.2', '1', '25', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('163', '1', '6543', 'เด็กหญิงนภัสร์นันท์', 'กัลยาณรัตน์', 'ป.2', '1', '26', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('164', '1', '6731', 'เด็กหญิงณัฐรินีย์', 'อุณหทวีทรัพย์', 'ป.2', '1', '28', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('165', '1', '7073', 'เด็กหญิงเณศรา', 'พานทอง', 'ป.2', '1', '29', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('166', '1', '7209', 'เด็กหญิงกัญญ์ณัชชา', 'น้ำทรง', 'ป.2', '1', '30', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('167', '1', '7210', 'เด็กหญิงลิลณฎา', 'กลิ่นทอง', 'ป.2', '1', '31', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('168', '1', '7216', 'เด็กหญิงกนกอร', 'สุขผล', 'ป.2', '1', '32', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('169', '1', '7220', 'เด็กหญิงปวีณ์นุช', 'สุจริตบรรณ', 'ป.2', '1', '33', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('170', '1', '6256', 'เด็กชายปัณณวิชญ์', 'ช่วยสร้าง', 'ป.2', '2', '1', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('171', '1', '6264', 'เด็กชายกตัญญู', 'อรรถพลภูษิต', 'ป.2', '2', '2', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('172', '1', '6265', 'เด็กชายกฤษกร', 'อินพาเพียร', 'ป.2', '2', '3', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('173', '1', '6313', 'เด็กชายณัทกฤช', 'นันทชัยปรีชา', 'ป.2', '2', '4', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('174', '1', '6505', 'เด็กชายภูดิท', 'ทองอำไพ', 'ป.2', '2', '5', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('175', '1', '6507', 'เด็กชายกิตติรักข์', 'แก้วจินดา', 'ป.2', '2', '6', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('176', '1', '6508', 'เด็กชายรฐนนท์', 'รุ่งเรืองศุภรัตน์', 'ป.2', '2', '7', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('177', '1', '6516', 'เด็กชายจารุวัฒน์', 'จ้อยชะรัด', 'ป.2', '2', '8', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('178', '1', '6692', 'เด็กชายธนพัฒน์', 'ลือยาม', 'ป.2', '2', '9', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('179', '1', '6698', 'เด็กชายดลภัทร', 'เทียนวงค์', 'ป.2', '2', '10', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('180', '1', '6972', 'เด็กชายปาณชัย', 'พลเมือง', 'ป.2', '2', '11', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('181', '1', '7203', 'เด็กชายเป็นเอก', 'ปฐมศิระโรจน์', 'ป.2', '2', '12', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('182', '1', '7204', 'เด็กชายบุญชนะ', 'กำไลทอง', 'ป.2', '2', '13', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('183', '1', '7206', 'เด็กชายอชิระ', 'อินพาเพียร', 'ป.2', '2', '14', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('184', '1', '7451', 'เด็กชายอัครวัฒณ์', 'ศิริชัยเอกวัฒน์', 'ป.2', '2', '15', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('185', '1', '5746', 'เด็กหญิงพัชญา', 'สุวรรณ', 'ป.2', '2', '16', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('186', '1', '6274', 'เด็กหญิงลลนา', 'เข็มแดง', 'ป.2', '2', '17', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('187', '1', '6281', 'เด็กหญิงญาดา', 'สุขเกตุ', 'ป.2', '2', '18', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('188', '1', '6282', 'เด็กหญิงชาคริยา', 'ปึงธนานุกิจ', 'ป.2', '2', '19', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('189', '1', '6284', 'เด็กหญิงสิริพรรณพิมล', 'โสภณอุดมพร', 'ป.2', '2', '20', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('190', '1', '6532', 'เด็กหญิงธัญสินี', 'บางเขม็ด', 'ป.2', '2', '21', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('191', '1', '6537', 'เด็กหญิงบุญญาพร', 'ไชยนอก', 'ป.2', '2', '22', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('192', '1', '6546', 'เด็กหญิงบัวสักการ์', 'สมานมิตร', 'ป.2', '2', '23', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('193', '1', '6548', 'เด็กหญิงกิตติธรา', 'กิตติวโรดม', 'ป.2', '2', '24', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('194', '1', '6551', 'เด็กหญิงธัญวลัย', 'โพธิ์รักษา', 'ป.2', '2', '25', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('195', '1', '6552', 'เด็กหญิงสิริอัปสร', 'แสงเกื้อหนุน', 'ป.2', '2', '26', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('196', '1', '7208', 'เด็กหญิงพรรณพร', 'เสียงเจริญ', 'ป.2', '2', '27', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('197', '1', '7214', 'เด็กหญิงคุณิตา', 'องคะศาสตร์', 'ป.2', '2', '28', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('198', '1', '7217', 'เด็กหญิงกฤตญกร', 'กรียินดี', 'ป.2', '2', '29', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('199', '1', '7218', 'เด็กหญิงธัญจิรา', 'ทองผิว', 'ป.2', '2', '30', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('200', '1', '7221', 'เด็กหญิงณิชาดา', 'สามเพชรเจริญ', 'ป.2', '2', '31', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('201', '1', '7457', 'เด็กหญิงณัฏฐ์ชานันท์', 'เฉียวเฉ่ง', 'ป.2', '2', '32', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('202', '1', '6544', 'เด็กหญิงพิมพ์พิศา', 'เรืองกิตติคุณ', 'ป.2', '2', '27', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('203', '1', '6258', 'เด็กชายถิรณัฏฐ์', 'อัศวเดชฤทธิ์', 'ป.2', '3', '1', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('204', '1', '6263', 'เด็กชายณวัสน์', 'กล้าหาญ', 'ป.2', '3', '2', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('205', '1', '6268', 'เด็กชายชวภณ', 'ไกรทองสุข', 'ป.2', '3', '3', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('206', '1', '6328', 'เด็กชายพรภวิษย์', 'ปุจฉาการ', 'ป.2', '3', '4', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('207', '1', '6506', 'เด็กชายธฤต', 'จิตรศิลป์ฉายากุล', 'ป.2', '3', '5', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('208', '1', '6513', 'เด็กชายชวิศ', 'แช่มมั่นคง', 'ป.2', '3', '6', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('209', '1', '6515', 'เด็กชายณัฐกิตติ์', 'ห้องสวัสดิ์', 'ป.2', '3', '7', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('210', '1', '6695', 'เด็กชายกมลภู', 'สามแก้ว', 'ป.2', '3', '8', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('211', '1', '6696', 'เด็กชายวุฒิภัทร', 'รอดภัย', 'ป.2', '3', '9', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('212', '1', '6697', 'เด็กชายนนทพัทธ์', 'หนูอินทร์', 'ป.2', '3', '10', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('213', '1', '7196', 'เด็กชายศรัณวุฒิ', 'ทรัพย์จรัสแสง', 'ป.2', '3', '11', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('214', '1', '7197', 'เด็กชายศรัณภัทร', 'ทรัพย์จรัสแสง', 'ป.2', '3', '12', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('215', '1', '7198', 'เด็กชายนิติธร', 'มณีโชติ', 'ป.2', '3', '13', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('216', '1', '7450', 'เด็กชายวงศ์วสุ', 'มีปัดชา', 'ป.2', '3', '14', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('217', '1', '6283', 'เด็กหญิงณภัชกมล', 'เมฆพัฒน์', 'ป.2', '3', '15', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('218', '1', '6286', 'เด็กหญิงปุณฑริก', 'ไตรเรืองกิจ', 'ป.2', '3', '16', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('219', '1', '6291', 'เด็กหญิงอัญชิษฐา', 'นาภะสินธุ์', 'ป.2', '3', '17', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('220', '1', '6326', 'เด็กหญิงชัชชญา', 'วรวิชญาวิวัฒน์', 'ป.2', '3', '18', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('221', '1', '6522', 'เด็กหญิงดนิตา', 'ทองอ่วม', 'ป.2', '3', '19', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('222', '1', '6529', 'เด็กหญิงกัญญาภัทร', 'พัฒนา', 'ป.2', '3', '20', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('223', '1', '6535', 'เด็กหญิงศิริภัสสร', 'สกุลกิตติพงษ์', 'ป.2', '3', '21', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('224', '1', '6536', 'เด็กหญิงเพ็ญกนิฎฐ์', 'กิจนิธิธาดา', 'ป.2', '3', '22', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('225', '1', '6550', 'เด็กหญิงสิณียกร', 'จ้างประเสริฐ', 'ป.2', '3', '23', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('226', '1', '6553', 'เด็กหญิงทิพานัน', 'คำจันทร์', 'ป.2', '3', '24', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('227', '1', '6699', 'เด็กหญิงชญาภา', 'เอี๊ยวเจริญ', 'ป.2', '3', '25', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('228', '1', '7212', 'เด็กหญิงกมลณัช', 'เรณูธรณ์', 'ป.2', '3', '26', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('229', '1', '7213', 'เด็กหญิงณัฐปภาดา', 'ฤทธิรณยุทธ', 'ป.2', '3', '27', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('230', '1', '7215', 'เด็กหญิงจิรัชญา', 'แสงทอง', 'ป.2', '3', '28', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('231', '1', '7223', 'เด็กหญิงอภิญญา', 'ผลเจริญรัตน์', 'ป.2', '3', '29', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('232', '1', '7456', 'เด็กหญิงพัทธ์ธีรา', 'ชมภูนิช', 'ป.2', '3', '30', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('233', '1', '7458', 'เด็กหญิงศิริกานดา', 'เข็มประดับ', 'ป.2', '3', '31', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('234', '1', '7592', 'เด็กหญิงอินทร์ญาดา', 'บำรุง', 'ป.2', '3', '32', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('235', '1', '6260', 'เด็กชายนเรศ', 'อุสาจิตร', 'ป.2', '4', '1', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('236', '1', '6266', 'เด็กชายศุภวุฒ', 'จั่นเรไร', 'ป.2', '4', '2', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('237', '1', '6329', 'เด็กชายปรีชากร', 'ชื่นเจริญ', 'ป.2', '4', '3', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('238', '1', '6500', 'เด็กชายชิณกฤช', 'พี่พานิช', 'ป.2', '4', '4', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('239', '1', '6502', 'เด็กชายอติวิชญ์', 'พาละแพน', 'ป.2', '4', '5', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('240', '1', '6512', 'เด็กชายนนธวัช', 'สุทธสม', 'ป.2', '4', '6', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('241', '1', '6514', 'เด็กชายพีระพัฒน์', 'กุ่ยสาคร', 'ป.2', '4', '7', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('242', '1', '6690', 'เด็กชายทวีรัชต์', 'ขุนทรง', 'ป.2', '4', '8', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('243', '1', '6693', 'เด็กชายกมลภพ', 'ทับไกร', 'ป.2', '4', '9', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('244', '1', '6694', 'เด็กชายจิรพงศ์', 'ส่งชัย', 'ป.2', '4', '10', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('245', '1', '7195', 'เด็กชายพงศกร', 'จันทร์สุขจำเริญ', 'ป.2', '4', '11', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('246', '1', '7199', 'เด็กชายชุติเดช', 'ทองประเสริฐ', 'ป.2', '4', '12', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('247', '1', '7202', 'เด็กชายธรัชท์', 'มีเขียว', 'ป.2', '4', '13', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('248', '1', '7205', 'เด็กชายปภิณวิชญ์', 'ดิษฐเกษร', 'ป.2', '4', '14', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('249', '1', '7452', 'เด็กชายธนกร', 'ประยุรกานต์', 'ป.2', '4', '15', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('250', '1', '7453', 'เด็กชายณัฐชวกรณ์', 'ทิพย์เลอเลิศ', 'ป.2', '4', '16', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('251', '1', '6301', 'เด็กหญิงธีนิดา', 'เกิดทอง', 'ป.2', '4', '17', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('252', '1', '6302', 'เด็กหญิงณธิดา', 'ผาตะโชติ', 'ป.2', '4', '18', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('253', '1', '6314', 'เด็กหญิงณิชาภา', 'เอมอ่อน', 'ป.2', '4', '19', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('254', '1', '6324', 'เด็กหญิงลลิล', 'เอี๊ยะมณี', 'ป.2', '4', '20', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('255', '1', '6521', 'เด็กหญิงสมัชญา', 'วรวงษ์', 'ป.2', '4', '21', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('256', '1', '6526', 'เด็กหญิงฉัตรปวีณ์', 'อริยทันโตศรี', 'ป.2', '4', '22', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('257', '1', '6527', 'เด็กหญิงขวัญจิรา', 'กุ่ยสาคร', 'ป.2', '4', '23', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('258', '1', '6531', 'เด็กหญิงปาลิดา', 'ปุเลทะตัง', 'ป.2', '4', '24', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('259', '1', '6541', 'เด็กหญิงชนาภา', 'อาบกิ่ง', 'ป.2', '4', '25', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('260', '1', '6545', 'เด็กหญิงณิชชา', 'ตุ้งสวัสดิ์', 'ป.2', '4', '26', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('261', '1', '6974', 'เด็กหญิงพุธิตา', 'พงศ์เนาวรัตน์', 'ป.2', '4', '27', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('262', '1', '6976', 'เด็กหญิงปรียาวดี', 'บุญทรง', 'ป.2', '4', '28', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('263', '1', '6977', 'เด็กหญิงดาวิกา', 'ศิริเมฆารักษ์', 'ป.2', '4', '29', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('264', '1', '7211', 'เด็กหญิงธนัชชา', 'พันธ์จีน', 'ป.2', '4', '30', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('265', '1', '7222', 'เด็กหญิงธนัชญา', 'ส่งเจริญทรัพย์', 'ป.2', '4', '31', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('266', '1', '7224', 'เด็กหญิงจิดาภา', 'วัฒนะพานิช', 'ป.2', '4', '32', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('267', '1', '5910', 'เด็กชายอชิรวิชญ์', 'จรดล', 'ป.3', '1', '1', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('268', '1', '5912', 'เด็กชายณัทพิพัฒน์', 'พรพรหมมาตร', 'ป.3', '1', '2', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('269', '1', '5913', 'เด็กชายธีร์ธวัช', 'สุดล้ำเลิศ', 'ป.3', '1', '3', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('270', '1', '5926', 'เด็กชายภูณัช', 'กลีบบัว', 'ป.3', '1', '4', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('271', '1', '5963', 'เด็กชายชลณวฤธิณ์', 'โพธิ์นา', 'ป.3', '1', '5', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('272', '1', '6216', 'เด็กชายณภัทร', 'นาภะสินธุ์', 'ป.3', '1', '6', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('273', '1', '6219', 'เด็กชายจิราวัฒน์', 'ล้ำเลิศเรืองไกร', 'ป.3', '1', '7', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('274', '1', '6222', 'เด็กชายภูเบศร์', 'เชาว์พานนท์', 'ป.3', '1', '8', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('275', '1', '6231', 'เด็กชายเนตรนรินทร์', 'เนตรจินดา', 'ป.3', '1', '9', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('276', '1', '6234', 'เด็กชายพฏาศรัย', 'โรจนบุรานนท์', 'ป.3', '1', '10', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('277', '1', '6925', 'เด็กชายธีรราช', 'เพ็ชรนิล', 'ป.3', '1', '11', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('278', '1', '6949', 'เด็กชายวงศกร', 'ปิ่นทับทิม', 'ป.3', '1', '12', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('279', '1', '6951', 'เด็กชายอเล็กส์', 'สุริย์แสง', 'ป.3', '1', '13', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('280', '1', '6952', 'เด็กชายสุวิศิษฏ์', 'ญาณวิทิต', 'ป.3', '1', '14', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('281', '1', '7445', 'เด็กชายณัฐวัศ', 'ศัทธาชิณศรี', 'ป.3', '1', '15', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('282', '1', '7446', 'เด็กชายอธิวัฒน์', 'มาลาคำ', 'ป.3', '1', '16', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('283', '1', '5588', 'เด็กหญิงพริมา', 'อัมระรงค์', 'ป.3', '1', '17', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('284', '1', '5918', 'เด็กหญิงภทรพร', 'กิมเห', 'ป.3', '1', '18', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('285', '1', '5919', 'เด็กหญิงรมิดา', 'อินทร์รอด', 'ป.3', '1', '19', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('286', '1', '5921', 'เด็กหญิงศศินันท์', 'ปราชญ์ชำนาญ', 'ป.3', '1', '20', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('287', '1', '5930', 'เด็กหญิงลลิตา', 'ตลับทอง', 'ป.3', '1', '21', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('288', '1', '5931', 'เด็กหญิงธัญญภัสร์', 'เจียมคุณานนท์', 'ป.3', '1', '22', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('289', '1', '5933', 'เด็กหญิงณัฏฐณิชา', 'เรืองศรี', 'ป.3', '1', '23', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('290', '1', '5964', 'เด็กหญิงรัชภัฏ', 'นวมหอม', 'ป.3', '1', '24', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('291', '1', '5973', 'เด็กหญิงณัฐรดา', 'ตาคำไชย', 'ป.3', '1', '25', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('292', '1', '6244', 'เด็กหญิงภุชชัชชา', 'เล็กล้วน', 'ป.3', '1', '26', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('293', '1', '6968', 'เด็กหญิงณัฏฐณิชา', 'ซ่อนกลิ่น', 'ป.3', '1', '27', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('294', '1', '7448', 'เด็กหญิงบุศรินทร์', 'พวงมาลัย', 'ป.3', '1', '28', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('295', '1', '6237', 'เด็กหญิงกรภัทร์', 'ฉายทองจันทร์', 'ป.3', '1', '29', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('296', '1', '5995', 'เด็กชายธนุธรณ์', 'แจ่มถนอม', 'ป.3', '2', '1', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('297', '1', '6220', 'เด็กชายสิปปกร', 'แจ้งภักดี', 'ป.3', '2', '2', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('298', '1', '6221', 'เด็กชายสิปปวิชญ์', 'แจ้งภักดี', 'ป.3', '2', '3', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('299', '1', '6224', 'เด็กชายณัฐพล', 'แซ่เจี่ย', 'ป.3', '2', '4', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('300', '1', '6225', 'เด็กชายศุภวิชญ์', 'ดำรงค์ธวัชชัย', 'ป.3', '2', '5', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('301', '1', '6317', 'เด็กชายนนทกร', 'พัฒนานันท์', 'ป.3', '2', '6', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('302', '1', '6490', 'เด็กชายปุณณพัฒน์', 'ศรีอนันต์', 'ป.3', '2', '7', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('303', '1', '6492', 'เด็กชายสรพัศ', 'ศรีทา', 'ป.3', '2', '8', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('304', '1', '6687', 'เด็กชายชรัลธร', 'วิจิตร', 'ป.3', '2', '9', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('305', '1', '6957', 'เด็กชายชินภัทร', 'ชังแต้ม', 'ป.3', '2', '10', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('306', '1', '6958', 'เด็กชายชัฎฬ์', 'อักษร', 'ป.3', '2', '11', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('307', '1', '7069', 'เด็กชายธน', 'ใจซื่อ', 'ป.3', '2', '12', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('308', '1', '7188', 'เด็กชายวรชิต', 'มั่นหมาย', 'ป.3', '2', '13', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('309', '1', '7443', 'เด็กชายนรัตน์นนท์', 'วิวัฒนภูษิต', 'ป.3', '2', '14', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('310', '1', '5564', 'เด็กหญิงชนกานต์', 'แต่งงาม', 'ป.3', '2', '15', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('311', '1', '5935', 'เด็กหญิงภรณ์ชนก', 'อุดสมใจ', 'ป.3', '2', '16', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('312', '1', '5972', 'เด็กหญิงปิ่นปินัทธ์', 'ไทรชมภู', 'ป.3', '2', '17', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('313', '1', '5976', 'เด็กหญิงรสลิน', 'ฤกษ์นาวี', 'ป.3', '2', '18', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('314', '1', '5994', 'เด็กหญิงฉัตรชนก', 'กระถินทอง', 'ป.3', '2', '19', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('315', '1', '6236', 'เด็กหญิงกุลชญา', 'กิจเจา', 'ป.3', '2', '20', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('316', '1', '6242', 'เด็กหญิงปาณิชา', 'ทรัพย์เงินทอง', 'ป.3', '2', '21', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('317', '1', '6245', 'เด็กหญิงอัยย์ญาดา', 'ภูทวี', 'ป.3', '2', '22', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('318', '1', '6252', 'เด็กหญิงอภิญญา', 'จักขุทิพย์', 'ป.3', '2', '23', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('319', '1', '6254', 'เด็กหญิงรินธารา', 'ศิรินิวัฒน์ชัย', 'ป.3', '2', '24', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('320', '1', '6790', 'เด็กหญิงลลิสา', 'นามวงศ์อภิชาติ', 'ป.3', '2', '25', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('321', '1', '6969', 'เด็กหญิงปณิดา', 'เหมือยพรม', 'ป.3', '2', '26', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('322', '1', '7192', 'เด็กหญิงอารีย์รัตน์', 'ชินศรีสุข', 'ป.3', '2', '27', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('323', '1', '7323', 'เด็กหญิงสติมา', 'จิตรมณี', 'ป.3', '2', '28', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('324', '1', '5987', 'เด็กหญิงปริญญ์รดา', 'อุณหทรงธรรม', 'ป.3', '2', '29', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('325', '1', '5911', 'เด็กชายสุวิกรม', 'ตั้งวัชโรบล', 'ป.3', '3', '1', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('326', '1', '5915', 'เด็กชายภควิน', 'แย้มบางยาง', 'ป.3', '3', '2', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('327', '1', '5925', 'เด็กชายปัณณธร', 'แก้วกระจ่าง', 'ป.3', '3', '3', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('328', '1', '6217', 'เด็กชายวัชรเกียรติ์', 'สุจิตธนานนท์', 'ป.3', '3', '4', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('329', '1', '6218', 'เด็กชายปภาดา', 'ทรัพย์มา', 'ป.3', '3', '5', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('330', '1', '6227', 'เด็กชายปกรณ์เกียรติ', 'ปสาทนีย์', 'ป.3', '3', '6', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('331', '1', '6230', 'เด็กชายอิทธิพัทธ์', 'พรเทวบัญชา', 'ป.3', '3', '7', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('332', '1', '6233', 'เด็กชายธนวินท์', 'ทองอำไพ', 'ป.3', '3', '8', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('333', '1', '6489', 'เด็กชายปิยากร', 'ไวยวรณ์', 'ป.3', '3', '9', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('334', '1', '6948', 'เด็กชายปิติภัทร', 'พลเมือง', 'ป.3', '3', '10', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('335', '1', '6954', 'เด็กชายวิสุทธิคุณ', 'วิวัฒน์สถิตกุล', 'ป.3', '3', '11', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('336', '1', '6955', 'เด็กชายเดชาภัทร', 'ยั่งยืน', 'ป.3', '3', '12', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('337', '1', '7189', 'เด็กชายปัณณวิชญ์', 'ดิษฐเกษร', 'ป.3', '3', '13', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('338', '1', '7312', 'เด็กชายชัชนันท์', 'นักคุ่ย', 'ป.3', '3', '14', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('339', '1', '7444', 'เด็กชายฐณวัฒน์', 'บุญวัฒนาจิรภาคย์', 'ป.3', '3', '15', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('340', '1', '5917', 'เด็กหญิงนนทกร', 'จรดล', 'ป.3', '3', '16', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('341', '1', '5920', 'เด็กหญิงกันต์ภัสสรณ์', 'น้อยพิทักษ์', 'ป.3', '3', '17', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('342', '1', '5929', 'เด็กหญิงรักษิณา', 'สุดปฐม', 'ป.3', '3', '18', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('343', '1', '5962', 'เด็กหญิงปัณฑารีย์', 'วัชรอัตยาพล', 'ป.3', '3', '19', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('344', '1', '5974', 'เด็กหญิงรวินทิรา', 'อิศราวุธพงษา', 'ป.3', '3', '20', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('345', '1', '6241', 'เด็กหญิงปาณิดา', 'ทรัพย์เงินทอง', 'ป.3', '3', '21', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('346', '1', '6250', 'เด็กหญิงทอปัด', 'เอกเผ่าพันธุ์', 'ป.3', '3', '22', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('347', '1', '6255', 'เด็กหญิงนิชกานต์', 'สุกเกษม', 'ป.3', '3', '23', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('348', '1', '6960', 'เด็กหญิงอารยา', 'อ่างบุญพงษ์', 'ป.3', '3', '24', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('349', '1', '6966', 'เด็กหญิงพิมพ์ชนก', 'เส็งสมวงศ์', 'ป.3', '3', '25', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('350', '1', '6967', 'เด็กหญิงสุทธิดา', 'นุ่มนวล', 'ป.3', '3', '26', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('351', '1', '7191', 'เด็กหญิงพิมพ์พิชชา', 'ยอดอิสราเกษ', 'ป.3', '3', '27', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('352', '1', '7447', 'เด็กหญิงณัฏฐณิชา', 'สมาธิ', 'ป.3', '3', '28', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('353', '1', '7591', 'เด็กหญิงพันธ์วิรา', 'บาโง้ย', 'ป.3', '3', '29', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('354', '1', '5928', 'เด็กชายสุวพิชญ์', 'ทนทาน', 'ป.3', '4', '1', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('355', '1', '6223', 'เด็กชายปรณต', 'เกตุแก้ว', 'ป.3', '4', '2', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('356', '1', '6226', 'เด็กชายจุมพล', 'คงสงค์', 'ป.3', '4', '3', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('357', '1', '6232', 'เด็กชายอัครสาสน์', 'โพธิ์เงิน', 'ป.3', '4', '4', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('358', '1', '6235', 'เด็กชายภูมิพศุตม์', 'เนตรโพธิ์แก้ว', 'ป.3', '4', '5', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('359', '1', '6305', 'เด็กชายอนันตชัย', 'ช่อฉาย', 'ป.3', '4', '6', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('360', '1', '6315', 'เด็กชายภูวิศ', 'แสงเกื้อหนุน', 'ป.3', '4', '7', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('361', '1', '6331', 'เด็กชายรวิภัทร', 'เชิญธรรมพร', 'ป.3', '4', '8', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('362', '1', '6491', 'เด็กชายธีธัช', 'รอดท่าไม้', 'ป.3', '4', '9', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('363', '1', '6493', 'เด็กชายอาชัญ', 'การะปักษ์', 'ป.3', '4', '10', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('364', '1', '6583', 'เด็กชายไตรรัตน์', 'บุตรน้ำเพชร', 'ป.3', '4', '11', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('365', '1', '6688', 'เด็กชายดนัยณัฏฐ์', 'ดียะตาม', 'ป.3', '4', '12', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('366', '1', '6946', 'เด็กชายธนันชัย', 'ตระกูลกาญจน์', 'ป.3', '4', '13', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('367', '1', '6947', 'เด็กชายพัชรินทร์', 'ลาดมี', 'ป.3', '4', '14', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('368', '1', '6953', 'เด็กชายภทรวุฒิ', 'ศรีสำราญ', 'ป.3', '4', '15', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('369', '1', '5985', 'เด็กหญิงชญาน์ทิพย์', 'ทรัพย์พานิช', 'ป.3', '4', '16', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('370', '1', '6240', 'เด็กหญิงปาณิภา', 'ทรัพย์เงินทอง', 'ป.3', '4', '17', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('371', '1', '6246', 'เด็กหญิงปพิชญา', 'สุดสังข์', 'ป.3', '4', '18', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('372', '1', '6251', 'เด็กหญิงพรปวีณ์', 'นิยมเดชา', 'ป.3', '4', '19', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('373', '1', '6298', 'เด็กหญิงสุกัญญา', 'กงประดิษฐ์', 'ป.3', '4', '20', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('374', '1', '6495', 'เด็กหญิงกัญญาณัท', 'การรักษา', 'ป.3', '4', '21', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('375', '1', '6496', 'เด็กหญิงลิลกาล', 'ของฮอม', 'ป.3', '4', '22', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('376', '1', '6497', 'เด็กหญิงพิราวรรณ', 'เจริญสุขใส', 'ป.3', '4', '23', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('377', '1', '6689', 'เด็กหญิงชัชญาณิจฌ์', 'เกยืน', 'ป.3', '4', '24', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('378', '1', '6961', 'เด็กหญิงปานชีวา', 'นันดี', 'ป.3', '4', '25', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('379', '1', '6964', 'เด็กหญิงโชติกา', 'ศิริเมฆารักษ์', 'ป.3', '4', '26', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('380', '1', '6965', 'เด็กหญิงวรัสยา', 'รีเอี่ยม', 'ป.3', '4', '27', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('381', '1', '6970', 'เด็กหญิงพิชญาภา', 'พุกยิ้ม', 'ป.3', '4', '28', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('382', '1', '7190', 'เด็กหญิงรวิสรา', 'กิมะพันธุ์', 'ป.3', '4', '29', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('383', '1', '5519', 'เด็กชายวชิรวิชญิ์', 'ครองลาภเจริญ', 'ป.4', '1', '1', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('384', '1', '5855', 'เด็กชายกฤษกร', 'อนุตธโต', 'ป.4', '1', '2', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('385', '1', '5856', 'เด็กชายชินณานันท์', 'พัชรอาภา', 'ป.4', '1', '3', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('386', '1', '5857', 'เด็กชายกฤติเดช', 'ธนัญชยะ', 'ป.4', '1', '4', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('387', '1', '5858', 'เด็กชายอภิวิชญ์', 'หงษ์ทอง', 'ป.4', '1', '5', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('388', '1', '5859', 'เด็กชายพลวัต', 'ยมนา', 'ป.4', '1', '6', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('389', '1', '6213', 'เด็กชายบูรพา', 'ดิษฐประชา', 'ป.4', '1', '7', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('390', '1', '6484', 'เด็กชายณัฐพัชร์', 'อิสริยฤทธานนท์', 'ป.4', '1', '8', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('391', '1', '6673', 'เด็กชายชัชพงศ์', 'แก้วอินทร์ศรี', 'ป.4', '1', '9', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('392', '1', '6782', 'เด็กชายชิษณุพงศ์', 'นาคนคร', 'ป.4', '1', '10', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('393', '1', '7072', 'เด็กชายกฤชพล', 'สิงห์ศาลา', 'ป.4', '1', '11', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('394', '1', '7175', 'เด็กชายนันทิภาคย์', 'ขะสำปทวน', 'ป.4', '1', '12', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('395', '1', '7177', 'เด็กชายบาโรดี  ณรงค์', 'เซดี', 'ป.4', '1', '13', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('396', '1', '7181', 'เด็กชายชยพล', 'อัมพร', 'ป.4', '1', '14', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('397', '1', '7439', 'เด็กชายณัฏฐ์พัฒต์', 'เฉียวเฉ่ง', 'ป.4', '1', '15', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('398', '1', '7577', 'เด็กชายกรณพัฒน์', 'บุญสิน', 'ป.4', '1', '16', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('399', '1', '5528', 'เด็กหญิงธันย์ชิตา', 'นุชพุก', 'ป.4', '1', '17', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('400', '1', '5529', 'เด็กหญิงชญาดา', 'ศรีสวัสดิ์', 'ป.4', '1', '18', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('401', '1', '5533', 'เด็กหญิงชญานภัส', 'ปุจฉาการ', 'ป.4', '1', '19', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('402', '1', '5536', 'เด็กหญิงพิมพ์ชนก', 'ธีราพงษ์', 'ป.4', '1', '20', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('403', '1', '5586', 'เด็กหญิงกานดา', 'เซี่ยงว่อง', 'ป.4', '1', '21', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('404', '1', '5862', 'เด็กหญิงวิศัลย์ศยา', 'ศรีสกุล', 'ป.4', '1', '22', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('405', '1', '5864', 'เด็กหญิงสินีนาถ', 'พืชพันธ์', 'ป.4', '1', '23', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('406', '1', '5866', 'เด็กหญิงพชรธิดา', 'ลิ้มสงวน', 'ป.4', '1', '24', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('407', '1', '6014', 'เด็กหญิงกฤติญา', 'ไชยพรชลิดา', 'ป.4', '1', '25', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('408', '1', '6215', 'เด็กหญิงอภิชญา', 'รุ่งโรจน์สาคร', 'ป.4', '1', '26', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('409', '1', '6679', 'เด็กหญิงพชิรา', 'พัดกลม', 'ป.4', '1', '27', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('410', '1', '6943', 'เด็กหญิงณิชรัตน์', 'เธียรภัทรวิทย์', 'ป.4', '1', '28', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('411', '1', '7183', 'เด็กหญิงธัญญามาศ', 'น้อยประชา', 'ป.4', '1', '29', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('412', '1', '7186', 'เด็กหญิงธัญชนก', 'เสาร์โพธิ์งาม', 'ป.4', '1', '30', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('413', '1', '7440', 'เด็กหญิงนันทิดา', 'โสภากุล', 'ป.4', '1', '31', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('414', '1', '5518', 'เด็กชายปถพล', 'พูลสวัสดิ์', 'ป.4', '2', '1', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('415', '1', '5525', 'เด็กชายกันตภณ', 'ศิริบูรณ์', 'ป.4', '2', '2', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('416', '1', '5868', 'เด็กชายพอรชา', 'เสือสิงห์', 'ป.4', '2', '3', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('417', '1', '5869', 'เด็กชายศุภณัฐ์', 'สีปักษา', 'ป.4', '2', '4', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('418', '1', '5870', 'เด็กชายรณภัทร', 'ช่างเกวียนดี', 'ป.4', '2', '5', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('419', '1', '5873', 'เด็กชายณัฏฐกิตติ์', 'พงษ์บัณฑิต', 'ป.4', '2', '6', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('420', '1', '5874', 'เด็กชายนวิน', 'สรนันทน์สกุล', 'ป.4', '2', '7', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('421', '1', '5875', 'เด็กชายภัทรเดช', 'บุญยกุลวิโรจน์', 'ป.4', '2', '8', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('422', '1', '6485', 'เด็กชายธีร์ธวัช', 'บุญยะ', 'ป.4', '2', '9', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('423', '1', '6675', 'เด็กชายปวิช', 'เลิศชาญฤทธ์', 'ป.4', '2', '10', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('424', '1', '6940', 'เด็กชายณัฏฐชัย', 'ชาญช่าง', 'ป.4', '2', '11', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('425', '1', '6941', 'เด็กชายอัศม์เดช', 'เหลืองขมิ้น', 'ป.4', '2', '12', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('426', '1', '7068', 'เด็กชายภาคิน', 'จันทร์ขจร', 'ป.4', '2', '13', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('427', '1', '7178', 'เด็กชายธีร์', 'แต่เจริญ', 'ป.4', '2', '14', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('428', '1', '7180', 'เด็กชายธัชภูมิ', 'ปัญญาบุญ', 'ป.4', '2', '15', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('429', '1', '7437', 'เด็กชายณัฐภูมินทร์', 'ทิพย์เลอเลิศ', 'ป.4', '2', '16', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('430', '1', '5531', 'เด็กหญิงวรดา', 'มูลทรัพย์', 'ป.4', '2', '17', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('431', '1', '5582', 'เด็กหญิงณัฐณิชชา', 'แย้มสุวรรณ', 'ป.4', '2', '18', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('432', '1', '5583', 'เด็กหญิงณัฐธิดา', 'ผาตะโชติ', 'ป.4', '2', '19', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('433', '1', '5877', 'เด็กหญิงวรวรรณ', 'อินทเกษ', 'ป.4', '2', '20', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('434', '1', '5878', 'เด็กหญิงญาดา', 'น้อยรักษา', 'ป.4', '2', '21', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('435', '1', '5880', 'เด็กหญิงวริศรา', 'อ่วมสืบเชื้อ', 'ป.4', '2', '22', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('436', '1', '5881', 'เด็กหญิงกานต์ธิดา', 'สมนาค', 'ป.4', '2', '23', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('437', '1', '5996', 'เด็กหญิงณิภัสชา', 'อุณหทวีทรัพย์', 'ป.4', '2', '24', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('438', '1', '6292', 'เด็กหญิงพิมพ์นารา', 'คงใจดี', 'ป.4', '2', '25', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('439', '1', '6587', 'เด็กหญิงญาณิศา', 'โพธิ์รักษา', 'ป.4', '2', '26', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('440', '1', '6682', 'เด็กหญิงณิลิน', 'เจริญวัย', 'ป.4', '2', '27', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('441', '1', '6779', 'เด็กหญิงสินินทร์', 'ผลาหาญ', 'ป.4', '2', '28', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('442', '1', '6945', 'เด็กหญิงรชาภัทร', 'จงสุขสันติกุล', 'ป.4', '2', '29', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('443', '1', '7185', 'เด็กหญิงพัทธนันท์', 'ทรัพย์ส่งแสง', 'ป.4', '2', '30', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('444', '1', '7442', 'เด็กหญิงรุจิรดา', 'ประยุรกานต์', 'ป.4', '2', '31', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('445', '1', '5523', 'เด็กชายคุณัชญ์', 'โพธิ์ทองคำ', 'ป.4', '3', '1', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('446', '1', '5524', 'เด็กชายสรัญพงศ์', 'จันทร์แจ่มฟ้า', 'ป.4', '3', '2', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('447', '1', '5587', 'เด็กชายพีระพัฒน์', 'กิจบำรุง', 'ป.4', '3', '3', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('448', '1', '5883', 'เด็กชายกิตเตชิษฐ์', 'ชูเชิด', 'ป.4', '3', '4', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('449', '1', '5884', 'เด็กชายณัฐธร', 'ไตรพุฒิคุณ', 'ป.4', '3', '5', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('450', '1', '5886', 'เด็กชายตะวัน', 'ภมรสูต', 'ป.4', '3', '6', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('451', '1', '5887', 'เด็กชายนฤบดินทร์', 'วังคำสาย', 'ป.4', '3', '7', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('452', '1', '5888', 'เด็กชายณัชญ์ภสิฐ', 'เฮงจินดาสิริธณัท', 'ป.4', '3', '8', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('453', '1', '5960', 'เด็กชายอิทธิศักดิ์', 'โพธิ์เสือ', 'ป.4', '3', '9', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('454', '1', '6304', 'เด็กชายภูภณ', 'ประกิจบุญฤทธิ์', 'ป.4', '3', '10', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('455', '1', '6674', 'เด็กชายณฐนนท์', 'หนูเปีย', 'ป.4', '3', '11', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('456', '1', '6677', 'เด็กชายนันทพัทธ์', 'สุริยากุลพันธ์', 'ป.4', '3', '12', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('457', '1', '6942', 'เด็กชายพัชรณัฏฐ์', 'เอี่ยมจินดา', 'ป.4', '3', '13', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('458', '1', '7174', 'เด็กชายชุติพนธ์', 'ตะโกตั้ง', 'ป.4', '3', '14', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('459', '1', '7179', 'เด็กชายอณาจักร', 'ลาภเวช', 'ป.4', '3', '15', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('460', '1', '7438', 'เด็กชายปัณณ์พัฒน์', 'จิตรามณีโรจน์', 'ป.4', '3', '16', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('461', '1', '5527', 'เด็กหญิงณัฐรดา', 'อิ่มสำราญรัชต์', 'ป.4', '3', '17', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('462', '1', '5539', 'เด็กหญิงกานต์พิชชา', 'ขนนกยูง', 'ป.4', '3', '18', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('463', '1', '5889', 'เด็กหญิงจุฑามาศ', 'คงเฉลิม', 'ป.4', '3', '19', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('464', '1', '5890', 'เด็กหญิงสุขิตา', 'สิทธิศุภฤกษ์', 'ป.4', '3', '20', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('465', '1', '5891', 'เด็กหญิงปกิตตา', 'มณีรัตน์', 'ป.4', '3', '21', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('466', '1', '5892', 'เด็กหญิงสิรภัทร', 'สุทธารมย์', 'ป.4', '3', '22', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('467', '1', '5893', 'เด็กหญิงธัญณิชา', 'เกียรติธำรง', 'ป.4', '3', '23', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('468', '1', '5894', 'เด็กหญิงกุลณัฐา', 'กฤษฎารักษ์', 'ป.4', '3', '24', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('469', '1', '5895', 'เด็กหญิงรวิสรา', 'ประมวลวงศ์', 'ป.4', '3', '25', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('470', '1', '5959', 'เด็กหญิงเนตรนารี', 'คชศาสตร์ศิลป์', 'ป.4', '3', '26', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('471', '1', '6487', 'เด็กหญิงรินน์รพัฒน์', 'สัมปันนานนท์', 'ป.4', '3', '27', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('472', '1', '6681', 'เด็กหญิงอมรรัตน์', 'แก้ววจีทรัพย์', 'ป.4', '3', '28', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('473', '1', '6684', 'เด็กหญิงวรันธร', 'โชติจารุดิลก', 'ป.4', '3', '29', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('474', '1', '6788', 'เด็กหญิงชนิญานันทน์', 'จันทรปราโมทย์', 'ป.4', '3', '30', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('475', '1', '6944', 'เด็กหญิงปณัฏฐ์กมล', 'พิชยวัฒน์', 'ป.4', '3', '31', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('476', '1', '7182', 'เด็กหญิงฐิติกาญจน์', 'เจริญจันทร์', 'ป.4', '3', '32', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('477', '1', '5476', 'เด็กชายภาคิน', 'สุขพันธ์', 'ป.4', '4', '1', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('478', '1', '5517', 'เด็กชายปวินท์วิธ', 'มั่นคง', 'ป.4', '4', '2', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('479', '1', '5520', 'เด็กชายอันดามัน', 'กึนพันธุ์', 'ป.4', '4', '3', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('480', '1', '5540', 'เด็กชายณปภัช', 'สมานไทย', 'ป.4', '4', '4', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('481', '1', '5584', 'เด็กชายธิตินันทน์', 'เขียวงามดี', 'ป.4', '4', '5', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('482', '1', '5592', 'เด็กชายอุดมโชค', 'รักษ์แพทย์', 'ป.4', '4', '6', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('483', '1', '5896', 'เด็กชายธนาดุล', 'ประดุจพงษ์เพชร์', 'ป.4', '4', '7', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('484', '1', '5897', 'เด็กชายณัฐชนน', 'ศิษย์โรจนฤทธิ์', 'ป.4', '4', '8', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('485', '1', '5899', 'เด็กชายชนาธิป', 'ทองเมืองหลวง', 'ป.4', '4', '9', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('486', '1', '5900', 'เด็กชายชยุตพงศ์', 'บุญอินเขียว', 'ป.4', '4', '10', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('487', '1', '5902', 'เด็กชายรฐนนท์', 'อุนทุโร', 'ป.4', '4', '11', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('488', '1', '6214', 'เด็กชายปภังกร', 'จงเจริญ', 'ป.4', '4', '12', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('489', '1', '6676', 'เด็กชายทินกร', 'บุญภักดี', 'ป.4', '4', '13', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('490', '1', '6678', 'เด็กชายอภิธาร', 'มีทิพย์', 'ป.4', '4', '14', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('491', '1', '7176', 'เด็กชายภัทรเวช', 'จินดาเย็น', 'ป.4', '4', '15', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('492', '1', '7311', 'เด็กชายศุภณัฐ', 'ระตะอาภร', 'ป.4', '4', '16', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('493', '1', '5537', 'เด็กหญิงวริศราณัท', 'อินทโชติ', 'ป.4', '4', '17', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('494', '1', '5538', 'เด็กหญิงปุณณดา', 'โพธิ์เงิน', 'ป.4', '4', '18', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('495', '1', '5903', 'เด็กหญิงอัศวรินทร์', 'กัลชนะ', 'ป.4', '4', '19', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('496', '1', '5904', 'เด็กหญิงกัญญาภัทร', 'เนตรสว่าง', 'ป.4', '4', '20', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('497', '1', '5905', 'เด็กหญิงณัฐชา', 'เหมือนท่าไม้', 'ป.4', '4', '21', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('498', '1', '5906', 'เด็กหญิงธนพร', 'อยู่รัตน์', 'ป.4', '4', '22', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('499', '1', '5907', 'เด็กหญิงปัญญารัตน์', 'นดตะขบ', 'ป.4', '4', '23', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('500', '1', '5909', 'เด็กหญิงกวิตา', 'พี่พานิช', 'ป.4', '4', '24', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('501', '1', '5961', 'เด็กหญิงคณิศร', 'จันทรัตน์', 'ป.4', '4', '25', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('502', '1', '6486', 'เด็กหญิงชัญญาพร', 'ตาคำดี', 'ป.4', '4', '26', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('503', '1', '6488', 'เด็กหญิงณัจฉรียา', 'ม่วงโมด', 'ป.4', '4', '27', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('504', '1', '6685', 'เด็กหญิงพิมพิศา', 'เจริญสาธิต', 'ป.4', '4', '28', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('505', '1', '6686', 'เด็กหญิงวัทนวิภา', 'ก่อเกียรติอาภา', 'ป.4', '4', '29', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('506', '1', '7187', 'เด็กหญิงชมนภา', 'เหลือบุญชู', 'ป.4', '4', '30', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('507', '1', '7441', 'เด็กหญิงพัทธนันท์', 'โตนดแก้ว', 'ป.4', '4', '31', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('508', '1', '7589', 'เด็กชายภูริ', 'ปัถวี', 'ป.4', '4', '32', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('509', '1', '5112', 'เด็กชายเขมวัฒน์', 'ทองแท่ง', 'ป.5', '1', '1', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('510', '1', '5113', 'เด็กชายพีรวิชญ์', 'กิจนิธิธาดา', 'ป.5', '1', '2', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('511', '1', '5114', 'เด็กชายศุภกร', 'แสงพิทักษ์', 'ป.5', '1', '3', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('512', '1', '5115', 'เด็กชายศุภกฤต', 'แสงพิทักษ์', 'ป.5', '1', '4', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('513', '1', '5124', 'เด็กชายวสุธร', 'สุนทรา', 'ป.5', '1', '5', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('514', '1', '5456', 'เด็กชายเอกภูสิษฐ์', 'ทองบัวศิริไล', 'ป.5', '1', '6', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('515', '1', '5457', 'เด็กชายอมฤต', 'สัมมเสถียร', 'ป.5', '1', '7', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('516', '1', '5458', 'เด็กชายชัยพิพัฒน์', 'ห้วยบุญรัตนา', 'ป.5', '1', '8', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('517', '1', '5469', 'เด็กชายธนบดินทร์', 'อินเฉิดฉาย', 'ป.5', '1', '9', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('518', '1', '5480', 'เด็กชายรรักษ์พล', 'เกษมศิรินาวิน', 'ป.5', '1', '10', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('519', '1', '6205', 'เด็กชายจิรภัทร', 'สุนทรภมรรัตน์', 'ป.5', '1', '11', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('520', '1', '6303', 'เด็กชายนภทีป์', 'พงศ์เนาวรัตน์', 'ป.5', '1', '12', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('521', '1', '6461', 'เด็กชายภูมิพัฒน์', 'ยอดชาญ', 'ป.5', '1', '13', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('522', '1', '6568', 'เด็กชายธนโชติ', 'พลเสน', 'ป.5', '1', '14', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('523', '1', '6932', 'เด็กชายคณิศร', 'ปรีเปรมโอน', 'ป.5', '1', '15', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('524', '1', '6933', 'เด็กชายยมลพร', 'เหลืองขมิ้น', 'ป.5', '1', '16', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('525', '1', '6934', 'เด็กชายภควัฒน์', 'แสงกล้า', 'ป.5', '1', '17', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('526', '1', '7163', 'เด็กชายณัฐนันท์', 'ชมภูนิช', 'ป.5', '1', '18', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('527', '1', '5130', 'เด็กหญิงพิมพ์นารา', 'ปิยธำรงรัตน์', 'ป.5', '1', '19', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('528', '1', '5132', 'เด็กหญิงอาภาพัชร์', 'สีลาโคตร', 'ป.5', '1', '20', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('529', '1', '5136', 'เด็กหญิงอัญญาดา', 'มณีรัตน์', 'ป.5', '1', '21', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('530', '1', '5185', 'เด็กหญิงธีรดา', 'เกิดทอง', 'ป.5', '1', '22', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('531', '1', '5198', 'เด็กหญิงพาบุญ', 'โตเลี้ยง', 'ป.5', '1', '23', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('532', '1', '5483', 'เด็กหญิงกัญชลิกา', 'ผาจบ', 'ป.5', '1', '24', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('533', '1', '5484', 'เด็กหญิงเกตน์นิภา', 'ธนัญชยะ', 'ป.5', '1', '25', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('534', '1', '5486', 'เด็กหญิงวรินรำไพ', 'เนินษะเกษ', 'ป.5', '1', '26', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('535', '1', '5487', 'เด็กหญิงชนิดาภา', 'ไชยโย', 'ป.5', '1', '27', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('536', '1', '5497', 'เด็กหญิงกัญญาลักษณ์', 'บุญมี', 'ป.5', '1', '28', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('537', '1', '5510', 'เด็กหญิงวรินทร์ลภัส', 'ราศรีดี', 'ป.5', '1', '29', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('538', '1', '5851', 'เด็กหญิงพอเพียง', 'ศรีทา', 'ป.5', '1', '30', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('539', '1', '5993', 'เด็กหญิงรินรดา', 'เพิ่มทองอินทร์', 'ป.5', '1', '31', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('540', '1', '6209', 'เด็กหญิงพรรณภัทร', 'พงศ์ศิริวิลาศ', 'ป.5', '1', '32', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('541', '1', '6467', 'เด็กหญิงณัฐพัชร์', 'เลี้ยงรักษา', 'ป.5', '1', '33', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('542', '1', '6474', 'เด็กหญิงพรรธน์ชญมน', 'พาณิชย์จำเริญ', 'ป.5', '1', '34', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('543', '1', '6480', 'เด็กหญิงศิริลักษณ์', 'อำแพงใต้', 'ป.5', '1', '35', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('544', '1', '6672', 'เด็กหญิงพิมพ์ณดา', 'พีรปรีชาวิทย์', 'ป.5', '1', '36', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('545', '1', '6937', 'เด็กหญิงลัลล์ลลิล', 'แก่นจันทร์', 'ป.5', '1', '37', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('546', '1', '7172', 'เด็กหญิงธัญชนก', 'เถื่อนถ้ำแก้ว', 'ป.5', '1', '38', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('547', '1', '7587', 'เด็กหญิงอรปรียา', 'ภู่แก้ว', 'ป.5', '1', '39', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('548', '1', '5116', 'เด็กชายณฐกร', 'ดุจพ่วงลาภ', 'ป.5', '2', '1', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('549', '1', '5121', 'เด็กชายสุเมธ', 'เปลี่ยนสี', 'ป.5', '2', '2', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('550', '1', '5122', 'เด็กชายสถิตคุณ', 'เกิดความสุข', 'ป.5', '2', '3', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('551', '1', '5193', 'เด็กชายกวีวัธน์', 'กลีบบัว', 'ป.5', '2', '4', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('552', '1', '5196', 'เด็กชายปรพล', 'มากรักษา', 'ป.5', '2', '5', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('553', '1', '5462', 'เด็กชายธีรเศรษฐ', 'โอชารส', 'ป.5', '2', '6', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('554', '1', '5463', 'เด็กชายธรณัส', 'พัฒนา', 'ป.5', '2', '7', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('555', '1', '5464', 'เด็กชายพนธกร', 'อุบลบาน', 'ป.5', '2', '8', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('556', '1', '5465', 'เด็กชายกิตติพัธน์', 'กุลศรีวัฒนไชย', 'ป.5', '2', '9', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('557', '1', '5466', 'เด็กชายไชยวรรณ', 'จันทร์อำพร', 'ป.5', '2', '10', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('558', '1', '5468', 'เด็กชายชวนน', 'หงษ์จันทร์', 'ป.5', '2', '11', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('559', '1', '5558', 'เด็กชายธัญเทพ', 'วารี', 'ป.5', '2', '12', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('560', '1', '6204', 'เด็กชายก้องภัฒ', 'สุบรรณเสณี', 'ป.5', '2', '13', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('561', '1', '6460', 'เด็กชายสิรวิชญ์', 'เครือแปง', 'ป.5', '2', '14', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('562', '1', '6464', 'เด็กชายธนโชติ', 'นาคไร่ขิง', 'ป.5', '2', '15', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('563', '1', '6465', 'เด็กชายธนเดช', 'นาคไร่ขิง', 'ป.5', '2', '16', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('564', '1', '6569', 'เด็กชายชนกชนม์', 'ทองสิมา', 'ป.5', '2', '17', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('565', '1', '6670', 'เด็กชายวรวัฒน์', 'สามแก้ว', 'ป.5', '2', '18', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('566', '1', '6787', 'เด็กชายณฐกฤตย์', 'วงศ์สันติวนิช', 'ป.5', '2', '19', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('567', '1', '5134', 'เด็กหญิงจิรกุลกัลยา', 'ธาดาชญา', 'ป.5', '2', '20', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('568', '1', '5165', 'เด็กหญิงภัทรา', 'ประมวลทอง', 'ป.5', '2', '21', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('569', '1', '5490', 'เด็กหญิงศศินิภา', 'จุ่นแพ', 'ป.5', '2', '22', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('570', '1', '5491', 'เด็กหญิงรมย์ชลี', 'อรรณพไพศาล', 'ป.5', '2', '23', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('571', '1', '5492', 'เด็กหญิงพัชสนันท์', 'ทินณรงค์', 'ป.5', '2', '24', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('572', '1', '5493', 'เด็กหญิงศุภาพิชญ์', 'พุฒทอง', 'ป.5', '2', '25', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('573', '1', '6210', 'เด็กหญิงฐิตามินทร์', 'ขาวแก้ว', 'ป.5', '2', '26', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('574', '1', '6211', 'เด็กหญิงปุณญดา', 'ผ่องตระกูล', 'ป.5', '2', '27', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('575', '1', '6468', 'เด็กหญิงวรัญญา', 'ส่งเมา', 'ป.5', '2', '28', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('576', '1', '6472', 'เด็กหญิงมิณลิณี', 'จันทรปราโมทย์', 'ป.5', '2', '29', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('577', '1', '6477', 'เด็กหญิงชนกนันท์', 'จันทิพย์วงษ์', 'ป.5', '2', '30', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('578', '1', '6483', 'เด็กหญิงณัฏฐณิชา', 'ตระกูลโอสถ', 'ป.5', '2', '31', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('579', '1', '6573', 'เด็กหญิงเจนจิรา', 'หงษ์ภูมี', 'ป.5', '2', '32', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('580', '1', '6936', 'เด็กหญิงฐานิตา', 'กำมณี', 'ป.5', '2', '33', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('581', '1', '6939', 'เด็กหญิงโชติกา', 'จันทร์พนอรักษ์', 'ป.5', '2', '34', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('582', '1', '7168', 'เด็กหญิงณัชยกาญจน์', 'เกติพันธ์', 'ป.5', '2', '35', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('583', '1', '7170', 'เด็กหญิงวรพรรณ', 'เสาร์โพธิ์งาม', 'ป.5', '2', '36', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('584', '1', '7171', 'เด็กหญิงพิมพ์เพชร', 'วิวัฒนภูษิต', 'ป.5', '2', '37', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('585', '1', '5561', 'เด็กหญิงปุณยนุช', 'อุณหทรงธรรม', 'ป.5', '2', '38', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('586', '1', '5117', 'เด็กชายอาริยะ', 'ชวนะศรีสกุลชัย', 'ป.5', '3', '1', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('587', '1', '5123', 'เด็กชายคุณธรรม', 'ประเสริฐพรรณ', 'ป.5', '3', '2', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('588', '1', '5125', 'เด็กชายวิริทธิ์พัฒน์', 'กลางประพันธ์', 'ป.5', '3', '3', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('589', '1', '5127', 'เด็กชายบวรวิชญ์', 'เพ็งส้ม', 'ป.5', '3', '4', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('590', '1', '5461', 'เด็กชายชยุต', 'ไกรทองสุข', 'ป.5', '3', '5', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('591', '1', '5467', 'เด็กชายอภิวิชญ์', 'ช่อฉาย', 'ป.5', '3', '6', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('592', '1', '5470', 'เด็กชายปุณณ์ณพิชญ์', 'รักแจ้ง', 'ป.5', '3', '7', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('593', '1', '5471', 'เด็กชายณัฐธีร์', 'ไตรพุฒิคุณ', 'ป.5', '3', '8', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('594', '1', '5475', 'เด็กชายธนาธิป', 'ดังตราชู', 'ป.5', '3', '9', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('595', '1', '5560', 'เด็กชายปัณณวิชญ์', 'วัชรอัตยาพล', 'ป.5', '3', '10', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('596', '1', '5853', 'เด็กชายกฤศณัฏฐ์', 'ควรประดิษฐ์', 'ป.5', '3', '11', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('597', '1', '6202', 'เด็กชายรัชชานนท์', 'ทรัพย์พญา', 'ป.5', '3', '12', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('598', '1', '6203', 'เด็กชายวินทัย', 'เตชะกิจขจร', 'ป.5', '3', '13', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('599', '1', '6668', 'เด็กชายธนรัตน์', 'ขุนทอง', 'ป.5', '3', '14', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('600', '1', '6922', 'เด็กชายวินญ์โยธา', 'ขินทอง', 'ป.5', '3', '15', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('601', '1', '6935', 'เด็กชายอิทธิพัทธ์', 'นิมิตรศดิกุล', 'ป.5', '3', '16', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('602', '1', '7164', 'เด็กชายธีรัชวินบุตร', 'ใคร่ครวญ', 'ป.5', '3', '17', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('603', '1', '7166', 'เด็กชายกฤตณัท', 'สมุทรโมฬี', 'ป.5', '3', '18', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('604', '1', '5128', 'เด็กหญิงปรีณาพรรณ', 'นาคทอง', 'ป.5', '3', '19', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('605', '1', '5138', 'เด็กหญิงหทัยทัต', 'เฟื่องขจร', 'ป.5', '3', '20', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('606', '1', '5139', 'เด็กหญิงนภัสรัญชน์', 'โพธิ์ทองคำ', 'ป.5', '3', '21', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('607', '1', '5181', 'เด็กหญิงปานิศา', 'โพธิ์ไกร', 'ป.5', '3', '22', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('608', '1', '5197', 'เด็กหญิงนวพร', 'อาราเม', 'ป.5', '3', '23', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('609', '1', '5488', 'เด็กหญิงกัญญ์ณัชชา', 'ศรีจิตต์แจ่ม', 'ป.5', '3', '24', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('610', '1', '5496', 'เด็กหญิงปุณยภา', 'โอชะพนัง', 'ป.5', '3', '25', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('611', '1', '5500', 'เด็กหญิงพัชรพร', 'อาบกิ่ง', 'ป.5', '3', '26', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('612', '1', '5501', 'เด็กหญิงพนิตกาญจน์', 'ทรัพย์ธงทอง', 'ป.5', '3', '27', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('613', '1', '5503', 'เด็กหญิงสาริศา', 'พูนขวัญ', 'ป.5', '3', '28', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('614', '1', '5580', 'เด็กหญิงวรรษมน', 'อัมระรงค์', 'ป.5', '3', '29', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('615', '1', '6208', 'เด็กหญิงจรรยมณฑน์', 'จงเจริญ', 'ป.5', '3', '30', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('616', '1', '6469', 'เด็กหญิงธาชินี', 'ดำรงค์ไทย', 'ป.5', '3', '31', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('617', '1', '6471', 'เด็กหญิงณปภัช', 'ลี้สุขสม', 'ป.5', '3', '32', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('618', '1', '6473', 'เด็กหญิงณัฐฌาพัชร์', 'แสงศิริ', 'ป.5', '3', '33', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('619', '1', '6478', 'เด็กหญิงปวริศา', 'สงประชา', 'ป.5', '3', '34', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('620', '1', '6481', 'เด็กหญิงพลอยนพัสสร', 'น้อยพิทักษ์', 'ป.5', '3', '35', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('621', '1', '7173', 'เด็กหญิงสุทัตตา', 'รอบคอบ', 'ป.5', '3', '36', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('622', '1', '7436', 'เด็กหญิงเปี่ยมกมล', 'สมรูป', 'ป.5', '3', '37', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('623', '1', '7588', 'เด็กหญิงปุณญาดา', 'แสงจันทร์', 'ป.5', '3', '38', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('624', '1', '5120', 'เด็กชายปุณณวัฒน์', 'โพธิ์เงิน', 'ป.5', '4', '1', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('625', '1', '5183', 'เด็กชายอนุวัต', 'เล็กนาเกร็ด', 'ป.5', '4', '2', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('626', '1', '5472', 'เด็กชายณัฐชนน', 'นัทธีศรี', 'ป.5', '4', '3', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('627', '1', '5478', 'เด็กชายปิติทัสสน์', 'ทองอ่วมใหญ่', 'ป.5', '4', '4', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('628', '1', '5479', 'เด็กชายเก้าทัพ', 'แจ่มเจริญ', 'ป.5', '4', '5', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('629', '1', '5481', 'เด็กชายชยุต', 'แช่มมั่นคง', 'ป.5', '4', '6', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('630', '1', '5482', 'เด็กชายพีรพล', 'จู๋ยืนยงค์', 'ป.5', '4', '7', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('631', '1', '5559', 'เด็กชายเจนวิทย์', 'อัครโชคศิรนนท์', 'ป.5', '4', '8', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('632', '1', '6462', 'เด็กชายวงศกร', 'วงศ์สุกฤต', 'ป.5', '4', '9', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('633', '1', '6463', 'เด็กชายจิรภัทร', 'สุขศรีสมบูรณ์', 'ป.5', '4', '10', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('634', '1', '6466', 'เด็กชายภัทรวิชญ์', 'ชาวนา', 'ป.5', '4', '11', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('635', '1', '6667', 'เด็กชายธนทัต', 'ทีปานุเคราะห์', 'ป.5', '4', '12', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('636', '1', '7162', 'เด็กชายปภังกร', 'กิจเจริญ', 'ป.5', '4', '13', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('637', '1', '7165', 'เด็กชายติณณภัทร์', 'ทองเทพ', 'ป.5', '4', '14', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('638', '1', '7167', 'เด็กชายชวกร', 'เหลือบุญชู', 'ป.5', '4', '15', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('639', '1', '7193', 'เด็กชายพิริยะพงษ์', 'ห่อมณีรัตน์', 'ป.5', '4', '16', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('640', '1', '7322', 'เด็กชายรชต', 'คณานนท์เดชา', 'ป.5', '4', '17', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('641', '1', '7434', 'เด็กชายณัฏฐ์คเณศ', 'ก๋ำนารายณ์', 'ป.5', '4', '18', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('642', '1', '7435', 'เด็กชายธนบดี', 'มีปัดชา', 'ป.5', '4', '19', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('643', '1', '5131', 'เด็กหญิงปาณิสรา', 'ราวรา', 'ป.5', '4', '20', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('644', '1', '5137', 'เด็กหญิงพัทธ์ธีรา', 'ภุมมาลา', 'ป.5', '4', '21', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('645', '1', '5140', 'เด็กหญิงวิภัทสรณ์', 'เอี่ยมอรุณไทย', 'ป.5', '4', '22', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('646', '1', '5164', 'เด็กหญิงธนภร', 'เชื้อสัสดี', 'ป.5', '4', '23', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('647', '1', '5187', 'เด็กหญิงภาวรีย์', 'ศรีวัลลภ', 'ป.5', '4', '24', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('648', '1', '5494', 'เด็กหญิงบัวบูชา', 'สมานมิตร', 'ป.5', '4', '25', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('649', '1', '5505', 'เด็กหญิงศศิรดา', 'แย้มสำรวล', 'ป.5', '4', '26', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('650', '1', '5506', 'เด็กหญิงกัญจน์ติมา', 'พงค์สวัสดิ์', 'ป.5', '4', '27', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('651', '1', '5507', 'เด็กหญิงณัฏฐนันท์', 'ทินกรณ์', 'ป.5', '4', '28', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('652', '1', '5509', 'เด็กหญิงพิมพ์ภัทรา', 'เกตุแก้ว', 'ป.5', '4', '29', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('653', '1', '5514', 'เด็กหญิงอัจฉราพัชร', 'ไชยจันทร์', 'ป.5', '4', '30', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('654', '1', '5579', 'เด็กหญิงนัทธชนก', 'คงศิริ', 'ป.5', '4', '31', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('655', '1', '6207', 'เด็กหญิงเมทินี', 'เกติพันธ์', 'ป.5', '4', '32', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('656', '1', '6296', 'เด็กหญิงนวินดา', 'ไทยเจียมอารีย์', 'ป.5', '4', '33', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('657', '1', '6470', 'เด็กหญิงปุณณภา', 'พรมกระทุ่มล้ม', 'ป.5', '4', '34', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('658', '1', '6475', 'เด็กหญิงสุภัชชา', 'พวงทอง', 'ป.5', '4', '35', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('659', '1', '6482', 'เด็กหญิงณฐมณฑ์', 'อัฐวงศ์', 'ป.5', '4', '36', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('660', '1', '6570', 'เด็กหญิงณัฏฐ์ชญาดา', 'หรรษภิญโญ', 'ป.5', '4', '37', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('661', '1', '7169', 'เด็กหญิงภควรรณ', 'มณีโชติ', 'ป.5', '4', '38', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('662', '1', '4650', 'เด็กชายกันตณัฐ', 'ชื่นเจริญ', 'ป.6', '1', '1', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('663', '1', '4655', 'เด็กชายวีรพัส', 'บุญสม', 'ป.6', '1', '2', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('664', '1', '4660', 'เด็กชายณัทพงศ์', 'โตไร่ขิง', 'ป.6', '1', '3', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('665', '1', '5044', 'เด็กชายปลายภพ', 'ตั้งสุทธิวงษ์', 'ป.6', '1', '4', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('666', '1', '5053', 'เด็กชายปัณวริศ', 'ฐานสิทธิโรจน์', 'ป.6', '1', '5', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('667', '1', '5061', 'เด็กชายกวิภัฎ', 'แซ่เล้า', 'ป.6', '1', '6', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('668', '1', '5062', 'เด็กชายกิตติภัทร์', 'รัตน์วิจิตต์เวช', 'ป.6', '1', '7', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('669', '1', '5067', 'เด็กชายณัฏฐพัชร์', 'วงษ์สวรรค์', 'ป.6', '1', '8', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('670', '1', '5068', 'เด็กชายปริญญา', 'อินทรโชติ', 'ป.6', '1', '9', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('671', '1', '5449', 'เด็กชายจิรณัฐ', 'อรรณพไพศาล', 'ป.6', '1', '10', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('672', '1', '6173', 'เด็กชายปรวัฒน์', 'ดำรงค์ธวัชชัย', 'ป.6', '1', '11', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('673', '1', '6174', 'เด็กชายกฤศกร', 'โบสุวรรณนานา', 'ป.6', '1', '12', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('674', '1', '6175', 'เด็กชายชลธี', 'อาจสามล', 'ป.6', '1', '13', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('675', '1', '6176', 'เด็กชายพชรพล', 'แผ่วบรรจง', 'ป.6', '1', '14', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('676', '1', '6177', 'เด็กชายกุณัชญ์', 'เจียมเอกฤทธิ์', 'ป.6', '1', '15', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('677', '1', '7431', 'เด็กชายณภัทร', 'พุ่มเกิด', 'ป.6', '1', '16', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('678', '1', '7576', 'เด็กชายวรนน', 'เกตุแจ่ม', 'ป.6', '1', '17', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('679', '1', '4667', 'เด็กหญิงรสสุคนธ์', 'อรรถพลภูษิต', 'ป.6', '1', '18', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('680', '1', '4673', 'เด็กหญิงเนตรอัปสร', 'เนตรจินดา', 'ป.6', '1', '19', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('681', '1', '4676', 'เด็กหญิงสุภัทรา', 'วาศเรืองโรจน์', 'ป.6', '1', '20', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('682', '1', '4679', 'เด็กหญิงกัญพัชญ์', 'ธรรมอภิพล', 'ป.6', '1', '21', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('683', '1', '4735', 'เด็กหญิงพิชชาภา', 'ทองดีนิธิพัฒน์', 'ป.6', '1', '22', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('684', '1', '4738', 'เด็กหญิงณภัสสรณ์', 'คุ้มสวัสดิ์', 'ป.6', '1', '23', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('685', '1', '5075', 'เด็กหญิงพุฒิภัททรา', 'หว่านพืช', 'ป.6', '1', '24', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('686', '1', '5080', 'เด็กหญิงวริษฐา', 'พุ่มตะโก', 'ป.6', '1', '25', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('687', '1', '5081', 'เด็กหญิงปารดา', 'สุขสมนึก', 'ป.6', '1', '26', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('688', '1', '5087', 'เด็กหญิงธนิศา', 'ช้าเบ็ญจา', 'ป.6', '1', '27', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('689', '1', '5090', 'เด็กหญิงธัญลภัส', 'เตชนาธนาวรัตน์', 'ป.6', '1', '28', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('690', '1', '5097', 'เด็กหญิงศรัณรัตน์', 'เรืองรอง', 'ป.6', '1', '29', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('691', '1', '5101', 'เด็กหญิงพีชญาภา', 'อธิภาคย์', 'ป.6', '1', '30', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('692', '1', '6197', 'เด็กหญิงพัชราวรินทร์', 'คำภาพงษ์', 'ป.6', '1', '31', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('693', '1', '6200', 'เด็กหญิงจิดาภา', 'สุทธิวิริยะกุล', 'ป.6', '1', '32', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('694', '1', '6201', 'เด็กหญิงอนัญญา', 'ภูล้นแก้ว', 'ป.6', '1', '33', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('695', '1', '6924', 'เด็กหญิงบุญญิกาธัญ', 'สอนใจ', 'ป.6', '1', '34', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('696', '1', '7433', 'เด็กหญิงกฤตยา', 'จีนเมือง', 'ป.6', '1', '35', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('697', '1', '4205', 'เด็กชายภูมิพัฒน์', 'แย้มสุวรรณ', 'ป.6', '2', '1', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('698', '1', '4653', 'เด็กชายเดชากิตติ์', 'แก้วประเสริฐ', 'ป.6', '2', '2', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('699', '1', '4659', 'เด็กชายภพไชยทัศน์', 'กาญจนถาวรวิบูล', 'ป.6', '2', '3', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('700', '1', '4734', 'เด็กชายณฐกร', 'พุ่มปรีชา', 'ป.6', '2', '4', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('701', '1', '5045', 'เด็กชายวิภู', 'สุขสงวน', 'ป.6', '2', '5', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('702', '1', '5047', 'เด็กชายวรินทร', 'จรดล', 'ป.6', '2', '6', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('703', '1', '5052', 'เด็กชายสรณ์สิริ', 'ศรีสัจจวาที', 'ป.6', '2', '7', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('704', '1', '5054', 'เด็กชายกนกพล', 'บำรุงกิจ', 'ป.6', '2', '8', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('705', '1', '5057', 'เด็กชายพชรพล', 'เปี่ยมสมบูรณ์', 'ป.6', '2', '9', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('706', '1', '5064', 'เด็กชายปรินทร', 'ท่าจีน', 'ป.6', '2', '10', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('707', '1', '5072', 'เด็กชายปองพล', 'คำทรัพย์', 'ป.6', '2', '11', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('708', '1', '5206', 'เด็กชายปิยพัชร์', 'รักษาคม', 'ป.6', '2', '12', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('709', '1', '5968', 'เด็กชายคุณานนต์', 'บุญยะศักดิ์', 'ป.6', '2', '13', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('710', '1', '5978', 'เด็กชายศรวิสิษฐ์', 'ธนิกกุลสิริโชติ', 'ป.6', '2', '14', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('711', '1', '6179', 'เด็กชายณัฐดนัย', 'สืบวงษ์เหรียญ', 'ป.6', '2', '15', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('712', '1', '6182', 'เด็กชายวรปรัชญ์', 'เขียวขำ', 'ป.6', '2', '16', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('713', '1', '6923', 'เด็กชายสุริยา', 'ธรรมรัตน์', 'ป.6', '2', '17', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('714', '1', '7157', 'เด็กชายอติลักษณ์', 'อัจฉริยวนิช', 'ป.6', '2', '18', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('715', '1', '4671', 'เด็กหญิงอรกัญญา', 'อรชร', 'ป.6', '2', '19', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('716', '1', '5074', 'เด็กหญิงเมธาวี', 'กิ่งกัลยา', 'ป.6', '2', '20', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('717', '1', '5076', 'เด็กหญิงสิริณิชา', 'หวันประวัติ', 'ป.6', '2', '21', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('718', '1', '5084', 'เด็กหญิงสุธิดา', 'ศักดิ์สุภาพ', 'ป.6', '2', '22', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('719', '1', '5085', 'เด็กหญิงณัฐธิตา', 'ปิ่นทำนัก', 'ป.6', '2', '23', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('720', '1', '5088', 'เด็กหญิงสิริกร', 'เปี่ยมมีสมบูรณ์', 'ป.6', '2', '24', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('721', '1', '5089', 'เด็กหญิงวาสิตา', 'ตลับทอง', 'ป.6', '2', '25', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('722', '1', '5105', 'เด็กหญิงสุประวีณ์', 'ทรัพย์มั่นคง', 'ป.6', '2', '26', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('723', '1', '5108', 'เด็กหญิงเพียงรดา', 'เสือสิงห์', 'ป.6', '2', '27', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('724', '1', '5498', 'เด็กหญิงอลิสสา', 'ชุนถนอม', 'ป.6', '2', '28', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('725', '1', '6199', 'เด็กหญิงกชพรรณ', 'หุ่นจันทร์', 'ป.6', '2', '29', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('726', '1', '6664', 'เด็กหญิงธัญชนก', 'บุญเรือน', 'ป.6', '2', '30', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('727', '1', '6665', 'เด็กหญิงพิชานันท์', 'โชติจารุดิลก', 'ป.6', '2', '31', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('728', '1', '6926', 'เด็กหญิงพิชญธิดา', 'พุกยิ้ม', 'ป.6', '2', '32', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('729', '1', '6927', 'เด็กหญิงสุทัตตา', 'รอดนางรอง', 'ป.6', '2', '33', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('730', '1', '7160', 'เด็กหญิงอุรชา', 'ม้าลำพอง', 'ป.6', '2', '34', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('731', '1', '7161', 'เด็กหญิงปรายน้ำฟ้า', 'ลิ้มสกุล', 'ป.6', '2', '35', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('732', '1', '4648', 'เด็กชายบุณยกร', 'เพ็งส้ม', 'ป.6', '3', '1', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('733', '1', '4661', 'เด็กชายกิติพัฒน์', 'ไชยจันทร์', 'ป.6', '3', '2', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('734', '1', '4664', 'เด็กชายณัฐพัชร์', 'ผิวเกลี้ยง', 'ป.6', '3', '3', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('735', '1', '4737', 'เด็กชายจิรพัฒน์', 'จงยิ่งยศ', 'ป.6', '3', '4', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('736', '1', '5043', 'เด็กชายกันตเมศฐ์', 'ศรีเทพเอี่ยม', 'ป.6', '3', '5', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('737', '1', '5046', 'เด็กชายพัฒนกฤช', 'สามงามยา', 'ป.6', '3', '6', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('738', '1', '5048', 'เด็กชายจิรายุ', 'กัลชนะ', 'ป.6', '3', '7', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('739', '1', '5050', 'เด็กชายชัยพิสิทธิ์', 'นรินทร์โชติ', 'ป.6', '3', '8', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('740', '1', '5051', 'เด็กชายวรวุฒิ', 'แก้วปราณี', 'ป.6', '3', '9', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('741', '1', '5056', 'เด็กชายชนนาถ', 'เรืองเทศ', 'ป.6', '3', '10', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('742', '1', '6181', 'เด็กชายภูดิส', 'ตันเสถียร', 'ป.6', '3', '11', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('743', '1', '6184', 'เด็กชายธรรมสรณ์', 'โพธิ์ทะเล', 'ป.6', '3', '12', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('744', '1', '6185', 'เด็กชายวรวุฒิ', 'ไชยคีณี', 'ป.6', '3', '13', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('745', '1', '6188', 'เด็กชายวรภพ', 'จันทะศรี', 'ป.6', '3', '14', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('746', '1', '6459', 'เด็กชายปัณณพัฒน์', 'ศรีอนันต์', 'ป.6', '3', '15', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('747', '1', '7155', 'เด็กชายอนาวินทร์', 'กอบการุณ', 'ป.6', '3', '16', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('748', '1', '7156', 'เด็กชายปัณณวิชญ์', 'ธนาไพศาลทรัพย์', 'ป.6', '3', '17', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('749', '1', '4670', 'เด็กหญิงพิชญธิดา', 'ชาบุญเรือง', 'ป.6', '3', '18', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('750', '1', '4674', 'เด็กหญิงอภิชญา', 'สุทธิกาโมทย์', 'ป.6', '3', '19', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('751', '1', '4719', 'เด็กหญิงวชิรญาณ์', 'เชื้อแถว', 'ป.6', '3', '20', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('752', '1', '5078', 'เด็กหญิงพัฒน์นรี', 'ภุมมาลา', 'ป.6', '3', '21', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('753', '1', '5091', 'เด็กหญิงปนิตา', 'โควศุภมงคล', 'ป.6', '3', '22', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('754', '1', '5093', 'เด็กหญิงณิชยาณัฏฐ์', 'กรังพานิชย์', 'ป.6', '3', '23', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('755', '1', '5098', 'เด็กหญิงณิชชา', 'สุภรัตนมงคล', 'ป.6', '3', '24', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('756', '1', '5102', 'เด็กหญิงนราทิพย์', 'ศิริสัจจวัฒน์', 'ป.6', '3', '25', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('757', '1', '5109', 'เด็กหญิงธนิดา', 'พุทธคี', 'ป.6', '3', '26', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('758', '1', '5454', 'เด็กหญิงปัณฑารีย์', 'วงษ์ศรี', 'ป.6', '3', '27', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('759', '1', '5843', 'เด็กหญิงพลอยขวัญ', 'น่วมไข่', 'ป.6', '3', '28', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('760', '1', '5844', 'เด็กหญิงภัทรนันฐ์', 'ธนัสจำรูญศักดิ์', 'ป.6', '3', '29', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('761', '1', '6194', 'เด็กหญิงวรินรำไพ', 'ยั่งยืน', 'ป.6', '3', '30', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('762', '1', '6196', 'เด็กหญิงพัทธ์ธีรา', 'วรวัฒนาชัยนนท์', 'ป.6', '3', '31', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('763', '1', '6332', 'เด็กหญิงเณศรา', 'โกกุล', 'ป.6', '3', '32', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('764', '1', '6666', 'เด็กหญิงธนัชพร', 'ศุภจิตเกษม', 'ป.6', '3', '33', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('765', '1', '6931', 'เด็กหญิงชีวาพร', 'เศวตวรชัย', 'ป.6', '3', '34', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('766', '1', '7071', 'เด็กหญิงนันท์ณภัส', 'เปรมเจริญ', 'ป.6', '3', '35', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('767', '1', '7432', 'เด็กหญิงหทัยชนก', 'หทัยเกียรติกุล', 'ป.6', '3', '36', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('768', '1', '4645', 'เด็กชายภูวเดช', 'เชื้อสัสดี', 'ป.6', '4', '1', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('769', '1', '4649', 'เด็กชายธนพัต', 'ช้างทอง', 'ป.6', '4', '2', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('770', '1', '4657', 'เด็กชายธนโชติ', 'อินทโชติ', 'ป.6', '4', '3', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('771', '1', '5055', 'เด็กชายภูวภัทร', 'ตันหยง', 'ป.6', '4', '4', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('772', '1', '5060', 'เด็กชายจิรายุ', 'เขียวพระอินทร์', 'ป.6', '4', '5', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('773', '1', '5070', 'เด็กชายปุญญพัฒน์', 'บุญบางยาง', 'ป.6', '4', '6', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('774', '1', '5071', 'เด็กชายพฤกษา', 'เผ่าพงศ์ษา', 'ป.6', '4', '7', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('775', '1', '5162', 'เด็กชายกันตพัฒน์', 'ลือยาม', 'ป.6', '4', '8', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('776', '1', '5163', 'เด็กชายธนกร', 'เกียรติสูงส่ง', 'ป.6', '4', '9', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('777', '1', '5451', 'เด็กชายชานนท์', 'ปึงธนานุกิจ', 'ป.6', '4', '10', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('778', '1', '5845', 'เด็กชายยอดชาย', 'ทวีอัครสถาพร', 'ป.6', '4', '11', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('779', '1', '5847', 'เด็กชายธนศิลป์', 'พูนสินรุ่งโรจน์', 'ป.6', '4', '12', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('780', '1', '5848', 'เด็กชายณัชพล', 'จารุเมธาชัย', 'ป.6', '4', '13', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('781', '1', '5849', 'เด็กชายปริญญ์', 'ปัญญาภวกุล', 'ป.6', '4', '14', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('782', '1', '5850', 'เด็กชายภัค', 'วิเชียร', 'ป.6', '4', '15', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('783', '1', '6186', 'เด็กชายยศสรัล', 'ทองอ่วม', 'ป.6', '4', '16', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('784', '1', '6189', 'เด็กชายณัฐพัชร์', 'เสถียรยานนท์', 'ป.6', '4', '17', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('785', '1', '6191', 'เด็กชายคมน์คุณัชญ์', 'เชียรชนะ', 'ป.6', '4', '18', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('786', '1', '4672', 'เด็กหญิงภัลลาฎา', 'ลักษณะโต', 'ป.6', '4', '19', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('787', '1', '4678', 'เด็กหญิงชนิศา', 'อู๋ไพจิตร', 'ป.6', '4', '20', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('788', '1', '4716', 'เด็กหญิงญาดา', 'ดิสตา', 'ป.6', '4', '21', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('789', '1', '5092', 'เด็กหญิงอภิสรา', 'สุกเกษม', 'ป.6', '4', '22', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('790', '1', '5096', 'เด็กหญิงทักษอร', 'สุวรรณโสภา', 'ป.6', '4', '23', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('791', '1', '5100', 'เด็กหญิงปิ่นเกล้า', 'จำปาวิทยาคุณ', 'ป.6', '4', '24', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('792', '1', '5103', 'เด็กหญิงปุณณภัทร', 'เหล่าอยู่ดี', 'ป.6', '4', '25', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('793', '1', '5106', 'เด็กหญิงภิญญาพัชญ์', 'อดิศัยสกุลชัย', 'ป.6', '4', '26', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('794', '1', '5110', 'เด็กหญิงญาณิศา', 'หงษ์ทอง', 'ป.6', '4', '27', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('795', '1', '5111', 'เด็กหญิงฌาณิญา', 'คัมภีร์ศาสตร์', 'ป.6', '4', '28', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('796', '1', '5455', 'เด็กหญิงภัทร์ธีรา', 'แก้วแป้นผา', 'ป.6', '4', '29', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('797', '1', '6192', 'เด็กหญิงวริศรา', 'ชัยศิลปีชีวะ', 'ป.6', '4', '30', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('798', '1', '6195', 'เด็กหญิงกัญญาณัฐ', 'สิงห์กุล', 'ป.6', '4', '31', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('799', '1', '6928', 'เด็กหญิงพัทธ์ธีรา', 'จิตสุนทรชัยกุล', 'ป.6', '4', '32', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('800', '1', '6929', 'เด็กหญิงกีรติ', 'ชุณหชาติ', 'ป.6', '4', '33', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('801', '1', '6930', 'เด็กหญิงชญาพา', 'สมไพบูลย์', 'ป.6', '4', '34', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('802', '1', '7158', 'เด็กหญิงบุญญารัสมิ์', 'ปัญจกาญจน์มณี', 'ป.6', '4', '35', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('803', '1', '7159', 'เด็กหญิงณิชานันท์', 'ไทรพงษ์พันธุ์', 'ป.6', '4', '36', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('804', '1', '4140', 'เด็กชายธีรเดช', 'วิเศษสม', 'ม.1', '1', '1', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('805', '1', '4141', 'เด็กชายหิรัญ', 'โชติสวัสดิ์', 'ม.1', '1', '2', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('806', '1', '4152', 'เด็กชายพิสิษฐ์', 'กลิ่นกระสันต์', 'ม.1', '1', '3', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('807', '1', '4202', 'เด็กชายจิตปวีร์', 'บัณฑูรธนานันท์', 'ม.1', '1', '4', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('808', '1', '4208', 'เด็กชายศุภวิชญ์', 'กิจนิธิธาดา', 'ม.1', '1', '5', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('809', '1', '4589', 'เด็กชายชวนากร', 'หงษ์คำ', 'ม.1', '1', '6', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('810', '1', '4594', 'เด็กชายทศพร', 'เอี่ยมจินดา', 'ม.1', '1', '7', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('811', '1', '4614', 'เด็กชายติณณภพ', 'วันสูง', 'ม.1', '1', '8', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('812', '1', '5032', 'เด็กชายภณธกร', 'สวัสดิ์พุก', 'ม.1', '1', '9', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('813', '1', '5035', 'เด็กชายธนภัทร์', 'สีดาพาลี', 'ม.1', '1', '10', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('814', '1', '5438', 'เด็กชายอนาวินน์', 'ดอนสุวรรณ์', 'ม.1', '1', '11', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('815', '1', '5440', 'เด็กชายตฤณ', 'สุปัญญารักษ์', 'ม.1', '1', '12', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('816', '1', '5800', 'เด็กชายดิศพงษ์', 'อบเชย', 'ม.1', '1', '13', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('817', '1', '5801', 'เด็กชายชาตรี', 'ทวีอัครสถาพร', 'ม.1', '1', '14', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('818', '1', '5820', 'เด็กชายสุธีฆเณศ', 'จิรัญญาวิวัฒน์', 'ม.1', '1', '15', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('819', '1', '6908', 'เด็กชายปธานิน', 'เพชรนุ้ย', 'ม.1', '1', '16', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('820', '1', '7344', 'เด็กชายเตชินท์', 'จารุวัฒนพานิช', 'ม.1', '1', '17', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('821', '1', '7346', 'เด็กชายภูวกฤต', 'กลักวงศ์', 'ม.1', '1', '18', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('822', '1', '7359', 'เด็กชายปฐพี', 'เกตุแก้ว', 'ม.1', '1', '19', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('823', '1', '7363', 'เด็กชายบุญญพงษ์', 'ก่อเกษมวงศ์', 'ม.1', '1', '20', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('824', '1', '7428', 'เด็กชายพุฒิลภณญ์', 'กาญจนอุดมการ', 'ม.1', '1', '21', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('825', '1', '7562', 'เด็กชายธนศักดิ์', 'เกษวัง', 'ม.1', '1', '22', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('826', '1', '7563', 'เด็กชายวิชญ์ภาส', 'ธรรมพุทธิรัตน์', 'ม.1', '1', '23', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('827', '1', '4155', 'เด็กหญิงธนัชญา', 'พุทธคี', 'ม.1', '1', '24', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('828', '1', '4160', 'เด็กหญิงกนกพิชญ์', 'ดุสฎีกาญจน', 'ม.1', '1', '25', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('829', '1', '4165', 'เด็กหญิงพิมพ์ณดา', 'ศุภธีระวาณิชย์', 'ม.1', '1', '26', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('830', '1', '4167', 'เด็กหญิงพิมพ์พรรณ', 'อภิสิทธิ์', 'ม.1', '1', '27', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('831', '1', '4624', 'เด็กหญิงรดาณัฐ', 'ไทยภักดี', 'ม.1', '1', '28', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('832', '1', '4625', 'เด็กหญิงณัฐสโรชา', 'ปานเจริญ', 'ม.1', '1', '29', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('833', '1', '4633', 'เด็กหญิงภัทรานิษฐ์', 'ตุลารัตนพงษ์', 'ม.1', '1', '30', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('834', '1', '4635', 'เด็กหญิงธนวันต์', 'พัดเกร็ด', 'ม.1', '1', '31', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('835', '1', '4638', 'เด็กหญิงวิรัญดา', 'เที่ยงตรง', 'ม.1', '1', '32', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('836', '1', '4641', 'เด็กหญิงพิมพิสุทธิ์', 'สายศร', 'ม.1', '1', '33', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('837', '1', '5445', 'เด็กหญิงกชกร', 'ปรียงค์', 'ม.1', '1', '34', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('838', '1', '5448', 'เด็กหญิงรติรัตน์', 'สุทรงชัย', 'ม.1', '1', '35', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('839', '1', '6172', 'เด็กหญิงวัชรากานต์', 'ธนโชติชัยวัฒน์', 'ม.1', '1', '36', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('840', '1', '6659', 'เด็กหญิงอัยย์รดา', 'ไทยพลับ', 'ม.1', '1', '37', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('841', '1', '7391', 'เด็กหญิงเรณุกา', 'จบศรี', 'ม.1', '1', '38', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('842', '1', '7398', 'เด็กหญิงชนัญญ์ทิชา', 'ไชยพิพัฒนกุล', 'ม.1', '1', '39', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('843', '1', '4143', 'เด็กชายไพศิษฐ์พุฒินันท์', 'ฟุ้งขจร', 'ม.1', '2', '1', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('844', '1', '4145', 'เด็กชายปุรุเมธ', 'อินธิสาร', 'ม.1', '2', '2', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('845', '1', '4148', 'เด็กชายธนนันท์', 'หนูอินทร์', 'ม.1', '2', '3', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('846', '1', '4583', 'เด็กชายฟ้าประทาน', 'ไข่แก้ว', 'ม.1', '2', '4', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('847', '1', '4586', 'เด็กชายชนิตร์นันท์', 'บุญบางยาง', 'ม.1', '2', '5', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('848', '1', '4587', 'เด็กชายพศธร', 'พันตาวงษ์', 'ม.1', '2', '6', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('849', '1', '4588', 'เด็กชายนันทิพัฒน์', 'วาศเรืองโรจน์', 'ม.1', '2', '7', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('850', '1', '4595', 'เด็กชายนันทพงษ์', 'จูคลองตัน', 'ม.1', '2', '8', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('851', '1', '4601', 'เด็กชายอภิสร', 'ช่อฉาย', 'ม.1', '2', '9', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('852', '1', '5813', 'เด็กชายทินภัทร', 'คำจันทร์', 'ม.1', '2', '10', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('853', '1', '5814', 'เด็กชายอธิษฐ์', 'แรมสูงเนิน', 'ม.1', '2', '11', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('854', '1', '5821', 'เด็กชายธนดล', 'ทองศรี', 'ม.1', '2', '12', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('855', '1', '5825', 'เด็กชายวริทธิ์ธร', 'ทิพย์สีนวล', 'ม.1', '2', '13', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('856', '1', '5835', 'เด็กชายจีรพัฒน์', 'สุขนิพิฐพร', 'ม.1', '2', '14', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('857', '1', '5836', 'เด็กชายกิติเทพ', 'โพธิ์ทะเล', 'ม.1', '2', '15', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('858', '1', '6170', 'เด็กชายกำลังทรัพย์', 'โพธิ์อบ', 'ม.1', '2', '16', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('859', '1', '6657', 'เด็กชายสุรพัศ', 'สระทองโต๊ะ', 'ม.1', '2', '17', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('860', '1', '7152', 'เด็กชายธีรวิทย์', 'เอื้อเศรษฐ์ถาวร', 'ม.1', '2', '18', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('861', '1', '7364', 'เด็กชายฐิติวัสภ์', 'โรมรุจนากร', 'ม.1', '2', '19', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('862', '1', '7375', 'เด็กชายภานุวัฒน์', 'ประภาสัย', 'ม.1', '2', '20', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('863', '1', '7426', 'เด็กชายณัฐณภัทร', 'ปฐมโรจนวงค์', 'ม.1', '2', '21', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('864', '1', '7564', 'เด็กชายธีภพ', 'ภู่เปี่ยมศักดิ์', 'ม.1', '2', '22', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('865', '1', '7566', 'เด็กชายกิตติเดช', 'คงเชื้อจีน', 'ม.1', '2', '23', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('866', '1', '4158', 'เด็กหญิงนันท์นภัส', 'แสงกล้า', 'ม.1', '2', '24', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('867', '1', '4631', 'เด็กหญิงณัชชา', 'บางเขม็ด', 'ม.1', '2', '25', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('868', '1', '4634', 'เด็กหญิงณภัทรวรัญญ์', 'ค่าเจริญ', 'ม.1', '2', '26', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('869', '1', '5036', 'เด็กหญิงณัฐชานันท์', 'รอดรักษาทรัพย์', 'ม.1', '2', '27', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('870', '1', '5038', 'เด็กหญิงกมลพร', 'โพธิ์ทองนาค', 'ม.1', '2', '28', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('871', '1', '5842', 'เด็กหญิงณัฐพัชร์', 'กิจทวีสมบูรณ์', 'ม.1', '2', '29', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('872', '1', '6660', 'เด็กหญิงธนพร', 'ศุภจิตเกษม', 'ม.1', '2', '30', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('873', '1', '7384', 'เด็กหญิงนัจภัค', 'ทวีพงษ์', 'ม.1', '2', '31', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('874', '1', '7396', 'เด็กหญิงชัชญาภา', 'ชั้นอินทร์งาม', 'ม.1', '2', '32', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('875', '1', '7401', 'เด็กหญิงพิมพ์พิชชา', 'ตั้งกิตติวัฒน์', 'ม.1', '2', '33', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('876', '1', '7402', 'เด็กหญิงภัทรพร', 'จาตุรัส', 'ม.1', '2', '34', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('877', '1', '7404', 'เด็กหญิงหนึ่งรดา', 'อิ่มสงวนดี', 'ม.1', '2', '35', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('878', '1', '7410', 'เด็กหญิงชัชชญา', 'ศักยภาพ', 'ม.1', '2', '36', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('879', '1', '7412', 'เด็กหญิงณัฏฐมณี', 'แก่นประยูร', 'ม.1', '2', '37', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('880', '1', '7414', 'เด็กหญิงโชติกา', 'พงษ์หาญพาณิชย์', 'ม.1', '2', '38', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('881', '1', '7573', 'เด็กหญิงฉันทนิษฐ์', 'ปิยพีรพรรณ', 'ม.1', '2', '39', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('882', '1', '7586', 'เด็กหญิงธิชานันท์', 'เหล่าเขตรกรณ์', 'ม.1', '2', '40', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('883', '1', '4580', 'เด็กชายศุภมงคล', 'งามดอกไม้', 'ม.1', '3', '1', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('884', '1', '4591', 'เด็กชายปราณพรรษ', 'นาคทอง', 'ม.1', '3', '2', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('885', '1', '4608', 'เด็กชายภคพล', 'ดังตราชู', 'ม.1', '3', '3', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('886', '1', '4611', 'เด็กชายธาราธร', 'สายศร', 'ม.1', '3', '4', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('887', '1', '5034', 'เด็กชายภูตรัย', 'เตชะสมิต', 'ม.1', '3', '5', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('888', '1', '5442', 'เด็กชายธนวรรธน์', 'บัวแจ่ม', 'ม.1', '3', '6', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('889', '1', '5802', 'เด็กชายกษิดิศ', 'ควรชม', 'ม.1', '3', '7', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('890', '1', '5812', 'เด็กชายธนภัทร', 'ธรรมเวช', 'ม.1', '3', '8', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('891', '1', '5834', 'เด็กชายรณกฤต', 'ของนา', 'ม.1', '3', '9', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('892', '1', '6781', 'เด็กชายพุฒิเศรษฐ์', 'วิจารย์', 'ม.1', '3', '10', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('893', '1', '6784', 'เด็กชายปิยธัช', 'สมิทธากร', 'ม.1', '3', '11', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('894', '1', '7345', 'เด็กชายสันติชัย', 'เมฆอรุณ', 'ม.1', '3', '12', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('895', '1', '7351', 'เด็กชายธนวิชญ์', 'แก่นท้าว', 'ม.1', '3', '13', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('896', '1', '7352', 'เด็กชายตุลธร', 'เดชอุดม', 'ม.1', '3', '14', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('897', '1', '7354', 'เด็กชายสรรพวัฒน์', 'โพธิรัตน์', 'ม.1', '3', '15', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('898', '1', '7356', 'เด็กชายธีรวัจน์', 'ธนัตพรกุล', 'ม.1', '3', '16', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('899', '1', '7369', 'เด็กชายธนัช', 'ชินฮะง้อ', 'ม.1', '3', '17', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('900', '1', '7372', 'เด็กชายนิธาน', 'กรุยทอง', 'ม.1', '3', '18', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('901', '1', '7374', 'เด็กชายทินกร', 'วัฒนาพร', 'ม.1', '3', '19', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('902', '1', '7427', 'เด็กชายปุณณวิช', 'ปัญโญนันท์', 'ม.1', '3', '20', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('903', '1', '7560', 'เด็กชายธนวิน', 'เอี่ยมคง', 'ม.1', '3', '21', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('904', '1', '7567', 'เด็กชายภคภูการ', 'เพ็ชรนคร', 'ม.1', '3', '22', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('905', '1', '7568', 'เด็กชายภัทรพงศ์', 'เนยขำ', 'ม.1', '3', '23', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('906', '1', '4163', 'เด็กหญิงเบญญาภา', 'จันทร์แจ่มฟ้า', 'ม.1', '3', '24', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('907', '1', '4166', 'เด็กหญิงธันยธรณ์', 'ไชยนอก', 'ม.1', '3', '25', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('908', '1', '4620', 'เด็กหญิงกษิรา', 'พูสุวรรณ', 'ม.1', '3', '26', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('909', '1', '4632', 'เด็กหญิงปภากร', 'คงไพร', 'ม.1', '3', '27', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('910', '1', '5818', 'เด็กหญิงณภัค', 'กิจสวัสดิ์', 'ม.1', '3', '28', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('911', '1', '5829', 'เด็กหญิงธวลิดา', 'เพชรทราย', 'ม.1', '3', '29', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('912', '1', '5839', 'เด็กหญิงรมิตา', 'เผ่าพงศ์ษา', 'ม.1', '3', '30', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('913', '1', '6919', 'เด็กหญิงณัฎณิชา', 'เกาะเต้น', 'ม.1', '3', '31', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('914', '1', '6920', 'เด็กหญิงณลดา', 'ภู่ทอง', 'ม.1', '3', '32', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('915', '1', '7388', 'เด็กหญิงณิชชาภัทร', 'วิจิตรไพรวัลย์', 'ม.1', '3', '33', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('916', '1', '7395', 'เด็กหญิงณัจยา', 'เชิญธรรมพร', 'ม.1', '3', '34', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('917', '1', '7400', 'เด็กหญิงณัฐณิชา', 'เกิดไพบูลย์', 'ม.1', '3', '35', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('918', '1', '7405', 'เด็กหญิงธิตาพร', 'จินตนารักถิ่น', 'ม.1', '3', '36', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('919', '1', '7409', 'เด็กหญิงกัญญาพัชร', 'ธีระพานิช', 'ม.1', '3', '37', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('920', '1', '7415', 'เด็กหญิงสโรชา', 'เหลืองสะอาด', 'ม.1', '3', '38', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('921', '1', '7569', 'เด็กหญิงกัญจนาพร', 'มะโน', 'ม.1', '3', '39', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('922', '1', '6585', 'เด็กชายภูมิภัทร', 'พินิจพล', 'ม.1', '3', '40', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('923', '1', '4146', 'เด็กชายธนเดช', 'พรชื่น', 'ม.1', '4', '1', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('924', '1', '4187', 'เด็กชายปารมี', 'กระถินทอง', 'ม.1', '4', '2', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('925', '1', '4584', 'เด็กชายดนัยณัฐ', 'แสงพิทักษ์', 'ม.1', '4', '3', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('926', '1', '4612', 'เด็กชายกฤษตฤณ', 'พิริยะกูลธร', 'ม.1', '4', '4', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('927', '1', '4616', 'เด็กชายภูวเดช', 'แสงเกื้อหนุน', 'ม.1', '4', '5', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('928', '1', '5833', 'เด็กชายสุทธิเขตต์', 'มะกรครรภ์', 'ม.1', '4', '6', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('929', '1', '6169', 'เด็กชายนนทฤทธิ์', 'เพ็งผกา', 'ม.1', '4', '7', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('930', '1', '6454', 'เด็กชายอณพัทย์', 'วิสิฐเพชรลดา', 'ม.1', '4', '8', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('931', '1', '6456', 'เด็กชายจิราธร', 'แนวแห่งธรรม', 'ม.1', '4', '9', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('932', '1', '6658', 'เด็กชายกนกศักดิ์', 'เสือสว่าง', 'ม.1', '4', '10', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('933', '1', '6909', 'เด็กชายภัทรกฤต', 'จันทราฤทธิกุล', 'ม.1', '4', '11', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('934', '1', '7347', 'เด็กชายสุธิชัย', 'เอ็งพัวศรี', 'ม.1', '4', '12', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('935', '1', '7348', 'เด็กชายศรปณัช', 'เจริญพร', 'ม.1', '4', '13', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('936', '1', '7358', 'เด็กชายอิงครัต', 'เจ้ยทองศรี', 'ม.1', '4', '14', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('937', '1', '7361', 'เด็กชายสิรวิชญ์', 'โพธิประสิทธิ์', 'ม.1', '4', '15', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('938', '1', '7365', 'เด็กชายณฐภัทร', 'นววัชรินทร์', 'ม.1', '4', '16', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('939', '1', '7367', 'เด็กชายชนกฤดิ', 'ศรีศิริวัฒน์', 'ม.1', '4', '17', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('940', '1', '7370', 'เด็กชายดลพัฒน์', 'ชนะศรีโยธิน', 'ม.1', '4', '18', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('941', '1', '7373', 'เด็กชายธนภูมิ', 'นะจะคูณ', 'ม.1', '4', '19', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('942', '1', '7376', 'เด็กชายนัธทวัฒน์', 'กิจจาอิทธิศร', 'ม.1', '4', '20', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('943', '1', '7559', 'เด็กชายอาสาฬห', 'สาคร', 'ม.1', '4', '21', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('944', '1', '4643', 'เด็กหญิงอัญชลีพร', 'เจริญต่อแสงเฉลิม', 'ม.1', '4', '22', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('945', '1', '4644', 'เด็กหญิงกัญชพร', 'บุญชัย', 'ม.1', '4', '23', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('946', '1', '4715', 'เด็กหญิงสุพิชญา', 'เรืองรอง', 'ม.1', '4', '24', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('947', '1', '5806', 'เด็กหญิงพิชชากรณ์', 'หุ่นสำราญ', 'ม.1', '4', '25', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('948', '1', '5841', 'เด็กหญิงวิรัลพัชร', 'ล้อศิริวงศ์', 'ม.1', '4', '26', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('949', '1', '5943', 'เด็กหญิงศุพิชญ์ชญา', 'ดาวผ่อง', 'ม.1', '4', '27', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('950', '1', '6912', 'เด็กหญิงนิลาวรรณ', 'จันทร์พนอรักษ์', 'ม.1', '4', '28', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('951', '1', '6913', 'เด็กหญิงภัคจิรา', 'เรไร', 'ม.1', '4', '29', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('952', '1', '6915', 'เด็กหญิงพัชรีพร', 'จันทร์งาม', 'ม.1', '4', '30', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('953', '1', '7380', 'เด็กหญิงปุญญิศา', 'เปรมศิริ', 'ม.1', '4', '31', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('954', '1', '7383', 'เด็กหญิงญาณิศา', 'แย้มสำรวล', 'ม.1', '4', '32', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('955', '1', '7386', 'เด็กหญิงพิชญ์ศิณี', 'จิระภาพันธ์', 'ม.1', '4', '33', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('956', '1', '7389', 'เด็กหญิงปิยาภรณ์', 'ยิ้มถนอม', 'ม.1', '4', '34', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('957', '1', '7393', 'เด็กหญิงกัญญาณัฐ', 'ศักดิ์ศิริรัตน์', 'ม.1', '4', '35', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('958', '1', '7406', 'เด็กหญิงชญาวดี', 'ปฐมกาญจนา', 'ม.1', '4', '36', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('959', '1', '7413', 'เด็กหญิงชญาณิศา', 'ราวเรือง', 'ม.1', '4', '37', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('960', '1', '7430', 'เด็กหญิงรวิภัทร', 'ปิ่นทอง', 'ม.1', '4', '38', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('961', '1', '7571', 'เด็กหญิงกันตินันท์', 'อยู่มา', 'ม.1', '4', '39', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('962', '1', '7572', 'เด็กหญิงเมทิกา', 'คุณัชวรกรณ์', 'ม.1', '4', '40', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('963', '1', '4153', 'เด็กชายพชร', 'ลิ้มสงวน', 'ม.1', '5', '1', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('964', '1', '4204', 'เด็กชายพลพิสิษฐ์', 'พรพรหมมาตร', 'ม.1', '5', '2', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('965', '1', '4606', 'เด็กชายชิษณุพงศ์', 'องอาจ', 'ม.1', '5', '3', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('966', '1', '4609', 'เด็กชายธัณย์ชนก', 'จันทร์เกษม', 'ม.1', '5', '4', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('967', '1', '4613', 'เด็กชายวีระยุทธ', 'คชศาสตร์ศิลป์', 'ม.1', '5', '5', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('968', '1', '5567', 'เด็กชายพงศกร', 'มธุรสพงศ์พันธ์', 'ม.1', '5', '6', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('969', '1', '5824', 'เด็กชายปฏิกาญจน์', 'สวัสดิ์เสริมศรี', 'ม.1', '5', '7', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('970', '1', '5949', 'เด็กชายนิธิศ', 'กัว', 'ม.1', '5', '8', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('971', '1', '5967', 'เด็กชายปรารักษ์', 'ม่วงคะลา', 'ม.1', '5', '9', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('972', '1', '5979', 'เด็กชายสิรภพ', 'สมบัติวงศ์ขจร', 'ม.1', '5', '10', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('973', '1', '6333', 'เด็กชายดลภิวัฒน์', 'สุวรรณศรี', 'ม.1', '5', '11', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('974', '1', '6455', 'เด็กชายจักรพงศ์', 'ชลพิวัฒน์ภูวดล', 'ม.1', '5', '12', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('975', '1', '6772', 'เด็กชายพัศวุฒิ', 'ก้วยไข่มุข', 'ม.1', '5', '13', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('976', '1', '6910', 'เด็กชายปกรเดช', 'ทิพย์สุมณฑา', 'ม.1', '5', '14', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('977', '1', '7349', 'เด็กชายจิณณพัต', 'อังกูรสุทธิพันธ์', 'ม.1', '5', '15', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('978', '1', '7357', 'เด็กชายอัฑฒกร', 'เจ้ยทองศรี', 'ม.1', '5', '16', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('979', '1', '7368', 'เด็กชายพสิษฐ์', 'บำเพ็ญผล', 'ม.1', '5', '17', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('980', '1', '7377', 'เด็กชายอัศม์เดช', 'เดชพรหม', 'ม.1', '5', '18', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('981', '1', '7378', 'เด็กชายธนธรณ์', 'ปิ่นทำนัก', 'ม.1', '5', '19', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('982', '1', '7561', 'เด็กชายนภัทร', 'สำเนียงล้ำ', 'ม.1', '5', '21', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('983', '1', '4621', 'เด็กหญิงธิดารัตน์', 'ยุรชาติ', 'ม.1', '5', '22', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('984', '1', '4626', 'เด็กหญิงธนณัฏฐ', 'ศรีสุทธี', 'ม.1', '5', '23', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('985', '1', '4741', 'เด็กหญิงเบญจรัตน์', 'ทินกรณ์', 'ม.1', '5', '24', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('986', '1', '5195', 'เด็กหญิงพิชามญชุ์', 'หุ่นงาม', 'ม.1', '5', '25', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('987', '1', '7154', 'เด็กหญิงพัฒน์นรี', 'ทรัพย์ส่งแสง', 'ม.1', '5', '26', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('988', '1', '7381', 'เด็กหญิงชยาวีร์', 'น้อยโสภา', 'ม.1', '5', '27', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('989', '1', '7385', 'เด็กหญิงพิรญาณ์', 'นกแก้ว', 'ม.1', '5', '28', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('990', '1', '7387', 'เด็กหญิงนรมน', 'งามเอนก', 'ม.1', '5', '29', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('991', '1', '7390', 'เด็กหญิงโชติกา', 'ทยาพัชร', 'ม.1', '5', '30', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('992', '1', '7392', 'เด็กหญิงกวินทิพย์', 'มีเสือทอง', 'ม.1', '5', '31', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('993', '1', '7397', 'เด็กหญิงปัญฑารีย์', 'อธิศิริรัตนไกร', 'ม.1', '5', '32', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('994', '1', '7403', 'เด็กหญิงพิมพ์วิไล', 'เอกพัชรวรรณ', 'ม.1', '5', '33', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('995', '1', '7407', 'เด็กหญิงเมธปิยา', 'แย้มนาม', 'ม.1', '5', '34', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('996', '1', '7408', 'เด็กหญิงกชพรรณ', 'คำจันทร์', 'ม.1', '5', '35', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('997', '1', '7429', 'เด็กหญิงธนภรณ์', 'สำรวมทรัพย์สิน', 'ม.1', '5', '36', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('998', '1', '7570', 'เด็กหญิงณัฐติกาญจน์', 'บุญเอี่ยมศรี', 'ม.1', '5', '37', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('999', '1', '7574', 'เด็กหญิงวริศรา', 'ใหญ่สีมา', 'ม.1', '5', '38', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1000', '1', '7575', 'เด็กหญิงปัทมาพร', 'แซ่ตั้น', 'ม.1', '5', '39', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1001', '1', '3682', 'เด็กชายพัทธนันท์', 'กวางเส็ง', 'ม.2', '1', '1', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1002', '1', '3688', 'เด็กชายจิรกร', 'ผิวเกลี้ยง', 'ม.2', '1', '2', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1003', '1', '3730', 'เด็กชายพุทธคุณ', 'ประเสริฐพรรณ', 'ม.2', '1', '3', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1004', '1', '4085', 'เด็กชายกฤติน', 'สุภรัตนมงคล', 'ม.2', '1', '4', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1005', '1', '4096', 'เด็กชายพงศ์ภัก', 'คุรุวัชรพงศ์', 'ม.2', '1', '5', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1006', '1', '4102', 'เด็กชายก้องภพ', 'แจ่มเจริญ', 'ม.2', '1', '6', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1007', '1', '4709', 'เด็กชายกัปปชัย', 'กริ่งเกษมศรี', 'ม.2', '1', '7', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1008', '1', '5019', 'เด็กชายปุญญพัฒน์', 'อริยานนท์', 'ม.2', '1', '8', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1009', '1', '5400', 'เด็กชายภูมิภากร', 'ช่างเกวียนดี', 'ม.2', '1', '9', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1010', '1', '5404', 'เด็กชายรัฐศาสตร์', 'ก่อเกียรติตระกูล', 'ม.2', '1', '10', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1011', '1', '5407', 'เด็กชายภูริทัต', 'สมเชื้อเวียง', 'ม.2', '1', '11', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1012', '1', '5408', 'เด็กชายคณุตม์', 'สุขเกษม', 'ม.2', '1', '12', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1013', '1', '5419', 'เด็กชายสิริ', 'เนียมหมวด', 'ม.2', '1', '13', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1014', '1', '6158', 'เด็กชายติณณ์', 'เตชะกิจขจร', 'ม.2', '1', '14', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1015', '1', '6164', 'เด็กชายพีรดนย์', 'จันทร์ร่มเย็น', 'ม.2', '1', '15', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1016', '1', '6446', 'เด็กชายปัณณวิชญ์', 'แจ่มสว่าง', 'ม.2', '1', '16', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1017', '1', '6448', 'เด็กชายธัญชนิต', 'บุญยะ', 'ม.2', '1', '17', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1018', '1', '6783', 'เด็กชายนรภัทร', 'สอาดเอี่ยม', 'ม.2', '1', '18', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1019', '1', '7097', 'เด็กชายพุทธคุณ', 'จิระมานิตย์', 'ม.2', '1', '19', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1020', '1', '7421', 'เด็กชายศุภวิชญ์', 'อุไรชื่น', 'ม.2', '1', '20', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1021', '1', '3698', 'เด็กหญิงณารันย์ฌา', 'ถิรวัลย์', 'ม.2', '1', '21', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1022', '1', '3700', 'เด็กหญิงอัญรินทร์', 'อัครโชคศิรนนท์', 'ม.2', '1', '22', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1023', '1', '4116', 'เด็กหญิงพัชญ์พิชา', 'บารมีธนธร', 'ม.2', '1', '23', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1024', '1', '4117', 'เด็กหญิงฐปนา', 'ศรีสำราญ', 'ม.2', '1', '24', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1025', '1', '4119', 'เด็กหญิงศศมล', 'จันทร์แรม', 'ม.2', '1', '25', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1026', '1', '4123', 'เด็กหญิงนภัส', 'อาราเม', 'ม.2', '1', '26', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1027', '1', '4128', 'เด็กหญิงจิรฐา', 'จารุพุทธิศิริพจน์', 'ม.2', '1', '27', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1028', '1', '4130', 'เด็กหญิงอธิชา', 'บาลสดชื่น', 'ม.2', '1', '28', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1029', '1', '5027', 'เด็กหญิงนภัทร', 'กรังพานิชย์', 'ม.2', '1', '29', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1030', '1', '5434', 'เด็กหญิงเมธาวี', 'เกติพันธ์', 'ม.2', '1', '30', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1031', '1', '6320', 'เด็กหญิงภวิตา', 'ภูวนิธิธนา', 'ม.2', '1', '31', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1032', '1', '6451', 'เด็กหญิงปพิชญา', 'เครือแปง', 'ม.2', '1', '32', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1033', '1', '6651', 'เด็กหญิงวริญญา', 'กันแก้ว', 'ม.2', '1', '33', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1034', '1', '6653', 'เด็กหญิงณัฐชนันท์', 'ด้วงพูล', 'ม.2', '1', '34', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1035', '1', '7117', 'เด็กหญิงวริศรา', 'เขียวขำ', 'ม.2', '1', '35', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1036', '1', '7129', 'เด็กหญิงปฐมาภรณ์', 'นิลประภา', 'ม.2', '1', '36', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1037', '1', '7131', 'เด็กหญิงโชติกา', 'ผิวอ่อน', 'ม.2', '1', '37', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1038', '1', '7138', 'เด็กหญิงอรุณศิริ', 'วันดี', 'ม.2', '1', '38', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1039', '1', '7141', 'เด็กหญิงภัสร์วรัญช์', 'ห้วยหงษ์ทอง', 'ม.2', '1', '39', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1040', '1', '3679', 'เด็กชายจิรวัฒน์', 'เรืองไชยวุฒิ์', 'ม.2', '2', '1', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1041', '1', '3681', 'เด็กชายพีรณัฐ', 'สุขศรี', 'ม.2', '2', '2', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1042', '1', '3687', 'เด็กชายนิติรักษ์', 'โพธิ์ทองนาค', 'ม.2', '2', '3', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1043', '1', '4077', 'เด็กชายภาณุพัฒน์', 'แก่นจันทร์', 'ม.2', '2', '4', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1044', '1', '4080', 'เด็กชายกิตติศักดิ์', 'กิตติเรืองชัย', 'ม.2', '2', '5', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1045', '1', '4092', 'เด็กชายอมรกฤศ', 'สามงามยา', 'ม.2', '2', '6', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1046', '1', '4095', 'เด็กชายวรากร', 'อุดมผล', 'ม.2', '2', '7', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1047', '1', '4103', 'เด็กชายณัฐกิตติ์', 'ฐานสิทธิโรจน์', 'ม.2', '2', '8', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1048', '1', '5409', 'เด็กชายพันธุ์ธัช', 'สุริยากุลพันธ์', 'ม.2', '2', '9', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1049', '1', '5794', 'เด็กชายศุภกฤต', 'รวมวงษ์', 'ม.2', '2', '10', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1050', '1', '6159', 'เด็กชายณัทธร', 'ลบเลื่อน', 'ม.2', '2', '11', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1051', '1', '6165', 'เด็กชายชนนน', 'หงษ์จันทร์', 'ม.2', '2', '12', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1052', '1', '6443', 'เด็กชายปัณณวัฒน์', 'วิสิฐเพชรลดา', 'ม.2', '2', '13', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1053', '1', '6449', 'เด็กชายธฤตภณ', 'ชิวปรีชา', 'ม.2', '2', '14', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1054', '1', '7093', 'เด็กชายฐิติพันธ์', 'ตลับนาค', 'ม.2', '2', '15', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1055', '1', '7096', 'เด็กชายณัฐนที', 'ธรรมวงค์', 'ม.2', '2', '16', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1056', '1', '7098', 'เด็กชายณชพล', 'ทรัพย์ส่งแสง', 'ม.2', '2', '17', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1057', '1', '3701', 'เด็กหญิงภคมน', 'กานดา', 'ม.2', '2', '18', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1058', '1', '3702', 'เด็กหญิงอิงฟ้า', 'เลี้ยงรักษา', 'ม.2', '2', '19', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1059', '1', '4115', 'เด็กหญิงชษนิ', 'พวงพิกุล', 'ม.2', '2', '20', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1060', '1', '4125', 'เด็กหญิงกัญญพัชร', 'อุบล', 'ม.2', '2', '21', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1061', '1', '4134', 'เด็กหญิงพิชญา', 'มาลัยศรี', 'ม.2', '2', '22', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1062', '1', '4138', 'เด็กหญิงชลนิภา', 'แซ่จิว', 'ม.2', '2', '23', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1063', '1', '4571', 'เด็กหญิงกีรติญา', 'หมื่นภู', 'ม.2', '2', '24', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1064', '1', '4575', 'เด็กหญิงชนิกานต์', 'สุคนธมาน', 'ม.2', '2', '25', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1065', '1', '5421', 'เด็กหญิงรัตนธร', 'ปิยะวงษ์', 'ม.2', '2', '26', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1066', '1', '6452', 'เด็กหญิงชนิสรา', 'เอี่ยมสุขมงคล', 'ม.2', '2', '27', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1067', '1', '6652', 'เด็กหญิงกนกลักษณ์', 'เส็งเจริญ', 'ม.2', '2', '28', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1068', '1', '6655', 'เด็กหญิงฑิตยา', 'สุวรรณจินดา', 'ม.2', '2', '29', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1069', '1', '6656', 'เด็กหญิงวรัญลักษณ์', 'แคบำรุง', 'ม.2', '2', '30', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1070', '1', '7113', 'เด็กหญิงปฐมวรรณ', 'ห่อมณีรัตน์', 'ม.2', '2', '31', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1071', '1', '7126', 'เด็กหญิงอรณิชา', 'ผลเจริญรัตน์', 'ม.2', '2', '32', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1072', '1', '7134', 'เด็กหญิงชาลินีย์', 'เหลือบุญชู', 'ม.2', '2', '33', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1073', '1', '7139', 'เด็กหญิงขวัญข้าว', 'ลาภเวช', 'ม.2', '2', '34', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1074', '1', '7424', 'เด็กหญิงญาณิศา', 'เสาศิริ', 'ม.2', '2', '35', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1075', '1', '7425', 'เด็กหญิงฐิติกาญจน์', 'ใคร่ครวญ', 'ม.2', '2', '36', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1076', '1', '3725', 'เด็กชายกิตติพัฒน์', 'โตเลี้ยง', 'ม.2', '3', '1', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1077', '1', '4098', 'เด็กชายชลวิทย์', 'ชลธี', 'ม.2', '3', '2', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1078', '1', '4712', 'เด็กชายสุรัตน์', 'ดวงแก้ว', 'ม.2', '3', '3', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1079', '1', '5417', 'เด็กชายคุณานนท์', 'อุนทุโร', 'ม.2', '3', '4', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1080', '1', '5791', 'เด็กชายภัทรภานุ', 'ผุดเผือก', 'ม.2', '3', '5', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1081', '1', '6312', 'เด็กชายอิทธิเชษฐ์', 'ห้วยหงษ์ทอง', 'ม.2', '3', '6', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1082', '1', '6444', 'เด็กชายธนาธิป', 'ตั้งศุภธวัช', 'ม.2', '3', '7', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1083', '1', '7089', 'เด็กชายวรภัทร', 'วงศ์รัชตโภคัย', 'ม.2', '3', '8', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1084', '1', '7091', 'เด็กชายภคิน', 'กิจสมัคร', 'ม.2', '3', '9', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1085', '1', '7094', 'เด็กชายอธิษฐ์ณัฐ', 'ทวีวุฒิจิรโชติ', 'ม.2', '3', '10', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1086', '1', '7099', 'เด็กชายจิรัชย์', 'นพมณีวิจิตร', 'ม.2', '3', '11', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1087', '1', '7106', 'เด็กชายต้นทอง พีเตอร์', 'คูเปอร์', 'ม.2', '3', '12', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1088', '1', '7109', 'เด็กชายภควัต', 'นิ่มอนงค์', 'ม.2', '3', '13', 'เขียว', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1089', '1', '7112', 'เด็กชายฤทธิ์', 'ประจุไทย', 'ม.2', '3', '14', 'ฟ้า', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1090', '1', '7149', 'เด็กชายตรีเพชร', 'ตรีเวชอักษร', 'ม.2', '3', '15', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1091', '1', '3697', 'เด็กหญิงภาวนียา', 'เยาวนนท์', 'ม.2', '3', '16', 'ชมพู', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1092', '1', '3699', 'เด็กหญิงกัญญาพัชร', 'ลือยาม', 'ม.2', '3', '17', 'ส้ม', '2025-11-03 15:08:47');
INSERT INTO `students` VALUES ('1093', '1', '3716', 'เด็กหญิงนริศรา', 'ปิยะพันธุ์', 'ม.2', '3', '18', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1094', '1', '4118', 'เด็กหญิงไอลดา', 'พิณทอง', 'ม.2', '3', '19', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1095', '1', '4180', 'เด็กหญิงภัทรวดี', 'ธนฤกษ์ชัย', 'ม.2', '3', '20', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1096', '1', '4713', 'เด็กหญิงศิริภัสสร', 'จึงเจริญวงศา', 'ม.2', '3', '21', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1097', '1', '5423', 'เด็กหญิงชุตินันท์', 'เล็กล้วน', 'ม.2', '3', '22', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1098', '1', '5427', 'เด็กหญิงวีรชา', 'ผจงสาลีปัญญา', 'ม.2', '3', '23', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1099', '1', '5430', 'เด็กหญิงรดา', 'ธนาพรอุดมโชค', 'ม.2', '3', '24', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1100', '1', '5792', 'เด็กหญิงรมิดา', 'เผ่าพงศ์ษา', 'ม.2', '3', '25', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1101', '1', '5799', 'เด็กหญิงนันท์นภัส', 'แดงบุญมี', 'ม.2', '3', '26', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1102', '1', '7120', 'เด็กหญิงพิมพ์พิศา', 'ธนะจินดานนท์', 'ม.2', '3', '27', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1103', '1', '7121', 'เด็กหญิงโชติกา', 'จบศรี', 'ม.2', '3', '28', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1104', '1', '7130', 'เด็กหญิงญาณิศา', 'รัตนศรี', 'ม.2', '3', '29', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1105', '1', '7135', 'เด็กหญิงกรณิศ', 'ศรีบุญเรือง', 'ม.2', '3', '30', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1106', '1', '7143', 'เด็กหญิงวรรณกานต์', 'บัวบาน', 'ม.2', '3', '31', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1107', '1', '7145', 'เด็กหญิงณัฏฐรัตน์', 'สุริยาวงษ์', 'ม.2', '3', '32', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1108', '1', '7146', 'เด็กหญิงกรภพ', 'วิวัฒนภูษิต', 'ม.2', '3', '33', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1109', '1', '7422', 'เด็กหญิงอภิชญา แอนนี่', 'แล็ตตี้', 'ม.2', '3', '34', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1110', '1', '7423', 'เด็กหญิงภัทรณัชชา', 'บุญศรีเมือง', 'ม.2', '3', '35', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1111', '1', '3625', 'เด็กชายภูชนะนันท์', 'แสงเนียม', 'ม.2', '4', '1', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1112', '1', '4076', 'เด็กชายลาภิศ', 'ขำอรุณ', 'ม.2', '4', '2', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1113', '1', '4079', 'เด็กชายปัณณธร', 'ญาณโกมุท', 'ม.2', '4', '3', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1114', '1', '4084', 'เด็กชายณวัฒน์', 'อ่วมสืบเชื้อ', 'ม.2', '4', '4', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1115', '1', '4724', 'เด็กชายกรพิเชษฐ์', 'จำปาเงิน', 'ม.2', '4', '5', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1116', '1', '5402', 'เด็กชายชยกร', 'บุญศรี', 'ม.2', '4', '6', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1117', '1', '6161', 'เด็กชายกรวิชญ์', 'สัมฤทธิ์วงศ์', 'ม.2', '4', '7', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1118', '1', '6334', 'เด็กชายพชรพล', 'ฤกษ์สำราญ', 'ม.2', '4', '8', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1119', '1', '6902', 'เด็กชายภูมิพัฒน์', 'จิตรามณีโรจน์', 'ม.2', '4', '9', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1120', '1', '6903', 'เด็กชายณวัฒน์', 'เกศทรัพย์สถาพร', 'ม.2', '4', '10', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1121', '1', '7090', 'เด็กชายศุภชัย', 'เมฆอรุณ', 'ม.2', '4', '11', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1122', '1', '7092', 'เด็กชายกันตินันท์', 'จรมา', 'ม.2', '4', '12', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1123', '1', '7100', 'เด็กชายธนกร', 'ฝั้นปันวงค์', 'ม.2', '4', '13', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1124', '1', '7104', 'เด็กชายสรกฤต', 'สังข์บรรจง', 'ม.2', '4', '14', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1125', '1', '7105', 'เด็กชายจิตรกร', 'เอมอิ่ม', 'ม.2', '4', '15', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1126', '1', '7108', 'เด็กชายณภัทร', 'นิ่มอนงค์', 'ม.2', '4', '16', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1127', '1', '7110', 'เด็กชายศุภวิชญ์', 'เถื่อนถ้ำแก้ว', 'ม.2', '4', '17', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1128', '1', '7148', 'เด็กชายธนภาคย์', 'เกิดโพชา', 'ม.2', '4', '18', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1129', '1', '4114', 'เด็กหญิงเบญจวรัมพร', 'อุทัย', 'ม.2', '4', '19', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1130', '1', '4127', 'เด็กหญิงณัฐณิชา', 'แซ่ลิ้ม', 'ม.2', '4', '20', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1131', '1', '5025', 'เด็กหญิงภิญญดา', 'เพชรนาคิน', 'ม.2', '4', '21', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1132', '1', '5160', 'เด็กหญิงนงนภัส', 'ร้อยอำแพง', 'ม.2', '4', '22', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1133', '1', '5420', 'เด็กหญิงธันย์ชนก', 'จันทร์ตรี', 'ม.2', '4', '23', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1134', '1', '5796', 'เด็กหญิงศิรภัสส์สร', 'ศิริตันหยง', 'ม.2', '4', '24', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1135', '1', '6450', 'เด็กหญิงปัณญพัชร์', 'มาลาบุปผา', 'ม.2', '4', '25', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1136', '1', '6905', 'เด็กหญิงเกศรินทร์', 'รันดาเว', 'ม.2', '4', '26', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1137', '1', '6906', 'เด็กหญิงวรวลัญช์', 'อวยชัย', 'ม.2', '4', '27', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1138', '1', '6907', 'เด็กหญิงภคพร', 'จิดารักษ์', 'ม.2', '4', '28', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1139', '1', '7124', 'เด็กหญิงฐานิดา', 'หลอดทองคำ', 'ม.2', '4', '29', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1140', '1', '7125', 'เด็กหญิงศิริลักษณ์', 'นงนุช', 'ม.2', '4', '30', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1141', '1', '7127', 'เด็กหญิงลภัสสินี', 'วารี', 'ม.2', '4', '31', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1142', '1', '7136', 'เด็กหญิงรัชณัน', 'ชมภูนิช', 'ม.2', '4', '32', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1143', '1', '7137', 'เด็กหญิงน้ำทอง', 'อ่วมเรืองศรี', 'ม.2', '4', '33', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1144', '1', '7142', 'เด็กหญิงปิ่นปินัทธ์', 'ขุนทอง', 'ม.2', '4', '34', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1145', '1', '3683', 'เด็กชายพชรธัช', 'ชูพาณิชสกุล', 'ม.2', '5', '1', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1146', '1', '3724', 'เด็กชายอภิวัชร์', 'ทองประสงค์', 'ม.2', '5', '2', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1147', '1', '4099', 'เด็กชายธนวัฒน์', 'พงศ์วรินทร์', 'ม.2', '5', '3', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1148', '1', '4566', 'เด็กชายกิ่งกมล', 'สุขจินดา', 'ม.2', '5', '4', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1149', '1', '4680', 'เด็กชายพีร์ระณัฐ', 'คงคารัตน์', 'ม.2', '5', '5', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1150', '1', '5199', 'เด็กชายจิรวัฒน์', 'มาดำ', 'ม.2', '5', '6', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1151', '1', '5401', 'เด็กชายชวิศ', 'วานิชพงษ์พันธุ์', 'ม.2', '5', '7', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1152', '1', '5406', 'เด็กชายธีราพล', 'โชติทรัพย์', 'ม.2', '5', '8', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1153', '1', '5412', 'เด็กชายนนธนภัทร', 'หนูเปีย', 'ม.2', '5', '9', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1154', '1', '5793', 'เด็กชายศุภกันต์', 'รวมวงษ์', 'ม.2', '5', '10', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1155', '1', '7095', 'เด็กชายอัคราช', 'เชิดสูงเนิน', 'ม.2', '5', '11', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1156', '1', '7101', 'เด็กชายเสฏฐวุฒิ', 'มุสิกะ', 'ม.2', '5', '12', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1157', '1', '7103', 'เด็กชายศุภกฤต', 'ฉิ่งทองคำ', 'ม.2', '5', '13', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1158', '1', '7111', 'เด็กชายทศธรรม', 'แดหวา', 'ม.2', '5', '14', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1159', '1', '4129', 'เด็กหญิงภัสส์ธีมา', 'สำราญศิลป์', 'ม.2', '5', '15', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1160', '1', '4132', 'เด็กหญิงกัญญพัชร', 'พงศ์วรินทร์', 'ม.2', '5', '16', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1161', '1', '4136', 'เด็กหญิงวรวลัญช์', 'เอกทรัพย์สิน', 'ม.2', '5', '17', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1162', '1', '5023', 'เด็กหญิงณัฐธิชา', 'ปิ่นทำนัก', 'ม.2', '5', '18', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1163', '1', '5159', 'เด็กหญิงณัฐธีรา', 'ท่าจีน', 'ม.2', '5', '19', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1164', '1', '5426', 'เด็กหญิงวรวรรณ', 'ธีรกวินพงศ์', 'ม.2', '5', '20', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1165', '1', '5437', 'เด็กหญิงสุภาวิกา', 'อุนาภาค', 'ม.2', '5', '21', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1166', '1', '7114', 'เด็กหญิงณัฐรดา', 'ศิริพงษ์เวคิน', 'ม.2', '5', '22', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1167', '1', '7116', 'เด็กหญิงศิรินญา', 'พลอยเหลี่ยม', 'ม.2', '5', '23', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1168', '1', '7119', 'เด็กหญิงธมลวรรณ', 'บุญเฉย', 'ม.2', '5', '24', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1169', '1', '7122', 'เด็กหญิงพลอยชมพู', 'กลึงสวน', 'ม.2', '5', '25', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1170', '1', '7128', 'เด็กหญิงศรุดา', 'คุณัชวรกรณ์', 'ม.2', '5', '26', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1171', '1', '7132', 'เด็กหญิงณฐมน', 'วงกลม', 'ม.2', '5', '27', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1172', '1', '7144', 'เด็กหญิงปภาวรินท์', 'กุลสืบ', 'ม.2', '5', '28', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1173', '1', '7147', 'เด็กหญิงณัฏฐ์พัชร์', 'พิทักษ์ภากร', 'ม.2', '5', '29', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1174', '1', '7150', 'เด็กหญิงภัทรมล', 'เรืองธัมรงค์', 'ม.2', '5', '30', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1175', '1', '7151', 'เด็กหญิงศิริภัสสร', 'จรูญลักษณานุชิต', 'ม.2', '5', '31', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1176', '1', '3598', 'เด็กชายพิสิษฐ์', 'ทรัพย์มั่นคง', 'ม.3', '1', '1', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1177', '1', '3599', 'เด็กชายนัทฐเศรษฐ์', 'สายทองอินทร์', 'ม.3', '1', '2', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1178', '1', '3602', 'เด็กชายเสฎฐวุฒิ', 'บุญขุนยัง', 'ม.3', '1', '3', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1179', '1', '3603', 'เด็กชายวรัญญู', 'เจ็งสวัสดิ์', 'ม.3', '1', '4', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1180', '1', '3604', 'เด็กชายชยางกูร', 'วิเศษสม', 'ม.3', '1', '5', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1181', '1', '3632', 'เด็กชายชุติเดช', 'กิจอุดมพร', 'ม.3', '1', '6', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1182', '1', '3722', 'เด็กชายวิศรุต', 'ทรงทรัพย์สิน', 'ม.3', '1', '7', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1183', '1', '3723', 'เด็กชายวรชัย', 'พูนขวัญ', 'ม.3', '1', '8', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1184', '1', '4564', 'เด็กชายนรวัฒน์', 'เล็กสุมา', 'ม.3', '1', '9', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1185', '1', '4987', 'เด็กชายศุภกฤษ', 'สัมมเสถียร', 'ม.3', '1', '10', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1186', '1', '4989', 'เด็กชายอิชย์ฐิภัส', 'สุขพงษ์ไทย', 'ม.3', '1', '11', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1187', '1', '5153', 'นายพรพัฒน์', 'เสมาทอง', 'ม.3', '1', '12', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1188', '1', '6441', 'เด็กชายวีรภัทร', 'เรืองพิจิตร', 'ม.3', '1', '13', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1189', '1', '6646', 'เด็กชายธีรกานต์', 'สมมาก', 'ม.3', '1', '14', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1190', '1', '6823', 'เด็กชายพุฒิพงศ์', 'สุนศุข', 'ม.3', '1', '15', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1191', '1', '6829', 'เด็กชายกฤษณ', 'รัตนนุ่มน้อย', 'ม.3', '1', '16', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1192', '1', '6831', 'เด็กชายอภิสิฏฐ์พล', 'พร้อมมงคล', 'ม.3', '1', '17', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1193', '1', '6842', 'เด็กชายธนาวุฒิ', 'จิระวิทยพงศ์', 'ม.3', '1', '18', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1194', '1', '6854', 'เด็กชายธศรัณย์', 'ณัฏฐ์ธนกุลบดี', 'ม.3', '1', '19', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1195', '1', '6897', 'เด็กชายธนดล', 'ตันเตชะสา', 'ม.3', '1', '20', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1196', '1', '3651', 'เด็กหญิงพรนภา', 'ธีราพงษ์', 'ม.3', '1', '21', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1197', '1', '3656', 'เด็กหญิงจันทกานฐ์', 'ณัทกลทีป์', 'ม.3', '1', '22', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1198', '1', '3659', 'เด็กหญิงอัจฉริยาภา', 'มรกตจินดา', 'ม.3', '1', '23', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1199', '1', '3660', 'เด็กหญิงศุภาภร', 'พันธ์ทอง', 'ม.3', '1', '24', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1200', '1', '3661', 'เด็กหญิงณัฏฐ์กานต์', 'อภิสิทธิ์', 'ม.3', '1', '25', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1201', '1', '3674', 'เด็กหญิงณัฐชยา', 'บูรณัติ', 'ม.3', '1', '26', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1202', '1', '4070', 'เด็กหญิงณภัสสรณ์', 'ชุติธีระวิทย์', 'ม.3', '1', '27', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1203', '1', '4568', 'เด็กหญิงชนากานต์', 'เงินสมุทร', 'ม.3', '1', '28', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1204', '1', '4569', 'เด็กหญิงเอมิกา', 'ลิ้มจิตสมบูรณ์', 'ม.3', '1', '29', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1205', '1', '5001', 'เด็กหญิงลภัสสินี', 'สุวรมงคล', 'ม.3', '1', '30', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1206', '1', '5006', 'เด็กหญิงพิมพ์รภัส', 'สุปัญญารักษ์', 'ม.3', '1', '31', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1207', '1', '5008', 'เด็กหญิงภิญญาพัชญ์', 'โสมดี', 'ม.3', '1', '32', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1208', '1', '5011', 'เด็กหญิงยศวรินทร์', 'ราศรีดี', 'ม.3', '1', '33', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1209', '1', '5013', 'เด็กหญิงรรัญลักษณ์', 'เกษมศิรินาวิน', 'ม.3', '1', '34', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1210', '1', '5172', 'เด็กหญิงแพรวา', 'รัตนเขมากร', 'ม.3', '1', '35', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1211', '1', '5395', 'เด็กหญิงณัชช์สิริ', 'ใสแสง', 'ม.3', '1', '36', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1212', '1', '6152', 'เด็กหญิงกรชนก', 'ชีวีวัฒน์', 'ม.3', '1', '37', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1213', '1', '6647', 'เด็กหญิงปัณณวีร์', 'อันอาตม์งาม', 'ม.3', '1', '38', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1214', '1', '6864', 'เด็กหญิงอภิศฎา', 'ฉันทวิลาศ', 'ม.3', '1', '39', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1215', '1', '6873', 'เด็กหญิงอมรพัฐ', 'สมิทธากร', 'ม.3', '1', '40', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1216', '1', '6876', 'เด็กหญิงกัญญาภัค', 'อุตตมะเวทิน', 'ม.3', '1', '41', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1217', '1', '7419', 'เด็กหญิงรัชเนศ', 'วัฒนาพร', 'ม.3', '1', '42', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1218', '1', '7420', 'เด็กหญิงอภิณห์พร', 'พัฒนฉัตร์ธนาธร', 'ม.3', '1', '43', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1219', '1', '3606', 'เด็กชายภูมิภัช', 'ธรรมอภิพล', 'ม.3', '2', '1', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1220', '1', '3619', 'เด็กชายชาญณภัทร', 'ปานเจริญ', 'ม.3', '2', '2', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1221', '1', '3622', 'เด็กชายภูรินท์', 'ราชคมน์', 'ม.3', '2', '3', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1222', '1', '3628', 'เด็กชายชญานนท์', 'บูรณัติ', 'ม.3', '2', '4', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1223', '1', '3635', 'เด็กชายภพธนภณ', 'ประไพพงค์', 'ม.3', '2', '5', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1224', '1', '4705', 'เด็กชายนราวิชญ์', 'ขำเสียงหวาน', 'ม.3', '2', '6', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1225', '1', '4986', 'เด็กชายญาณกร', 'โตอนันต์', 'ม.3', '2', '7', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1226', '1', '4992', 'เด็กชายศุภวิชญ์', 'เข็มขาว', 'ม.3', '2', '8', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1227', '1', '4996', 'เด็กชายพศิน', 'สุครีวก', 'ม.3', '2', '9', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1228', '1', '5778', 'เด็กชายกรปพน', 'ตั้งฤาษีเจริญ', 'ม.3', '2', '10', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1229', '1', '5789', 'เด็กชายณัชพล', 'ธาราวัฒนกิต', 'ม.3', '2', '11', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1230', '1', '5982', 'เด็กชายณฐวรรธน์', 'เอกวรานุกูลศิริ', 'ม.3', '2', '12', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1231', '1', '6146', 'เด็กชายศรัณย์ฤทธิ์', 'กาญจนานุชิต', 'ม.3', '2', '13', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1232', '1', '6297', 'เด็กชายปัณณพงศ์', 'ไทยเจียมอารีย์', 'ม.3', '2', '14', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1233', '1', '6440', 'เด็กชายกัมปนาท', 'วานิชทวีวัฒน์', 'ม.3', '2', '15', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1234', '1', '6819', 'เด็กชายรัชชานนท์', 'โภคา', 'ม.3', '2', '16', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1235', '1', '6824', 'เด็กชายศิริวัตธน์', 'สุวรรณศรี', 'ม.3', '2', '17', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1236', '1', '6826', 'เด็กชายณดล', 'เกศทรัพย์สถาพร', 'ม.3', '2', '18', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1237', '1', '6836', 'เด็กชายไชยภัทร', 'ฮั่วเฮง', 'ม.3', '2', '19', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1238', '1', '6845', 'เด็กชายสรกฤช', 'เหล่าเหมมณี', 'ม.3', '2', '20', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1239', '1', '7086', 'เด็กชายพศิน', 'พสิษฐ์จิรไพโรจน์', 'ม.3', '2', '21', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1240', '1', '7087', 'เด็กชายวัทธิกร', 'บัวบาน', 'ม.3', '2', '22', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1241', '1', '3658', 'เด็กหญิงพิชญ์นาฏ', 'นวานุช', 'ม.3', '2', '23', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1242', '1', '3662', 'เด็กหญิงธริศรา', 'อินเฉิดฉาย', 'ม.3', '2', '24', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1243', '1', '3673', 'เด็กหญิงวชิรญาณ์', 'จรดล', 'ม.3', '2', '25', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1244', '1', '4073', 'เด็กหญิงกัลยกรณ์', 'ละออทรัพย์', 'ม.3', '2', '26', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1245', '1', '4075', 'เด็กหญิงณิชกานต์', 'วิทยาพันธ์ประชา', 'ม.3', '2', '27', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1246', '1', '4113', 'เด็กหญิงเขมจิรา', 'ดิรัญเพชร', 'ม.3', '2', '28', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1247', '1', '5004', 'เด็กหญิงรวิสรา', 'คนใจซื่อ', 'ม.3', '2', '29', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1248', '1', '5007', 'เด็กหญิงณัฐนรี', 'เหล็งบำรุง', 'ม.3', '2', '30', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1249', '1', '5154', 'เด็กหญิงสุวพิชชา', 'จั่นบางยาง', 'ม.3', '2', '31', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1250', '1', '5780', 'เด็กหญิงณัฐนันท์', 'ปัจฉิมพิหงค์', 'ม.3', '2', '32', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1251', '1', '5783', 'เด็กหญิงชัญญา', 'ทองส่งโสม', 'ม.3', '2', '33', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1252', '1', '6859', 'นางสาวกัญญาภัค', 'เอกเผ่าพันธุ์', 'ม.3', '2', '34', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1253', '1', '6866', 'เด็กหญิงอภิสรา', 'นีละศรี', 'ม.3', '2', '35', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1254', '1', '6870', 'เด็กหญิงธนัฎฐา', 'ฤกษ์เจริญชัย', 'ม.3', '2', '36', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1255', '1', '6875', 'เด็กหญิงภิญญาพัชญ์', 'สุวรรณประเสริฐ', 'ม.3', '2', '37', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1256', '1', '6888', 'เด็กหญิงนภัทร', 'จงสุขสันติกุล', 'ม.3', '2', '38', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1257', '1', '6896', 'เด็กหญิงณิชา', 'เจริญวัย', 'ม.3', '2', '39', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1258', '1', '7088', 'เด็กหญิงพิมพ์ขวัญ', 'สุวรรณมณี', 'ม.3', '2', '40', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1259', '1', '3609', 'เด็กชายจิรภัทร', 'นิยมญาติ', 'ม.3', '3', '1', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1260', '1', '3611', 'เด็กชายอินทร์ฐณัฐ', 'เรืองฉาย', 'ม.3', '3', '2', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1261', '1', '3627', 'เด็กชายปัณณวิชญ์', 'ล้ำเลิศเรืองไกร', 'ม.3', '3', '3', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1262', '1', '3633', 'เด็กชายวราเมธ', 'ไทยภักดี', 'ม.3', '3', '4', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1263', '1', '3637', 'เด็กชายธนเดช', 'วงศ์สุวรรณ', 'ม.3', '3', '5', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1264', '1', '3640', 'เด็กชายกิตติภพ', 'รัตนผล', 'ม.3', '3', '6', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1265', '1', '6148', 'เด็กชายเมธี', 'พูลสวัสดิ์', 'ม.3', '3', '7', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1266', '1', '6813', 'นายธนพล', 'บุตรสุริย์', 'ม.3', '3', '8', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1267', '1', '6827', 'เด็กชายปรเมธ', 'เกษลาม', 'ม.3', '3', '9', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1268', '1', '6828', 'เด็กชายเตชิต', 'วัฒนวรณัน', 'ม.3', '3', '10', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1269', '1', '6830', 'เด็กชายอาภากร', 'สิรศักดิ์ชัยกุล', 'ม.3', '3', '11', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1270', '1', '6832', 'เด็กชายภคพล', 'เอี่ยมจินดา', 'ม.3', '3', '12', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1271', '1', '6833', 'เด็กชายภาสพงศ์', 'จินตนารักถิ่น', 'ม.3', '3', '13', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1272', '1', '6843', 'เด็กชายกันต์กวี', 'เอี่ยมวงศ์หิรัญ', 'ม.3', '3', '14', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1273', '1', '6847', 'เด็กชายปธานิน', 'สกาวรัตน์', 'ม.3', '3', '15', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1274', '1', '6850', 'เด็กชายคณพศ', 'รอดรักษา', 'ม.3', '3', '16', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1275', '1', '6855', 'เด็กชายชยพล', 'นิมิตรศดิกุล', 'ม.3', '3', '17', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1276', '1', '6898', 'เด็กชายธัญนพ', 'จันทร์สืบเชื้อสาย', 'ม.3', '3', '18', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1277', '1', '7061', 'เด็กชายศรรวริศ', 'อ่อนแก้ว', 'ม.3', '3', '19', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1278', '1', '3641', 'นางสาวธนวันต์', 'ไทยเดช', 'ม.3', '3', '20', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1279', '1', '3654', 'เด็กหญิงณธิดา', 'บุญเทียม', 'ม.3', '3', '21', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1280', '1', '4074', 'เด็กหญิงหนึ่งมีนา', 'หอมพวงภู่', 'ม.3', '3', '22', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1281', '1', '5010', 'เด็กหญิงณพัชญา', 'สุวรรณ', 'ม.3', '3', '23', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1282', '1', '5790', 'เด็กหญิงสุฐิตา', 'สุขาบูรณ์', 'ม.3', '3', '24', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1283', '1', '6149', 'เด็กหญิงรมย์รัมภา', 'สุรภักดิ์ภิรมย์', 'ม.3', '3', '25', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1284', '1', '6648', 'เด็กหญิงปวริศา', 'โชติจารุดิลก', 'ม.3', '3', '26', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1285', '1', '6857', 'เด็กหญิงเมยา', 'จินตนาวลี', 'ม.3', '3', '27', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1286', '1', '6858', 'นางสาวปุญญารัศม์', 'บุผาสน', 'ม.3', '3', '28', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1287', '1', '6860', 'นางสาวนัชชา', 'รอดนางรอง', 'ม.3', '3', '29', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1288', '1', '6862', 'นางสาวปณัฎฐ์สรณ์', 'พุ่มดียิ่ง', 'ม.3', '3', '30', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1289', '1', '6869', 'เด็กหญิงชาคริยา', 'เศวตวรชัย', 'ม.3', '3', '31', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1290', '1', '6872', 'เด็กหญิงปวีณ์กร', 'ปั้นปาน', 'ม.3', '3', '32', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1291', '1', '6877', 'เด็กหญิงพิมพ์พิชชา', 'เอกพัชรวรรณ', 'ม.3', '3', '33', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1292', '1', '6881', 'นางสาวพัสวีร์ณัฐ', 'เจริญพร', 'ม.3', '3', '34', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1293', '1', '6882', 'เด็กหญิงปโยธรา', 'ธนาพรทิพา', 'ม.3', '3', '35', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1294', '1', '6885', 'เด็กหญิงรุจาภา', 'พันการุ่ง', 'ม.3', '3', '36', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1295', '1', '6893', 'เด็กหญิงสรศิริ', 'เจริญราษฎร์', 'ม.3', '3', '37', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1296', '1', '3612', 'เด็กชายณัฐพัชร์', 'ศรีพุ่มไข่', 'ม.3', '4', '1', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1297', '1', '3631', 'เด็กชายโยธิน', 'ยุรชาติ', 'ม.3', '4', '2', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1298', '1', '3634', 'เด็กชายนวิน', 'แสงสุริย์วงศ์', 'ม.3', '4', '3', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1299', '1', '3639', 'เด็กชายภูดิศ', 'แสงเกื้อหนุน', 'ม.3', '4', '4', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1300', '1', '4064', 'เด็กชายอภิชา', 'อธิวันดี', 'ม.3', '4', '5', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1301', '1', '4065', 'เด็กชายสิรธี', 'ชนประเสริฐ', 'ม.3', '4', '6', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1302', '1', '4563', 'เด็กชายนนทพัทธ์', 'มหาพงษ์', 'ม.3', '4', '7', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1303', '1', '5155', 'เด็กชายเตชธรรม', 'จุ้ยบาง', 'ม.3', '4', '8', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1304', '1', '6439', 'เด็กชายทินภัทร์', 'วาดี', 'ม.3', '4', '9', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1305', '1', '6771', 'เด็กชายคณธัช', 'ก้วยไข่มุข', 'ม.3', '4', '10', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1306', '1', '6821', 'เด็กชายอธิพัทธ์', 'โชคลาภบุญไชย', 'ม.3', '4', '11', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1307', '1', '6825', 'เด็กชายพิสิษฐ์', 'เพ็งสว่าง', 'ม.3', '4', '12', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1308', '1', '6834', 'เด็กชายศิรวิทย์', 'พงษ์หาญพาณิชย์', 'ม.3', '4', '13', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1309', '1', '6838', 'เด็กชายกรณินทร์', 'พัชราพิมล', 'ม.3', '4', '14', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1310', '1', '6840', 'เด็กชายกันต์รพี', 'วงศ์พัฒนานิวาศ', 'ม.3', '4', '15', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1311', '1', '6844', 'เด็กชายปณิธิ', 'เพชรนุ้ย', 'ม.3', '4', '16', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1312', '1', '6851', 'เด็กชายธนโชติ', 'นงค์นวล', 'ม.3', '4', '17', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1313', '1', '6853', 'เด็กชายปัญณพล', 'แร่อรุณ', 'ม.3', '4', '18', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1314', '1', '6856', 'เด็กชายศศกร', 'เรืองฤทธิ์', 'ม.3', '4', '19', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1315', '1', '7070', 'เด็กชายวิสุทธิรัตน์', 'ใจซื่อ', 'ม.3', '4', '20', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1316', '1', '3649', 'เด็กหญิงชลภัสสรณ์', 'จรดล', 'ม.3', '4', '21', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1317', '1', '4704', 'เด็กหญิงปัญจพร', 'จันทรวินิจ', 'ม.3', '4', '22', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1318', '1', '5015', 'เด็กหญิงธาราทิพย์', 'ตั้งสุทธิวงษ์', 'ม.3', '4', '23', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1319', '1', '5018', 'เด็กหญิงฐิตารีย์', 'รอดรักษาทรัพย์', 'ม.3', '4', '24', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1320', '1', '5171', 'นางสาวพิมพ์ลดา', 'ชิวปรีชา', 'ม.3', '4', '25', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1321', '1', '5779', 'เด็กหญิงพัชรพร', 'พักโพธิ์เย็น', 'ม.3', '4', '26', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1322', '1', '5786', 'เด็กหญิงหทัยภัทร์', 'กฤษฎารักษ์', 'ม.3', '4', '27', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1323', '1', '6150', 'เด็กหญิงณิชากร', 'จันทร์วาววาม', 'ม.3', '4', '28', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1324', '1', '6154', 'เด็กหญิงอิฏฐา', 'ทองศรี', 'ม.3', '4', '29', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1325', '1', '6156', 'เด็กหญิงกัญญารัตน์', 'จันทร์ดี', 'ม.3', '4', '30', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1326', '1', '6442', 'เด็กหญิงธีรชญาน์', 'ใจมั่น', 'ม.3', '4', '31', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1327', '1', '6861', 'นางสาวอัญรินทร์', 'ทรัพย์วิไลพร', 'ม.3', '4', '32', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1328', '1', '6874', 'นางสาวณัชปภา', 'ฮกฮวดซิ้ม', 'ม.3', '4', '33', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1329', '1', '6878', 'เด็กหญิงอัญชิสา', 'ศรีสันติเวศน์', 'ม.3', '4', '34', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1330', '1', '6880', 'เด็กหญิงอนุตา', 'อุตเสน', 'ม.3', '4', '35', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1331', '1', '6890', 'เด็กหญิงกัญญาพัชร', 'พุกสุริย์วงศ์', 'ม.3', '4', '36', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1332', '1', '6900', 'เด็กหญิงลินธิลา', 'ขำตา', 'ม.3', '4', '37', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1333', '1', '7062', 'นางสาวพลอยลดา', 'คนไว', 'ม.3', '4', '38', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1334', '1', '3605', 'เด็กชายธนรัตน์', 'หมอนทอง', 'ม.3', '5', '1', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1335', '1', '3623', 'เด็กชายพรหมพิริยะ', 'พูพะเนียด', 'ม.3', '5', '2', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1336', '1', '3626', 'เด็กชายภัทรนันท์', 'สถิรวัฒนานนท์', 'ม.3', '5', '3', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1337', '1', '4982', 'นายวรรธนธร', 'คำสันติพร', 'ม.3', '5', '4', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1338', '1', '4990', 'เด็กชายอรรถนนท์', 'ซุ่นสมบุญ', 'ม.3', '5', '5', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1339', '1', '5179', 'เด็กชายพันธุ์ชัย', 'วัฒน์สืบแถว', 'ม.3', '5', '6', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1340', '1', '5992', 'เด็กชายชุติมันต์', 'ศรีอนันต์', 'ม.3', '5', '7', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1341', '1', '6770', 'เด็กชายภูวเดช', 'แอรอง', 'ม.3', '5', '8', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1342', '1', '6815', 'นายษมากร', 'มะกรวัฒนะ', 'ม.3', '5', '9', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1343', '1', '6816', 'นายวสุ', 'ชูวงศ์วาน', 'ม.3', '5', '10', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1344', '1', '6817', 'นายสิทธินันท์', 'น้อมศิริ', 'ม.3', '5', '11', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1345', '1', '6820', 'เด็กชายธนาธิป', 'หอระตะ', 'ม.3', '5', '12', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1346', '1', '6839', 'เด็กชายธีรเมธ', 'ลาภปรากฏ', 'ม.3', '5', '13', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1347', '1', '6841', 'เด็กชายภูดิท', 'ขวัญเล็ก', 'ม.3', '5', '14', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1348', '1', '6846', 'เด็กชายดุลยธันย์', 'ปัญจกาญจน์มณี', 'ม.3', '5', '15', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1349', '1', '6848', 'เด็กชายก้องณพัฒน์', 'หุ่นงาม', 'ม.3', '5', '16', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1350', '1', '6849', 'เด็กชายวิวรรธน์', 'ลิ้มบวรนันท์', 'ม.3', '5', '17', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1351', '1', '6852', 'เด็กชายปฏิพัทธิ์', 'พันธ์ประชา', 'ม.3', '5', '18', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1352', '1', '6901', 'เด็กชายปัณณ์', 'สุนทรีเกษม', 'ม.3', '5', '19', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1353', '1', '3645', 'เด็กหญิงไอรดา', 'สุยะ', 'ม.3', '5', '20', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1354', '1', '3650', 'เด็กหญิงวรพิชชา', 'บุญเสรฐ', 'ม.3', '5', '21', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1355', '1', '3665', 'เด็กหญิงวิชญาพร', 'ชุมรักษา', 'ม.3', '5', '22', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1356', '1', '3675', 'เด็กหญิงจิรากร', 'ดอนไพรมี', 'ม.3', '5', '23', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1357', '1', '3731', 'เด็กหญิงชัชนันท์', 'เจี๊ยบนา', 'ม.3', '5', '24', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1358', '1', '4067', 'เด็กหญิงเจนจิรา', 'เอี่ยมจินดา', 'ม.3', '5', '25', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1359', '1', '4567', 'เด็กหญิงณัฐบวรจันทร์', 'นรินทร์โชติ', 'ม.3', '5', '26', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1360', '1', '4706', 'เด็กหญิงกุลจิราณัฐ', 'สุภิเวก', 'ม.3', '5', '27', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1361', '1', '4999', 'เด็กหญิงปวริศา', 'คงคล้าย', 'ม.3', '5', '28', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1362', '1', '5399', 'เด็กหญิงทิตารีย์', 'โชคภัทรพิบูลย์', 'ม.3', '5', '29', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1363', '1', '5784', 'เด็กหญิงศรัณฐ์พร', 'จั่นเพ็ชร์', 'ม.3', '5', '30', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1364', '1', '5785', 'เด็กหญิงจิตร์ตานันท์', 'พิมพ์ทอง', 'ม.3', '5', '31', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1365', '1', '6155', 'เด็กหญิงวราภรณ์', 'ทิพโสต', 'ม.3', '5', '32', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1366', '1', '6871', 'เด็กหญิงแพรเพชร', 'ทองแท่งใหญ่', 'ม.3', '5', '33', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1367', '1', '6883', 'เด็กหญิงสาริศา', 'ค้าทวี', 'ม.3', '5', '34', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1368', '1', '6886', 'เด็กหญิงจิราพัชร์', 'จารีบูรณภาพ', 'ม.3', '5', '35', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1369', '1', '6892', 'เด็กหญิงณธิดา', 'โจววิทูรกิจ', 'ม.3', '5', '36', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1370', '1', '7584', 'เด็กหญิงพุฒิพร', 'หันนาคินทร์', 'ม.3', '5', '38', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1371', '1', '3167', 'นายธนิก', 'คมปรียารัตน์', 'ม.4', '1', '1', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1372', '1', '3168', 'นายไกรวิชญ์', 'ราหมัน', 'ม.4', '1', '2', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1373', '1', '3174', 'นายวุฒิภัทร', 'พัดเกร็ด', 'ม.4', '1', '3', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1374', '1', '3180', 'นายกันตพัชร', 'น้อยพิทักษ์', 'ม.4', '1', '4', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1375', '1', '3580', 'นายพิชเญศ', 'สุขสงวน', 'ม.4', '1', '5', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1376', '1', '4051', 'นายภัทรภูเบศ', 'ฉิ่งทองคำ', 'ม.4', '1', '6', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1377', '1', '4519', 'นายศุภฤกษ์', 'เจ็ดสี', 'ม.4', '1', '7', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1378', '1', '4527', 'นายธนัตถ์นินทร์', 'พุกสังข์ทอง', 'ม.4', '1', '8', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1379', '1', '4528', 'นายปานภ', 'น้อยใจ', 'ม.4', '1', '9', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1380', '1', '4974', 'นายพีรันธร', 'เจียมคุณานนท์', 'ม.4', '1', '10', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1381', '1', '4977', 'นายคุณธรรม', 'โพธิ์รักษา', 'ม.4', '1', '11', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1382', '1', '6137', 'นายกิตติพิชญ์', 'วงศ์อนุ', 'ม.4', '1', '12', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1383', '1', '6138', 'นายจิรโรจน์', 'ธนโชติชัยวัฒน์', 'ม.4', '1', '13', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1384', '1', '6597', 'นายกัลป์ระพี', 'ศรีขันติกุล', 'ม.4', '1', '14', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1385', '1', '6604', 'นายสรัลพร', 'ผิวงาม', 'ม.4', '1', '15', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1386', '1', '6607', 'นายนันท์ธนภูมิ', 'ห้วยหงษ์ทอง', 'ม.4', '1', '16', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1387', '1', '6615', 'นายภควชล', 'ชัยชาติ', 'ม.4', '1', '17', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1388', '1', '6620', 'นายพสิษฐ์', 'อุณหพงศา', 'ม.4', '1', '18', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1389', '1', '3196', 'นางสาวเปรมยุดา', 'คงสกุล', 'ม.4', '1', '19', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1390', '1', '3201', 'นางสาวพัฑฒิดา', 'พวงทอง', 'ม.4', '1', '20', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1391', '1', '3207', 'นางสาวภัทรวดี', 'พลสงคราม', 'ม.4', '1', '21', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1392', '1', '3215', 'นางสาวเขมณัฏฐ์', 'เกิดไพบูลย์', 'ม.4', '1', '22', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1393', '1', '4545', 'นางสาวณัฐวรา', 'นาคขำ', 'ม.4', '1', '23', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1394', '1', '4978', 'นางสาวกรชนก', 'เดชา', 'ม.4', '1', '24', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1395', '1', '5392', 'นางสาวธัมมทินนา', 'สมเชื้อเวียง', 'ม.4', '1', '25', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1396', '1', '6630', 'นางสาวปนัดดา', 'ระหงษ์', 'ม.4', '1', '26', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1397', '1', '6640', 'นางสาววรัชญาน์', 'อวยชัย', 'ม.4', '1', '27', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1398', '1', '7085', 'นางสาวสิรินดา', 'เกตุแก้ว', 'ม.4', '1', '28', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1399', '1', '3164', 'นายเสริมมิตร', 'สุดประเสริฐ', 'ม.4', '2', '1', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1400', '1', '3166', 'นายนัทธพงศ์', 'จันทร์เกษม', 'ม.4', '2', '2', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1401', '1', '3175', 'นายรชต', 'จูคลองตัน', 'ม.4', '2', '3', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1402', '1', '3178', 'นายชวัลวิทย์', 'ชลธี', 'ม.4', '2', '4', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1403', '1', '3579', 'นายอธิภัทร', 'จินดาเย็น', 'ม.4', '2', '5', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1404', '1', '4511', 'นายจตุรวิทย์', 'ชาชำนาญ', 'ม.4', '2', '6', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1405', '1', '4520', 'นายฐกร', 'นุ่มวงศ์', 'ม.4', '2', '7', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1406', '1', '4536', 'นายนรภัทร', 'บุริพันธ์', 'ม.4', '2', '8', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1407', '1', '4540', 'นายพิชญุตม์', 'มุณีพู', 'ม.4', '2', '9', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1408', '1', '4725', 'นายกิตติเชษฐ์', 'จำปาเงิน', 'ม.4', '2', '10', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1409', '1', '5768', 'นายปิตินันท์', 'ศักดิ์ศิลปอุดม', 'ม.4', '2', '11', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1410', '1', '5777', 'นายอธิเชษฐ', 'ปรียงค์', 'ม.4', '2', '12', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1411', '1', '6341', 'นายวินชยากร', 'ชูราศรี', 'ม.4', '2', '13', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1412', '1', '6435', 'นายอนันตกฤษฏิ์', 'ใจแกล้ว', 'ม.4', '2', '14', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1413', '1', '6591', 'นายพนาย', 'ท้วมอ้น', 'ม.4', '2', '15', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1414', '1', '6598', 'นายพัณณพงษ์', 'วงศ์วรปกรณ์', 'ม.4', '2', '16', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1415', '1', '6608', 'นายปัณณทัต', 'คงสุข', 'ม.4', '2', '17', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1416', '1', '6812', 'นายพิทักษ์พงศ์', 'มุสิกะ', 'ม.4', '2', '18', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1417', '1', '3204', 'นางสาวกัลยภัทร', 'นพคุณชัยกิจ', 'ม.4', '2', '19', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1418', '1', '3587', 'นางสาวกันต์กนิษฐ์', 'วงศ์สุกฤต', 'ม.4', '2', '20', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1419', '1', '4981', 'นางสาวภัทรภร', 'สวัสดิ์พุก', 'ม.4', '2', '21', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1420', '1', '5203', 'นางสาวระพีศิลป์', 'รุ้งทองนิรันดร์', 'ม.4', '2', '22', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1421', '1', '6144', 'นางสาวธัชชา', 'แต้ยินดี', 'ม.4', '2', '23', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1422', '1', '6438', 'นางสาวฉัตรสุภางค์', 'ลาภพืชอุดม', 'ม.4', '2', '24', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1423', '1', '6635', 'นางสาวอรปรีญา', 'แซ่ซือ', 'ม.4', '2', '25', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1424', '1', '6645', 'นางสาวถิรดา', 'ดวงไชย', 'ม.4', '2', '26', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1425', '1', '7336', 'นางสาวสุชัญญา', 'แสงเพลิง', 'ม.4', '2', '27', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1426', '1', '7338', 'นางสาวนนทชา', 'นิธิวรภัทร', 'ม.4', '2', '28', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1427', '1', '7340', 'นางสาวกรกมล', 'ภู่ทอง', 'ม.4', '2', '29', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1428', '1', '7418', 'นางสาววิกานดา', 'ศิลป์ลา', 'ม.4', '2', '30', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1429', '1', '7558', 'นางสาวนันทชพร', 'ธรรมธนศิริ', 'ม.4', '2', '31', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1430', '1', '3163', 'นายวชิรวุธ', 'สุขผล', 'ม.4', '3', '1', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1431', '1', '3577', 'นายอภิวัฒน์', 'พุ่มเจริญ', 'ม.4', '3', '2', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1432', '1', '4513', 'นายพิรานันท์', 'วาศเรืองโรจน์', 'ม.4', '3', '3', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1433', '1', '4526', 'นายปุณณัตถ์', 'สงประชา', 'ม.4', '3', '4', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1434', '1', '4529', 'นายณัฐชนน', 'กิจทรัพย์ทวี', 'ม.4', '3', '5', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1435', '1', '4532', 'นายวิชยุตม์', 'จาละ', 'ม.4', '3', '6', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1436', '1', '4534', 'นายปวริศ', 'ปราชญ์ชำนาญ', 'ม.4', '3', '7', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1437', '1', '4535', 'นายอธิษฐ์', 'พิพัฒน์สวัสดิ์', 'ม.4', '3', '8', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1438', '1', '4542', 'นายนิชคุณ', 'ฟูจิวารา', 'ม.4', '3', '9', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1439', '1', '5391', 'นายกันตภณ', 'ศรีตันยู', 'ม.4', '3', '10', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1440', '1', '6142', 'นายภารัณ', 'รอดรักษา', 'ม.4', '3', '11', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1441', '1', '6594', 'นายธิปธนา', 'นิ่มนวน', 'ม.4', '3', '12', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1442', '1', '6601', 'นายคทาธร', 'สุจริตจิตร', 'ม.4', '3', '13', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1443', '1', '6622', 'นายพลกฤต', 'ก.ศรีสุวรรณ', 'ม.4', '3', '14', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1444', '1', '7332', 'นายวริษฐ์', 'คณาคุปต์', 'ม.4', '3', '15', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1445', '1', '7557', 'นายอธิพัชร์', 'ธนศิริพร้อมสุข', 'ม.4', '3', '16', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1446', '1', '3190', 'นางสาวอุษณิษา', 'ห้วยหงษ์ทอง', 'ม.4', '3', '17', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1447', '1', '3212', 'นางสาวพรรณวรท', 'พุ่มปรีชา', 'ม.4', '3', '18', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1448', '1', '4559', 'นางสาวธรรศมณฑน์', 'อังคสุรกร', 'ม.4', '3', '19', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1449', '1', '5202', 'นางสาวปัทมพร', 'สุรินทร์', 'ม.4', '3', '20', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1450', '1', '5771', 'นางสาวเมลดา', 'พงษ์บัณฑิต', 'ม.4', '3', '21', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1451', '1', '6437', 'นางสาวกภิญนัณย์', 'จันทร์บุญเจือ', 'ม.4', '3', '22', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1452', '1', '6632', 'นางสาวณัฐณิชา', 'วิจิตรไพรวัลย์', 'ม.4', '3', '23', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1453', '1', '6634', 'นางสาวมาธวี', 'แก้วเจริญ', 'ม.4', '3', '24', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1454', '1', '6637', 'นางสาวอัฐภิญญา', 'ลิ่มกองลาภ', 'ม.4', '3', '25', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1455', '1', '6638', 'นางสาววรปภา', 'ปิยะพันธุ์', 'ม.4', '3', '26', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1456', '1', '6639', 'นางสาวชญานนท์', 'สงวนนวล', 'ม.4', '3', '27', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1457', '1', '6641', 'นางสาวณัฐภัสสร', 'ลิ้มชาโตอมตะ', 'ม.4', '3', '28', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1458', '1', '7083', 'นางสาวพิมพ์วลัญช์', 'แก้วแววน้อย', 'ม.4', '3', '29', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1459', '1', '7341', 'เด็กหญิงพัชรพร', 'วัฒนาธนเกียรติ', 'ม.4', '3', '30', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1460', '1', '3157', 'นายณัฐวุฒิ', 'มรรคสุทธิกุล', 'ม.4', '4', '1', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1461', '1', '3165', 'นายวัชรพงศ์', 'อยู่ยืนเป็นสุข', 'ม.4', '4', '2', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1462', '1', '3173', 'นายณัฐดน', 'สุขมาก', 'ม.4', '4', '3', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1463', '1', '3176', 'นายตฤณ', 'มณีรัตน์', 'ม.4', '4', '4', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1464', '1', '3711', 'นายชัยวัฒน์', 'เฉิน', 'ม.4', '4', '5', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1465', '1', '4198', 'นายภูวณัฎฐ์', 'ลำลองรัตน์', 'ม.4', '4', '6', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1466', '1', '4518', 'นายธนกฤต', 'กิจจานนท์', 'ม.4', '4', '7', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1467', '1', '5150', 'นายธนพัต', 'รักษาคม', 'ม.4', '4', '8', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1468', '1', '5944', 'นายธัญณัฏฐนันธ', 'พิทักษ์เผ่า', 'ม.4', '4', '9', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1469', '1', '6140', 'นายสมิทธ์', 'นิพัทธ์เจริญวงศ์', 'ม.4', '4', '10', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1470', '1', '6592', 'นายจักรวาล', 'บุณยะมาน', 'ม.4', '4', '11', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1471', '1', '6595', 'นายธีรภพ', 'คูเจริญทรัพย์', 'ม.4', '4', '12', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1472', '1', '6611', 'นายธนกฤต', 'โชติ', 'ม.4', '4', '13', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1473', '1', '6613', 'นายกิตติเมต', 'อยู่ญาติมาก', 'ม.4', '4', '14', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1474', '1', '6621', 'นายพงศภัทร', 'กล่อมสวัสดิ์', 'ม.4', '4', '15', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1475', '1', '6624', 'เด็กชายธนภัทร', 'ฟักประไพ', 'ม.4', '4', '16', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1476', '1', '7333', 'นายศุภวุฒิ', 'ศิริรัตนาวราคุณ', 'ม.4', '4', '17', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1477', '1', '7417', 'นายศาสตรา', 'สงชนะ', 'ม.4', '4', '18', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1478', '1', '3187', 'นางสาวรวิสรา', 'แจ่มแจ้ง', 'ม.4', '4', '19', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1479', '1', '3195', 'นางสาวฐิติรัตน์', 'จองจตุพร', 'ม.4', '4', '20', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1480', '1', '4054', 'นางสาวภัทรวดี', 'ชูเทียน', 'ม.4', '4', '21', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1481', '1', '4056', 'นางสาวณัฏฐณิชา', 'พงษ์พันธ์ปัญญา', 'ม.4', '4', '22', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1482', '1', '4057', 'นางสาวสรัลพร', 'เผ่าพงศ์ษา', 'ม.4', '4', '23', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1483', '1', '4059', 'นางสาววรรณษชล', 'เจริญสุข', 'ม.4', '4', '24', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1484', '1', '4555', 'นางสาวอริยากานต์', 'สุภิเวก', 'ม.4', '4', '25', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1485', '1', '5770', 'นางสาวจุฑาทิพย์', 'แต้วงศ์ชัยพฤกษ์', 'ม.4', '4', '26', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1486', '1', '6293', 'นางสาวบุณยวีร์', 'นิ่มนวล', 'ม.4', '4', '27', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1487', '1', '6644', 'นางสาวพรปวีณ์', 'พีรปรีชาวิทย์', 'ม.4', '4', '28', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1488', '1', '6811', 'นางสาวอชิรญา', 'นวมนิ่ม', 'ม.4', '4', '29', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1489', '1', '7339', 'นางสาวสรัณปภา', 'พฤฒารา', 'ม.4', '4', '30', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1490', '1', '2809', 'นายศรัณย์', 'นิยมญาติ', 'ม.5', '1', '1', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1491', '1', '2812', 'นายฐิติวัสส์', 'เจษฎานันท์', 'ม.5', '1', '2', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1492', '1', '3146', 'นายกฤติน', 'ทองเต็ม', 'ม.5', '1', '3', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1493', '1', '3568', 'นายธนภัค', 'เนตรธุวกุล', 'ม.5', '1', '4', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1494', '1', '4004', 'นายอริยะ', 'เงินวิเศษ', 'ม.5', '1', '5', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1495', '1', '4006', 'นายนนทกร', 'น้อยนารถ', 'ม.5', '1', '6', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1496', '1', '4008', 'นายปกเขต', 'วันชัย', 'ม.5', '1', '7', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1497', '1', '4009', 'นายรวิพล', 'ลักษณประวัติ', 'ม.5', '1', '8', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1498', '1', '4964', 'นายกันต์พงษ์', 'ขจรศิริผล', 'ม.5', '1', '9', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1499', '1', '4969', 'นายปุณณวิช', 'แก้วกระจ่าง', 'ม.5', '1', '10', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1500', '1', '5571', 'นายทชภณ', 'กอบทองสิริโชค', 'ม.5', '1', '11', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1501', '1', '5765', 'นายศศวัฒน์', 'ชัยภูริหิรัญญ์', 'ม.5', '1', '12', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1502', '1', '6365', 'นายเตชิต', 'ไกรสิทธิ์', 'ม.5', '1', '13', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1503', '1', '6370', 'นายพิชญกิตติ์', 'นุชกลาง', 'ม.5', '1', '14', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1504', '1', '6372', 'นายศุภกร', 'ฮั่วเฮง', 'ม.5', '1', '15', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1505', '1', '6373', 'นายปัณณธร', 'แจ่มสว่าง', 'ม.5', '1', '16', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1506', '1', '6377', 'นายเตชินท์', 'พันการุ่ง', 'ม.5', '1', '17', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1507', '1', '6379', 'นายพิรชัช', 'ท้วมจุ้ย', 'ม.5', '1', '18', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1508', '1', '4031', 'นางสาวปุณญดา', 'สุปัญญารักษ์', 'ม.5', '1', '19', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1509', '1', '4043', 'นางสาวสุพิชชากาญจน์', 'สิทธิศุภฤกษ์', 'ม.5', '1', '20', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1510', '1', '4047', 'นางสาวกันตา', 'ยิ้มถนอม', 'ม.5', '1', '21', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1511', '1', '4509', 'นางสาววนัสนันน์', 'มาชมสมบูรณ์', 'ม.5', '1', '22', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1512', '1', '4972', 'นางสาวจินต์จุฑา', 'ด่านขุนทด', 'ม.5', '1', '23', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1513', '1', '6390', 'นางสาวเมธาวินี', 'แก้วเจริญ', 'ม.5', '1', '24', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1514', '1', '6393', 'นางสาวเขมณัฏฐ์', 'เทียมสุวรรณ์', 'ม.5', '1', '25', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1515', '1', '6398', 'นางสาวนันท์นภัส', 'นพวงษ์ศิริ', 'ม.5', '1', '26', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1516', '1', '6414', 'นางสาววีรดา', 'หมื่นศักดิ์สุระ', 'ม.5', '1', '27', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1517', '1', '6416', 'นางสาวกนกกร', 'สันติภาพชัย', 'ม.5', '1', '28', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1518', '1', '6420', 'นางสาววิลาสินี', 'วันดี', 'ม.5', '1', '29', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1519', '1', '7081', 'นางสาวภัททิยา', 'อังศุวัฒนา', 'ม.5', '1', '30', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1520', '1', '2805', 'นายนครินทร์', 'ชุ่มจินดา', 'ม.5', '2', '1', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1521', '1', '2806', 'นายฐาปนพงศ์', 'ชีวัน', 'ม.5', '2', '2', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1522', '1', '2813', 'นายภูมิหิรัญ', 'ปู่จุ้ย', 'ม.5', '2', '3', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1523', '1', '2820', 'นายกฤติมา', 'ศุภอุดมฤกษ์', 'ม.5', '2', '4', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1524', '1', '3569', 'นายภูมินทร์', 'รัตนกุล', 'ม.5', '2', '5', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1525', '1', '4002', 'นายจิราวุฒิ', 'สุขสมัย', 'ม.5', '2', '6', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1526', '1', '4018', 'นายภูวมินทร์', 'อุดสมใจ', 'ม.5', '2', '7', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1527', '1', '4502', 'นายจิตอนันต์', 'จิระเกียรติกุล', 'ม.5', '2', '8', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1528', '1', '6356', 'นายพลวรรธน์', 'สิขิวัฒน์', 'ม.5', '2', '9', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1529', '1', '6362', 'นายศิรวัฒน์', 'ธนะกิจรุ่งเรือง', 'ม.5', '2', '10', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1530', '1', '6364', 'นายกิตติพงษ์', 'แสงจันทร์', 'ม.5', '2', '11', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1531', '1', '6376', 'นายกมลภพ', 'อบเชย', 'ม.5', '2', '12', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1532', '1', '6378', 'นายนฤป', 'แสงทอง', 'ม.5', '2', '13', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1533', '1', '6380', 'นายสัญกร', 'คงแจ่ม', 'ม.5', '2', '14', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1534', '1', '6383', 'นายวชรพร', 'นราแก้ว', 'ม.5', '2', '15', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1535', '1', '2833', 'นางสาวพรปวีณ์', 'เอี่ยมสอาด', 'ม.5', '2', '16', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1536', '1', '2835', 'นางสาวปณาลี', 'ชาญธัญกรรม', 'ม.5', '2', '17', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1537', '1', '2840', 'นางสาวชญาณิศา', 'ชูสุวรรณ', 'ม.5', '2', '18', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1538', '1', '4042', 'นางสาวพัชนีวรรณ', 'พุฒวราธรภักดี', 'ม.5', '2', '19', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1539', '1', '4970', 'นางสาวกัณฑ์ญาญ์สินี', 'จันทร์เทียน', 'ม.5', '2', '20', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1540', '1', '5767', 'นางสาวกรวินทร์', 'แซ่เตียว', 'ม.5', '2', '21', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1541', '1', '6397', 'นางสาวปานตะวัน', 'สายบุ่งคล้า', 'ม.5', '2', '22', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1542', '1', '6401', 'นางสาวกนกกาญจน์', 'สามศรีโพธิ์แก้ว', 'ม.5', '2', '23', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1543', '1', '6405', 'นางสาวพิชญาภา', 'สุระพิณชัย', 'ม.5', '2', '24', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1544', '1', '6407', 'นางสาวสุรัตนา', 'รุ่งสว่าง', 'ม.5', '2', '25', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1545', '1', '6415', 'นางสาวกนกพร', 'สันติภาพชัย', 'ม.5', '2', '26', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1546', '1', '6428', 'นางสาวภัทรสุดา', 'สุภัคสรรค์', 'ม.5', '2', '27', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1547', '1', '6431', 'นางสาวกัญญาพัชร', 'อนุสรธนาวัฒน์', 'ม.5', '2', '28', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1548', '1', '6433', 'นางสาวสิปาง', 'แท่นนิล', 'ม.5', '2', '29', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1549', '1', '6579', 'นางสาวแพรวา', 'ติยะรัตนาชัย', 'ม.5', '2', '30', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1550', '1', '2814', 'นายกัณฑ์อเนก', 'นวานุช', 'ม.5', '3', '1', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1551', '1', '2816', 'นายเลิศสุวัฒน์', 'เทศเนตร์แจ่ม', 'ม.5', '3', '2', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1552', '1', '4007', 'นายสุริยัน', 'ดิรัญเพชร', 'ม.5', '3', '3', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1553', '1', '4182', 'นายรพีพัฒน์', 'พัฒนา', 'ม.5', '3', '4', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1554', '1', '4499', 'นายกิตติกวิน', 'จึงเจริญวงศา', 'ม.5', '3', '5', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1555', '1', '4503', 'นายณัฐ', 'เก้าเอี้ยน', 'ม.5', '3', '6', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1556', '1', '4960', 'นายวรพล', 'ฐิติดำรงชัย', 'ม.5', '3', '7', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1557', '1', '5749', 'นายเมธา', 'แย้มนาม', 'ม.5', '3', '8', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1558', '1', '5756', 'นายศุภกฤษ', 'พวงทอง', 'ม.5', '3', '9', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1559', '1', '6135', 'นายวรัญญู', 'ล้อศิริวงศ์', 'ม.5', '3', '10', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1560', '1', '6351', 'นายณัฐพล', 'น้อยบุตร', 'ม.5', '3', '11', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1561', '1', '6357', 'นายญาณวุฒิ', 'แซ่ลี้', 'ม.5', '3', '12', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1562', '1', '6371', 'นายภูมิรพี', 'เจริญสุข', 'ม.5', '3', '13', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1563', '1', '6375', 'นายธนกฤต', 'พีชยาอังกูร', 'ม.5', '3', '14', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1564', '1', '7079', 'นายฆฤณ', 'รอดหมวน', 'ม.5', '3', '15', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1565', '1', '2847', 'นางสาวศิริพร', 'ไทยสง', 'ม.5', '3', '16', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1566', '1', '3151', 'นางสาวกัญจน์ณัฏฐ์', 'น้อยอุดม', 'ม.5', '3', '17', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1567', '1', '3154', 'นางสาวธวัลรัตน์', 'ทรัพย์ธงทอง', 'ม.5', '3', '18', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1568', '1', '5573', 'นางสาวสุประวีณ์', 'สมบัติวงศ์ขจร', 'ม.5', '3', '19', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1569', '1', '6336', 'นางสาวณัชชา', 'แก้วนวลศรี', 'ม.5', '3', '20', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1570', '1', '6391', 'นางสาวปทิตตา', 'อภิโชควรกร', 'ม.5', '3', '21', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1571', '1', '6392', 'นางสาวอธิชล', 'ใจสงเคราะห์', 'ม.5', '3', '22', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1572', '1', '6408', 'นางสาวธมลวรรณ', 'รอดคลองตัน', 'ม.5', '3', '23', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1573', '1', '6410', 'นางสาวสิริยา', 'สุวรรณรัตน์', 'ม.5', '3', '24', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1574', '1', '6426', 'นางสาวปฏิวรภา', 'เหมือนสิงห์', 'ม.5', '3', '25', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1575', '1', '6429', 'นางสาวศุภิสรา', 'คุ้มพะเนียด', 'ม.5', '3', '26', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1576', '1', '6432', 'นางสาวกมลลักษณ์', 'มณีชัย', 'ม.5', '3', '27', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1577', '1', '6576', 'นางสาวธัญจิรา', 'แจ่มศิริ', 'ม.5', '3', '28', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1578', '1', '7313', 'นางสาวสาริศา', 'คล้ายสุบรรณ', 'ม.5', '3', '29', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1579', '1', '2807', 'นายภคพล', 'จรดล', 'ม.5', '4', '1', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1580', '1', '2822', 'นายชยพล', 'กานดา', 'ม.5', '4', '2', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1581', '1', '4000', 'นายชวิศ', 'เดชะ', 'ม.5', '4', '3', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1582', '1', '4001', 'นายเดชภูมินท์', 'ชิวค้า', 'ม.5', '4', '4', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1583', '1', '4027', 'นายพีรธัช', 'อุ่นไพศาลกุล', 'ม.5', '4', '5', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1584', '1', '4028', 'นายปุณยวีร์', 'เนียมนะรา', 'ม.5', '4', '6', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1585', '1', '4029', 'นายเสกสรร', 'แซ่ตัง', 'ม.5', '4', '7', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1586', '1', '4496', 'นายณัฏฐภาค', 'ไถ้บ้านกวย', 'ม.5', '4', '8', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1587', '1', '4497', 'นายกันตณัฐ', 'จงยิ่งยศ', 'ม.5', '4', '9', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1588', '1', '5385', 'นายนิติภูมิ', 'เลี้ยงรักษา', 'ม.5', '4', '10', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1589', '1', '5750', 'นายสมยศ', 'ต่อแสงเฉลิม', 'ม.5', '4', '11', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1590', '1', '6342', 'นายจิรสิน', 'นพมณีวิจิตร', 'ม.5', '4', '12', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1591', '1', '6344', 'นายอติวัตติ์', 'กริตตานุกูล', 'ม.5', '4', '13', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1592', '1', '6349', 'นายปฐมเดช', 'แซ่ซิ', 'ม.5', '4', '14', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1593', '1', '6355', 'นายปภิณวิช', 'ทองยา', 'ม.5', '4', '15', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1594', '1', '6360', 'นายภัทรพล', 'กิจสวัสดิ์', 'ม.5', '4', '16', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1595', '1', '2831', 'นางสาวอังคณา', 'หุ่นลำภู', 'ม.5', '4', '17', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1596', '1', '2844', 'นางสาวปิยเนตร', 'บุญสม', 'ม.5', '4', '18', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1597', '1', '4037', 'นางสาวสุภาสิริ', 'จอกสมุทร', 'ม.5', '4', '19', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1598', '1', '5751', 'นางสาวณัฐรดา', 'พงษ์สวัสดิ์', 'ม.5', '4', '20', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1599', '1', '5762', 'นางสาวชามาณัฏฐ์', 'อัศวเดชฤทธิ์', 'ม.5', '4', '21', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1600', '1', '5983', 'นางสาวณฐิตา', 'ตัณฑ์เจริญรัตน์', 'ม.5', '4', '22', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1601', '1', '6337', 'นางสาวกมลวรรณ', 'พวงระย้า', 'ม.5', '4', '23', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1602', '1', '6388', 'นางสาวกันต์กนิษฐ์', 'สอดแสงอรุณงาม', 'ม.5', '4', '24', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1603', '1', '6389', 'นางสาวพลอยปภัส', 'วรวัชร์วุฒิไกร', 'ม.5', '4', '25', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1604', '1', '6403', 'นางสาวกตกร', 'ธนาพรทิพา', 'ม.5', '4', '26', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1605', '1', '6409', 'นางสาวณฐมน', 'ผูกพานิช', 'ม.5', '4', '27', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1606', '1', '6412', 'นางสาวชญานิศ', 'กระดังงา', 'ม.5', '4', '28', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1607', '1', '6417', 'นางสาวกนกพรรณ', 'สันติภาพชัย', 'ม.5', '4', '29', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1608', '1', '6419', 'นางสาวกัลยรักษ์', 'ชนะชัยสิทธิ์', 'ม.5', '4', '30', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1609', '1', '7080', 'นางสาวอมลวรรณ', 'คีรีวงค์', 'ม.5', '4', '31', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1610', '1', '2522', 'นายพงศ์พีระ', 'เหลืองไพรินทร์', 'ม.6', '1', '1', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1611', '1', '3136', 'นายปิติภัทร', 'โควศุภมงคล', 'ม.6', '1', '2', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1612', '1', '3522', 'นายชานน', 'พานิช', 'ม.6', '1', '3', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1613', '1', '3530', 'นายวรเมธ', 'ปราชญ์ชำนาญ', 'ม.6', '1', '4', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1614', '1', '4945', 'นายหาญเศรษฐ์', 'ศรีเทพเอี่ยม', 'ม.6', '1', '5', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1615', '1', '4948', 'นายธนภัทร', 'เรืองเทศ', 'ม.6', '1', '6', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1616', '1', '5377', 'นายกฤตพิพัฒน์', 'ลี', 'ม.6', '1', '7', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1617', '1', '6026', 'นายธนบดี', 'แต้ยินดี', 'ม.6', '1', '8', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1618', '1', '6032', 'นายกฤติณัฐ', 'สังวาลย์พานิช', 'ม.6', '1', '9', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1619', '1', '6044', 'นายนฤสรณ์', 'ทองยินดี', 'ม.6', '1', '10', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1620', '1', '6046', 'นายปัณณวัชร์', 'สุรภักดิ์ภิรมย์', 'ม.6', '1', '11', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1621', '1', '6065', 'นายปวรุศ', 'ตรัยมงคลกูล', 'ม.6', '1', '12', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1622', '1', '6071', 'นายสุปรีชา', 'โชคบริบูรณ์', 'ม.6', '1', '13', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1623', '1', '6319', 'นายบุณยสิทธิ์', 'กิตติวัชระชัย', 'ม.6', '1', '14', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1624', '1', '2535', 'นางสาววรรณภัสสรณ์', 'กลิ่นระรวย', 'ม.6', '1', '15', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1625', '1', '2540', 'นางสาวธัญชนก', 'จิตอารี', 'ม.6', '1', '16', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1626', '1', '2548', 'นางสาวธิตยา', 'นิลวัตถา', 'ม.6', '1', '17', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1627', '1', '2553', 'นางสาวนิพิธฐา', 'สุนทรกิจ', 'ม.6', '1', '18', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1628', '1', '3229', 'นางสาวสิรินดา', 'ช่วยสร้าง', 'ม.6', '1', '19', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1629', '1', '3547', 'นางสาวอณัศยา', 'อยู่สวัสดิ์', 'ม.6', '1', '20', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1630', '1', '3553', 'นางสาววรกาญจน์', 'กิจเจา', 'ม.6', '1', '21', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1631', '1', '3561', 'นางสาวชัญญานุช', 'วงศ์สุกฤต', 'ม.6', '1', '22', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1632', '1', '3990', 'นางสาวกษมาภรณ์', 'กริ่งเกษมศรี', 'ม.6', '1', '23', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1633', '1', '3993', 'นางสาวชนัดดา', 'สุดสัตย์', 'ม.6', '1', '24', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1634', '1', '3994', 'นางสาวภณิตา', 'สุริวงษ์', 'ม.6', '1', '25', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1635', '1', '4487', 'นางสาวนันท์นภัส', 'เจ็ดสี', 'ม.6', '1', '26', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1636', '1', '4951', 'นางสาวพิมพ์ชนก', 'ธรรมเวช', 'ม.6', '1', '27', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1637', '1', '4956', 'นางสาวทิพยสุดา', 'ยืนยง', 'ม.6', '1', '28', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1638', '1', '5630', 'นางสาวธัญญ์รัศม์', 'บูรณะธนะสิน', 'ม.6', '1', '29', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1639', '1', '6077', 'นางสาวกวิสรา', 'สีจ๊ะแปง', 'ม.6', '1', '30', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1640', '1', '6084', 'นางสาวนันท์นลิน', 'เสถียรพานิช', 'ม.6', '1', '31', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1641', '1', '6085', 'นางสาวชัญญานุช', 'ลาภพืชอุดม', 'ม.6', '1', '32', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1642', '1', '6092', 'นางสาวขรินทิพย์', 'กนกมงคล', 'ม.6', '1', '33', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1643', '1', '6104', 'นางสาวธีริศรา', 'เลี้ยงอำนวย', 'ม.6', '1', '34', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1644', '1', '6116', 'นางสาวสิรสาร์', 'คงนาน', 'ม.6', '1', '35', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1645', '1', '6128', 'นางสาววรรณรดา', 'เกิดโพธิ์ชา', 'ม.6', '1', '36', 'ฟ้า', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1646', '1', '2499', 'นายสุทธิรักษ์', 'โพธิ์ทองนาค', 'ม.6', '2', '1', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1647', '1', '2507', 'นายธราดล', 'ตัณฑ์ไพบูลย์', 'ม.6', '2', '2', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1648', '1', '2523', 'นายณัฐวัชร์', 'แก้วประเสริฐ', 'ม.6', '2', '3', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1649', '1', '2530', 'นายณัฐพัฒน์', 'งามดอกไม้', 'ม.6', '2', '4', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1650', '1', '2573', 'นายจาตุรนต์', 'โพธิสัตย์', 'ม.6', '2', '5', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1651', '1', '3137', 'นายณัฐวัฒน์', 'มั่นคง', 'ม.6', '2', '6', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1652', '1', '3523', 'นายชนวีร์', 'พานิช', 'ม.6', '2', '7', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1653', '1', '3525', 'นายณภัทร', 'เสรีธรรมกุล', 'ม.6', '2', '8', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1654', '1', '3987', 'นายวงศพัทธ์', 'งามประโคน', 'ม.6', '2', '9', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1655', '1', '3989', 'นายธณกร', 'ระหงษ์', 'ม.6', '2', '10', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1656', '1', '4484', 'นายพัฒน์ทพล', 'สื่อเฉย', 'ม.6', '2', '11', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1657', '1', '5747', 'นายวิสุทธิ์อมร', 'ฤทธิเศรษฐวัฒน์', 'ม.6', '2', '12', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1658', '1', '6022', 'นายรัฐวัสส์', 'วิริยะยุทมา', 'ม.6', '2', '13', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1659', '1', '6023', 'นายบดิศร', 'ฉันทวิลาศ', 'ม.6', '2', '14', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1660', '1', '6025', 'นายนิติธาดา', 'เคารพธรรม', 'ม.6', '2', '15', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1661', '1', '6048', 'นายนพัตธร', 'เทียมทัด', 'ม.6', '2', '16', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1662', '1', '6050', 'นายนันทวัฒน์', 'เอกสินิทธ์กุล', 'ม.6', '2', '17', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1663', '1', '6052', 'นายอินทัช', 'ลิ้มพิพัฒน์', 'ม.6', '2', '18', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1664', '1', '6055', 'นายกิตติทัต', 'สุขเกษม', 'ม.6', '2', '19', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1665', '1', '6058', 'นายวรภัทร', 'คนใหญ่', 'ม.6', '2', '20', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1666', '1', '2539', 'นางสาวธนัญชนก', 'จันทร์เกษม', 'ม.6', '2', '21', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1667', '1', '2544', 'นางสาวจิรชญา', 'เจี๊ยบนา', 'ม.6', '2', '22', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1668', '1', '2547', 'นางสาวจารุกัญญ์', 'คงสกุล', 'ม.6', '2', '23', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1669', '1', '2556', 'นางสาวณัฐนรี', 'ศรีสัจจวาที', 'ม.6', '2', '24', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1670', '1', '2797', 'นางสาวอรรจนา', 'จุ้ยสามพราน', 'ม.6', '2', '25', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1671', '1', '2801', 'นางสาวมนีวรรณ', 'เนียมครุฑ', 'ม.6', '2', '26', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1672', '1', '3556', 'นางสาวลภัสรดา', 'เนตรสว่าง', 'ม.6', '2', '27', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1673', '1', '3998', 'นางสาวชนิกานต์', 'เอี่ยมสำอางค์', 'ม.6', '2', '28', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1674', '1', '5166', 'นางสาวปวริศา', 'ทองเสงี่ยม', 'ม.6', '2', '29', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1675', '1', '6073', 'นางสาวภัควรรณ', 'ธนะจินดานนท์', 'ม.6', '2', '30', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1676', '1', '6075', 'นางสาวกานต์ชนก', 'ปิ่นทองคำ', 'ม.6', '2', '31', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1677', '1', '6082', 'นางสาวณัฏฐธิดา', 'ดวงหิรัญ', 'ม.6', '2', '32', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1678', '1', '6097', 'นางสาวรัชธพัชร์', 'ธนเกียรติธิกุล', 'ม.6', '2', '33', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1679', '1', '6121', 'นางสาวอานันท์ปภา', 'ฟองมูล', 'ม.6', '2', '34', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1680', '1', '6122', 'นางสาวชญาดา', 'กองแก้ว', 'ม.6', '2', '35', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1681', '1', '6132', 'นางสาววริญญา', 'กลั่นวาที', 'ม.6', '2', '37', 'เขียว', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1682', '1', '2526', 'นายปณชัย', 'ตีรถะ', 'ม.6', '3', '1', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1683', '1', '3519', 'นายภูวณัฏฐ์', 'รุจิราธัญวัฒน์', 'ม.6', '3', '2', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1684', '1', '3521', 'นายธนากร', 'เลิศสำราญ', 'ม.6', '3', '3', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1685', '1', '3526', 'นายวุฒิภัทร', 'เขตอุดมชัย', 'ม.6', '3', '4', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1686', '1', '4482', 'นายอภิสิทธิ์', 'ชูคง', 'ม.6', '3', '5', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1687', '1', '5941', 'นายระพีพัฒน์', 'นึกรัมย์', 'ม.6', '3', '6', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1688', '1', '6031', 'นายกฤติเดช', 'ลาวัณย์วิสุทธิ์', 'ม.6', '3', '7', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1689', '1', '6035', 'นายธนกร', 'อุดมผล', 'ม.6', '3', '8', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1690', '1', '6037', 'นายณัฐวัชต์', 'วรวัฒนาชัยนนท์', 'ม.6', '3', '9', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1691', '1', '6038', 'นายจิรพัฒน์', 'ชาวสวนเจริญ', 'ม.6', '3', '10', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1692', '1', '6039', 'นายชวรัชญ์', 'ศิริรักษ์', 'ม.6', '3', '11', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1693', '1', '6043', 'นายโชติวัฒน์', 'ประภาสัย', 'ม.6', '3', '12', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1694', '1', '6049', 'นายเอกณัฐ', 'เขียวพริ้ง', 'ม.6', '3', '13', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1695', '1', '6054', 'นายชินธันย์', 'บุผาสน', 'ม.6', '3', '14', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1696', '1', '6057', 'นายจีรพัฒน์', 'ภิรมย์พุด', 'ม.6', '3', '15', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1697', '1', '6060', 'นายบุณยวีร์', 'เจียมเอกฤทธิ์', 'ม.6', '3', '16', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1698', '1', '6063', 'นายรวิกันต์', 'รุ่งเรือง', 'ม.6', '3', '17', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1699', '1', '6068', 'นายนิพิฐพนธ์', 'แซ่เอี๊ยว', 'ม.6', '3', '18', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1700', '1', '2537', 'นางสาวพรปวีณ์', 'ไหลวารินทร์', 'ม.6', '3', '19', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1701', '1', '2549', 'นางสาวณัฏฐิยา', 'จำปาทอง', 'ม.6', '3', '20', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1702', '1', '2552', 'นางสาวชลธิชา', 'แก้วปราณี', 'ม.6', '3', '21', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1703', '1', '2558', 'นางสาวศุภากร', 'สร้อยพุดตาน', 'ม.6', '3', '22', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1704', '1', '3545', 'นางสาวณัฐธยาน์', 'หงษ์เต็งสกุล', 'ม.6', '3', '23', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1705', '1', '3549', 'นางสาวชนกาญ', 'ลื่นเสือ', 'ม.6', '3', '24', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1706', '1', '3555', 'นางสาวอภิญญา', 'บางวัฒนา', 'ม.6', '3', '25', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1707', '1', '3565', 'นางสาววลดา', 'บุญชู', 'ม.6', '3', '26', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1708', '1', '4492', 'นางสาวประสิตา', 'รุมาคม', 'ม.6', '3', '27', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1709', '1', '6096', 'นางสาวกชพรรณ', 'ศรีชูศิลป์', 'ม.6', '3', '28', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1710', '1', '6102', 'นางสาวณภัทร', 'ศูนย์คุ้ม', 'ม.6', '3', '29', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1711', '1', '6110', 'นางสาวชนัญชิดา', 'ไพบูลย์', 'ม.6', '3', '30', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1712', '1', '6114', 'นางสาวนัทชา', 'พรหมเดช', 'ม.6', '3', '31', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1713', '1', '6123', 'นางสาวสุริวิภา', 'แก้วจินดา', 'ม.6', '3', '32', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1714', '1', '6124', 'นางสาวณัฐชนา', 'ก่อเกษมพร', 'ม.6', '3', '33', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1715', '1', '6125', 'นางสาววริศรา', 'ปิ่นทอง', 'ม.6', '3', '34', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1716', '1', '6130', 'นางสาวณภัทร', 'การุณรัตนกุล', 'ม.6', '3', '35', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1717', '1', '6131', 'นางสาวณภัค', 'พานตะโกสกุล', 'ม.6', '3', '36', 'ชมพู', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1718', '1', '2497', 'นายกิตติกร', 'กิตติเรืองชัย', 'ม.6', '4', '1', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1719', '1', '2503', 'นายภควัต', 'เจริญชาศรี', 'ม.6', '4', '2', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1720', '1', '2504', 'นายอภิชาย', 'ชายทวีป', 'ม.6', '4', '3', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1721', '1', '2508', 'นายจิตตพัฒน์', 'ครองลาภเจริญ', 'ม.6', '4', '4', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1722', '1', '2514', 'นายชวกร', 'พลชัย', 'ม.6', '4', '5', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1723', '1', '2518', 'นายภูริภัทร', 'นพคุณชัยกิจ', 'ม.6', '4', '6', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1724', '1', '3531', 'นายตฤณ', 'ปัทมธีรนัน', 'ม.6', '4', '7', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1725', '1', '3536', 'นายพงศ์กฤต', 'อบเชย', 'ม.6', '4', '8', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1726', '1', '3543', 'นายวัชรวิศว์', 'ชุมรักษา', 'ม.6', '4', '9', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1727', '1', '4194', 'นายปภาวิน', 'วงเวียน', 'ม.6', '4', '10', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1728', '1', '4943', 'นายธนนท์', 'ปัญญากรกิตติคุณ', 'ม.6', '4', '11', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1729', '1', '5148', 'นายอิทธิพัทธ์', 'รักษาคม', 'ม.6', '4', '12', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1730', '1', '5635', 'นายธนาวุฒิ', 'พาวันทา', 'ม.6', '4', '13', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1731', '1', '6020', 'นายกฤติน', 'สงวนสัตย์', 'ม.6', '4', '14', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1732', '1', '6027', 'นายภาคิน', 'อภิรักษ์ขิต', 'ม.6', '4', '15', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1733', '1', '6033', 'นายปกป้อง', 'กายเย็น', 'ม.6', '4', '16', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1734', '1', '6036', 'นายพุฒิธนัสถ์', 'อำภาราม', 'ม.6', '4', '17', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1735', '1', '6056', 'นายฌานพัฒน์', 'พงษ์สระพัง', 'ม.6', '4', '18', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1736', '1', '6059', 'นายธีนกฤษณ์', 'ใจมั่น', 'ม.6', '4', '19', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1737', '1', '6061', 'นายสรวิชญ์', 'นิพัทธ์เจริญวงศ์', 'ม.6', '4', '20', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1738', '1', '6070', 'นายกิตติภพ', 'พุกพบสุข', 'ม.6', '4', '21', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1739', '1', '2559', 'นางสาวกุลฉัตร', 'บุริพันธ์', 'ม.6', '4', '22', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1740', '1', '2560', 'นางสาวอภิญญา', 'โพธิ์คำ', 'ม.6', '4', '23', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1741', '1', '3141', 'นางสาวจรัสรวี', 'พุทธวิถี', 'ม.6', '4', '24', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1742', '1', '3548', 'นางสาวญาณิศา', 'พวงเงินสกุล', 'ม.6', '4', '25', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1743', '1', '3552', 'นางสาวพัชรพร', 'สีดาพาลี', 'ม.6', '4', '26', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1744', '1', '3563', 'นางสาววรัชตรา', 'วรายุกฤตานน', 'ม.6', '4', '27', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1745', '1', '3991', 'นางสาวจิรนันท์', 'ชำนิไกร', 'ม.6', '4', '28', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1746', '1', '3996', 'นางสาวนริศรา', 'ศรีประเสริฐ', 'ม.6', '4', '29', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1747', '1', '4954', 'นางสาวชลนา', 'เดชธนาวรกิตต์', 'ม.6', '4', '30', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1748', '1', '6089', 'นางสาวเตนิลยา', 'นัดสูงวงษ์', 'ม.6', '4', '31', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1749', '1', '6090', 'นางสาวปาณิสรา', 'คงสุข', 'ม.6', '4', '32', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1750', '1', '6103', 'นางสาวฟ้าวิไล', 'นราวัฒนเศรษฐ์', 'ม.6', '4', '33', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1751', '1', '6108', 'นางสาวนันธพร', 'อิทธิรักษ์ชัยกุล', 'ม.6', '4', '34', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1752', '1', '6112', 'นางสาวสุทธาศินี', 'บางวัฒนา', 'ม.6', '4', '35', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1753', '1', '6113', 'นางสาวกันต์กวี', 'ศรีวิรานนท์', 'ม.6', '4', '36', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1754', '1', '6129', 'นางสาวอิษฎา', 'สมสุข', 'ม.6', '4', '37', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1755', '1', '6340', 'นางสาวปภาวี', 'อันอาตม์งาม', 'ม.6', '4', '38', 'ส้ม', '2025-11-03 15:08:48');
INSERT INTO `students` VALUES ('1756', '1', '6889', 'เด็กหญิงกชมน', 'ลีลาคุณารักษ์', 'ม.3', '5', '37', 'ฟ้า', '2025-11-03 15:42:48');

DROP TABLE IF EXISTS `track_heats`;
CREATE TABLE `track_heats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year_id` int(10) unsigned NOT NULL,
  `sport_id` int(10) unsigned NOT NULL,
  `heat_no` int(10) unsigned NOT NULL,
  `lanes_used` tinyint(4) NOT NULL DEFAULT 8,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_track` (`year_id`,`sport_id`,`heat_no`)
) ENGINE=InnoDB AUTO_INCREMENT=220 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `track_lane_assignments`;
CREATE TABLE `track_lane_assignments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `heat_id` int(10) unsigned NOT NULL,
  `lane_no` tinyint(4) NOT NULL,
  `color` enum('เขียว','ฟ้า','ชมพู','ส้ม') NOT NULL,
  `registration_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_heat_lane` (`heat_id`,`lane_no`)
) ENGINE=InnoDB AUTO_INCREMENT=1361 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `track_lane_usage`;
CREATE TABLE `track_lane_usage` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year_id` int(10) unsigned NOT NULL,
  `color` enum('เขียว','ฟ้า','ชมพู','ส้ม') NOT NULL,
  `lane_no` tinyint(4) NOT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usage` (`year_id`,`color`,`lane_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `track_results`;
CREATE TABLE `track_results` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `heat_id` int(10) unsigned NOT NULL,
  `lane_no` tinyint(3) unsigned NOT NULL,
  `time_str` varchar(32) DEFAULT NULL,
  `rank` tinyint(3) unsigned DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_record` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_heat_lane` (`heat_id`,`lane_no`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `role` enum('admin','staff','referee') NOT NULL DEFAULT 'staff',
  `staff_color` enum('ส้ม','เขียว','ชมพู','ฟ้า') DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES ('1', 'admingreen', '$2y$10$K1y8dtG268vB04.deXZ1GuK/2VBBqAeBhsxmp24mZYVaEA5IxbUOC', 'Admin สีเขียว', 'staff', 'เขียว', '1', '2025-10-08 13:33:11');
INSERT INTO `users` VALUES ('2', 'adminorange', '$2y$10$tEWFyhH9Jr9.Ii26Ct0q..E0dKMgDg2q9/lvWkQ32Hx4rRLpAthxm', 'Admin สีส้ม', 'staff', 'ส้ม', '1', '2025-10-08 13:33:30');
INSERT INTO `users` VALUES ('3', 'adminpink', '$2y$10$CSXlqxfFIeE4qUGXSkVCzev.b6XIq5O4LPa74wTs08XXpP9RZ.mQu', 'Admin สีชมพู', 'staff', 'ชมพู', '1', '2025-10-08 13:34:19');
INSERT INTO `users` VALUES ('4', 'adminskyblue', '$2y$10$Pvn47q4OmIPEmAtlvEfNmexDi.7ohzCy6hqJIW7d10iqM4xZ36.mu', 'Admin สีฟ้า', 'staff', 'ฟ้า', '1', '2025-10-08 13:34:33');
INSERT INTO `users` VALUES ('7', 'admin', '$2y$10$HGvwWZXiiVH/XXBVbWXR8e3Vtr6V.hh893Y1/FgkKrpPSm5RQVsfa', 'admin', 'admin', NULL, '1', '2025-10-08 14:26:46');
INSERT INTO `users` VALUES ('10', 't00241', '$2y$10$/j9i1GBtS7DXUVHaX/2BTeTgpnk5RnSHhmBes5Y3QhRoNebs0W4HK', 'นายธนา บุญชู', 'admin', NULL, '1', '2025-11-05 09:26:20');
INSERT INTO `users` VALUES ('11', 't00423', '$2y$10$zVpqXzPyMtkmL9EpK.RxUe/dMm3uGWtYA9RkcuZNULSZ/5DmFf4xe', 'นายขวัญชัย เณรแตง', 'admin', NULL, '1', '2025-11-05 09:26:52');
INSERT INTO `users` VALUES ('12', 't00418', '$2y$10$FH3vNyMlG2mIb3Ly9Z0buu.e6qo4/Y1osZK2dq5qHjME0O7CP8Zru', 'นายสุเมธ พงษ์ภีละ', 'admin', NULL, '1', '2025-11-05 09:27:15');
INSERT INTO `users` VALUES ('13', 't00116', '$2y$10$gOkENRe9WnMFGRz4ccUQEuIlOHvHT9fuEvk3eprIZF3eWhXeFFcyK', 'นายศุภเกียรติ วิริภิรมย์กูล', 'admin', NULL, '1', '2025-11-05 09:27:51');
INSERT INTO `users` VALUES ('14', 't00254', '$2y$10$ImG/478wKWY4WqhLOfnvtO8fUJkaQN4jetlnsOtubfX2PKMizS/JS', 'นายพรพจน์ พฤกษานันท์', 'admin', NULL, '1', '2025-11-05 09:29:30');
INSERT INTO `users` VALUES ('15', 't00466', '$2y$10$DTcdnSr0FXsQpXAMHsMieO1eydjTYbJ9qxbx4AqxE0/Q1Ibv8OosO', 'นายปรมัย สัมเภาว์มาลย์', 'admin', NULL, '1', '2025-12-14 10:12:46');
INSERT INTO `users` VALUES ('16', 'T00407', '$2y$10$LZbJaZx.czBPj3D4MHqmcuPP6wgrpWXGPQskuuK1B4oy2SoDqCBuO', 'นางสาวศิริวรรณ  อมรพงษ์ไพศาล', 'staff', 'เขียว', '1', '2025-12-14 10:12:58');
INSERT INTO `users` VALUES ('17', 'T00359', '$2y$10$Mxvl2LLNnuahE8QHOJQas.N7uuwt/bjqHjSJCRyq5JgCG4Jfyvlj.', 'นางสาวอรัญญา  เดชพันธ์', 'staff', 'เขียว', '1', '2025-12-14 10:12:58');
INSERT INTO `users` VALUES ('18', 'T00101', '$2y$10$WtBI3E0BsrjvmcdX5HOddebbbnVejLKnPVnK0jF7/4WlOdrmWcBbK', 'นางสาวโชตพิพัฒน์  เชาว์รุ่งโรจน์ชัย', 'staff', 'เขียว', '1', '2025-12-14 10:12:58');
INSERT INTO `users` VALUES ('19', 'T00405', '$2y$10$RAZbqDYKOSHkcqGyemgID.ZwVGiV6P4hJ9O4Pqp6Wo8U7Aq1KGLya', 'นางสาวพรธิชา  อิ่มอยู่', 'staff', 'เขียว', '1', '2025-12-14 10:12:58');
INSERT INTO `users` VALUES ('20', 'T00066', '$2y$10$tkml3bajpiCaPyNT95jHx.XQoxVdRUPAFBA61Yf9iUBVWSpqX2sYu', 'นางสาวรัชดา  อินเฉิดฉาย', 'staff', 'เขียว', '1', '2025-12-14 10:12:58');
INSERT INTO `users` VALUES ('21', 'T00470', '$2y$10$Eb/ObsYV.sPc11wqmhsTJek7AQvkJsdsa60cBahl5qIaU.WSt1lCO', 'นางสาวสิริเพ็ญ แก้วแสง', 'staff', 'เขียว', '1', '2025-12-14 10:12:59');
INSERT INTO `users` VALUES ('22', 'T00027', '$2y$10$mPQo9wMrHI9nEnDSIf0qoOs5lHvyfvWHf3tZM2pxSWvZQiux.vjzu', 'นางกรรณิกา  มาเรียน', 'staff', 'เขียว', '1', '2025-12-14 10:12:59');
INSERT INTO `users` VALUES ('23', 'T00346', '$2y$10$rZq683A8PnmpG14sR7uM6.jv/PID9H1EH8Y.jwM6ba4VEIAqsEDyS', 'นางสาวจีรนันท์  บุญช่วย', 'staff', 'เขียว', '1', '2025-12-14 10:12:59');
INSERT INTO `users` VALUES ('24', 'T00333', '$2y$10$298NYjP6k1hl0FqLPEW..OYd8XyRZhamo/JLUzooOZiM2GkQnckAW', 'นางสาวทิพเนตร  มะกรูดอินทร์', 'staff', 'เขียว', '1', '2025-12-14 10:12:59');
INSERT INTO `users` VALUES ('25', 'T00464', '$2y$10$1MgiBEZHCyLqkDP9gez.Tu0cP4/jjQT6Y2r4yergZIEtRetrrImAi', 'นางสาวจินต์จุฑา พฤกษเสม', 'staff', 'เขียว', '1', '2025-12-14 10:12:59');
INSERT INTO `users` VALUES ('26', 'T00472', '$2y$10$kvk/THUfsJhMbuQmIeO2g.KnTPk.MsotqRjc2jdpH2qULUyr981hK', 'นางสาวอำมรัต ทองสุขดี', 'staff', 'เขียว', '1', '2025-12-14 10:12:59');
INSERT INTO `users` VALUES ('27', 'T00468', '$2y$10$gewTcBYKrp/rcvnvow1n6en/qddH/HpKdgFTXRTC27enBcknQvh0y', 'นายศุภชัย ใจเย็น', 'staff', 'เขียว', '1', '2025-12-14 10:12:59');
INSERT INTO `users` VALUES ('28', 'T00150', '$2y$10$RK6RYj2.lejnG97wA17tZ.oqJJKzhfM3tM3fdi69cJYb2n0zlXTzu', 'นางสาวอัญชลี  ศรีแก้วช่วง', 'staff', 'เขียว', '1', '2025-12-14 10:12:59');
INSERT INTO `users` VALUES ('29', 'T00388', '$2y$10$fG3NZ/E6SD2Ck/zVxIZxoO2oloKvB3qnBAPAeblnrGxSSyvqPgf6q', 'นางสาวกาญจนา  ทองคุ้ม', 'staff', 'ส้ม', '1', '2025-12-14 10:27:11');
INSERT INTO `users` VALUES ('30', 'T00278', '$2y$10$lHcQAEQqDIT0LHVJJtjJT.GJaIaRr.5sLDqjJyJekC4DDKqrETQQS', 'นางสาวฐิติมา  เนี๊ยะอั๋น', 'staff', 'ส้ม', '1', '2025-12-14 10:27:11');
INSERT INTO `users` VALUES ('31', 'T00042', '$2y$10$C0onTM/N2.MkehB70NKPpOS4MXx3yj6/daAlHWD7hTaJ.vmOyQaHG', 'นางสาวขนิษฐา ทองสมเพียร', 'staff', 'ส้ม', '1', '2025-12-14 10:27:12');
INSERT INTO `users` VALUES ('32', 'T00452', '$2y$10$J7XJY7Scj4dtWPgID9TFouLxhBjvkq7i.PcFsS9CmYJKRF14UwXHS', 'นางสาวภัทรินี คำฝาด', 'staff', 'ส้ม', '1', '2025-12-14 10:27:12');
INSERT INTO `users` VALUES ('33', 'T00473', '$2y$10$I9cfdKxs86H5nA.1.g58J.nxm59mrgYKhytXcZzZnncViEbfeLVkq', 'นางสาวกฤติมา จันหอม', 'staff', 'ส้ม', '1', '2025-12-14 10:27:12');
INSERT INTO `users` VALUES ('34', 'T00467', '$2y$10$hmnhErqWa5AGw.bw0IlU7OWtGW9/./F6Vvi56xuC8U7ld0XfIj4v.', 'นางสาวดวงดาว ตั้งสุภาพรรณ', 'staff', 'ส้ม', '1', '2025-12-14 10:27:12');
INSERT INTO `users` VALUES ('35', 'T00406', '$2y$10$U61SWDnOPNrNiqK5ZAjaXOlPIzjPPzOHcRttUrlNdjPwaNan7Gdbm', 'นางธัญทิพย์  รัตนารมย์', 'staff', 'ส้ม', '1', '2025-12-14 10:27:13');
INSERT INTO `users` VALUES ('36', 'T00180', '$2y$10$v3VKsN4ILJZA7qtNX9i/N.PcsX1Q6ogR5acVJ35t27ZT.P50Ri9Ei', 'นางสาวดวงพร เจนกิจณรงค์', 'staff', 'ส้ม', '1', '2025-12-14 10:27:13');
INSERT INTO `users` VALUES ('37', 'T00252', '$2y$10$iG7VHV1YhqGMUS8g8FJBt.cOKc5Sz.oxtt4/b4DqylyLHiVmfYk22', 'นางสาวสุนิสา กลัดจ้อย', 'staff', 'ส้ม', '1', '2025-12-14 10:27:13');
INSERT INTO `users` VALUES ('38', 'T00203', '$2y$10$8a4kDqDmISA7y5J5kACAkOiPlOHQhV00GuxU5F002aK/X/ldvJh3.', 'นางสาวบัณฑิตา เอี๊ยะมณี', 'staff', 'ส้ม', '1', '2025-12-14 10:27:13');
INSERT INTO `users` VALUES ('39', 'T00264', '$2y$10$bcw6QlWXYEycmysl9JhIR.DpwdILd8U9Pbpvsd81oAQsI/X/Zk43u', 'นางปริศนา รอดมณี', 'staff', 'ส้ม', '1', '2025-12-14 10:27:13');
INSERT INTO `users` VALUES ('40', 'T00123', '$2y$10$GKxt7L1rPyunW7w89cspfea9h9dBOp6AtNxkUqEYejTqy18OC5a/y', 'นางสาวนฤมล ถิรวัลย์', 'staff', 'ส้ม', '1', '2025-12-14 10:27:14');
INSERT INTO `users` VALUES ('41', 'T00033', '$2y$10$EoR5nGgnwBEbsplJ3g2AHOxaYNV.z8SlyyNEiRvnjddLqUy1snEua', 'นางพัศภัสสรณ์ ปรุงเรือน', 'staff', 'ชมพู', '1', '2025-12-14 10:27:14');
INSERT INTO `users` VALUES ('42', 'T00356', '$2y$10$sdPgZTtKWf6PhfCxZSedluh7uiBL75T1Dqpd4/Vjlcrij79RPlljq', 'นายอรรณพ วงศาโรจน์', 'staff', 'ชมพู', '1', '2025-12-14 10:27:14');
INSERT INTO `users` VALUES ('43', 'T00331', '$2y$10$JVRqBPgeqkVYLM5vIsbZHuBF4cRpGbDvAm3qcP.CzP0EIBaCdtT/6', 'นางสาวสุวนันท์ คำทองดี', 'staff', 'ชมพู', '1', '2025-12-14 10:27:14');
INSERT INTO `users` VALUES ('44', 'T00383', '$2y$10$AAgnZYPt367yqruEaqspHuILJBcsN5/ZBik8rQaV/HvIR1IMeyxl.', 'นางสาวสุพัฐฐิญา บัวเข็มเพชร', 'staff', 'ชมพู', '1', '2025-12-14 10:27:14');
INSERT INTO `users` VALUES ('45', 'T00251', '$2y$10$g6.OQJOabGTYC7RwkZQpl.KBnta.CzePGTfKVKpdwci4ZI5hVsGG2', 'นางสาวพิทยารัตน์ รามัญอุดม', 'staff', 'ชมพู', '1', '2025-12-14 10:27:14');
INSERT INTO `users` VALUES ('46', 'T00461', '$2y$10$LTOKuAG5dKL.cnNbSUd5Fe7GUxUj07oDBKUK2Xb4xXx8IFOFrseIC', 'นางสาววิไลวรรณ ภักดี', 'staff', 'ชมพู', '1', '2025-12-14 10:27:15');
INSERT INTO `users` VALUES ('47', 'T00236', '$2y$10$pSWuWi/rIiaAdfC3rSh.5OAPl0eIkNrih8x0V/2iOCazVrLoHSBey', 'นางสาวคณาลักษณ์ บึงจันทร์', 'staff', 'ชมพู', '1', '2025-12-14 10:27:15');
INSERT INTO `users` VALUES ('48', 'T00474', '$2y$10$QsCEzXdqWBD81v5rUdQt6./YnxiwvWyGoJECYXaA4MC0.5.yS9w3y', 'นางณิสาภัสร์ เซ็งมณี', 'staff', 'ชมพู', '1', '2025-12-14 10:27:15');
INSERT INTO `users` VALUES ('49', 'T00169', '$2y$10$vx83anhaKO5MPGzehvXhmeYwGt6B3rTmnxYSXPE92R2Kijws7ShAO', 'นางสาวพรพิมล มอญถนอม', 'staff', 'ชมพู', '1', '2025-12-14 10:27:15');
INSERT INTO `users` VALUES ('50', 'T00189', '$2y$10$v9X9P9xXAG/GSdGdPqb23ubfa7PERsciPJm83GFzfKbYKO7R.rnFO', 'นางสาวอรชา สว่างชื่น', 'staff', 'ชมพู', '1', '2025-12-14 10:27:15');
INSERT INTO `users` VALUES ('51', 'T00368', '$2y$10$WDogKyWT/EFse9wa1jznjOqyiE1w.ymKMcgQFXGtIeatZQzanbKOu', 'นางสาวทัศนีย์ ผามะณีย์', 'staff', 'ชมพู', '1', '2025-12-14 10:27:16');
INSERT INTO `users` VALUES ('52', 'T00154', '$2y$10$2Uw64mJMIsdxHyKkYMPMdO2C18xoaxXrbM19h32sWg3lrhY9LH80C', 'นางสาวธำรงลักษณ์ แต่งงาม', 'staff', 'ชมพู', '1', '2025-12-14 10:27:16');
INSERT INTO `users` VALUES ('53', 'T00337', '$2y$10$FZLElZmxhWBwH8/wbhPwjeFiJ3iSlLE34hJY4/jo8pDMoTPMomws.', 'นางสาววรรณิภา เทียนไธสง', 'staff', 'ฟ้า', '1', '2025-12-14 10:27:16');
INSERT INTO `users` VALUES ('54', 'T00375', '$2y$10$YBeOnF1jnoZoQxvUcVzGzu/rXvdGxjw8/OJyBapcFgR4wfm5.t3NW', 'นางสาวธนศรี  เกิดเกตุปิ่น', 'staff', 'ฟ้า', '1', '2025-12-14 10:27:16');
INSERT INTO `users` VALUES ('55', 'T00349', '$2y$10$EosNy3lZ8i6PCDzeQtcsrOnY/a4oSig9VLhqQc2S41.GLKiZrIb.O', 'นางสาวณัฐธิดา แก้วคำ', 'staff', 'ฟ้า', '1', '2025-12-14 10:27:16');
INSERT INTO `users` VALUES ('56', 'T00471', '$2y$10$w8ZtU03sp7.Y.ZbH0rI/HuYxmtgDO.AwnwroEqNcDbsZ.EwxeYhTW', 'นางสาวสุจิตรา มูลเหลา', 'staff', 'ฟ้า', '1', '2025-12-14 10:27:17');
INSERT INTO `users` VALUES ('57', 'T00338', '$2y$10$nFHrbMea0pcPvHgd4c5T1uNVnB1e1JhYEQeJcdLjaC8kSBr6/B5fS', 'นางสาวจตุพร  บุญลือ', 'staff', 'ฟ้า', '1', '2025-12-14 10:27:17');
INSERT INTO `users` VALUES ('58', 'T00424', '$2y$10$fjNrL5BdHZufxMCaHpnckOtEDHKY5LgZiMhHpgGyxiMekvb.LUJai', 'นางสาวสุชาดา อู่ตะเภา', 'staff', 'ฟ้า', '1', '2025-12-14 10:27:17');
INSERT INTO `users` VALUES ('59', 'T00286', '$2y$10$Au2u1mQKOx0sA2n.W3FSD.og6rDCB3FtvjVK.ilZLFIwP8E85gFKG', 'นางสาวสุกานดา อิ่มบุญ', 'staff', 'ฟ้า', '1', '2025-12-14 10:27:17');
INSERT INTO `users` VALUES ('60', 'T00479', '$2y$10$Ztt6umU15BZgeHdyYg6XjOdd004btmwNONrMf3osNn4lBMQ/Uu./q', 'นางสาววารุลี อุปถัมย์', 'staff', 'ฟ้า', '1', '2025-12-14 10:27:18');
INSERT INTO `users` VALUES ('61', 'T00057', '$2y$10$1gsCM85pJHpS.yvEtvpu1.4US0KME/xIAW3.2X1ZP5YtM/z.61XBW', 'นางสาวพจวรรณ วรรณฤมล', 'staff', 'ฟ้า', '1', '2025-12-14 10:27:18');
INSERT INTO `users` VALUES ('62', 'T00342', '$2y$10$510t4qMQD.hBDp8Q4.HfxOsPeMg7Y3yY82/6lptazoFsWzckH/VIS', 'นางสาวสิริลักษณ์ แสงประสาร', 'staff', 'ฟ้า', '1', '2025-12-14 10:27:18');
INSERT INTO `users` VALUES ('63', 'T00249', '$2y$10$nDwE9.34VVk2reVdw/OBA.A66YkBBouy4sWMrmriiMIEkb5FUzaZa', 'นางสาวทิพวรรณ จิ๋วแก้ว', 'staff', 'ฟ้า', '1', '2025-12-14 10:27:18');
INSERT INTO `users` VALUES ('64', 'T00350', '$2y$10$WB1egxrvYKtTPMnoLP89JeKmW6m9Bx7OFD.LaFVNKLt63TG2LihtO', 'นางสาวภัทรา ใจบุญ', 'staff', 'ฟ้า', '1', '2025-12-14 10:27:18');

SET FOREIGN_KEY_CHECKS=1;
