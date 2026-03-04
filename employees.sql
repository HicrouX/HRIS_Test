-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 04, 2026 at 06:26 AM
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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD KEY `fk_emp_users` (`user_id`),
  ADD KEY `fk_emp_cluster` (`cluster_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_emp_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_emp_cluster` FOREIGN KEY (`cluster_id`) REFERENCES `clusters` (`cluster_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
