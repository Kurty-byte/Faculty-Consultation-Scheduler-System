/*
 Navicat Premium Dump SQL

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 100432 (10.4.32-MariaDB)
 Source Host           : localhost:3306
 Source Schema         : fcss_database

 Target Server Type    : MySQL
 Target Server Version : 100432 (10.4.32-MariaDB)
 File Encoding         : 65001

 Date: 22/05/2025 22:06:24
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for appointment_history
-- ----------------------------
DROP TABLE IF EXISTS `appointment_history`;
CREATE TABLE `appointment_history`  (
  `history_id` int NOT NULL AUTO_INCREMENT,
  `appointment_id` int NOT NULL,
  `status_change` enum('created','approved','rejected','cancelled','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `changed_by_user_id` int NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`) USING BTREE,
  INDEX `appointment_history_appointment_id_fk`(`appointment_id` ASC) USING BTREE,
  INDEX `appointment_history_user_id_fk`(`changed_by_user_id` ASC) USING BTREE,
  CONSTRAINT `appointment_history_appointment_id_fk` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `appointment_history_user_id_fk` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 142 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for appointments
-- ----------------------------
DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments`  (
  `appointment_id` int NOT NULL AUTO_INCREMENT,
  `schedule_id` int NOT NULL,
  `student_id` int NOT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `is_cancelled` tinyint(1) NOT NULL DEFAULT 0,
  `modality` enum('physical','virtual') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `platform` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `location` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `appointed_on` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_on` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`appointment_id`) USING BTREE,
  INDEX `appointments_schedule_id_fk`(`schedule_id` ASC) USING BTREE,
  INDEX `appointments_student_id_fk`(`student_id` ASC) USING BTREE,
  INDEX `appointments_date_idx`(`appointment_date` ASC) USING BTREE,
  INDEX `appointments_updated_idx`(`updated_on` ASC) USING BTREE,
  INDEX `appointments_appointed_idx`(`appointed_on` ASC) USING BTREE,
  CONSTRAINT `appointments_schedule_id_fk` FOREIGN KEY (`schedule_id`) REFERENCES `availability_schedules` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `appointments_student_id_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 28 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for availability_schedules
-- ----------------------------
DROP TABLE IF EXISTS `availability_schedules`;
CREATE TABLE `availability_schedules`  (
  `schedule_id` int NOT NULL AUTO_INCREMENT,
  `faculty_id` int NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_id`) USING BTREE,
  INDEX `availability_schedules_faculty_id_fk`(`faculty_id` ASC) USING BTREE,
  INDEX `availability_day_idx`(`day_of_week` ASC) USING BTREE,
  CONSTRAINT `availability_schedules_faculty_id_fk` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 17 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for departments
-- ----------------------------
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments`  (
  `department_id` int NOT NULL AUTO_INCREMENT,
  `department_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`department_id`) USING BTREE,
  UNIQUE INDEX `unique_department_name`(`department_name` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for faculty
-- ----------------------------
DROP TABLE IF EXISTS `faculty`;
CREATE TABLE `faculty`  (
  `faculty_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `department_id` int NOT NULL,
  `office_email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `office_phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('active','on leave','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`faculty_id`) USING BTREE,
  INDEX `faculty_user_id_fk`(`user_id` ASC) USING BTREE,
  INDEX `faculty_department_id_fk`(`department_id` ASC) USING BTREE,
  CONSTRAINT `faculty_department_id_fk` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `faculty_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for notifications
-- ----------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications`  (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `appointment_id` int NOT NULL,
  `notification_type` enum('appointment_request','appointment_approved','appointment_rejected','appointment_cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_read` tinyint(1) NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`) USING BTREE,
  INDEX `idx_notification_type`(`notification_type` ASC) USING BTREE,
  INDEX `idx_appointment_user`(`appointment_id` ASC, `user_id` ASC) USING BTREE,
  INDEX `notifications_created_idx`(`created_at` ASC) USING BTREE,
  INDEX `notifications_ibfk_1`(`user_id` ASC) USING BTREE,
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 24 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for students
-- ----------------------------
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students`  (
  `student_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `department_id` int NOT NULL,
  `year_level` int NOT NULL,
  `academic_year` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `enrollment_status` enum('regular','irregular','shiftee','returnee') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`student_id`) USING BTREE,
  INDEX `students_user_id_fk`(`user_id` ASC) USING BTREE,
  INDEX `students_department_id_fk`(`department_id` ASC) USING BTREE,
  CONSTRAINT `students_department_id_fk` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `students_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `middle_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `birthdate` date NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','faculty','student') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `profile_picture` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`) USING BTREE,
  UNIQUE INDEX `unique_email`(`email` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 20 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- View structure for appointment_updates_view
-- ----------------------------
DROP VIEW IF EXISTS `appointment_updates_view`;
CREATE ALGORITHM = UNDEFINED SQL SECURITY DEFINER VIEW `appointment_updates_view` AS SELECT 
    a.appointment_id,
    a.student_id,
    a.appointment_date,
    a.start_time,
    a.end_time,
    a.is_approved,
    a.is_cancelled,
    a.appointed_on,
    a.updated_on,
    s.faculty_id,
    uf.first_name as faculty_first_name,
    uf.last_name as faculty_last_name,
    us.first_name as student_first_name,
    us.last_name as student_last_name,
    CASE 
        WHEN a.appointed_on > DATE_SUB(NOW(), INTERVAL 1 MINUTE) THEN 'new_booking'
        WHEN a.is_approved = 1 AND a.updated_on > DATE_SUB(NOW(), INTERVAL 1 MINUTE) THEN 'approved'
        WHEN a.is_cancelled = 1 AND a.updated_on > DATE_SUB(NOW(), INTERVAL 1 MINUTE) THEN 'cancelled'
        ELSE 'updated'
    END as recent_activity_type
FROM appointments a
JOIN availability_schedules s ON a.schedule_id = s.schedule_id
JOIN faculty f ON s.faculty_id = f.faculty_id
JOIN users uf ON f.user_id = uf.user_id
JOIN students st ON a.student_id = st.student_id
JOIN users us ON st.user_id = us.user_id
WHERE a.appointed_on > DATE_SUB(NOW(), INTERVAL 1 HOUR) 
   OR a.updated_on > DATE_SUB(NOW(), INTERVAL 1 HOUR) ;

-- ----------------------------
-- Procedure structure for CleanOldRealtimeData
-- ----------------------------
DROP PROCEDURE IF EXISTS `CleanOldRealtimeData`;
delimiter ;;
CREATE PROCEDURE `CleanOldRealtimeData`()
BEGIN
    -- Clean activities older than 1 day
    DELETE FROM realtime_activities 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY);
    
    -- Clean user activity for inactive users
    DELETE ua FROM user_activity ua
    JOIN users u ON ua.user_id = u.user_id
    WHERE u.is_active = 0;
    
    -- Update statistics
    SELECT 
        (SELECT COUNT(*) FROM realtime_activities) as remaining_activities,
        (SELECT COUNT(*) FROM user_activity) as active_users,
        NOW() as cleaned_at;
END
;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;
