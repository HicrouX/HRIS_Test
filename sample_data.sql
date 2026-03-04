-- Official HRIS Sample Data (Ultra-Compatible Version)
-- Dependencies: Ensure official_hris_db is created

SET FOREIGN_KEY_CHECKS = 0;

-- Using DELETE instead of TRUNCATE for better compatibility with Foreign Keys
DELETE FROM `break_logs`;
DELETE FROM `time_logs`;
DELETE FROM `attendance_logs`;
DELETE FROM `cluster_members`;
DELETE FROM `schedules`;
DELETE FROM `attendance_disputes`;
DELETE FROM `leave_requests`;
DELETE FROM `overtime_requests`;
DELETE FROM `announcements`;
DELETE FROM `clusters`;
DELETE FROM `employees`;
DELETE FROM `users`;
DELETE FROM `roles`;

-- Resetting Auto-Increments to 1 for a clean start
ALTER TABLE `attendance_logs` AUTO_INCREMENT = 1;
ALTER TABLE `clusters` AUTO_INCREMENT = 1;
ALTER TABLE `employees` AUTO_INCREMENT = 1;
ALTER TABLE `leave_requests` AUTO_INCREMENT = 1;
ALTER TABLE `overtime_requests` AUTO_INCREMENT = 1;
ALTER TABLE `users` AUTO_INCREMENT = 1;
ALTER TABLE `roles` AUTO_INCREMENT = 1;
ALTER TABLE `time_logs` AUTO_INCREMENT = 1;
ALTER TABLE `attendance_disputes` AUTO_INCREMENT = 1;
ALTER TABLE `announcements` AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. ROLES
INSERT INTO `roles` (`role_id`, `role_name`, `role_description`) VALUES
(1, 'Employee', 'Standard staff access'),
(2, 'Coach', 'Team management access'),
(3, 'Admin', 'HR and attendance management'),
(4, 'Super Admin', 'Full system configuration');

-- 2. USERS
INSERT INTO `users` (`user_id`, `email`, `password`, `role_id`) VALUES
(1, 'super@hris.com', 'pass123', 4),
(2, 'admin@hris.com', 'pass123', 3),
(3, 'coach1@hris.com', 'pass123', 2),
(4, 'coach2@hris.com', 'pass123', 2),
(5, 'emp1@hris.com', 'pass123', 1),
(6, 'emp2@hris.com', 'pass123', 1),
(7, 'emp3@hris.com', 'pass123', 1),
(8, 'emp4@hris.com', 'pass123', 1),
(9, 'emp5@hris.com', 'pass123', 1);

-- 3. EMPLOYEES
INSERT INTO `employees` (`employee_id`, `user_id`, `first_name`, `last_name`, `email`, `position`, `employee_type`, `date_hired`) VALUES
(1, 1, 'John', 'Super', 'super@hris.com', 'System Owner', 'Permanent', '2023-01-01'),
(2, 2, 'Jane', 'Admin', 'admin@hris.com', 'HR Manager', 'Permanent', '2023-02-15'),
(3, 3, 'Robert', 'Coach', 'coach1@hris.com', 'Team Lead', 'Permanent', '2023-03-10'),
(4, 4, 'Sarah', 'Manager', 'coach2@hris.com', 'Operations Manager', 'Permanent', '2023-04-05'),
(5, 5, 'Alice', 'Smith', 'emp1@hris.com', 'Developer', 'Regular', '2024-01-10'),
(6, 6, 'Bob', 'Jones', 'emp2@hris.com', 'Designer', 'Regular', '2024-01-12'),
(7, 7, 'Charlie', 'Brown', 'emp3@hris.com', 'Support', 'Probationary', '2024-02-01'),
(8, 8, 'David', 'Wilson', 'emp4@hris.com', 'Developer', 'Regular', '2024-02-15'),
(9, 9, 'Eve', 'Davis', 'emp5@hris.com', 'QA Engineer', 'Regular', '2024-03-01');

-- 4. CLUSTERS
INSERT INTO `clusters` (`cluster_id`, `name`, `description`, `user_id`, `status`) VALUES
(1, 'Tech Cluster A', 'Software Development Team', 3, 'active'),
(2, 'Design Cluster B', 'Creative and UI/UX Team', 4, 'active');

-- 5. CLUSTER MEMBERS
INSERT INTO `cluster_members` (`cluster_id`, `employee_id`) VALUES
(1, 5), (1, 8), (1, 9), (2, 6), (2, 7);

-- 6. SCHEDULES
INSERT INTO `schedules` (`employee_id`, `cluster_id`, `day_of_week`, `start_time`, `end_time`, `work_setup`) VALUES
(5, 1, 'Monday', '08:00:00', '17:00:00', 'Onsite'),
(5, 1, 'Tuesday', '08:00:00', '17:00:00', 'Onsite'),
(5, 1, 'Wednesday', '08:00:00', '17:00:00', 'Onsite'),
(5, 1, 'Thursday', '08:00:00', '17:00:00', 'Onsite'),
(5, 1, 'Friday', '08:00:00', '17:00:00', 'Onsite');

-- 7. ATTENDANCE & TIME LOGS
INSERT INTO `attendance_logs` (`attendance_id`, `employee_id`, `cluster_id`, `attendance_date`, `attendance_status`) VALUES
(1, 5, 1, CURDATE(), 'Present'),
(2, 6, 2, CURDATE(), 'Late');

INSERT INTO `time_logs` (`time_log_id`, `employee_id`, `user_id`, `attendance_id`, `time_in`, `log_date`) VALUES
(1, 5, 5, 1, CONCAT(CURDATE(), ' 07:55:00'), CURDATE()),
(2, 6, 6, 2, CONCAT(CURDATE(), ' 09:15:00'), CURDATE());

-- 8. LEAVE REQUESTS
INSERT INTO `leave_requests` (`employee_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`) VALUES
(7, 'Sick Leave', '2026-03-05', '2026-03-06', 'Fever', 'Pending');

-- 9. OVERTIME REQUESTS
INSERT INTO `overtime_requests` (`employee_id`, `ot_type`, `start_time`, `end_time`, `purpose`, `status`) VALUES
(5, 'Regular Overtime', '2026-03-04 17:00:00', '2026-03-04 19:00:00', 'Deploying updates', 'Pending');

-- 10. ANNOUNCEMENTS
INSERT INTO `announcements` (`title`, `content`, `posted_by`) VALUES
('Welcome to the New HRIS', 'We have successfully migrated to the new database system.', 1);

COMMIT;
