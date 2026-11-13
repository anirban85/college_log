-- EduTrack Pro — Faculty College Log & Attendance Tracker Database Schema
-- Compatible with MySQL 5.7+ / MariaDB 10.3+ (XAMPP Default)

CREATE DATABASE IF NOT EXISTS `college_log_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `college_log_db`;

-- 1. Users Table (Authentication & Faculty Profiles)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NULL,
    `department` VARCHAR(200) NOT NULL DEFAULT 'Dept. of Physics, The Heritage College',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Daily Campus Timings (Arrival & Departure log per date)
CREATE TABLE IF NOT EXISTS `daily_timings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `log_date` DATE NOT NULL,
    `arrival_time` VARCHAR(10) NOT NULL DEFAULT '09:15',
    `departure_time` VARCHAR(10) NOT NULL DEFAULT '16:45',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_date` (`user_id`, `log_date`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Activity Logs Table (Class Sessions, Lab Practicals, Exam Duties)
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `log_code` VARCHAR(50) NOT NULL,
    `user_id` INT NOT NULL,
    `log_date` DATE NOT NULL,
    `arrival_time` VARCHAR(10) NOT NULL DEFAULT '09:15',
    `departure_time` VARCHAR(10) NOT NULL DEFAULT '16:45',
    `activity_type` VARCHAR(50) NOT NULL,
    `semester` VARCHAR(20) NOT NULL,
    `slot_start` VARCHAR(10) NOT NULL,
    `slot_end` VARCHAR(10) NOT NULL,
    `subject_or_exam` VARCHAR(255) NOT NULL,
    `room_or_hall` VARCHAR(100) NOT NULL,
    `topic_or_duty` TEXT NULL,
    `remarks` TEXT NULL,
    `student_count` INT NOT NULL DEFAULT 0,
    `present_count` INT NOT NULL DEFAULT 0,
    `absent_count` INT NOT NULL DEFAULT 0,
    `attendance_json` LONGTEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_date` (`user_id`, `log_date`),
    INDEX `idx_user_sem` (`user_id`, `semester`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Semester Rosters Table (Class students list per semester)
CREATE TABLE IF NOT EXISTS `semester_rosters` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `semester` VARCHAR(20) NOT NULL,
    `roll_no` VARCHAR(50) NOT NULL,
    `student_name` VARCHAR(150) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_roster` (`user_id`, `semester`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

