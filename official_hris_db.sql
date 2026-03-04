-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 04, 2026 at 06:48 AM
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
-- Database: `official_hris_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `posted_by` int(11) NOT NULL,
  `date_posted` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `posted_by`, `date_posted`) VALUES
(1, 'Welcome to the New HRIS', 'We have successfully migrated to the new database system.', 1, '2026-03-04 09:38:15');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_disputes`
--

CREATE TABLE `attendance_disputes` (
  `dispute_id` int(11) NOT NULL,
  `cluster_id` int(11) DEFAULT NULL,
  `employee_id` int(11) NOT NULL,
  `dispute_date` date NOT NULL,
  `dispute_type` varchar(100) DEFAULT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Endorsed','Approved','Denied') DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `attendance_id` int(11) NOT NULL,
  `cluster_id` int(11) DEFAULT NULL,
  `employee_id` int(11) NOT NULL,
  `timelog_id` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `attendance_date` date NOT NULL,
  `attendance_status` enum('Present','Absent','Late','Overtime','On Leave') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`attendance_id`, `cluster_id`, `employee_id`, `timelog_id`, `note`, `updated_at`, `attendance_date`, `attendance_status`) VALUES
(1, 1, 5, NULL, NULL, '2026-03-04 01:38:15', '2026-03-04', 'Present'),
(2, 2, 6, NULL, NULL, '2026-03-04 01:38:15', '2026-03-04', 'Late');

-- --------------------------------------------------------

--
-- Table structure for table `break_logs`
--

CREATE TABLE `break_logs` (
  `break_log_id` int(11) NOT NULL,
  `cluster_id` int(11) DEFAULT NULL,
  `time_log_id` int(11) NOT NULL,
  `break_start` datetime NOT NULL,
  `break_end` datetime DEFAULT NULL,
  `total_break_hour` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clusters`
--

CREATE TABLE `clusters` (
  `cluster_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','active','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clusters`
--

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
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cluster_members`
--

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
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `position` varchar(50) DEFAULT NULL,
  `cluster_id` int(11) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `employment_status` varchar(20) DEFAULT NULL,
  `employee_type` varchar(30) DEFAULT NULL,
  `date_hired` date NOT NULL,
  PRIMARY KEY (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `user_id`, `first_name`, `middle_name`, `last_name`, `address`, `birthdate`, `email`, `position`, `cluster_id`, `contact_number`, `employment_status`, `employee_type`, `date_hired`) VALUES
(1, 1, 'John', NULL, 'Super', NULL, NULL, 'super@hris.com', 'System Owner', NULL, NULL, NULL, 'Permanent', '2023-01-01'),
(2, 2, 'Jane', NULL, 'Admin', NULL, NULL, 'admin@hris.com', 'HR Manager', NULL, NULL, NULL, 'Permanent', '2023-02-15'),
(3, 3, 'Robert', NULL, 'Coach', NULL, NULL, 'coach1@hris.com', 'Team Lead', 1, NULL, NULL, 'Permanent', '2023-03-10'),
(4, 4, 'Sarah', NULL, 'Manager', NULL, NULL, 'coach2@hris.com', 'Operations Manager', 2, NULL, NULL, 'Permanent', '2023-04-05'),
(5, 5, 'Alice', NULL, 'Smith', NULL, NULL, 'emp1@hris.com', 'Developer', 1, NULL, NULL, 'Regular', '2024-01-10'),
(6, 6, 'Bob', NULL, 'Jones', NULL, NULL, 'emp2@hris.com', 'Designer', 2, NULL, NULL, 'Regular', '2024-01-12'),
(7, 7, 'Charlie', NULL, 'Brown', NULL, NULL, 'emp3@hris.com', 'Support', 2, NULL, NULL, 'Probationary', '2024-02-01'),
(8, 8, 'David', NULL, 'Wilson', NULL, NULL, 'emp4@hris.com', 'Developer', 1, NULL, NULL, 'Regular', '2024-02-15'),
(9, 9, 'Eve', NULL, 'Davis', NULL, NULL, 'emp5@hris.com', 'QA Engineer', 1, NULL, NULL, 'Regular', '2024-03-01');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `leave_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `agreement_1` tinyint(1) DEFAULT 0,
  `agreement_2` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`leave_id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`, `reviewed_by`, `approved_by`, `agreement_1`, `agreement_2`, `created_at`, `remarks`) VALUES
(1, 7, 'Sick Leave', '2026-03-05', '2026-03-06', 'Fever', 'Pending', NULL, NULL, 0, 0, '2026-03-04 01:38:15', NULL),
(2, 5, 'Vacation Leave', '2026-03-04', '2026-03-07', 'I wanna be alone for a while', 'Pending', NULL, NULL, 1, 1, '2026-03-04 01:46:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `overtime_requests`
--

CREATE TABLE `overtime_requests` (
  `ot_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `ot_type` enum('Regular Overtime','Duty on Rest Day','Duty on Rest Day OT') NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `purpose` text NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `agreement_1` tinyint(1) NOT NULL DEFAULT 0,
  `agreement_2` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Pending','Endorsed','Approved','Denied') DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `overtime_requests`
--

INSERT INTO `overtime_requests` (`ot_id`, `employee_id`, `ot_type`, `start_time`, `end_time`, `purpose`, `approved_by`, `agreement_1`, `agreement_2`, `status`, `created_at`, `remarks`) VALUES
(1, 5, 'Regular Overtime', '2026-03-04 17:00:00', '2026-03-04 19:00:00', 'Deploying updates', NULL, 0, 0, 'Pending', '2026-03-04 09:38:15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_description` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `role_description`) VALUES
(1, 'Employee', 'Standard staff access'),
(2, 'Coach', 'Team management access'),
(3, 'Admin', 'HR and attendance management'),
(4, 'Super Admin', 'Full system configuration');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `cluster_id` int(11) DEFAULT NULL,
  `employee_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
  `shift_type` enum('Morning','Mid','Night') DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `work_setup` enum('Onsite','WFH','Hybrid') DEFAULT NULL,
  `breaksched_start` datetime DEFAULT NULL,
  `breaksched_end` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

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
  `time_log_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `attendance_id` int(11) NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `break_start` datetime DEFAULT NULL,
  `break_end` datetime DEFAULT NULL,
  `total_hours` double(5,2) DEFAULT NULL,
  `log_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_logs`
--

INSERT INTO `time_logs` (`time_log_id`, `employee_id`, `user_id`, `attendance_id`, `time_in`, `time_out`, `break_start`, `break_end`, `total_hours`, `log_date`) VALUES
(1, 5, 5, 1, '2026-03-04 07:55:00', NULL, NULL, NULL, NULL, '2026-03-04'),
(2, 6, 6, 2, '2026-03-04 09:15:00', NULL, NULL, NULL, NULL, '2026-03-04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `fk_ann_users` (`posted_by`);

--
-- Indexes for table `attendance_disputes`
--
ALTER TABLE `attendance_disputes`
  ADD PRIMARY KEY (`dispute_id`),
  ADD KEY `fk_ad_cluster` (`cluster_id`),
  ADD KEY `fk_ad_emp` (`employee_id`);

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_employee_date` (`employee_id`,`attendance_date`),
  ADD KEY `fk_att_cluster` (`cluster_id`),
  ADD KEY `fk_att_emp` (`employee_id`);

--
-- Indexes for table `break_logs`
--
ALTER TABLE `break_logs`
  ADD PRIMARY KEY (`break_log_id`),
  ADD KEY `fk_bl_cluster` (`cluster_id`),
  ADD KEY `fk_bl_tl` (`time_log_id`);

--
-- Indexes for table `clusters`
--
ALTER TABLE `clusters`
  ADD PRIMARY KEY (`cluster_id`),
  ADD KEY `fk_clusters_users` (`user_id`);

--
-- Indexes for table `cluster_members`
--
ALTER TABLE `cluster_members`
  ADD PRIMARY KEY (`cluster_id`,`employee_id`),
  ADD KEY `fk_cm_emp` (`employee_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD KEY `fk_emp_users` (`user_id`),
  ADD KEY `fk_emp_cluster` (`cluster_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `fk_lr_emp` (`employee_id`),
  ADD KEY `fk_lr_rev` (`reviewed_by`),
  ADD KEY `fk_lr_app` (`approved_by`);

--
-- Indexes for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD PRIMARY KEY (`ot_id`),
  ADD KEY `fk_ot_emp` (`employee_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `fk_sched_cluster` (`cluster_id`),
  ADD KEY `fk_sched_emp` (`employee_id`);

--
-- Indexes for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD PRIMARY KEY (`time_log_id`),
  ADD KEY `fk_tl_emp` (`employee_id`),
  ADD KEY `fk_tl_user` (`user_id`),
  ADD KEY `fk_tl_att` (`attendance_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_roles` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance_disputes`
--
ALTER TABLE `attendance_disputes`
  MODIFY `dispute_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `break_logs`
--
ALTER TABLE `break_logs`
  MODIFY `break_log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clusters`
--
ALTER TABLE `clusters`
  MODIFY `cluster_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  MODIFY `ot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `time_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_ann_users` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_disputes`
--
ALTER TABLE `attendance_disputes`
  ADD CONSTRAINT `fk_ad_cluster` FOREIGN KEY (`cluster_id`) REFERENCES `clusters` (`cluster_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ad_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `fk_att_cluster` FOREIGN KEY (`cluster_id`) REFERENCES `clusters` (`cluster_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_att_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `break_logs`
--
ALTER TABLE `break_logs`
  ADD CONSTRAINT `fk_bl_cluster` FOREIGN KEY (`cluster_id`) REFERENCES `clusters` (`cluster_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bl_tl` FOREIGN KEY (`time_log_id`) REFERENCES `time_logs` (`time_log_id`) ON DELETE CASCADE;

--
-- Constraints for table `clusters`
--
ALTER TABLE `clusters`
  ADD CONSTRAINT `fk_clusters_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `cluster_members`
--
ALTER TABLE `cluster_members`
  ADD CONSTRAINT `fk_cm_cluster` FOREIGN KEY (`cluster_id`) REFERENCES `clusters` (`cluster_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cm_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_emp_cluster` FOREIGN KEY (`cluster_id`) REFERENCES `clusters` (`cluster_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_emp_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `fk_lr_app` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_lr_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lr_rev` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD CONSTRAINT `fk_ot_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `fk_sched_cluster` FOREIGN KEY (`cluster_id`) REFERENCES `clusters` (`cluster_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sched_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD CONSTRAINT `fk_tl_att` FOREIGN KEY (`attendance_id`) REFERENCES `attendance_logs` (`attendance_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tl_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
