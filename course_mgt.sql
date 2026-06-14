-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 14, 2026 at 12:22 PM
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
-- Database: `course_mgt`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_logs`
--

CREATE TABLE `access_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `page` varchar(255) NOT NULL,
  `access_type` varchar(50) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `access_logs`
--

INSERT INTO `access_logs` (`log_id`, `user_id`, `page`, `access_type`, `timestamp`) VALUES
(1, 1, 'view_courses.php', 'view', '2026-02-21 14:01:41'),
(2, 1, 'create_course.php', 'view', '2026-02-21 14:04:01'),
(3, 1, 'create_course.php', 'view', '2026-02-21 14:04:02'),
(4, 1, 'create_course.php', 'view', '2026-02-21 14:04:02'),
(5, 1, 'create_course.php', 'view', '2026-02-21 14:04:03'),
(6, 1, 'create_course.php', 'view', '2026-02-21 14:05:06'),
(7, 1, 'create_course.php', 'view', '2026-02-21 14:05:07'),
(8, 1, 'create_course.php', 'view', '2026-02-21 14:05:07'),
(9, 1, 'create_course.php', 'view', '2026-02-21 14:05:08'),
(10, 1, 'create_course.php', 'view', '2026-02-21 14:05:08'),
(11, 1, 'create_course.php', 'view', '2026-02-21 14:05:08'),
(12, 1, 'view_courses.php', 'view', '2026-02-21 14:05:12'),
(13, 1, 'view_courses.php', 'view', '2026-02-21 14:05:13'),
(14, 1, 'view_courses.php', 'view', '2026-02-21 14:05:13'),
(15, 1, 'view_courses.php', 'view', '2026-02-21 14:05:13'),
(16, 1, 'create_course', 'success', '2026-02-21 14:09:31'),
(17, 1, 'delete_course', 'success', '2026-02-21 14:12:13'),
(18, 1, 'login', 'success', '2026-03-04 10:45:00'),
(19, 1, 'create_course', 'success', '2026-03-04 10:48:06'),
(20, 1, 'delete_course', 'success', '2026-03-04 11:23:13'),
(21, 1, 'login', 'success', '2026-03-04 11:54:18'),
(22, 1, 'login', 'success', '2026-03-12 13:19:45'),
(23, 1, 'login', 'success', '2026-03-12 13:23:22'),
(24, 1, 'login', 'success', '2026-03-14 17:07:08'),
(25, 1, 'create_course', 'success', '2026-03-14 17:26:39'),
(26, 1, 'login', 'success', '2026-03-16 16:39:31'),
(27, 1, 'login', 'success', '2026-03-25 18:46:11'),
(28, 1, 'create_course', 'success', '2026-03-25 18:50:18'),
(29, 1, 'delete_course', 'success', '2026-03-25 18:50:26'),
(30, 1, 'modules/courses/view_course.php', 'view', '2026-03-25 18:51:10'),
(31, 1, 'login', 'success', '2026-03-25 19:19:56'),
(32, 1, 'login', 'success', '2026-03-29 00:48:51'),
(33, 1, 'login', 'success', '2026-03-29 16:18:25'),
(34, 1, 'create_course', 'success', '2026-03-29 16:20:32'),
(35, 1, 'modules/courses/view_course.php', 'view', '2026-03-29 16:20:57'),
(36, 1, 'login', 'success', '2026-03-29 16:37:40'),
(37, 1, 'login', 'success', '2026-03-29 16:58:52'),
(38, 1, 'login', 'success', '2026-03-29 17:19:13'),
(39, 1, 'login', 'success', '2026-03-29 18:11:50'),
(40, 1, 'create_course', 'success', '2026-03-29 18:19:03'),
(41, 1, 'delete_course', 'success', '2026-03-29 18:19:11'),
(42, 1, 'login', 'success', '2026-03-29 18:40:59'),
(43, 1, 'login', 'success', '2026-03-30 09:32:29'),
(44, 1, 'login', 'success', '2026-04-02 09:30:20'),
(45, 1, 'login', 'success', '2026-04-02 09:44:41'),
(46, 1, 'login', 'success', '2026-04-29 09:44:54'),
(47, 1, 'login', 'success', '2026-04-29 09:47:52'),
(48, 1, 'login', 'success', '2026-05-03 14:11:31'),
(49, 10, 'login', 'success', '2026-05-03 14:27:58'),
(50, 10, 'dashboard.php', 'view', '2026-05-03 14:27:58'),
(51, 10, 'dashboard.php', 'view', '2026-05-03 14:33:45'),
(52, 10, 'dashboard.php', 'view', '2026-05-03 14:33:45'),
(53, 10, 'logout', 'success', '2026-05-03 14:34:27'),
(54, 10, 'login', 'success', '2026-05-03 14:34:38'),
(55, 10, 'logout', 'success', '2026-05-03 14:39:48'),
(56, 4, 'login', 'success', '2026-05-03 14:40:09'),
(57, 4, 'logout', 'success', '2026-05-03 14:42:14'),
(58, 4, 'login', 'success', '2026-05-03 14:42:34'),
(59, 4, 'logout', 'success', '2026-05-03 14:45:27'),
(60, 10, 'login', 'success', '2026-05-03 14:45:34'),
(61, 10, 'logout', 'success', '2026-05-03 15:00:12'),
(62, 11, 'student_login', 'success', '2026-05-03 15:01:10'),
(63, 11, 'logout', 'success', '2026-05-03 15:02:04'),
(64, 1, 'logout', 'success', '2026-05-03 15:03:07'),
(65, 1, 'login', 'success', '2026-05-03 15:03:27'),
(66, 4, 'student_login', 'success', '2026-05-03 15:08:20'),
(67, 4, 'student/submit_assignment.php', 'view', '2026-05-03 15:10:01'),
(68, 4, 'student/submit_assignment.php', 'view', '2026-05-03 15:12:23'),
(69, 4, 'logout', 'success', '2026-05-03 15:12:49'),
(70, 12, 'student_login', 'success', '2026-05-03 15:13:51'),
(71, 12, 'logout', 'success', '2026-05-03 15:14:49'),
(72, 4, 'student_login', 'success', '2026-05-03 15:14:56'),
(73, 1, 'logout', 'success', '2026-05-03 15:15:33'),
(74, 1, 'login', 'success', '2026-05-03 15:16:07'),
(75, 4, 'logout', 'success', '2026-05-03 15:17:00'),
(76, 12, 'student_login', 'success', '2026-05-03 15:17:10'),
(77, 12, 'logout', 'success', '2026-05-03 15:17:41'),
(78, 4, 'student_login', 'success', '2026-05-03 15:17:49'),
(79, 1, 'logout', 'success', '2026-05-03 15:39:08'),
(80, 4, 'student_login', 'success', '2026-05-03 15:39:24'),
(81, 4, 'logout', 'success', '2026-05-03 15:39:48'),
(82, 1, 'login', 'success', '2026-05-06 09:16:18'),
(83, 1, 'logout', 'success', '2026-05-06 09:19:14'),
(84, 13, 'student_login', 'success', '2026-05-06 09:20:17'),
(85, 13, 'logout', 'success', '2026-05-06 09:20:47'),
(86, 1, 'login', 'success', '2026-05-06 09:21:31'),
(87, 1, 'logout', 'success', '2026-05-06 09:22:53'),
(88, 1, 'login', 'success', '2026-05-06 09:23:45'),
(89, 1, 'logout', 'success', '2026-05-06 09:25:01'),
(90, 13, 'student_login', 'success', '2026-05-06 09:25:19'),
(91, 13, 'dashboard.php', 'view', '2026-05-06 09:26:32'),
(92, 13, 'dashboard.php', 'view', '2026-05-06 09:26:33'),
(93, 13, 'dashboard.php', 'view', '2026-05-06 09:26:34'),
(94, 13, 'dashboard.php', 'view', '2026-05-06 09:26:34'),
(95, 13, 'dashboard.php', 'view', '2026-05-06 09:26:35'),
(96, 13, 'dashboard.php', 'view', '2026-05-06 09:26:35'),
(97, 13, 'dashboard.php', 'view', '2026-05-06 09:26:35'),
(98, 13, 'dashboard.php', 'view', '2026-05-06 09:26:35'),
(99, 13, 'dashboard.php', 'view', '2026-05-06 09:26:38'),
(100, 13, 'dashboard.php', 'view', '2026-05-06 09:26:45'),
(101, 13, 'dashboard.php', 'view', '2026-05-06 09:29:28'),
(102, 13, 'dashboard.php', 'view', '2026-05-06 09:29:30'),
(103, 5, 'student_login', 'success', '2026-05-12 08:44:55'),
(104, 5, 'dashboard.php', 'view', '2026-05-12 08:45:30'),
(105, 5, 'dashboard.php', 'view', '2026-05-12 08:45:34'),
(106, 5, 'dashboard.php', 'view', '2026-05-12 08:45:44'),
(107, 5, 'dashboard.php', 'view', '2026-05-12 08:45:54'),
(108, 5, 'dashboard.php', 'view', '2026-05-12 08:45:56'),
(109, 1, 'login', 'success', '2026-05-12 08:49:52'),
(110, 1, 'logout', 'success', '2026-05-12 08:50:07'),
(111, 5, 'student_login', 'success', '2026-05-12 08:50:21'),
(112, 5, 'logout', 'success', '2026-05-12 08:52:32'),
(113, 1, 'login', 'success', '2026-05-12 08:52:56'),
(114, 5, 'student_login', 'success', '2026-05-19 09:41:28'),
(115, 5, 'logout', 'success', '2026-05-19 09:44:53'),
(116, 1, 'login', 'success', '2026-05-19 09:45:32'),
(117, 1, 'logout', 'success', '2026-05-19 09:48:05'),
(118, 1, 'login', 'success', '2026-05-21 11:51:42'),
(119, 1, 'logout', 'success', '2026-05-21 11:55:26'),
(120, 14, 'login', 'success', '2026-05-21 11:55:46'),
(121, 14, 'logout', 'success', '2026-05-21 12:10:56'),
(122, 1, 'login', 'success', '2026-05-21 12:11:44'),
(123, 1, 'logout', 'success', '2026-05-21 12:13:08'),
(124, 5, 'student_login', 'success', '2026-05-21 12:14:52'),
(125, 5, 'logout', 'success', '2026-05-21 12:15:43'),
(126, 15, 'student_login', 'success', '2026-05-21 12:16:52'),
(127, 15, 'logout', 'success', '2026-05-21 12:22:43'),
(128, 14, 'login', 'success', '2026-05-21 12:25:19'),
(129, 1, 'login', 'success', '2026-05-25 14:51:24'),
(130, 1, 'logout', 'success', '2026-05-25 15:00:18'),
(131, 14, 'login', 'success', '2026-05-25 15:00:47'),
(132, 14, 'logout', 'success', '2026-05-25 15:06:26'),
(133, 1, 'login', 'success', '2026-05-25 15:07:04'),
(134, 1, 'logout', 'success', '2026-05-25 15:09:03'),
(135, 15, 'student_login', 'success', '2026-05-25 15:09:57'),
(136, 15, 'logout', 'success', '2026-05-25 15:15:03'),
(137, 1, 'login', 'success', '2026-05-25 15:15:21'),
(138, 1, 'logout', 'success', '2026-05-25 16:18:51'),
(139, 4, 'student_login', 'success', '2026-05-25 16:19:02'),
(140, 4, 'logout', 'success', '2026-05-25 16:23:33'),
(141, 1, 'login', 'success', '2026-05-25 16:23:46'),
(142, 1, 'modules/courses/view_course.php', 'view', '2026-05-25 16:26:14'),
(143, 1, 'modules/courses/view_course.php', 'view', '2026-05-25 16:26:42'),
(144, 1, 'logout', 'success', '2026-05-25 16:34:14'),
(145, 4, 'student_login', 'success', '2026-05-25 16:34:26'),
(146, 4, 'logout', 'success', '2026-05-25 17:00:59'),
(147, 1, 'login', 'success', '2026-05-25 17:01:53'),
(148, 1, 'login', 'success', '2026-06-03 09:14:51'),
(149, 1, 'login', 'success', '2026-06-14 11:25:39'),
(150, 1, 'logout', 'success', '2026-06-14 11:27:19'),
(151, 14, 'login', 'success', '2026-06-14 11:27:29'),
(152, 14, 'logout', 'success', '2026-06-14 11:28:19'),
(153, 5, 'student_login', 'success', '2026-06-14 11:28:37');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `assignment_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`assignment_id`, `course_id`, `title`, `description`, `due_date`, `created_by`, `created_at`) VALUES
(2, 1, 'Purposal', 'according to the project', '2026-03-04 11:41:00', 1, '2026-03-04 06:41:52'),
(5, 6, 'Research on toyota company', 'how the company grow their standards in market\r\nis it deliver the cars according to the needs of public', '2026-04-05 12:00:00', 1, '2026-03-29 13:23:13'),
(6, 6, 'Assignement on bpm', 'ncdnjkckjnhvrkevnjunvjrhvnjdv hiucjencrvhj', '2026-05-14 15:09:00', 1, '2026-05-03 10:09:40'),
(7, 7, 'law of tot', '', '2026-05-27 12:00:00', 1, '2026-05-25 11:25:47');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `status` enum('present','absent','late') NOT NULL,
  `recorded_by` int(11) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `course_id`, `student_id`, `session_date`, `status`, `recorded_by`, `recorded_at`) VALUES
(10, 6, 4, '2026-03-21', 'present', 1, '2026-03-29 11:25:40'),
(11, 6, 5, '2026-03-21', 'late', 1, '2026-03-29 11:25:40'),
(22, 6, 4, '2026-03-29', 'present', 1, '2026-03-29 11:43:43'),
(23, 6, 5, '2026-03-29', 'present', 1, '2026-03-29 11:43:43'),
(26, 1, 4, '2026-03-29', 'present', 1, '2026-03-29 11:48:17'),
(27, 1, 5, '2026-03-29', 'absent', 1, '2026-03-29 11:48:17'),
(29, 7, 8, '2026-03-29', 'present', 1, '2026-03-29 13:30:46'),
(30, 7, 4, '2026-03-29', 'absent', 1, '2026-03-29 13:30:46'),
(31, 7, 8, '2026-04-02', 'absent', 1, '2026-04-02 04:46:42'),
(32, 7, 4, '2026-04-02', 'present', 1, '2026-04-02 04:46:42'),
(33, 7, 8, '2026-05-03', 'present', 1, '2026-05-03 09:56:20'),
(34, 7, 4, '2026-05-03', 'late', 1, '2026-05-03 09:56:20'),
(35, 1, 4, '2026-05-06', 'late', 1, '2026-05-06 04:18:20'),
(36, 1, 5, '2026-05-06', 'present', 1, '2026-05-06 04:18:20'),
(37, 6, 4, '2026-05-21', 'present', 14, '2026-05-21 07:25:40'),
(38, 6, 5, '2026-05-21', 'present', 14, '2026-05-21 07:25:40');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `dept_id` int(11) NOT NULL,
  `instructor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_code`, `course_name`, `dept_id`, `instructor_id`) VALUES
(1, 'CS101', 'Introduction to Programming', 1, NULL),
(6, 'BBA108', 'BPM', 2, 14),
(7, 'LD 101', 'LLB', 3, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `dept_code` varchar(20) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`dept_id`, `dept_name`, `dept_code`, `description`) VALUES
(1, 'Computer Science', 'CS', NULL),
(2, 'Business Adminstration', 'DBA', NULL),
(3, 'Law Department', 'LD', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `student_id`, `course_id`, `enrollment_date`, `status`) VALUES
(3, 4, 1, '2026-03-16 11:50:37', 'active'),
(4, 5, 1, '2026-03-16 11:50:37', 'active'),
(5, 4, 6, '2026-03-29 11:21:27', 'active'),
(6, 5, 6, '2026-03-29 11:23:53', 'active'),
(7, 4, 7, '2026-03-29 13:19:25', 'active'),
(8, 8, 7, '2026-03-29 13:30:31', 'active'),
(9, 12, 7, '2026-05-03 10:16:50', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `grade_id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `feedback` text DEFAULT NULL,
  `graded_by` int(11) NOT NULL,
  `graded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`grade_id`, `submission_id`, `marks_obtained`, `feedback`, `graded_by`, `graded_at`) VALUES
(1, 1, 10.00, 'Well done student1. Keep it up', 0, '2026-05-03 10:27:39');

-- --------------------------------------------------------

--
-- Table structure for table `role_access`
--

CREATE TABLE `role_access` (
  `role_id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_access`
--

INSERT INTO `role_access` (`role_id`, `page_id`, `can_view`, `can_edit`) VALUES
(1, 1, 1, 1),
(1, 2, 1, 1),
(1, 3, 1, 1),
(1, 4, 1, 1),
(1, 5, 1, 1),
(1, 6, 1, 1),
(1, 7, 1, 1),
(1, 8, 1, 1),
(1, 9, 1, 1),
(1, 10, 1, 1),
(1, 11, 1, 0),
(1, 12, 1, 0),
(1, 13, 1, 0),
(1, 14, 1, 0),
(1, 19, 1, 0),
(1, 20, 1, 0),
(1, 21, 1, 0),
(1, 22, 1, 0),
(1, 28, 1, 0),
(1, 29, 1, 0),
(1, 30, 1, 0),
(2, 1, 1, 1),
(2, 2, 1, 1),
(2, 3, 1, 0),
(2, 4, 1, 1),
(2, 5, 1, 1),
(2, 6, 1, 1),
(2, 7, 1, 1),
(2, 8, 1, 1),
(2, 9, 1, 1),
(2, 10, 1, 1),
(2, 11, 1, 0),
(2, 12, 1, 0),
(2, 13, 1, 0),
(2, 14, 1, 0),
(2, 19, 1, 0),
(2, 20, 1, 0),
(2, 21, 1, 0),
(2, 22, 1, 0),
(2, 28, 1, 0),
(2, 29, 1, 0),
(2, 30, 1, 0),
(3, 1, 1, 1),
(3, 2, 0, 1),
(3, 3, 0, 1),
(3, 4, 0, 1),
(3, 5, 0, 1),
(3, 6, 0, 1),
(3, 7, 1, 1),
(3, 8, 0, 1),
(3, 9, 0, 1),
(3, 10, 1, 1),
(3, 11, 1, 0),
(3, 12, 1, 0),
(3, 13, 1, 0),
(3, 14, 1, 0),
(3, 19, 1, 0),
(3, 20, 1, 0),
(3, 28, 1, 0),
(3, 29, 1, 0),
(3, 30, 1, 0),
(4, 23, 1, 0),
(4, 24, 1, 0),
(4, 25, 1, 0),
(4, 26, 1, 0),
(4, 27, 1, 0),
(4, 30, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `submission_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) NOT NULL,
  `status` enum('pending','submitted','graded') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`submission_id`, `assignment_id`, `student_id`, `submission_date`, `file_path`, `status`) VALUES
(1, 6, 4, '2026-05-03 10:19:33', 'uploads/submissions/5ts487p2m6j3lf156n9dci37t9_1777803573.jpeg', 'graded');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_name` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_name`, `setting_value`) VALUES
('app_logo', 'logo.png'),
('app_name', 'University Course Management'),
('dark_mode', '0');

-- --------------------------------------------------------

--
-- Table structure for table `sys_pages`
--

CREATE TABLE `sys_pages` (
  `page_id` int(11) NOT NULL,
  `page_title` varchar(100) NOT NULL,
  `page_url` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `icon_class` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sys_pages`
--

INSERT INTO `sys_pages` (`page_id`, `page_title`, `page_url`, `parent_id`, `icon_class`) VALUES
(1, 'Dashboard', 'dashboard.php', 0, 'fas fa-tachometer-alt'),
(2, 'Users', '#', 0, 'fas fa-users'),
(3, 'Manage Roles', 'modules/users/create_role.php', 2, 'fas fa-user-tag'),
(4, 'Manage Users', 'modules/users/manage_users.php', 2, 'fas fa-user-cog'),
(5, 'Courses', '#', 0, 'fas fa-book'),
(6, 'Create Course', 'modules/courses/create_course.php', 5, 'fas fa-plus-circle'),
(7, 'View Courses', 'modules/courses/view_courses.php', 5, 'fas fa-list'),
(8, 'Enrollment', '#', 0, 'fas fa-user-graduate'),
(9, 'Enroll Students', 'modules/enrollment/enroll_student.php', 8, 'fas fa-user-plus'),
(10, 'View Enrollments', 'modules/enrollment/view_enrollments.php', 8, 'fas fa-list'),
(11, 'Attendance', '#', 0, 'fas fa-calendar-check'),
(12, 'Mark Attendance', 'modules/attendance/mark_attendance.php', 11, 'far fa-circle'),
(13, 'Assignments', '#', 0, 'fas fa-tasks'),
(14, 'Create Assignment', 'modules/assignments/create_assignment.php', 13, 'far fa-circle'),
(19, 'View Attendance', 'modules/attendance/view_attendance.php', 11, 'far fa-circle'),
(20, 'View Assignments', 'modules/assignments/view_assignments.php', 13, 'far fa-circle'),
(21, 'Departments', '#', 0, 'fas fa-building'),
(22, 'Manage Departments', 'modules/departments/manage_departments.php', 21, 'far fa-circle'),
(23, 'Student Dashboard', 'student/dashboard.php', 0, 'fas fa-tachometer-alt'),
(24, 'My Courses', 'student/my_courses.php', 0, 'fas fa-book'),
(25, 'My Assignments', 'student/my_assignments.php', 0, 'fas fa-tasks'),
(26, 'My Attendance', 'student/my_attendance.php', 0, 'fas fa-calendar-check'),
(27, 'Submit Assignment', 'student/submit_assignment.php', -1, 'fas fa-upload'),
(28, 'View Submissions', 'modules/assignments/view_submissions.php', -1, 'fas fa-tasks'),
(29, 'Grade Submission', 'modules/assignments/grade_submission.php', -1, 'fas fa-tasks'),
(30, 'View Course', 'modules/courses/view_course.php', -1, 'fas fa-eye');

-- --------------------------------------------------------

--
-- Table structure for table `sys_roles`
--

CREATE TABLE `sys_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sys_roles`
--

INSERT INTO `sys_roles` (`role_id`, `role_name`) VALUES
(2, 'admin'),
(6, 'Clerk'),
(7, 'HOD'),
(3, 'instructor'),
(5, 'Librarian'),
(4, 'student'),
(1, 'super_admin');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `role_id`, `created_at`) VALUES
(1, 'admin', '$2a$12$dDHPLZfVDxSHTkgMaA7u3uqANhVu0.4nZO.Old5Ng4p46O4u2kdbi', 1, '2026-02-21 07:14:56'),
(4, 'mugheez', '$2y$10$e2CG3dcUxt2XeZgHAIM..Ocojw/ObFnrF93ss/tJjgt6pGgVWYtsy', 4, '2026-03-16 11:49:25'),
(5, 'student2', '$2y$10$SE4EqkXvBYdpH6xMazSYJO1MJDSFkXyqZcwdnuJnspNQ/rSGyFbt2', 4, '2026-03-16 11:49:48'),
(6, 'Dr Waris Ali', '$2y$10$mxwywzlC8HT1LDs2NSw8BeXrWllsRmixYnByD5TUc69QhozTjG4By', 7, '2026-03-29 13:15:06'),
(8, 'Ahmad', '$2y$10$Pie/4CuXFTL3w0ShVRybBOvH4.iVwj9ntCPZBb7wqfMxu/960o7hu', 4, '2026-03-29 13:26:51'),
(9, 'usama', '$2y$10$nKfqD8LWbCttsoelALRjG./PRVkj7yELa0tpBPNTy4f02CvbSVO1.', 4, '2026-03-29 13:27:11'),
(10, 'Ali', '$2y$10$IA/YD/joSEly52yiSnDpQ.XtvD62a5Omt5hQD0GrJu228P8H1b9aS', 4, '2026-05-03 09:27:42'),
(11, 'Umair', '$2y$10$WfSH.JWFvQ001BufQqiIDexXrpfnQt/wYC2li5PeOEvBtnoL2zo9S', 4, '2026-05-03 10:01:00'),
(12, 'HASEEB', '$2y$10$P5MqscvCG7VxfH6IidTS8u.aDIiHSfvbiMfTcSRCOe7ngLpPLY9c.', 4, '2026-05-03 10:13:40'),
(13, 'tayyab', '$2y$10$PT0KVg8cbH6ik4XQbcO//O95qJZRXHAOg0.ECjMxquzl1cQ41fTRe', 4, '2026-05-06 04:20:07'),
(14, 'sir umair', '$2y$10$o1xtb6upONEAfZSxnMkcH.FthapnptKOf4kX1zUGC0APkEB5w5aCy', 3, '2026-05-21 06:53:57'),
(15, 'wahab', '$2y$10$5BeXGaLDwfH.FjasXu6m5O2ElCnYcTly9f3qo1PtPPfCgXVBEXszC', 4, '2026-05-21 07:16:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_logs`
--
ALTER TABLE `access_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_access` (`user_id`,`timestamp`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `dept_id` (`dept_id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`),
  ADD UNIQUE KEY `uq_dept_code` (`dept_code`),
  ADD UNIQUE KEY `uq_dept_name` (`dept_name`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD KEY `submission_id` (`submission_id`);

--
-- Indexes for table `role_access`
--
ALTER TABLE `role_access`
  ADD PRIMARY KEY (`role_id`,`page_id`),
  ADD KEY `page_id` (`page_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_name`);

--
-- Indexes for table `sys_pages`
--
ALTER TABLE `sys_pages`
  ADD PRIMARY KEY (`page_id`);

--
-- Indexes for table `sys_roles`
--
ALTER TABLE `sys_roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

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
-- AUTO_INCREMENT for table `access_logs`
--
ALTER TABLE `access_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sys_pages`
--
ALTER TABLE `sys_pages`
  MODIFY `page_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `sys_roles`
--
ALTER TABLE `sys_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `access_logs`
--
ALTER TABLE `access_logs`
  ADD CONSTRAINT `access_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `fk_assignments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`),
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`);

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `fk_grades_submission` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`submission_id`) ON DELETE CASCADE;

--
-- Constraints for table `role_access`
--
ALTER TABLE `role_access`
  ADD CONSTRAINT `role_access_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `sys_roles` (`role_id`),
  ADD CONSTRAINT `role_access_ibfk_2` FOREIGN KEY (`page_id`) REFERENCES `sys_pages` (`page_id`);

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `fk_submissions_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`assignment_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `sys_roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
