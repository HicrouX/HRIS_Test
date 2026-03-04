-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 04, 2026 at 07:49 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `system_hris_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `target` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `date_posted` datetime DEFAULT NULL,
  PRIMARY KEY (`announcement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `posted_by`, `date_posted`) VALUES
(1, 'Welcome to the New HRIS', 'We have successfully migrated to the new database system.', 1, '2026-03-04 09:38:15');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_disputes`
--

CREATE TABLE `attendance_disputes` (
  `dispute_id` int(11) NOT NULL AUTO_INCREMENT,
  `cluster_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `dispute_date` date DEFAULT NULL,
  `dispute_type` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Endorsed','Approved','Denied') DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`dispute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `cluster_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `timelog_id` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `attendance_date` date DEFAULT NULL,
  `attendance_status` enum('Present','Absent','Late','Overtime','On Leave') DEFAULT NULL,
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `unique_employee_date` (`employee_id`,`attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `attendance_logs` (`attendance_id`, `cluster_id`, `employee_id`, `timelog_id`, `note`, `updated_at`, `attendance_date`, `attendance_status`) VALUES
(1, 1, 5, NULL, NULL, '2026-03-04 01:38:15', '2026-03-04', 'Present'),
(2, 2, 6, NULL, NULL, '2026-03-04 01:38:15', '2026-03-04', 'Late');

-- --------------------------------------------------------

--
-- Table structure for table `break_logs`
--

CREATE TABLE `break_logs` (
  `break_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `time_log_id` int(11) DEFAULT NULL,
  `cluster_id` int(11) DEFAULT NULL,
  `break_start` datetime DEFAULT NULL,
  `break_end` datetime DEFAULT NULL,
  `total_break_hour` double(11,2) DEFAULT NULL,
  PRIMARY KEY (`break_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clusters`
--

CREATE TABLE `clusters` (
  `cluster_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('pending','active','rejected') DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`cluster_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `clusters` (`cluster_id`, `name`, `description`, `user_id`, `status`, `rejection_reason`, `created_at`) VALUES
(1, 'Tech Cluster A', 'Software Development Team', 3, 'active', NULL, '2026-03-04 01:38:15'),
(2, 'Design Cluster B', 'Creative and UI/UX Team', 4, 'active', NULL, '2026-03-04 01:38:15');

-- --------------------------------------------------------

--
-- Table structure for table `cluster_members`
--

CREATE TABLE `cluster_members` (
  `cluster_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`cluster_id`,`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cluster_members` (`cluster_id`, `employee_id`, `assigned_at`) VALUES
(1, 5, '2026-03-04 01:38:15'),
(1, 8, '2026-03-04 01:38:15'),
(1, 9, '2026-03-04 01:38:15'),
(2, 6, '2026-03-04 01:38:15'),
(2, 7, '2026-03-04 01:38:15');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `permission_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `personal_email` varchar(100) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `account` varchar(100) DEFAULT NULL,
  `cluster_id` int(11) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `employment_status` varchar(20) DEFAULT NULL,
  `employee_type` varchar(30) DEFAULT NULL,
  `date_hired` date DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `employees` (`employee_id`, `user_id`, `first_name`, `last_name`, `email`, `position`, `cluster_id`, `employee_type`, `date_hired`) VALUES
(1, 1, 'John', 'Super', 'super@hris.com', 'System Owner', NULL, 'Permanent', '2023-01-01'),
(2, 2, 'Jane', 'Admin', 'admin@hris.com', 'HR Manager', NULL, 'Permanent', '2023-02-15'),
(3, 3, 'Robert', 'Coach', 'coach1@hris.com', 'Team Lead', 1, 'Permanent', '2023-03-10'),
(4, 4, 'Sarah', 'Manager', 'coach2@hris.com', 'Operations Manager', 2, 'Permanent', '2023-04-05'),
(5, 5, 'Alice', 'Smith', 'emp1@hris.com', 'Developer', 1, 'Regular', '2024-01-10'),
(6, 6, 'Bob', 'Jones', 'emp2@hris.com', 'Designer', 2, 'Regular', '2024-01-12'),
(7, 7, 'Charlie', 'Brown', 'emp3@hris.com', 'Support', 2, 'Probationary', '2024-02-01'),
(8, 8, 'David', 'Wilson', 'emp4@hris.com', 'Developer', 1, 'Regular', '2024-02-15'),
(9, 9, 'Eve', 'Davis', 'emp5@hris.com', 'QA Engineer', 1, 'Regular', '2024-03-01');

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `holiday_id` int(11) NOT NULL AUTO_INCREMENT,
  `holiday_name` varchar(50) DEFAULT NULL,
  `holiday_date` date DEFAULT NULL,
  PRIMARY KEY (`holiday_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `leave_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `leave_type` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `agreement_1` tinyint(1) DEFAULT NULL,
  `agreement_2` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`leave_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `leave_requests` (`leave_id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`, `reviewed_by`, `approved_by`, `agreement_1`, `agreement_2`, `created_at`, `remarks`) VALUES
(1, 7, 'Sick Leave', '2026-03-05', '2026-03-06', 'Fever', 'Pending', NULL, NULL, 0, 0, '2026-03-04 01:38:15', NULL),
(2, 5, 'Vacation Leave', '2026-03-04', '2026-03-07', 'I wanna be alone for a while', 'Pending', NULL, NULL, 1, 1, '2026-03-04 01:46:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `overtime_requests`
--

CREATE TABLE `overtime_requests` (
  `ot_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `ot_type` enum('Regular Overtime','Duty on Rest Day','Duty on Rest Day OT') DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `agreement_1` tinyint(1) DEFAULT NULL,
  `agreement_2` tinyint(1) DEFAULT NULL,
  `status` enum('Pending','Endorsed','Approved','Denied') DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`ot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `overtime_requests` (`ot_id`, `employee_id`, `ot_type`, `start_time`, `end_time`, `purpose`, `approved_by`, `agreement_1`, `agreement_2`, `status`, `created_at`, `remarks`) VALUES
(1, 5, 'Regular Overtime', '2026-03-04 17:00:00', '2026-03-04 19:00:00', 'Deploying updates', NULL, 0, 0, 'Pending', '2026-03-04 09:38:15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permission`
--

CREATE TABLE `permission` (
  `permission_id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) DEFAULT NULL,
  `role_description` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `roles` (`role_id`, `role_name`, `role_description`) VALUES
(1, 'Employee', 'Standard staff access'),
(2, 'Coach', 'Team management access'),
(3, 'Admin', 'HR and attendance management'),
(4, 'Super Admin', 'Full system configuration');

-- --------------------------------------------------------

--
-- Table structure for table `role_permission`
--

CREATE TABLE `role_permission` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `cluster_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
  `shift_type` enum('Morning','Mid','Night') DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `work_setup` enum('Onsite','WFH','Hybrid') DEFAULT NULL,
  `breaksched_start` datetime DEFAULT NULL,
  `breaksched_end` datetime DEFAULT NULL,
  PRIMARY KEY (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `schedules` (`schedule_id`, `cluster_id`, `employee_id`, `day_of_week`, `shift_type`, `start_time`, `end_time`, `work_setup`, `breaksched_start`, `breaksched_end`) VALUES
(1, 1, 5, 'Monday', NULL, '08:00:00', '17:00:00', 'Onsite', NULL, NULL),
(2, 1, 5, 'Tuesday', NULL, '08:00:00', '17:00:00', 'Onsite', NULL, NULL),
(3, 1, 5, 'Wednesday', NULL, '08:00:00', '17:00:00', 'Onsite', NULL, NULL),
(4, 1, 5, 'Thursday', NULL, '08:00:00', '17:00:00', 'Onsite', NULL, NULL),
(5, 1, 5, 'Friday', NULL, '08:00:00', '17:00:00', 'Onsite', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `time_logs`
--

CREATE TABLE `time_logs` (
  `time_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `attendance_id` int(11) DEFAULT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `break_start` datetime DEFAULT NULL,
  `break_end` datetime DEFAULT NULL,
  `total_hours` double(5,2) DEFAULT NULL,
  `log_date` date DEFAULT NULL,
  `tag` enum('On Time','Late','Absent','Break Time','Lunch Time') DEFAULT NULL,
  PRIMARY KEY (`time_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `time_logs` (`time_log_id`, `employee_id`, `user_id`, `attendance_id`, `time_in`, `time_out`, `break_start`, `break_end`, `total_hours`, `log_date`) VALUES
(1, 5, 5, 1, '2026-03-04 07:55:00', NULL, NULL, NULL, NULL, '2026-03-04'),
(2, 6, 6, 2, '2026-03-04 09:15:00', NULL, NULL, NULL, NULL, '2026-03-04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`user_id`, `email`, `password`, `role_id`, `created_at`) VALUES
(1, 'super@hris.com', 'pass123', 4, '2026-03-04 09:38:15'),
(2, 'admin@hris.com', 'pass123', 3, '2026-03-04 09:38:15'),
(3, 'coach1@hris.com', 'pass123', 2, '2026-03-04 09:38:15'),
(4, 'coach2@hris.com', 'pass123', 2, '2026-03-04 09:38:15'),
(5, 'emp1@hris.com', 'pass123', 1, '2026-03-04 09:38:15'),
(6, 'emp2@hris.com', 'pass123', 1, '2026-03-04 09:38:15'),
(7, 'emp3@hris.com', 'pass123', 1, '2026-03-04 09:38:15'),
(8, 'emp4@hris.com', 'pass123', 1, '2026-03-04 09:38:15'),
(9, 'emp5@hris.com', 'pass123', 1, '2026-03-04 09:38:15');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `permission_id` int(11) DEFAULT NULL,
  `is_allowed` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Constraints for dumped tables
--

ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`);

ALTER TABLE `attendance_disputes`
  ADD CONSTRAINT `attendance_disputes_ibfk_1` FOREIGN KEY (`cluster_id`) REFERENCES `clusters` (`cluster_id`),
  ADD CONSTRAINT `attendance_disputes_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `attendance_logs_ibfk_1` FOREIGN KEY (`cluster_id`) REFERENCES `clusters` (`cluster_id`),
  ADD CONSTRAINT `attendance_logs_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

ALTER TABLE `break_logs`
  ADD CONSTRAINT `break_logs_ibfk_1` FOREIGN KEY (`time_log_id`) REFERENCES `time_logs` (`time_log_id`),
  ADD CONSTRAINT `break_logs_ibfk_2` FOREIGN KEY (`cluster_id`) REFERENCES `clusters` (`cluster_id`);

ALTER TABLE `clusters`
  ADD CONSTRAINT `clusters_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

ALTER TABLE `cluster_members`
  ADD CONSTRAINT `cluster_members_ibfk_1` FOREIGN KEY (`cluster_id`) REFERENCES `clusters` (`cluster_id`),
  ADD CONSTRAINT `cluster_members_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permission` (`permission_id`);

ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `leave_requests_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`);

ALTER TABLE `overtime_requests`
  ADD CONSTRAINT `overtime_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `overtime_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`);

ALTER TABLE `role_permission`
  ADD CONSTRAINT `role_permission_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`),
  ADD CONSTRAINT `role_permission_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permission` (`permission_id`);

ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`cluster_id`) REFERENCES `clusters` (`cluster_id`),
  ADD CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

ALTER TABLE `time_logs`
  ADD CONSTRAINT `time_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `time_logs_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `user_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permission` (`permission_id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
