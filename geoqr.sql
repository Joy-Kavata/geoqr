-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2026 at 12:30 PM
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
-- Database: `geoqr`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `log_id` int(11) NOT NULL,
  `session_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `check_in_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'present',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`log_id`, `session_id`, `user_id`, `check_in_time`, `status`, `latitude`, `longitude`, `created_at`) VALUES
(1, 2, 5, '2026-06-24 14:14:17', 'present', NULL, NULL, '2026-06-24 14:14:17'),
(2, 3, 2, '2026-06-24 15:04:33', 'present', NULL, NULL, '2026-06-24 15:04:33'),
(3, 3, 5, '2026-06-24 15:07:43', 'present', NULL, NULL, '2026-06-24 15:07:43'),
(4, 7, 2, '2026-06-26 12:45:19', 'present', NULL, NULL, '2026-06-26 12:45:19'),
(5, 7, 5, '2026-06-26 12:45:51', 'present', NULL, NULL, '2026-06-26 12:45:51'),
(6, 8, 2, '2026-06-26 17:12:58', 'present', NULL, NULL, '2026-06-26 17:12:58');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sessions`
--

CREATE TABLE `attendance_sessions` (
  `session_id` int(11) NOT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `geofence_id` int(11) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_sessions`
--

INSERT INTO `attendance_sessions` (`session_id`, `unit_id`, `geofence_id`, `start_time`, `end_time`, `created_by`, `created_at`) VALUES
(1, 3, 1, '2026-06-23 19:10:00', '2026-06-23 20:10:00', 3, '2026-06-23 19:03:19'),
(2, 4, 1, '2026-06-24 13:45:00', '2026-06-24 14:45:00', 3, '2026-06-24 13:43:51'),
(3, 3, 8, '2026-06-24 15:00:00', '2026-06-24 16:00:00', 3, '2026-06-24 14:56:28'),
(5, 6, 2, '2026-06-24 15:00:00', '2026-06-24 16:00:00', 3, '2026-06-24 15:02:16'),
(6, 3, 1, '2026-06-26 09:33:00', '2026-06-26 09:38:00', 3, '2026-06-26 09:32:23'),
(7, 3, 15, '2026-06-26 12:45:00', '2026-06-26 13:05:00', 3, '2026-06-26 12:43:04'),
(8, 3, 15, '2026-06-26 17:05:00', '2026-06-26 17:20:00', 3, '2026-06-26 17:00:45'),
(9, 1, 12, '2026-06-26 17:30:00', '2026-06-26 17:50:00', 3, '2026-06-26 17:29:41'),
(10, 1, 12, '2026-06-26 18:24:00', '2026-06-26 18:49:00', 3, '2026-06-26 18:23:00');

-- --------------------------------------------------------

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `enrollment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollment`
--

INSERT INTO `enrollment` (`enrollment_id`, `user_id`, `unit_id`, `enrolled_at`) VALUES
(3, 2, 3, '2026-06-24 12:16:38'),
(4, 5, 4, '2026-06-24 13:42:21'),
(6, 5, 1, '2026-06-24 14:51:43'),
(7, 5, 3, '2026-06-24 15:07:02'),
(8, 2, 1, '2026-06-26 17:27:50');

-- --------------------------------------------------------

--
-- Table structure for table `geofences`
--

CREATE TABLE `geofences` (
  `geofence_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `radius` int(11) DEFAULT 100,
  `unit_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `geofences`
--

INSERT INTO `geofences` (`geofence_id`, `name`, `latitude`, `longitude`, `radius`, `unit_id`, `created_at`) VALUES
(1, 'DL', -1.25352800, 36.85988900, 17, 4, '2026-06-23 16:25:28'),
(2, 'TC_04', -1.25367300, 36.86007600, 67, 8, '2026-06-23 16:27:12'),
(7, 'TC_1-4', -1.25350200, 36.86016200, 59, 5, '2026-06-23 17:13:32'),
(8, 'TC_1-5', -1.25390900, 36.86520400, 70, 6, '2026-06-23 17:15:02'),
(12, 'TC_3-1', -1.25363600, 36.85997000, 50, 1, '2026-06-24 14:50:40'),
(13, 'Test Lab 1', -1.25284000, 36.85522200, 65, 7, '2026-06-26 11:49:47'),
(14, 'Test Lab 2', -1.25284000, 36.85524300, 70, 2, '2026-06-26 11:55:22'),
(15, 'Test Lab 3', -1.25262700, 36.85517100, 23, 3, '2026-06-26 12:02:55');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(3, 'admin'),
(2, 'lecturer'),
(1, 'student');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `unit_id` int(11) NOT NULL,
  `unit_name` varchar(100) NOT NULL,
  `unit_code` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`unit_id`, `unit_name`, `unit_code`, `user_id`, `created_at`) VALUES
(1, 'Web Development 101', 'WD101', 3, '2026-06-23 12:27:01'),
(2, 'Advanced JavaScript', 'JS201', 3, '2026-06-23 12:27:01'),
(3, 'Data Structures and Algorithms', 'DSA301', 3, '2026-06-23 12:27:01'),
(4, 'Mobile App Development', 'MAD401', 3, '2026-06-23 12:27:01'),
(5, 'Database Systems', 'DBS501', 3, '2026-06-23 12:27:01'),
(6, 'Computer Networks', 'CN601', 3, '2026-06-23 12:27:01'),
(7, 'Software Engineering', 'SE701', 3, '2026-06-23 12:27:01'),
(8, 'Artificial Intelligence', 'AI801', 3, '2026-06-23 12:27:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role_id`, `created_at`) VALUES
(2, 'Kavata Joy', '2305376@students.kcau.ac.ke', '$2y$10$z1lY/BcbUom16TY0Ps8kwefjkjp9ym5kvhsM5iLF4snMB2Wrt2yZW', 1, '2026-06-18 18:05:18'),
(3, 'Elizabeth S', 'eliza@gmail.com', '$2y$10$CeNGs4rDRCr49EwD1IFBcOiuYSa5AXAeCgBsH4LsJld0MrgeRRmbO', 2, '2026-06-18 18:15:21'),
(4, 'Victor Mwendwa', 'victor@gmail.com', '$2y$10$yk0uOPJ2b3/nlqaamXVDP.Hvbb.pWQBqt3TH/6qc.TEsVIMyXMNPW', 3, '2026-06-18 18:29:07'),
(5, 'Linet Mweru', 'linet@gmail.com', '$2y$10$QvKLFCuLH40m7UfJ66/5hOjMZdPBkbKaI7rwc/X5XKUG0J35x.0Ia', 1, '2026-06-24 12:36:32'),
(6, 'John Joe', 'joe@gmail.com', '$2y$10$cUV2fgNYRTtE9VoXedXEH.eL2Gg44gddAIzBh7ZUT/0dAXeN4pRrS', 1, '2026-06-24 12:38:46'),
(7, 'Faith Mwende', 'faith@gmail.com', '$2y$10$GqybsnuhAoy4h6Pts9NYReW78bzkw5SIR0Xl4sNOvWRp3BzE6S2OO', 1, '2026-06-24 14:53:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `geofence_id` (`geofence_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `unique_enrollment` (`user_id`,`unit_id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `geofences`
--
ALTER TABLE `geofences`
  ADD PRIMARY KEY (`geofence_id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`unit_id`),
  ADD UNIQUE KEY `unit_code` (`unit_code`),
  ADD KEY `lecturer_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `geofences`
--
ALTER TABLE `geofences`
  MODIFY `geofence_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `attendance_logs_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions` (`session_id`),
  ADD CONSTRAINT `attendance_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD CONSTRAINT `attendance_sessions_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `units` (`unit_id`),
  ADD CONSTRAINT `attendance_sessions_ibfk_2` FOREIGN KEY (`geofence_id`) REFERENCES `geofences` (`geofence_id`),
  ADD CONSTRAINT `attendance_sessions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD CONSTRAINT `enrollment_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `enrollment_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`unit_id`);

--
-- Constraints for table `geofences`
--
ALTER TABLE `geofences`
  ADD CONSTRAINT `geofences_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `units` (`unit_id`);

--
-- Constraints for table `units`
--
ALTER TABLE `units`
  ADD CONSTRAINT `units_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
