-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 23, 2026 at 01:31 PM
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
-- Database: `hris_db`
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

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `attendance_status` enum('Present','Absent','Late','Overtime','On Leave','Undertime','Duty on Rest Day','Tardy') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `employee_id`, `attendance_date`, `attendance_status`) VALUES
(1, 101, '2026-01-30', 'On Leave'),
(2, 101, '2026-01-31', 'On Leave'),
(3, 101, '2026-02-01', 'On Leave'),
(4, 101, '2026-01-26', 'Present'),
(5, 101, '2026-01-27', 'Late'),
(6, 101, '2026-01-28', 'On Leave'),
(7, 101, '2026-01-29', 'Present'),
(8, 101, '2026-01-31', 'On Leave'),
(9, 101, '2026-02-01', 'On Leave'),
(10, 101, '2026-02-02', 'On Leave'),
(11, 101, '2026-02-03', 'Present'),
(12, 102, '2026-01-26', 'Present'),
(13, 102, '2026-01-27', 'Present'),
(14, 102, '2026-01-28', 'Present'),
(15, 102, '2026-01-29', 'On Leave'),
(16, 102, '2026-01-30', 'Present'),
(17, 101, '2026-01-26', 'Present'),
(18, 101, '2026-01-27', 'Present'),
(19, 101, '2026-01-28', 'Present'),
(20, 101, '2026-01-29', 'Present'),
(21, 101, '2026-01-30', 'On Leave'),
(22, 101, '2026-02-05', 'On Leave'),
(23, 101, '2026-02-06', 'On Leave'),
(24, 101, '2026-02-07', 'On Leave'),
(25, 101, '2026-02-05', 'On Leave'),
(26, 101, '2026-02-06', 'On Leave'),
(27, 101, '2026-02-07', 'On Leave'),
(28, 102, '2026-01-05', 'Present'),
(29, 102, '2026-01-06', 'Present'),
(30, 102, '2026-01-07', 'Late'),
(31, 102, '2026-01-08', 'Present'),
(32, 102, '2026-01-09', 'Overtime'),
(33, 102, '2026-01-12', 'Present'),
(34, 102, '2026-01-13', 'Present'),
(35, 102, '2026-01-14', 'Undertime'),
(36, 102, '2026-01-15', 'Present'),
(37, 102, '2026-01-16', 'Present'),
(38, 102, '2026-02-02', 'Present'),
(39, 102, '2026-02-03', 'Late'),
(40, 102, '2026-02-04', 'Present'),
(41, 102, '2026-02-05', 'Overtime'),
(42, 102, '2026-02-06', 'Present'),
(43, 103, '2026-01-05', 'Present'),
(44, 103, '2026-01-06', 'Present'),
(45, 103, '2026-01-07', 'Present'),
(46, 103, '2026-01-08', 'Late'),
(47, 103, '2026-01-09', 'Undertime'),
(48, 103, '2026-01-12', 'Present'),
(49, 103, '2026-01-13', 'Present'),
(50, 103, '2026-01-14', 'Overtime'),
(51, 103, '2026-01-15', 'Present'),
(52, 103, '2026-01-16', 'Present'),
(53, 103, '2026-02-02', 'Present'),
(54, 103, '2026-02-03', 'Present'),
(55, 103, '2026-02-04', 'Overtime'),
(56, 103, '2026-02-05', 'Late'),
(57, 103, '2026-02-06', 'Present'),
(58, 101, '2026-02-05', 'On Leave'),
(59, 101, '2026-02-06', 'On Leave'),
(60, 101, '2026-02-07', 'On Leave'),
(61, 101, '2026-02-05', 'On Leave'),
(62, 101, '2026-02-06', 'On Leave'),
(63, 101, '2026-02-07', 'On Leave'),
(64, 101, '2026-01-30', 'On Leave'),
(65, 101, '2026-01-31', 'On Leave'),
(66, 101, '2026-02-02', 'On Leave'),
(67, 101, '2026-02-03', 'On Leave'),
(68, 101, '2026-02-04', 'On Leave'),
(69, 101, '2026-02-05', 'On Leave'),
(70, 101, '2026-02-06', 'On Leave'),
(71, 101, '2026-02-07', 'On Leave'),
(72, 101, '2026-02-08', 'On Leave'),
(73, 101, '2026-02-09', 'On Leave'),
(74, 101, '2026-02-10', 'On Leave'),
(75, 101, '2026-02-11', 'On Leave'),
(76, 101, '2026-02-12', 'On Leave'),
(77, 101, '2026-02-13', 'On Leave'),
(78, 101, '2026-02-14', 'On Leave'),
(79, 101, '2026-02-15', 'On Leave'),
(80, 101, '2026-02-16', 'On Leave'),
(81, 101, '2026-02-17', 'On Leave'),
(82, 101, '2026-02-18', 'On Leave'),
(83, 101, '2026-02-19', 'On Leave'),
(84, 101, '2026-02-20', 'On Leave'),
(85, 101, '2026-02-21', 'On Leave'),
(86, 101, '2026-02-22', 'On Leave'),
(87, 101, '2026-02-23', 'On Leave'),
(88, 101, '2026-02-24', 'On Leave'),
(89, 101, '2026-02-25', 'On Leave'),
(90, 101, '2026-02-26', 'On Leave'),
(91, 101, '2026-02-27', 'On Leave'),
(92, 101, '2026-02-28', 'On Leave'),
(93, 101, '2026-03-01', 'On Leave'),
(94, 101, '2026-03-02', 'On Leave'),
(95, 101, '2026-03-03', 'On Leave'),
(96, 101, '2026-03-04', 'On Leave'),
(97, 101, '2026-03-05', 'On Leave'),
(98, 101, '2026-03-06', 'On Leave'),
(99, 101, '2026-03-07', 'On Leave'),
(100, 101, '2026-01-29', 'Overtime'),
(101, 101, '2026-02-12', 'On Leave'),
(102, 101, '2026-02-13', 'On Leave'),
(103, 101, '2026-02-14', 'On Leave'),
(104, 101, '2026-02-15', 'On Leave'),
(105, 101, '2026-02-16', 'On Leave'),
(106, 101, '2026-02-14', 'On Leave'),
(107, 101, '2026-02-15', 'On Leave'),
(108, 101, '2026-02-16', 'On Leave'),
(109, 101, '2026-02-17', 'On Leave'),
(110, 101, '2026-02-18', 'On Leave'),
(111, 101, '2026-02-19', 'On Leave'),
(112, 101, '2026-02-20', 'On Leave'),
(113, 101, '2026-02-21', 'On Leave'),
(114, 101, '2026-02-22', 'On Leave'),
(115, 101, '2026-02-23', 'On Leave'),
(116, 101, '2026-02-24', 'On Leave'),
(117, 101, '2026-02-25', 'On Leave'),
(118, 101, '2026-02-26', 'On Leave'),
(119, 101, '2026-02-27', 'On Leave'),
(120, 101, '2026-02-28', 'On Leave'),
(121, 101, '2026-02-12', 'Overtime'),
(122, 101, '2026-02-12', 'Present'),
(123, 108, '2026-02-10', 'Present'),
(124, 108, '2026-02-11', 'Late'),
(125, 109, '2026-02-10', 'Tardy'),
(126, 109, '2026-02-11', 'Present'),
(127, 110, '2026-02-10', 'Duty on Rest Day'),
(128, 111, '2026-02-10', 'Undertime'),
(129, 104, '2026-02-14', 'Present'),
(130, 105, '2026-02-14', 'Present'),
(131, 106, '2026-02-14', 'Present'),
(132, 107, '2026-02-14', 'Late'),
(133, 113, '2026-02-12', 'Present'),
(134, 114, '2026-02-14', 'Present'),
(135, 112, '2026-02-14', 'Absent'),
(136, 115, '2026-02-14', 'Late');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_disputes`
--

CREATE TABLE `attendance_disputes` (
  `dispute_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `dispute_date` date NOT NULL,
  `dispute_type` varchar(100) DEFAULT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Endorsed','Approved','Denied') DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_disputes`
--

INSERT INTO `attendance_disputes` (`dispute_id`, `employee_id`, `dispute_date`, `dispute_type`, `reason`, `status`, `created_at`, `remarks`) VALUES
(1, 101, '2026-02-12', NULL, 'because of long line in cafeteria ', 'Approved', '2026-02-12 19:50:10', ''),
(2, 113, '2026-02-12', 'Forgot Time In/Out', 'Bio-metric scanner was offline upon arrival.', 'Approved', '2026-02-14 20:04:45', ''),
(3, 102, '2026-02-14', 'Forgot Time In/Out', 'sample [Proposed: 21:59]', 'Denied', '2026-02-14 21:00:05', 'not enough information');

-- --------------------------------------------------------

--
-- Table structure for table `break_logs`
--

CREATE TABLE `break_logs` (
  `break_log_id` int(11) NOT NULL,
  `time_log_id` int(11) NOT NULL,
  `break_start` datetime NOT NULL,
  `break_end` datetime DEFAULT NULL,
  `total_break_minutes` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `position` varchar(50) DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `employment_status` varchar(20) DEFAULT NULL,
  `date_hired` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `user_id`, `first_name`, `last_name`, `email`, `position`, `department`, `contact_number`, `employment_status`, `date_hired`) VALUES
(101, 0, 'John', 'Doe', 'john.doe@ireply.com', 'Customer Service Rep', 'Operations', NULL, NULL, '0000-00-00'),
(102, 0, 'Charina', 'Vargas', 'charina.vargas@ireply.com', 'Team Coach', 'Operations', NULL, NULL, '0000-00-00'),
(103, 0, 'Super', 'Admin', 'admin@ireply.com', 'Operations Manager', 'Management', NULL, NULL, '0000-00-00'),
(104, 0, 'Sarah', 'Admin', 'sarah@ireply.com', 'Administrator', 'IT', NULL, NULL, '2026-01-01'),
(105, 0, 'Bruce', 'Admin', 'bruce@ireply.com', 'Administrator', 'IT', NULL, NULL, '2026-01-01'),
(106, 0, 'Bravo', 'Coach', 'bravo@ireply.com', 'Team Coach', 'Operations', NULL, NULL, '2026-01-01'),
(107, 0, 'Charlie', 'Coach', 'charlie@ireply.com', 'Team Coach', 'Operations', NULL, NULL, '2026-01-01'),
(108, 0, 'Alice', 'Wonder', 'alice@ireply.com', 'CSR', 'Operations', NULL, NULL, '2026-02-01'),
(109, 0, 'Bob', 'Builder', 'bob@ireply.com', 'CSR', 'Operations', NULL, NULL, '2026-02-01'),
(110, 0, 'Charlie', 'Chaplin', 'c_chaplin@ireply.com', 'CSR', 'Operations', NULL, NULL, '2026-02-01'),
(111, 0, 'David', 'Beckham', 'david@ireply.com', 'CSR', 'Operations', NULL, NULL, '2026-02-01'),
(112, 0, 'Eve', 'Polastri', 'eve@ireply.com', 'CSR', 'Operations', NULL, NULL, '2026-02-01'),
(113, 0, 'Frank', 'Sinatra', 'frank@ireply.com', 'CSR', 'Operations', NULL, NULL, '2026-02-01'),
(114, 0, 'Grace', 'Kelly', 'grace@ireply.com', 'CSR', 'Operations', NULL, NULL, '2026-02-01'),
(115, 0, 'Harry', 'Potter', 'harry@ireply.com', 'CSR', 'Operations', NULL, NULL, '2026-02-01');

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `holiday_id` int(11) NOT NULL,
  `holiday_name` varchar(50) NOT NULL,
  `holiday_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 101, 'Sick Leave', '2026-01-30', '2026-02-01', 'flu', 'Approved', 2, 103, 1, 1, '2026-01-29 11:45:41', NULL),
(2, 101, 'Vacation Leave', '2026-01-31', '2026-02-03', 'Flu', 'Approved', 2, 103, 1, 1, '2026-01-29 12:11:31', NULL),
(3, 101, 'Sick Leave', '2026-02-02', '2026-03-07', 'Maternity', 'Approved', 2, 103, 1, 1, '2026-01-29 12:23:41', NULL),
(4, 101, 'Sick Leave', '2026-01-30', '2026-01-31', 'Severe Flu', 'Approved', 102, 103, 0, 0, '2026-01-30 08:07:09', NULL),
(5, 101, 'Sick Leave', '2026-02-05', '2026-02-07', 'FLU WITH COUGH', 'Approved', 2, 103, 1, 1, '2026-02-03 10:25:46', NULL),
(6, 101, 'Sick Leave', '2026-02-12', '2026-02-16', 'FLU WITH ANXIETY', 'Approved', 2, NULL, 1, 1, '2026-02-12 01:35:29', NULL),
(7, 101, 'Vacation Leave', '2026-02-12', '2026-02-19', 'Because I want to have fun', 'Denied', 2, NULL, 1, 1, '2026-02-12 02:25:08', NULL),
(8, 101, 'Sick Leave', '2026-02-14', '2026-02-28', 'infection', 'Approved', 2, NULL, 1, 1, '2026-02-12 07:34:26', NULL),
(9, 108, 'Vacation Leave', '2026-02-20', '2026-02-22', 'Family reunion', 'Pending', NULL, NULL, 1, 1, '2026-02-14 12:04:45', NULL),
(10, 109, 'Sick Leave', '2026-02-14', '2026-02-15', 'Severe headache', 'Endorsed', 102, NULL, 1, 1, '2026-02-14 12:04:45', NULL),
(11, 110, 'Emergency Leave', '2026-02-16', '2026-02-16', 'House repairs', 'Approved', 102, NULL, 1, 1, '2026-02-14 12:04:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `overtime_requests`
--

CREATE TABLE `overtime_requests` (
  `ot_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `coach_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `supervisor` varchar(100) NOT NULL,
  `ot_type` enum('Regular Overtime','Duty on Rest Day','Duty on Rest Day OT') NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `purpose` text NOT NULL,
  `agreement_1` tinyint(1) NOT NULL DEFAULT 0,
  `agreement_2` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Pending','Endorsed','Approved','Denied') DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `overtime_requests`
--

INSERT INTO `overtime_requests` (`ot_id`, `employee_id`, `coach_name`, `email`, `supervisor`, `ot_type`, `start_time`, `end_time`, `purpose`, `agreement_1`, `agreement_2`, `status`, `approved_by`, `created_at`, `remarks`) VALUES
(1, 101, '', '', '', 'Regular Overtime', '2026-01-29 17:00:00', '2026-01-29 19:00:00', 'Urgent Project Alpha', 0, 0, 'Approved', 103, '2026-01-30 16:07:09', NULL),
(2, 101, '', '', '', 'Regular Overtime', '2026-02-12 07:18:00', '2026-02-13 07:18:00', 'Managing the system', 1, 1, 'Approved', NULL, '2026-02-12 17:19:04', NULL),
(3, 111, 'Bravo Coach', '', '', 'Regular Overtime', '2026-02-14 18:00:00', '2026-02-14 20:00:00', 'Backlog processing', 0, 0, 'Pending', NULL, '2026-02-14 20:04:45', NULL),
(4, 112, 'Bravo Coach', '', '', 'Duty on Rest Day', '2026-02-15 09:00:00', '2026-02-15 17:00:00', 'Weekend System Maintenance', 0, 0, 'Endorsed', NULL, '2026-02-14 20:04:45', NULL);

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
(1, 'Employee', 'Regular employee'),
(2, 'Coach', 'Team Supervisor'),
(3, 'Admin', 'Administrator'),
(4, 'Super Admin', 'Developer');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `work_day` varchar(10) DEFAULT NULL,
  `schedule_start` time NOT NULL,
  `schedule_end` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `employee_id`, `work_day`, `schedule_start`, `schedule_end`) VALUES
(1, 101, 'Monday', '09:00:00', '18:00:00'),
(2, 101, 'Tuesday', '09:00:00', '18:00:00'),
(3, 101, 'Wednesday', '09:00:00', '18:00:00'),
(4, 101, 'Thursday', '09:00:00', '18:00:00'),
(5, 101, 'Friday', '09:00:00', '18:00:00'),
(6, 102, 'Monday', '09:00:00', '18:00:00'),
(7, 102, 'Tuesday', '09:00:00', '18:00:00'),
(8, 102, 'Wednesday', '09:00:00', '18:00:00'),
(9, 102, 'Thursday', '09:00:00', '18:00:00'),
(10, 102, 'Friday', '09:00:00', '18:00:00'),
(11, 103, 'Monday', '08:00:00', '17:00:00'),
(12, 103, 'Tuesday', '08:00:00', '17:00:00'),
(13, 103, 'Wednesday', '08:00:00', '17:00:00'),
(14, 103, 'Thursday', '08:00:00', '17:00:00'),
(15, 103, 'Friday', '08:00:00', '17:00:00'),
(16, 108, 'Monday', '09:00:00', '18:00:00'),
(17, 108, 'Tuesday', '09:00:00', '18:00:00'),
(18, 108, 'Wednesday', '09:00:00', '18:00:00'),
(19, 108, 'Thursday', '09:00:00', '18:00:00'),
(20, 108, 'Friday', '09:00:00', '18:00:00'),
(21, 109, 'Monday', '09:00:00', '18:00:00'),
(22, 109, 'Tuesday', '09:00:00', '18:00:00'),
(23, 109, 'Wednesday', '09:00:00', '18:00:00'),
(24, 109, 'Thursday', '09:00:00', '18:00:00'),
(25, 109, 'Friday', '09:00:00', '18:00:00'),
(26, 104, 'Saturday', '09:00:00', '18:00:00'),
(27, 105, 'Saturday', '09:00:00', '18:00:00'),
(28, 106, 'Saturday', '09:00:00', '18:00:00'),
(29, 107, 'Saturday', '09:00:00', '18:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `team_cluster`
--

CREATE TABLE `team_cluster` (
  `team_id` int(11) NOT NULL,
  `team_name` varchar(50) NOT NULL,
  `coach_id` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_cluster`
--

INSERT INTO `team_cluster` (`team_id`, `team_name`, `coach_id`, `approved_by`) VALUES
(1, 'Team Alpha', 102, NULL),
(2, 'Team Bravo', 106, NULL),
(3, 'Team Charlie', 107, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `team_member_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`team_member_id`, `team_id`, `employee_id`) VALUES
(1, 1, 101),
(2, 1, 108),
(3, 1, 109),
(4, 2, 110),
(5, 2, 111),
(6, 2, 112),
(7, 3, 113),
(8, 3, 114),
(9, 3, 115);

-- --------------------------------------------------------

--
-- Table structure for table `time_logs`
--

CREATE TABLE `time_logs` (
  `time_log_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `attendance_id` int(11) DEFAULT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `log_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_logs`
--

INSERT INTO `time_logs` (`time_log_id`, `employee_id`, `attendance_id`, `time_in`, `time_out`, `log_date`) VALUES
(1, 101, 1, '2026-01-26 08:00:00', '2026-01-26 17:00:00', '2026-01-26'),
(2, 101, 2, '2026-01-27 08:45:00', '2026-01-27 17:00:00', '2026-01-27'),
(3, 101, 4, '2026-01-29 07:55:00', '2026-01-29 17:30:00', '2026-01-29'),
(4, 102, 12, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-26'),
(5, 102, 13, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-27'),
(6, 102, 14, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-28'),
(7, 102, 16, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-30'),
(8, 102, 28, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-05'),
(9, 102, 29, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-06'),
(10, 102, 31, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-08'),
(11, 102, 33, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-12'),
(12, 102, 34, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-13'),
(13, 102, 36, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-15'),
(14, 102, 37, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-16'),
(15, 102, 38, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-02-02'),
(16, 102, 40, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-02-04'),
(17, 102, 42, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-02-06'),
(19, 102, 30, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-07'),
(20, 102, 39, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-02-03'),
(22, 102, 32, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-09'),
(23, 102, 41, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-02-05'),
(25, 102, 35, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-14'),
(26, 103, 43, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-05'),
(27, 103, 44, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-06'),
(28, 103, 45, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-07'),
(29, 103, 48, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-12'),
(30, 103, 49, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-13'),
(31, 103, 51, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-15'),
(32, 103, 52, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-16'),
(33, 103, 53, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-02-02'),
(34, 103, 54, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-02-03'),
(35, 103, 57, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-02-06'),
(41, 103, 46, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-08'),
(42, 103, 56, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-02-05'),
(44, 103, 50, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-14'),
(45, 103, 55, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-02-04'),
(47, 103, 47, '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-01-09'),
(48, 108, 123, '2026-02-10 08:55:00', '2026-02-10 18:05:00', '2026-02-10'),
(49, 108, 124, '2026-02-11 09:15:00', '2026-02-11 18:00:00', '2026-02-11'),
(50, 109, 125, '2026-02-10 09:45:00', '2026-02-10 18:00:00', '2026-02-10'),
(51, 104, 123, '2026-02-14 08:30:00', '2026-02-14 17:30:00', '2026-02-14'),
(52, 105, 124, '2026-02-14 09:00:00', '2026-02-14 18:00:00', '2026-02-14'),
(53, 106, 125, '2026-02-14 08:55:00', '2026-02-14 17:55:00', '2026-02-14'),
(54, 107, 126, '2026-02-14 09:45:00', '2026-02-14 18:00:00', '2026-02-14'),
(55, 114, 134, '2026-02-14 09:00:00', '2026-02-14 18:00:00', '2026-02-14'),
(56, 112, 135, NULL, NULL, '2026-02-14'),
(57, 115, 136, '2026-02-14 09:45:00', '2026-02-14 18:00:00', '2026-02-14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `password_hash`, `role_id`, `employee_id`, `created_at`) VALUES
(1, 'johndoe', 'pass123', '', 1, 101, '2026-01-29 19:14:21'),
(2, 'charina', 'pass123', '', 2, 102, '2026-01-29 19:14:21'),
(3, 'admin', 'pass123', '', 3, 103, '2026-01-29 19:14:21'),
(4, 'admin2', 'pass123', '', 3, 104, '2026-02-14 18:48:06'),
(5, 'admin3', 'pass123', '', 3, 105, '2026-02-14 18:48:06'),
(6, 'coach_bravo', 'pass123', '', 2, 106, '2026-02-14 18:48:06'),
(7, 'coach_charlie', 'pass123', '', 2, 107, '2026-02-14 18:48:06'),
(8, 'alice', 'pass123', '', 1, 108, '2026-02-14 18:48:06'),
(9, 'bob', 'pass123', '', 1, 109, '2026-02-14 18:48:06'),
(10, 'charlie_user', 'pass123', '', 1, 110, '2026-02-14 18:48:06'),
(11, 'david_user', 'pass123', '', 1, 111, '2026-02-14 18:48:06'),
(12, 'eve_user', 'pass123', '', 1, 112, '2026-02-14 18:48:06'),
(13, 'frank_user', 'pass123', '', 1, 113, '2026-02-14 18:48:06'),
(14, 'grace_user', 'pass123', '', 1, 114, '2026-02-14 18:48:06'),
(15, 'harry_user', 'pass123', '', 1, 115, '2026-02-14 18:48:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `attendance_disputes`
--
ALTER TABLE `attendance_disputes`
  ADD PRIMARY KEY (`dispute_id`);

--
-- Indexes for table `break_logs`
--
ALTER TABLE `break_logs`
  ADD PRIMARY KEY (`break_log_id`),
  ADD KEY `time_log_id` (`time_log_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`holiday_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  ADD PRIMARY KEY (`ot_id`);

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
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `team_cluster`
--
ALTER TABLE `team_cluster`
  ADD PRIMARY KEY (`team_id`),
  ADD KEY `coach_id` (`coach_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`team_member_id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD PRIMARY KEY (`time_log_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `attendance_id` (`attendance_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `attendance_disputes`
--
ALTER TABLE `attendance_disputes`
  MODIFY `dispute_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `break_logs`
--
ALTER TABLE `break_logs`
  MODIFY `break_log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `holiday_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `overtime_requests`
--
ALTER TABLE `overtime_requests`
  MODIFY `ot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `team_cluster`
--
ALTER TABLE `team_cluster`
  MODIFY `team_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `team_member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `time_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `break_logs`
--
ALTER TABLE `break_logs`
  ADD CONSTRAINT `break_logs_ibfk_1` FOREIGN KEY (`time_log_id`) REFERENCES `time_logs` (`time_log_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
