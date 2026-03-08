-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 24, 2026 at 08:16 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Hulo`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'Login', 'User logged in successfully', '::1', '2025-11-11 17:03:12'),
(2, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2025-11-11 17:05:25'),
(3, 1, 'Bulk Control', 'Turned all streetlights OFF', '::1', '2025-11-11 17:05:31'),
(4, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2025-11-11 17:05:39'),
(5, 1, 'Light Control', 'Toggled light #1 to OFF', '::1', '2025-11-11 17:13:11'),
(6, 1, 'Light Control', 'Toggled light #3 to OFF', '::1', '2025-11-11 17:13:19'),
(7, 1, 'Bulk Control', 'Turned all streetlights ON at 30%', '::1', '2025-11-11 17:13:40'),
(8, 1, 'Logout', 'User logged out', '::1', '2025-11-11 17:15:33'),
(9, 1, 'Login', 'User logged in successfully', '::1', '2025-11-11 17:28:00'),
(10, 1, 'Light Control', 'Toggled light #3 to OFF', '::1', '2025-11-11 17:29:37'),
(11, 1, 'Logout', 'User logged out', '::1', '2025-11-11 17:31:26'),
(12, 1, 'Login', 'User logged in successfully', '::1', '2025-11-11 17:33:11'),
(13, 1, 'Bulk Control', 'Turned all streetlights OFF', '::1', '2025-11-11 17:33:48'),
(14, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2025-11-11 17:33:52'),
(15, 1, 'Light Control', 'Toggled light #5 to OFF', '::1', '2025-11-11 17:33:56'),
(16, 1, 'Light Control', 'Toggled light #12 to OFF', '::1', '2025-11-11 17:33:59'),
(17, 1, 'Logout', 'User logged out', '::1', '2025-11-11 17:36:57'),
(18, 1, 'Login', 'User logged in successfully', '::1', '2025-11-11 17:37:34'),
(19, 1, 'Login', 'User logged in successfully', '::1', '2025-11-12 01:40:12'),
(20, 1, 'Login', 'User logged in successfully', '::1', '2025-11-12 01:41:24'),
(21, 1, 'Bulk Control', 'Turned all streetlights OFF', '::1', '2025-11-12 01:43:10'),
(22, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2025-11-12 01:43:14'),
(23, 1, 'Bulk Control', 'Turned all streetlights ON at 30%', '::1', '2025-11-12 01:43:20'),
(24, 1, 'Bulk Control', 'Turned all streetlights ON at 10%', '::1', '2025-11-12 01:43:26'),
(25, 1, 'Logout', 'User logged out', '::1', '2025-11-12 01:58:28'),
(26, 1, 'Login', 'User logged in successfully', '::1', '2025-11-12 03:43:36'),
(27, 1, 'Bulk Control', 'Turned all streetlights ON at 40%', '::1', '2025-11-12 03:44:29'),
(28, 1, 'Bulk Control', 'Turned all streetlights OFF', '::1', '2025-11-12 03:44:39'),
(29, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2025-11-12 03:44:43'),
(30, 1, 'Light Control', 'Toggled light #3 to OFF', '::1', '2025-11-12 03:44:47'),
(31, 1, 'Light Control', 'Toggled light #13 to OFF', '::1', '2025-11-12 03:44:50'),
(32, 1, 'Bulk Control', 'Turned all streetlights OFF', '::1', '2025-11-12 03:55:40'),
(33, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2025-11-12 08:20:24'),
(34, 1, 'Logout', 'User logged out', '::1', '2025-11-12 08:21:40'),
(35, 1, 'Login', 'User logged in successfully', '::1', '2025-11-19 13:28:31'),
(36, 1, 'Login', 'User logged in successfully', '::1', '2025-11-25 18:34:56'),
(37, 1, 'Logout', 'User logged out', '::1', '2025-11-25 18:35:05'),
(38, 1, 'Login', 'User logged in successfully', '::1', '2025-11-25 18:35:42'),
(39, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 14:21:22'),
(40, 1, 'Light Control', 'Toggled light #1 to OFF', '::1', '2025-11-30 14:22:17'),
(41, 1, 'Light Control', 'Toggled light #1 to ON', '::1', '2025-11-30 14:22:23'),
(42, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 14:24:30'),
(43, 1, 'Logout', 'User logged out', '::1', '2025-11-30 14:25:28'),
(44, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 14:26:07'),
(45, 1, 'Logout', 'User logged out', '::1', '2025-11-30 14:31:09'),
(46, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 14:32:11'),
(47, 1, 'Logout', 'User logged out', '::1', '2025-11-30 14:46:39'),
(48, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 14:46:42'),
(49, 1, 'Logout', 'User logged out', '::1', '2025-11-30 14:49:18'),
(50, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 14:49:21'),
(51, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 14:49:50'),
(52, 1, 'Light Control', 'Toggled light #1 to OFF', '::1', '2025-11-30 14:54:48'),
(53, 1, 'Light Control', 'Toggled light #2 to OFF', '::1', '2025-11-30 14:54:51'),
(54, 1, 'Light Control', 'Toggled light #3 to OFF', '::1', '2025-11-30 14:54:57'),
(55, 1, 'Light Control', 'Toggled light #1 to ON', '::1', '2025-11-30 14:55:00'),
(56, 1, 'Logout', 'User logged out', '::1', '2025-11-30 14:57:25'),
(57, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 14:57:27'),
(58, 1, 'Logout', 'User logged out', '::1', '2025-11-30 15:10:40'),
(59, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 15:11:22'),
(60, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-11-30 15:19:41'),
(61, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-11-30 15:20:17'),
(62, 1, 'Logout', 'User logged out', '::1', '2025-11-30 15:20:20'),
(63, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 15:20:22'),
(64, 1, 'Logout', 'User logged out', '::1', '2025-11-30 15:25:14'),
(65, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 15:25:21'),
(66, 1, 'Logout', 'User logged out', '::1', '2025-11-30 15:25:32'),
(67, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 15:25:35'),
(68, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 15:27:58'),
(69, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-11-30 15:28:13'),
(70, 1, 'Logout', 'User logged out', '::1', '2025-11-30 15:28:27'),
(71, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 15:28:31'),
(72, 1, 'Logout', 'User logged out', '::1', '2025-11-30 15:34:50'),
(73, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 15:34:52'),
(74, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-11-30 15:35:39'),
(75, 1, 'Login', 'User logged in successfully', '::1', '2025-11-30 15:49:53'),
(76, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 07:24:34'),
(77, 1, 'Light Control', 'Toggled light #1 to OFF', '::1', '2025-12-01 07:25:48'),
(78, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-01 07:29:32'),
(79, 1, 'Light Control', 'Toggled light #1 to OFF', '::1', '2025-12-01 07:51:37'),
(80, 1, 'Light Control', 'Toggled light #1 to OFF', '::1', '2025-12-01 08:00:03'),
(81, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2025-12-01 08:00:10'),
(82, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-01 08:19:02'),
(83, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-01 08:19:21'),
(84, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-01 08:19:50'),
(85, 1, 'Bulk Control', 'Turned all streetlights OFF', '::1', '2025-12-01 08:33:24'),
(86, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 08:42:39'),
(87, 1, 'Light Control', 'Toggled light #1 to ON', '::1', '2025-12-01 08:43:31'),
(88, 1, 'Logout', 'User logged out', '::1', '2025-12-01 08:48:29'),
(89, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 08:54:45'),
(90, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 09:07:33'),
(91, 1, 'Logout', 'User logged out', '::1', '2025-12-01 09:07:47'),
(92, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 09:14:38'),
(93, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2025-12-01 09:15:01'),
(94, 1, 'Logout', 'User logged out', '::1', '2025-12-01 09:15:31'),
(95, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 09:20:59'),
(96, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 09:22:14'),
(97, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 09:27:36'),
(98, 1, 'Logout', 'User logged out', '::1', '2025-12-01 09:31:25'),
(99, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 09:31:27'),
(100, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 09:43:55'),
(101, 1, 'Logout', 'User logged out', '::1', '2025-12-01 10:00:37'),
(102, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 10:02:45'),
(103, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 10:08:42'),
(104, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 10:09:47'),
(105, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 10:10:30'),
(106, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 10:11:03'),
(107, 1, 'Logout', 'User logged out', '::1', '2025-12-01 10:20:45'),
(108, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-01 10:30:27'),
(109, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 11:04:47'),
(110, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 13:45:20'),
(111, 1, 'Login', 'User logged in successfully', '::1', '2025-12-01 21:35:51'),
(112, 1, 'Bulk Control', 'Turned all streetlights OFF', '::1', '2025-12-01 22:18:56'),
(113, 1, 'Bulk Control', 'Turned all streetlights OFF', '::1', '2025-12-01 22:19:08'),
(114, 1, 'Bulk Control', 'Turned all streetlights OFF', '::1', '2025-12-01 22:19:36'),
(115, 1, 'Bulk Control', 'Turned all streetlights OFF', '::1', '2025-12-01 22:19:49'),
(116, 1, 'Light Control', 'Toggled light #1 to ON', '::1', '2025-12-01 22:26:35'),
(117, 1, 'Login', 'User logged in successfully', '::1', '2025-12-02 05:35:27'),
(118, 1, 'Bulk Control', 'Turned all streetlights OFF', '::1', '2025-12-02 05:36:12'),
(119, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2025-12-02 05:36:21'),
(120, 1, 'Light Control', 'Toggled light #1 to OFF', '::1', '2025-12-02 05:49:24'),
(121, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2025-12-02 05:49:31'),
(122, 1, 'Bulk Control', 'Turned all streetlights OFF', '::1', '2025-12-02 05:57:11'),
(123, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2025-12-02 05:57:14'),
(124, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2025-12-02 06:06:36'),
(125, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2025-12-02 06:06:43'),
(126, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2025-12-02 06:09:38'),
(127, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2025-12-02 06:10:11'),
(128, 1, 'Thresholds Updated', 'Updated predictive maintenance thresholds', '::1', '2025-12-02 07:13:18'),
(129, 1, 'Thresholds Updated', 'Updated predictive maintenance thresholds', '::1', '2025-12-02 07:13:54'),
(130, 1, 'Work Order Created', 'Work order created for alert #2', '::1', '2025-12-02 07:14:51'),
(131, 1, 'Work Order Created', 'Work order created for alert #3', '::1', '2025-12-02 07:14:57'),
(132, 1, 'Work Order Created', 'Work order created for alert #1', '::1', '2025-12-02 07:15:01'),
(133, 1, 'Work Order Updated', 'Work order #1 status changed to Completed', '::1', '2025-12-02 07:15:12'),
(134, 1, 'Work Order Updated', 'Work order #2 status changed to Completed', '::1', '2025-12-02 07:15:20'),
(135, 1, 'Work Order Updated', 'Work order #3 status changed to Scheduled', '::1', '2025-12-02 07:15:23'),
(136, 1, 'Work Order Updated', 'Work order #3 status changed to Completed', '::1', '2025-12-02 07:15:30'),
(137, 1, 'Login', 'User logged in successfully', '::1', '2025-12-02 07:22:40'),
(138, 1, 'Login', 'User logged in successfully', '::1', '2025-12-02 07:23:26'),
(139, 1, 'Work Order Created', 'Work order created for alert #4', '::1', '2025-12-02 08:46:11'),
(140, 1, 'Work Order Updated', 'Work order #4 status changed to In Progress', '::1', '2025-12-02 08:46:19'),
(141, 1, 'Work Order Updated', 'Work order #4 status changed to Completed', '::1', '2025-12-02 08:46:26'),
(142, 1, 'Logout', 'User logged out', '::1', '2025-12-02 08:47:59'),
(143, 1, 'Login', 'User logged in successfully', '::1', '2025-12-02 08:48:05'),
(144, 1, 'Light Control', 'Toggled light #1 to OFF', '::1', '2025-12-02 10:05:51'),
(145, 1, 'Bulk Control', 'Turned all streetlights OFF', '::1', '2025-12-02 10:05:51'),
(146, 1, 'Light Control', 'Toggled light #1 to ON', '::1', '2025-12-02 10:05:56'),
(147, 1, 'Logout', 'User logged out', '::1', '2025-12-02 10:13:56'),
(148, 1, 'Login', 'User logged in successfully', '::1', '2025-12-02 10:18:19'),
(149, 1, 'Light Control', 'Toggled light #12 to ON', '::1', '2025-12-02 10:31:14'),
(150, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-02 10:42:38'),
(151, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-02 10:42:55'),
(152, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-02 10:43:05'),
(153, 1, 'Login', 'User logged in successfully', '::1', '2025-12-04 08:14:31'),
(154, 1, 'Light Control', 'Toggled light #1 to OFF', '::1', '2025-12-04 08:15:29'),
(155, 1, 'Login', 'User logged in successfully', '::1', '2025-12-05 20:14:48'),
(156, 1, 'Logout', 'User logged out', '::1', '2025-12-06 13:35:25'),
(157, 1, 'Login', 'User logged in successfully', '::1', '2025-12-06 13:35:28'),
(158, 1, 'Login', 'User logged in successfully', '::1', '2025-12-06 13:55:12'),
(159, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 12:19:18'),
(160, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 12:24:13'),
(161, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 12:25:03'),
(162, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 12:26:17'),
(163, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 12:38:34'),
(164, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 12:41:45'),
(165, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 13:21:42'),
(166, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 13:30:13'),
(167, 1, 'Logout', 'User logged out', '::1', '2025-12-07 13:40:19'),
(168, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 13:43:49'),
(169, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-07 13:57:33'),
(170, 1, 'Logout', 'User logged out', '::1', '2025-12-07 13:57:36'),
(171, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 13:57:38'),
(172, 1, 'Logout', 'User logged out', '::1', '2025-12-07 13:57:43'),
(173, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 13:57:46'),
(174, 1, 'Logout', 'User logged out', '::1', '2025-12-07 13:58:07'),
(175, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 13:58:09'),
(176, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-07 13:58:36'),
(177, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-07 14:00:01'),
(178, 1, 'Logout', 'User logged out', '::1', '2025-12-07 14:00:07'),
(179, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 14:00:09'),
(180, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-07 14:04:09'),
(181, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-07 14:08:00'),
(182, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-07 14:08:26'),
(183, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-07 14:10:43'),
(184, 1, 'Logout', 'User logged out', '::1', '2025-12-07 14:10:52'),
(185, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 14:10:53'),
(186, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-07 14:12:41'),
(187, 1, 'Logout', 'User logged out', '::1', '2025-12-07 14:12:43'),
(188, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 14:12:45'),
(189, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-07 14:14:06'),
(190, 1, 'Logout', 'User logged out', '::1', '2025-12-07 14:14:08'),
(191, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 14:16:41'),
(192, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-07 14:22:33'),
(193, 1, 'Logout', 'User logged out', '::1', '2025-12-07 14:22:54'),
(194, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 14:22:56'),
(195, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 17:00:38'),
(196, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-07 17:05:56'),
(197, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-07 17:06:10'),
(198, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-07 17:15:05'),
(199, 1, 'Light Control', 'Toggled light #12 to OFF', '::1', '2025-12-07 17:46:38'),
(200, 1, 'Light Control', 'Toggled light #1 to ON', '::1', '2025-12-07 17:46:42'),
(201, 1, 'Logout', 'User logged out', '::1', '2025-12-07 17:54:37'),
(202, 1, 'Login', 'User logged in successfully', '::1', '2025-12-07 17:56:54'),
(203, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2025-12-07 18:03:15'),
(204, 1, 'Login', 'User logged in successfully', '::1', '2025-12-17 12:03:46'),
(205, 1, 'Login', 'User logged in successfully', '::1', '2025-12-19 05:13:39'),
(206, 1, 'Login', 'User logged in successfully', '::1', '2026-01-22 12:24:51'),
(207, 1, 'Login', 'User logged in successfully', '::1', '2026-01-22 12:30:32'),
(208, 1, 'Diagnostics', 'Ran self-check on SL-001', '::1', '2026-01-22 15:18:41'),
(209, 1, 'Login', 'User logged in successfully', '::1', '2026-01-26 10:30:56'),
(210, 1, 'Login', 'User logged in successfully', '::1', '2026-02-23 11:42:57'),
(211, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2026-02-23 11:43:49'),
(212, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2026-02-24 04:37:44'),
(213, 1, 'Schedule Created', 'Created schedule: Day Mode', '::1', '2026-02-24 05:07:04'),
(214, 1, 'Logout', 'User logged out', '::1', '2026-02-24 06:14:34'),
(215, 1, 'Login', 'User logged in successfully', '::1', '2026-02-24 06:16:22'),
(216, 1, 'Work Order', 'Created scheduled work order for light #7', '::1', '2026-02-24 13:22:55'),
(217, 1, 'Work Order Updated', 'Work order #5 status changed to Completed', '::1', '2026-02-24 13:23:56'),
(218, 1, 'Work Order', 'Created scheduled work order for light #11', '::1', '2026-02-24 13:24:09'),
(219, 1, 'Camera Config', 'Updated settings for Camera #1', '::1', '2026-02-24 14:16:05'),
(220, 1, 'Work Order', 'Created scheduled work order for light #3', '::1', '2026-02-24 14:16:31'),
(221, 1, 'Bulk Control', 'Turned all streetlights ON at 70%', '::1', '2026-02-24 16:45:31'),
(222, 1, 'Bulk Control', 'Turned all streetlights ON at 75%', '::1', '2026-02-24 16:57:05'),
(223, 1, 'Bulk Control', 'Turned all streetlights ON at 100%', '::1', '2026-02-24 16:57:58'),
(224, 1, 'Bulk Control', 'Turned all streetlights ON at 50%', '::1', '2026-02-24 17:04:53'),
(225, 1, 'Bulk Control', 'Turned all streetlights ON at 10%', '::1', '2026-02-24 17:05:15'),
(226, 1, 'Bulk Control', 'Turned all streetlights ON at 50%', '::1', '2026-02-24 17:10:27'),
(227, 1, 'Bulk Control', 'Turned all streetlights ON at 50%', '::1', '2026-02-24 17:18:50'),
(228, 1, 'Bulk Control', 'Turned all streetlights ON at 50%', '::1', '2026-02-24 17:20:15'),
(229, 1, 'Logout', 'User logged out', '::1', '2026-02-24 17:20:28'),
(230, 1, 'Login', 'User logged in successfully', '::1', '2026-02-24 17:20:35'),
(231, 1, 'Logout', 'User logged out', '::1', '2026-02-24 17:21:41'),
(232, 1, 'Login', 'User logged in successfully', '::1', '2026-02-24 17:21:52'),
(233, 1, 'Logout', 'User logged out', '::1', '2026-02-24 17:22:42'),
(234, 1, 'Login', 'User logged in successfully', '::1', '2026-02-24 17:22:51'),
(235, 1, 'Logout', 'User logged out', '::1', '2026-02-24 17:23:23'),
(236, 1, 'Login', 'User logged in successfully', '::1', '2026-02-24 17:23:34'),
(237, 1, 'Logout', 'User logged out', '::1', '2026-02-24 17:31:43'),
(238, 1, 'Login', 'User logged in successfully', '::1', '2026-02-24 17:31:52'),
(239, 1, 'Logout', 'User logged out', '::1', '2026-02-24 17:33:54'),
(240, 1, 'Login', 'User logged in successfully', '::1', '2026-02-24 17:34:00'),
(241, 1, 'Schedule Updated', 'Updated schedule: Day Mode', '::1', '2026-02-24 17:42:23'),
(242, 1, 'Work Order', 'Created scheduled work order for light #6', '::1', '2026-02-24 17:50:07'),
(243, 1, 'Work Order Updated', 'Work order #6 status changed to Completed', '::1', '2026-02-24 17:50:25'),
(244, 1, 'Schedule Created', 'Created schedule: sadsda', '::1', '2026-02-24 17:50:36'),
(245, 1, 'System Preferences Updated', 'Updated system preferences and branding', '::1', '2026-02-24 17:50:52'),
(246, 1, 'Work Order', 'Created scheduled work order for light #11', '::1', '2026-02-24 17:51:09'),
(247, 1, 'Camera Added', 'Added new camera: cam -006', '::1', '2026-02-24 17:51:35'),
(248, 1, 'Bulk Control', 'Turned all streetlights ON at 50%', '::1', '2026-02-24 17:51:52'),
(249, 1, 'Bulk Control', 'Turned all streetlights OFF', '::1', '2026-02-24 17:51:56'),
(250, 1, 'Diagnostics', 'Ran self-check on SL-001', '::1', '2026-02-24 17:52:02'),
(251, 1, 'Work Order', 'Created scheduled work order for light #2', '::1', '2026-02-24 17:52:44'),
(252, 1, 'Work Order Updated', 'Work order #7 status changed to Scheduled', '::1', '2026-02-24 17:54:07'),
(253, 1, 'Work Order Updated', 'Work order #8 status changed to Cancelled', '::1', '2026-02-24 17:54:26'),
(254, 1, 'Schedule Deleted', 'Deleted schedule: sadsda', '::1', '2026-02-24 17:56:49'),
(255, 1, 'Camera Config', 'Updated settings for Camera #5', '::1', '2026-02-24 17:58:10'),
(256, 1, 'Schedule Updated', 'Updated schedule: Day Mode', '::1', '2026-02-24 18:01:38'),
(257, 1, 'Camera Deleted', 'Deleted camera ID: 5', '::1', '2026-02-24 18:05:06'),
(258, 1, 'Diagnostics', 'Ran self-check on SL-002', '::1', '2026-02-24 18:08:24'),
(259, 1, 'Diagnostics', 'Ran self-check on SL-032', '::1', '2026-02-24 18:09:26'),
(260, 1, 'Diagnostics', 'Ran self-check on SL-002', '::1', '2026-02-24 18:09:31'),
(261, 1, 'Diagnostics', 'Ran self-check on SL-004', '::1', '2026-02-24 18:09:42'),
(262, 1, 'Diagnostics', 'Ran self-check on SL-009', '::1', '2026-02-24 18:09:53'),
(263, 1, 'Diagnostics', 'Ran self-check on SL-019', '::1', '2026-02-24 18:10:42'),
(264, 1, 'Bulk Control', 'Turned all streetlights ON at 50%', '::1', '2026-02-24 18:22:05'),
(265, 1, 'Logout', 'User logged out', '::1', '2026-02-24 18:26:55'),
(266, 1, 'Login', 'User logged in successfully', '::1', '2026-02-24 18:29:37'),
(267, 1, 'Logout', 'User logged out', '::1', '2026-02-24 18:29:52'),
(268, 1, 'Login', 'User logged in successfully', '::1', '2026-02-24 18:30:01'),
(269, 1, 'Logout', 'User logged out', '::1', '2026-02-24 18:30:09'),
(270, 1, 'Login', 'User logged in successfully', '::1', '2026-02-24 18:34:58'),
(271, 1, 'Bulk Control', 'Turned all streetlights ON at 50%', '::1', '2026-02-24 18:35:45'),
(272, 1, 'Camera Added', 'Added new camera: Cam sadasd', '::1', '2026-02-24 18:36:06'),
(273, 1, 'Camera Deleted', 'Deleted camera ID: 6', '::1', '2026-02-24 18:36:27'),
(274, 1, 'Bulk Control', 'Turned all streetlights ON at 75%', '::1', '2026-02-24 18:37:30');

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `alert_id` int(11) NOT NULL,
  `light_id` int(11) NOT NULL,
  `alert_type` enum('Fault','Warning','Predictive') NOT NULL,
  `severity` enum('Low','Medium','High') NOT NULL,
  `description` text NOT NULL,
  `status` enum('Open','Acknowledged','Resolved') DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `rul_estimate` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alerts`
--

INSERT INTO `alerts` (`alert_id`, `light_id`, `alert_type`, `severity`, `description`, `status`, `created_at`, `acknowledged_at`, `acknowledged_by`, `resolved_at`, `rul_estimate`) VALUES
(1, 1, 'Predictive', 'Medium', 'Low voltage detected: 1.798V (threshold: 2V). Battery may need replacement.', 'Resolved', '2025-12-01 07:24:34', '2025-12-02 07:15:01', 1, '2026-02-24 17:50:25', '14 days'),
(2, 1, 'Fault', 'High', 'Low brightness detected: 0 lux (threshold: 20 lux). Lamp may be aging.', 'Resolved', '2025-12-01 07:32:13', '2025-12-02 07:14:51', 1, '2026-02-24 13:23:56', NULL),
(3, 1, 'Fault', 'High', 'Lamp fault detected on SL-001', 'Resolved', '2025-12-01 08:40:15', '2025-12-02 07:14:57', 1, '2025-12-02 07:15:20', NULL),
(4, 1, 'Fault', 'High', 'Low voltage detected: 0.142V (threshold: 2V). Battery may need replacement.', 'Resolved', '2025-12-02 07:23:26', '2025-12-02 08:46:11', 1, '2025-12-02 08:46:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cameras`
--

CREATE TABLE `cameras` (
  `camera_id` int(11) NOT NULL,
  `camera_name` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('Online','Offline','Maintenance') DEFAULT 'Online',
  `resolution` varchar(20) DEFAULT '1920x1080',
  `fps` int(11) DEFAULT 25,
  `nvr_ip` varchar(50) DEFAULT NULL,
  `nvr_port` int(11) DEFAULT 554,
  `channel` int(11) DEFAULT 1,
  `username` varchar(50) DEFAULT 'admin',
  `password` varchar(100) DEFAULT NULL,
  `stream_type` enum('main','sub') DEFAULT 'main',
  `protocol` enum('rtsp','http') DEFAULT 'rtsp',
  `installation_date` date DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cameras`
--

INSERT INTO `cameras` (`camera_id`, `camera_name`, `location`, `latitude`, `longitude`, `status`, `resolution`, `fps`, `nvr_ip`, `nvr_port`, `channel`, `username`, `password`, `stream_type`, `protocol`, `installation_date`, `last_maintenance`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'CAM-001', 'Barangay Hall Main Entrance', 14.57940000, 121.03590000, 'Online', '1920x1080', 25, '192.168.1.64', 554, 1, 'admin', 'admin123', 'main', 'rtsp', '2024-11-15', NULL, NULL, '2026-02-24 11:29:48', '2026-02-24 11:29:48'),
(2, 'CAM-002', 'Basketball Court Area', 14.57980000, 121.03620000, 'Online', '1920x1080', 25, '192.168.1.64', 554, 2, 'admin', 'admin123', 'main', 'rtsp', '2024-11-15', NULL, NULL, '2026-02-24 11:29:48', '2026-02-24 11:29:48'),
(3, 'CAM-003', 'Covered Court & Stage', 14.57920000, 121.03650000, 'Online', '1920x1080', 25, '192.168.1.64', 554, 3, 'admin', 'admin123', 'main', 'rtsp', '2024-11-15', NULL, NULL, '2026-02-24 11:29:48', '2026-02-24 11:29:48'),
(4, 'CAM-004', 'Street View - Main Road', 14.57960000, 121.03680000, 'Online', '1920x1080', 25, '192.168.1.64', 554, 4, 'admin', 'admin123', 'main', 'rtsp', '2024-11-15', NULL, NULL, '2026-02-24 11:29:48', '2026-02-24 11:29:48');

-- --------------------------------------------------------

--
-- Table structure for table `camera_events`
--

CREATE TABLE `camera_events` (
  `event_id` int(11) NOT NULL,
  `camera_id` int(11) NOT NULL,
  `event_type` enum('Motion Detected','Connection Lost','Recording Started','Recording Stopped','Alert') DEFAULT 'Motion Detected',
  `description` text DEFAULT NULL,
  `snapshot_id` int(11) DEFAULT NULL,
  `severity` enum('Low','Medium','High','Critical') DEFAULT 'Low',
  `event_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved` tinyint(1) DEFAULT 0,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `camera_events`
--

INSERT INTO `camera_events` (`event_id`, `camera_id`, `event_type`, `description`, `snapshot_id`, `severity`, `event_time`, `resolved`, `resolved_at`, `resolved_by`) VALUES
(1, 1, 'Motion Detected', 'Motion detected at main entrance', NULL, 'Low', '2026-02-24 10:29:48', 0, NULL, NULL),
(2, 2, 'Motion Detected', 'Activity in basketball court area', NULL, 'Low', '2026-02-24 10:44:48', 0, NULL, NULL),
(3, 1, 'Alert', 'Unusual activity detected after hours', NULL, 'High', '2026-02-24 10:59:48', 0, NULL, NULL),
(4, 3, 'Motion Detected', 'Movement near covered court', NULL, 'Low', '2026-02-24 11:14:48', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `camera_snapshots`
--

CREATE TABLE `camera_snapshots` (
  `snapshot_id` int(11) NOT NULL,
  `camera_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(500) NOT NULL,
  `filesize` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `camera_snapshots`
--

INSERT INTO `camera_snapshots` (`snapshot_id`, `camera_id`, `filename`, `filepath`, `filesize`, `created_at`, `created_by`, `notes`) VALUES
(1, 1, 'snapshot_cam1_20251209120000.jpg', 'snapshots/snapshot_cam1_20251209120000.jpg', NULL, '2026-02-24 09:29:48', NULL, NULL),
(2, 2, 'snapshot_cam2_20251209130000.jpg', 'snapshots/snapshot_cam2_20251209130000.jpg', NULL, '2026-02-24 10:29:48', NULL, NULL),
(3, 3, 'snapshot_cam3_20251209140000.jpg', 'snapshots/snapshot_cam3_20251209140000.jpg', NULL, '2026-02-24 10:59:48', NULL, NULL),
(4, 4, 'snapshot_cam4_20251209143000.jpg', 'snapshots/snapshot_cam4_20251209143000.jpg', NULL, '2026-02-24 11:14:48', NULL, NULL),
(5, 1, 'snapshot_cam1_20251222120000.jpg', 'snapshots/snapshot_cam1_20251222120000.jpg', NULL, '2026-02-24 09:30:04', NULL, NULL),
(6, 2, 'snapshot_cam2_20251222130000.jpg', 'snapshots/snapshot_cam2_20251222130000.jpg', NULL, '2026-02-24 10:30:04', NULL, NULL),
(7, 3, 'snapshot_cam3_20251222140000.jpg', 'snapshots/snapshot_cam3_20251222140000.jpg', NULL, '2026-02-24 11:00:04', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cctv_cameras`
--

CREATE TABLE `cctv_cameras` (
  `camera_id` int(11) NOT NULL,
  `camera_name` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `stream_url` varchar(500) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('Online','Offline','Maintenance') DEFAULT 'Online',
  `resolution` varchar(20) DEFAULT NULL,
  `fps` int(11) DEFAULT 15,
  `installation_date` date DEFAULT NULL,
  `last_checked` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cctv_cameras`
--

INSERT INTO `cctv_cameras` (`camera_id`, `camera_name`, `location`, `stream_url`, `latitude`, `longitude`, `status`, `resolution`, `fps`, `installation_date`, `last_checked`) VALUES
(1, 'CAM-01', 'Main Street Entrance', NULL, 14.65070000, 121.04940000, 'Online', '1080p', 15, '2024-01-15', '2025-11-11 16:48:22'),
(2, 'CAM-02', 'Barangay Hall Front', NULL, 14.65130000, 121.05080000, 'Online', '1080p', 15, '2024-01-17', '2025-11-11 16:48:22'),
(3, 'CAM-03', 'Market Area Overview', NULL, 14.65200000, 121.05150000, 'Online', '1080p', 15, '2024-01-18', '2025-11-11 16:48:22'),
(4, 'CAM-04', 'Sports Complex Entrance', NULL, 14.65250000, 121.05200000, 'Online', '1080p', 15, '2024-01-20', '2025-11-11 16:48:22');

-- --------------------------------------------------------

--
-- Table structure for table `cctv_footage`
--

CREATE TABLE `cctv_footage` (
  `footage_id` bigint(20) NOT NULL,
  `camera_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `duration` int(11) DEFAULT NULL,
  `event_type` enum('Continuous','Motion','Alert','Manual') DEFAULT 'Continuous',
  `cloud_backup_status` enum('Pending','Uploaded','Failed') DEFAULT 'Pending',
  `uploaded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `diagnostic_logs`
--

CREATE TABLE `diagnostic_logs` (
  `diagnostic_id` int(11) NOT NULL,
  `light_id` int(11) NOT NULL,
  `test_type` varchar(100) NOT NULL,
  `result` text NOT NULL,
  `notes` text DEFAULT NULL,
  `tested_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `diagnostic_logs`
--

INSERT INTO `diagnostic_logs` (`diagnostic_id`, `light_id`, `test_type`, `result`, `notes`, `tested_at`) VALUES
(1, 1, 'Self-Check', '{\"power_test\":\"PASS\",\"sensor_test\":\"PASS\",\"connectivity_test\":\"PASS\",\"dimming_test\":\"PASS\",\"timestamp\":\"2025-12-06 10:30:00\"}', 'Automated self-check - all systems nominal', '2025-12-06 02:30:00'),
(2, 2, 'Self-Check', '{\"power_test\":\"PASS\",\"sensor_test\":\"FAIL\",\"connectivity_test\":\"PASS\",\"dimming_test\":\"PASS\",\"timestamp\":\"2025-12-05 14:15:00\"}', 'Sensor malfunction detected - requires maintenance', '2025-12-05 06:15:00'),
(3, 3, 'Manual Test', '{\"power_test\":\"PASS\",\"sensor_test\":\"PASS\",\"connectivity_test\":\"PASS\",\"dimming_test\":\"PASS\",\"timestamp\":\"2025-12-04 09:00:00\"}', 'Manual test after repair', '2025-12-04 01:00:00'),
(4, 1, 'Self-Check', '{\"power_test\":\"PASS\",\"sensor_test\":\"PASS\",\"connectivity_test\":\"FAIL\",\"dimming_test\":\"PASS\",\"timestamp\":\"2026-01-22 23:18:41\"}', 'Automated self-check diagnostic test', '2026-01-22 15:18:41'),
(5, 1, 'Self-Check', '{\"power_test\":\"PASS\",\"sensor_test\":\"PASS\",\"connectivity_test\":\"FAIL\",\"dimming_test\":\"FAIL\",\"timestamp\":\"2026-02-25 01:52:02\"}', 'Automated self-check diagnostic test', '2026-02-24 17:52:02'),
(6, 2, 'Self-Check', '{\"power_test\":\"PASS\",\"sensor_test\":\"FAIL\",\"connectivity_test\":\"FAIL\",\"dimming_test\":\"FAIL\",\"timestamp\":\"2026-02-25 02:08:24\"}', 'Automated self-check diagnostic test', '2026-02-24 18:08:24'),
(7, 32, 'Self-Check', '{\"power_test\":\"PASS\",\"sensor_test\":\"FAIL\",\"connectivity_test\":\"FAIL\",\"dimming_test\":\"FAIL\",\"timestamp\":\"2026-02-25 02:09:26\"}', 'Automated self-check diagnostic test', '2026-02-24 18:09:26'),
(8, 2, 'Self-Check', '{\"power_test\":\"FAIL\",\"sensor_test\":\"PASS\",\"connectivity_test\":\"PASS\",\"dimming_test\":\"FAIL\",\"timestamp\":\"2026-02-25 02:09:31\"}', 'Automated self-check diagnostic test', '2026-02-24 18:09:31'),
(9, 4, 'Self-Check', '{\"power_test\":\"FAIL\",\"sensor_test\":\"FAIL\",\"connectivity_test\":\"FAIL\",\"dimming_test\":\"FAIL\",\"timestamp\":\"2026-02-25 02:09:42\"}', 'Automated self-check diagnostic test', '2026-02-24 18:09:42'),
(10, 9, 'Self-Check', '{\"power_test\":\"FAIL\",\"sensor_test\":\"FAIL\",\"connectivity_test\":\"FAIL\",\"dimming_test\":\"FAIL\",\"timestamp\":\"2026-02-25 02:09:53\"}', 'Automated self-check diagnostic test', '2026-02-24 18:09:53'),
(11, 19, 'Self-Check', '{\"power_test\":\"FAIL\",\"sensor_test\":\"FAIL\",\"connectivity_test\":\"FAIL\",\"dimming_test\":\"FAIL\",\"timestamp\":\"2026-02-25 02:10:42\"}', 'Automated self-check diagnostic test', '2026-02-24 18:10:42');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_logs`
--

CREATE TABLE `maintenance_logs` (
  `log_id` int(11) NOT NULL,
  `light_id` int(11) NOT NULL,
  `alert_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `action_taken` text NOT NULL,
  `notes` text DEFAULT NULL,
  `parts_replaced` text DEFAULT NULL,
  `maintenance_date` datetime DEFAULT current_timestamp(),
  `completion_time` int(11) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_logs`
--

INSERT INTO `maintenance_logs` (`log_id`, `light_id`, `alert_id`, `user_id`, `action_taken`, `notes`, `parts_replaced`, `maintenance_date`, `completion_time`, `cost`, `status`) VALUES
(1, 1, 2, 1, 'sdasas', 'asdasdasd', 'sadasd', '2025-12-02 15:14:00', 0, 0.00, 'Completed'),
(2, 1, 3, 1, 'asdasd', 'asdasd', 'asdasd', '2025-12-02 15:14:00', 0, 0.00, 'Completed'),
(3, 1, 1, 1, 'asdasd', 'asdasd', 'sadasd', '2025-12-02 15:14:00', 0, 0.00, 'Completed'),
(4, 1, 4, 1, 'Need work', 'asdasd', '', '2025-12-02 16:46:00', 0, 0.00, 'Completed'),
(5, 7, 2, 1, 'sdasd', 'asdasd', 'Led', '2026-02-24 21:22:55', 20, 100.00, 'Completed'),
(6, 11, 1, 1, 'sadsd', 'asdasd', 'fddsf', '2026-02-24 21:24:09', 45, NULL, 'Completed'),
(7, 3, 2, 1, 'sdfsdfsdf', 'sdfsdfsdfsd', 'ddgdfg', '2026-02-24 22:16:31', 45, NULL, 'Scheduled'),
(8, 6, 2, 1, 'sadasdasd', 'fggredgf', 'erwer', '2026-02-25 01:50:07', 90, NULL, 'Cancelled'),
(9, 11, 3, 1, 'dfsdf', 'gfhghfg', NULL, '2026-02-25 01:51:09', NULL, NULL, 'Scheduled'),
(10, 2, 2, 1, 'asdasdf', 'dsfsdf', NULL, '2026-02-25 01:52:44', NULL, NULL, 'Scheduled');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_presets`
--

CREATE TABLE `schedule_presets` (
  `schedule_id` int(11) NOT NULL,
  `preset_name` varchar(100) NOT NULL,
  `time_on` time NOT NULL,
  `time_off` time NOT NULL,
  `dimming_level` int(11) DEFAULT 70,
  `days_of_week` varchar(50) DEFAULT 'Mon,Tue,Wed,Thu,Fri,Sat,Sun',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_presets`
--

INSERT INTO `schedule_presets` (`schedule_id`, `preset_name`, `time_on`, `time_off`, `dimming_level`, `days_of_week`, `is_active`, `created_at`, `created_by`) VALUES
(1, 'Default Night Schedule', '18:00:00', '06:00:00', 70, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', 1, '2025-11-11 16:48:22', 1),
(2, 'Day Mode', '06:00:00', '21:00:00', 70, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', 1, '2026-02-24 05:07:04', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sensor_data`
--

CREATE TABLE `sensor_data` (
  `data_id` bigint(20) NOT NULL,
  `light_id` int(11) NOT NULL,
  `brightness_level` decimal(5,2) DEFAULT NULL,
  `current_consumption` decimal(6,3) DEFAULT NULL,
  `voltage` decimal(5,2) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sensor_data`
--

INSERT INTO `sensor_data` (`data_id`, `light_id`, `brightness_level`, `current_consumption`, `voltage`, `temperature`, `timestamp`) VALUES
(1, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:21:22'),
(2, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:22:49'),
(3, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:23:27'),
(4, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:25:24'),
(5, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:25:26'),
(6, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:26:07'),
(7, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:26:39'),
(8, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:27:10'),
(9, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:27:36'),
(10, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:28:07'),
(11, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:28:39'),
(12, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:29:10'),
(13, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:29:40'),
(14, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:30:12'),
(15, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:30:42'),
(16, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:31:05'),
(17, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:32:11'),
(18, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:32:30'),
(19, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:46:30'),
(20, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:46:31'),
(21, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:46:32'),
(22, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:46:42'),
(23, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:49:21'),
(24, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:49:50'),
(25, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:52:40'),
(26, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:52:54'),
(27, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:53:16'),
(28, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:54:43'),
(29, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 14:57:28'),
(30, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:10:23'),
(31, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:11:22'),
(32, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:11:48'),
(33, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:12:18'),
(34, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:12:49'),
(35, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:13:19'),
(36, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:13:48'),
(37, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:13:49'),
(38, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:14:20'),
(39, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:14:49'),
(40, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:15:19'),
(41, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:15:49'),
(42, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:15:50'),
(43, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:16:20'),
(44, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:16:50'),
(45, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:17:20'),
(46, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:17:35'),
(47, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:17:36'),
(48, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:19:43'),
(49, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:19:44'),
(50, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:20:22'),
(51, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:20:53'),
(52, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:21:24'),
(53, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:21:53'),
(54, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:22:23'),
(55, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:22:23'),
(56, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:22:55'),
(57, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:23:24'),
(58, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:23:54'),
(59, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:24:24'),
(60, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:24:24'),
(61, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:24:55'),
(62, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:27:58'),
(63, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:28:16'),
(64, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:28:31'),
(65, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:29:03'),
(66, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:29:33'),
(67, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:30:02'),
(68, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:30:32'),
(69, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:30:32'),
(70, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:31:03'),
(71, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:31:33'),
(72, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:32:03'),
(73, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:32:33'),
(74, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:32:33'),
(75, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:33:04'),
(76, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:33:34'),
(77, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:34:04'),
(78, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:34:34'),
(79, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:34:34'),
(80, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:34:52'),
(81, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:35:06'),
(82, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:44:09'),
(83, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:44:30'),
(84, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:45:00'),
(85, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:45:30'),
(86, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:46:00'),
(87, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:46:30'),
(88, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:46:31'),
(89, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:47:01'),
(90, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:47:32'),
(91, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:48:01'),
(92, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:48:31'),
(93, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:48:31'),
(94, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:48:39'),
(95, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:48:40'),
(96, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:48:48'),
(97, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:49:18'),
(98, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:49:53'),
(99, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:50:01'),
(100, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:50:23'),
(101, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:50:24'),
(102, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:50:53'),
(103, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:50:54'),
(104, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:51:23'),
(105, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:51:24'),
(106, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:51:53'),
(107, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:51:54'),
(108, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:51:54'),
(109, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:52:24'),
(110, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:52:25'),
(111, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:52:54'),
(112, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:52:55'),
(113, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:53:24'),
(114, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:53:24'),
(115, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:53:54'),
(116, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:53:54'),
(117, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:53:55'),
(118, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:54:24'),
(119, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:54:25'),
(120, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:54:54'),
(121, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:54:55'),
(122, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:55:24'),
(123, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:55:25'),
(124, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:55:54'),
(125, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:55:55'),
(126, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:55:56'),
(127, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:56:24'),
(128, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:56:26'),
(129, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:56:54'),
(130, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:56:56'),
(131, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:57:24'),
(132, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:57:26'),
(133, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:57:54'),
(134, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:57:56'),
(135, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:57:56'),
(136, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:58:24'),
(137, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:58:27'),
(138, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:58:57'),
(139, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:59:12'),
(140, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:59:27'),
(141, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:59:57'),
(142, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 15:59:58'),
(143, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:00:12'),
(144, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:00:28'),
(145, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:00:58'),
(146, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:01:12'),
(147, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:01:28'),
(148, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:01:58'),
(149, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:01:59'),
(150, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:02:12'),
(151, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:02:29'),
(152, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:02:59'),
(153, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:03:12'),
(154, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:03:29'),
(155, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:03:59'),
(156, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:03:59'),
(157, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:04:12'),
(158, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:04:30'),
(159, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:05:00'),
(160, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:05:12'),
(161, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:05:30'),
(162, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:06:00'),
(163, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:06:01'),
(164, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:06:12'),
(165, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:06:31'),
(166, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:07:01'),
(167, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:07:12'),
(168, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:07:31'),
(169, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:08:01'),
(170, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:08:01'),
(171, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:08:13'),
(172, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:08:32'),
(173, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:09:02'),
(174, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:09:12'),
(175, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:09:32'),
(176, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:10:02'),
(177, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:10:03'),
(178, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:10:12'),
(179, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:10:33'),
(180, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:11:04'),
(181, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:11:12'),
(182, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:11:33'),
(183, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:12:03'),
(184, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:12:04'),
(185, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:12:12'),
(186, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:12:34'),
(187, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:13:04'),
(188, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:13:12'),
(189, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:13:34'),
(190, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:14:04'),
(191, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:14:04'),
(192, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:14:12'),
(193, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:14:35'),
(194, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:15:05'),
(195, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:15:12'),
(196, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:15:35'),
(197, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:16:05'),
(198, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:16:06'),
(199, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:16:12'),
(200, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:16:37'),
(201, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:17:07'),
(202, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:32:37'),
(203, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:33:01'),
(204, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:48:57'),
(205, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:48:57'),
(206, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:49:03'),
(207, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:57:11'),
(208, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:57:40'),
(209, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:57:45'),
(210, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:58:10'),
(211, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:58:40'),
(212, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:58:41'),
(213, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:58:45'),
(214, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:59:12'),
(215, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:59:41'),
(216, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 16:59:45'),
(217, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:00:11'),
(218, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:00:41'),
(219, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:00:41'),
(220, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:00:45'),
(221, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:01:12'),
(222, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:01:42'),
(223, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:01:45'),
(224, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:02:12'),
(225, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:02:42'),
(226, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:02:42'),
(227, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:02:45'),
(228, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:03:13'),
(229, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:03:43'),
(230, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:03:45'),
(231, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:04:13'),
(232, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:04:43'),
(233, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:04:43'),
(234, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:04:45'),
(235, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:05:14'),
(236, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:05:44'),
(237, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:05:45'),
(238, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:06:14'),
(239, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:06:44'),
(240, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:06:44'),
(241, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:06:45'),
(242, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:07:16'),
(243, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:07:45'),
(244, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:07:46'),
(245, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:08:15'),
(246, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:08:45'),
(247, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:08:46'),
(248, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:08:46'),
(249, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:09:17'),
(250, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:09:45'),
(251, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:09:47'),
(252, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:10:16'),
(253, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:10:45'),
(254, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:10:46'),
(255, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:10:47'),
(256, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:11:17'),
(257, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:11:45'),
(258, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:11:47'),
(259, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:12:17'),
(260, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:12:45'),
(261, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:12:47'),
(262, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:12:47'),
(263, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:13:18'),
(264, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:13:45'),
(265, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:13:48'),
(266, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:14:18'),
(267, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:14:46'),
(268, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:14:48'),
(269, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:14:48'),
(270, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:15:19'),
(271, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:15:45'),
(272, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:15:49'),
(273, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:16:19'),
(274, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:16:45'),
(275, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:16:49'),
(276, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:16:49'),
(277, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:17:20'),
(278, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:17:45'),
(279, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:17:50'),
(280, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:18:20'),
(281, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:18:45'),
(282, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:18:50'),
(283, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:18:50'),
(284, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:19:21'),
(285, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:19:45'),
(286, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:19:51'),
(287, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:20:21'),
(288, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:20:45'),
(289, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:20:51'),
(290, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:20:52'),
(291, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:21:23'),
(292, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:21:45'),
(293, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:21:53'),
(294, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:22:22'),
(295, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:22:46'),
(296, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:22:52'),
(297, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:22:52'),
(298, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:23:23'),
(299, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:23:45'),
(300, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:23:58'),
(301, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:24:23'),
(302, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:24:46'),
(303, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:24:53'),
(304, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:24:54'),
(305, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:25:24'),
(306, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:25:45'),
(307, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:25:54'),
(308, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:26:24'),
(309, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:26:45'),
(310, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:26:54'),
(311, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:26:54'),
(312, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:27:25'),
(313, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:27:46'),
(314, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:27:55'),
(315, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:28:25'),
(316, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:28:45'),
(317, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:28:55'),
(318, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:28:55'),
(319, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:29:27'),
(320, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:29:45'),
(321, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:29:56'),
(322, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:30:26'),
(323, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:30:45'),
(324, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:30:56'),
(325, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:30:57'),
(326, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:31:27'),
(327, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:31:45'),
(328, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:31:57'),
(329, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:32:27'),
(330, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:32:46'),
(331, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:32:57'),
(332, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:32:57'),
(333, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:33:29'),
(334, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:33:45'),
(335, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:33:59'),
(336, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:34:28'),
(337, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:34:46'),
(338, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:34:58'),
(339, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:34:59'),
(340, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:35:29'),
(341, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:35:45'),
(342, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:36:00'),
(343, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:36:29'),
(344, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:36:45'),
(345, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:36:59'),
(346, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:37:00'),
(347, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:37:31'),
(348, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:37:45'),
(349, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:38:01'),
(350, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:38:30'),
(351, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:38:45'),
(352, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:39:00'),
(353, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:39:00'),
(354, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:39:32'),
(355, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:39:45'),
(356, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:40:02'),
(357, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:40:31'),
(358, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:40:45'),
(359, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:41:01'),
(360, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:41:02'),
(361, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:41:33'),
(362, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:41:45'),
(363, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:42:02'),
(364, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:42:32'),
(365, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:42:45'),
(366, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:43:02'),
(367, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:43:03'),
(368, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:43:34'),
(369, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:43:45'),
(370, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:44:04'),
(371, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:44:33'),
(372, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:44:46'),
(373, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:45:03'),
(374, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:45:04'),
(375, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:45:34'),
(376, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:45:45'),
(377, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:46:05'),
(378, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:46:34'),
(379, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:46:46'),
(380, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:47:04'),
(381, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:47:05'),
(382, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:47:35'),
(383, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:47:46'),
(384, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:48:06'),
(385, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:48:35'),
(386, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:48:46'),
(387, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:49:05'),
(388, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:49:05'),
(389, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:49:36'),
(390, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:49:45'),
(391, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:50:06'),
(392, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:50:36'),
(393, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:50:46'),
(394, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:51:06'),
(395, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:51:06'),
(396, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:51:37'),
(397, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:51:45'),
(398, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:52:08'),
(399, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:52:37'),
(400, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:52:45'),
(401, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:53:07'),
(402, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:53:08'),
(403, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:53:38'),
(404, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:53:45'),
(405, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:54:09'),
(406, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:54:38'),
(407, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:54:46'),
(408, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:55:08'),
(409, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:55:09'),
(410, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:55:39'),
(411, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:55:45'),
(412, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:56:10'),
(413, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:56:39'),
(414, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:56:45'),
(415, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:57:09'),
(416, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:57:10'),
(417, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:57:40'),
(418, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:57:45'),
(419, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:58:11'),
(420, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:58:40'),
(421, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:58:45'),
(422, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:59:10'),
(423, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:59:10'),
(424, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:59:42'),
(425, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 17:59:45'),
(426, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:00:11'),
(427, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:00:41'),
(428, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:00:46'),
(429, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:01:11'),
(430, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:01:11'),
(431, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:01:43'),
(432, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:01:45'),
(433, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:02:13'),
(434, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:02:42'),
(435, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:02:45'),
(436, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:03:12'),
(437, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:03:12'),
(438, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:03:43'),
(439, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:03:45'),
(440, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:04:14'),
(441, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:04:43'),
(442, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:04:46'),
(443, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:05:13'),
(444, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:05:13'),
(445, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:05:45'),
(446, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:05:45'),
(447, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:06:15'),
(448, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:06:44'),
(449, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:06:46'),
(450, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:07:14'),
(451, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:07:14'),
(452, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:07:46'),
(453, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:07:46'),
(454, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:08:16'),
(455, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:08:45'),
(456, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:08:46'),
(457, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:09:15'),
(458, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:09:16'),
(459, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:09:45'),
(460, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:09:47'),
(461, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:10:17'),
(462, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:10:46'),
(463, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:10:46'),
(464, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:11:16'),
(465, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:11:16'),
(466, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:11:45'),
(467, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:11:48'),
(468, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:12:18'),
(469, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:12:46'),
(470, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:12:47'),
(471, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:13:17'),
(472, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:13:18'),
(473, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:13:45'),
(474, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:13:48'),
(475, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:14:19'),
(476, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:14:45'),
(477, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:14:48'),
(478, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:15:18'),
(479, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:15:18'),
(480, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:15:45'),
(481, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:15:50'),
(482, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:16:20'),
(483, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:16:45'),
(484, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:16:49'),
(485, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:17:19'),
(486, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:17:19'),
(487, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:17:45'),
(488, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:17:50'),
(489, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:18:21'),
(490, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:18:46'),
(491, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:18:50'),
(492, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:19:20'),
(493, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:19:20'),
(494, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:19:45'),
(495, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:19:52'),
(496, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:20:22'),
(497, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:20:45'),
(498, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:20:51'),
(499, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:21:21'),
(500, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:21:22'),
(501, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:21:45'),
(502, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:21:52'),
(503, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:22:22'),
(504, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:22:46'),
(505, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:22:52'),
(506, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:23:22'),
(507, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:23:22'),
(508, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:23:45'),
(509, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:23:53'),
(510, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:24:23'),
(511, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:24:45'),
(512, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:24:53'),
(513, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:25:23'),
(514, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:25:24'),
(515, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:25:45'),
(516, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:25:54'),
(517, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:26:25'),
(518, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:26:46'),
(519, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:26:54'),
(520, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:27:24'),
(521, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:27:24'),
(522, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:27:45'),
(523, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:27:56'),
(524, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:28:26'),
(525, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:28:46'),
(526, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:28:55'),
(527, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:29:25'),
(528, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:29:26'),
(529, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:29:45'),
(530, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:29:56'),
(531, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:30:26'),
(532, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:30:45'),
(533, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:30:56'),
(534, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:31:26'),
(535, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:31:27'),
(536, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:31:45'),
(537, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:31:58'),
(538, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:32:27'),
(539, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:32:45'),
(540, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:32:57'),
(541, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:33:27'),
(542, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:33:27'),
(543, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:33:45'),
(544, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:33:58'),
(545, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:34:29'),
(546, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:34:46'),
(547, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:34:58'),
(548, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:35:28'),
(549, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:35:29'),
(550, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:35:45'),
(551, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:36:00'),
(552, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:36:29'),
(553, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:36:46'),
(554, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:36:59'),
(555, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:37:29'),
(556, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:37:29'),
(557, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:37:45'),
(558, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:38:01'),
(559, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:38:30'),
(560, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:38:45'),
(561, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:39:00'),
(562, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:39:30'),
(563, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:39:31'),
(564, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:39:45'),
(565, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:40:02'),
(566, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:40:31'),
(567, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:40:45'),
(568, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:41:01'),
(569, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:41:31'),
(570, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:41:32'),
(571, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:41:45'),
(572, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:42:03'),
(573, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:42:33'),
(574, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:42:46'),
(575, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:43:02'),
(576, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:43:32'),
(577, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:43:33'),
(578, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:43:45'),
(579, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:44:04'),
(580, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:44:33'),
(581, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:44:45'),
(582, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:45:03'),
(583, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:45:33'),
(584, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:45:34'),
(585, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:45:45'),
(586, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:46:05'),
(587, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:46:34'),
(588, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:46:45'),
(589, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:47:04'),
(590, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:47:34'),
(591, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:47:35'),
(592, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:47:46'),
(593, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:48:06'),
(594, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:48:36'),
(595, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:48:45'),
(596, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:49:05'),
(597, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:49:35'),
(598, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:49:36'),
(599, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:49:45'),
(600, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:50:07'),
(601, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:50:37'),
(602, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:50:45'),
(603, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:51:06'),
(604, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:51:36'),
(605, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:51:37'),
(606, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:51:45'),
(607, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:52:08'),
(608, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:52:41'),
(609, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:52:49'),
(610, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:53:11'),
(611, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:53:41'),
(612, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:53:41'),
(613, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:53:49'),
(614, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:54:12'),
(615, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:54:42'),
(616, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:54:49'),
(617, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:55:12'),
(618, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:55:42'),
(619, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:55:43'),
(620, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:55:49'),
(621, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:56:13'),
(622, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:56:43'),
(623, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:56:49'),
(624, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:57:13'),
(625, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:57:43'),
(626, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:57:43'),
(627, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:57:49'),
(628, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:58:14'),
(629, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:58:44'),
(630, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:58:49'),
(631, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:59:14'),
(632, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:59:44'),
(633, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:59:45'),
(634, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 18:59:49'),
(635, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:00:15'),
(636, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:00:45'),
(637, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:00:49'),
(638, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:01:15'),
(639, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:01:45'),
(640, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:01:46'),
(641, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:01:49'),
(642, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:02:16'),
(643, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:02:46'),
(644, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:02:49'),
(645, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:03:16'),
(646, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:03:46'),
(647, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:03:46'),
(648, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:03:49'),
(649, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:04:17'),
(650, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:04:48'),
(651, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:04:49'),
(652, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:05:17'),
(653, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:05:47'),
(654, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:05:47'),
(655, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:05:49'),
(656, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:06:18'),
(657, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:06:48'),
(658, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:06:49'),
(659, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:07:18'),
(660, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:07:48'),
(661, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:07:49'),
(662, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:07:49'),
(663, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:08:19'),
(664, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:08:49'),
(665, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:08:49'),
(666, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:09:19'),
(667, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:09:49'),
(668, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:09:50'),
(669, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:09:51'),
(670, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:10:20'),
(671, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:10:49'),
(672, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:10:50'),
(673, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:11:20'),
(674, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:11:49'),
(675, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:11:50'),
(676, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:11:50'),
(677, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:12:21'),
(678, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:12:49'),
(679, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:12:51'),
(680, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:13:21'),
(681, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:13:49'),
(682, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:13:51'),
(683, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:13:52'),
(684, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:14:22'),
(685, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:14:49'),
(686, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:14:53'),
(687, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:15:22'),
(688, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:15:54'),
(689, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:15:54'),
(690, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:15:54'),
(691, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:16:25'),
(692, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:16:49'),
(693, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:16:56'),
(694, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:17:25'),
(695, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:17:49'),
(696, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:17:55'),
(697, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:17:56'),
(698, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:18:26'),
(699, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:18:49'),
(700, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:18:56'),
(701, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:19:26'),
(702, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:19:50'),
(703, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:19:56'),
(704, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:19:56'),
(705, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:20:28'),
(706, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:20:49'),
(707, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:20:58'),
(708, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:21:27'),
(709, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:21:49'),
(710, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:21:57'),
(711, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:21:58'),
(712, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:22:28'),
(713, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:22:49'),
(714, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:22:59'),
(715, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:23:28'),
(716, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:23:50'),
(717, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:23:58'),
(718, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:23:59'),
(719, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:24:29'),
(720, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:24:50'),
(721, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:25:00'),
(722, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:25:29'),
(723, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:25:49'),
(724, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:25:59'),
(725, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:26:00'),
(726, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:26:30'),
(727, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:26:49'),
(728, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:27:00'),
(729, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:27:30'),
(730, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:27:49'),
(731, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:28:00'),
(732, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:28:00'),
(733, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:28:31'),
(734, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:28:49'),
(735, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:29:01'),
(736, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:29:31'),
(737, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:29:50'),
(738, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:30:01'),
(739, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:30:02'),
(740, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:30:32'),
(741, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:30:49'),
(742, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:31:02'),
(743, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:31:32'),
(744, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:31:49'),
(745, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:32:02'),
(746, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:32:03'),
(747, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:32:33'),
(748, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:32:49'),
(749, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:33:04'),
(750, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:33:33'),
(751, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:33:49'),
(752, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:34:03'),
(753, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:34:04'),
(754, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:34:34'),
(755, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:34:49'),
(756, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:35:04'),
(757, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:35:34'),
(758, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:35:49'),
(759, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:36:04'),
(760, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:36:04'),
(761, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:36:36'),
(762, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:36:49'),
(763, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:37:06'),
(764, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:37:35'),
(765, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:37:49'),
(766, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:38:05'),
(767, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:38:06'),
(768, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:38:37'),
(769, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:38:49'),
(770, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:39:07'),
(771, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:39:36'),
(772, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:39:49'),
(773, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:40:06'),
(774, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:40:06'),
(775, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:40:37'),
(776, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:40:49'),
(777, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:41:07'),
(778, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:41:37'),
(779, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:41:49'),
(780, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:42:07'),
(781, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:42:08'),
(782, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:42:38'),
(783, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:42:49'),
(784, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:43:08'),
(785, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:43:38'),
(786, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:43:49'),
(787, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:44:08'),
(788, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:44:08'),
(789, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:44:39'),
(790, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:44:49'),
(791, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:45:09'),
(792, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:45:39'),
(793, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:45:49'),
(794, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:46:09'),
(795, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:46:09'),
(796, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:46:40'),
(797, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:46:49'),
(798, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:47:11'),
(799, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:47:40'),
(800, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:47:49'),
(801, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:48:10'),
(802, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:48:11'),
(803, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:48:41'),
(804, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:48:49'),
(805, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:49:11'),
(806, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:49:41'),
(807, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:49:49'),
(808, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:50:11'),
(809, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:50:12'),
(810, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:50:43'),
(811, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:50:49'),
(812, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:51:12'),
(813, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:51:42'),
(814, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:51:50'),
(815, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:52:12'),
(816, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:52:12'),
(817, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:52:44'),
(818, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:52:49'),
(819, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:53:13'),
(820, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:53:43'),
(821, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:53:49'),
(822, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:54:13'),
(823, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:54:14'),
(824, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:54:44'),
(825, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:54:49'),
(826, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:55:14'),
(827, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:55:44'),
(828, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:55:49'),
(829, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:56:14'),
(830, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:56:14'),
(831, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:56:45'),
(832, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:56:49'),
(833, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:57:15'),
(834, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:57:45'),
(835, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:57:49'),
(836, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:58:15'),
(837, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:58:15'),
(838, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:58:46'),
(839, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:58:49'),
(840, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:59:16'),
(841, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:59:46'),
(842, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 19:59:49'),
(843, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:00:16'),
(844, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:00:17'),
(845, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:00:47'),
(846, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:00:49'),
(847, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:01:17'),
(848, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:01:47'),
(849, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:01:49'),
(850, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:02:17'),
(851, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:02:17'),
(852, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:02:48'),
(853, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:02:49'),
(854, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:03:18'),
(855, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:03:48'),
(856, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:03:49'),
(857, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:04:18'),
(858, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:04:18'),
(859, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:04:50'),
(860, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:04:50'),
(861, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:05:19'),
(862, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:05:49'),
(863, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:05:50'),
(864, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:06:19'),
(865, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:06:19'),
(866, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:06:49'),
(867, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:06:51'),
(868, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:07:21'),
(869, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:07:50'),
(870, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:07:50'),
(871, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:08:20'),
(872, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:08:21'),
(873, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:08:49'),
(874, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:08:51'),
(875, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:09:22'),
(876, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:09:49');
INSERT INTO `sensor_data` (`data_id`, `light_id`, `brightness_level`, `current_consumption`, `voltage`, `temperature`, `timestamp`) VALUES
(877, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:09:51'),
(878, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:10:21'),
(879, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:10:22'),
(880, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:10:49'),
(881, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:10:52'),
(882, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:11:22'),
(883, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:11:49'),
(884, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:11:52'),
(885, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:12:22'),
(886, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:12:22'),
(887, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:12:49'),
(888, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:12:53'),
(889, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:13:23'),
(890, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:13:49'),
(891, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:13:53'),
(892, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:14:23'),
(893, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:14:24'),
(894, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:14:49'),
(895, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:14:54'),
(896, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:15:25'),
(897, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:15:50'),
(898, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:15:54'),
(899, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:16:24'),
(900, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:16:25'),
(901, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:32:15'),
(902, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:32:21'),
(903, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:32:51'),
(904, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:48:39'),
(905, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 20:48:45'),
(906, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 21:04:41'),
(907, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 21:04:42'),
(908, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 21:05:05'),
(909, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 21:20:39'),
(910, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 21:21:08'),
(911, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 21:36:57'),
(912, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 21:37:04'),
(913, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 21:37:34'),
(914, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 21:37:36'),
(915, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 21:53:22'),
(916, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 21:53:32'),
(917, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 22:09:27'),
(918, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 22:09:48'),
(919, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 22:17:13'),
(920, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 22:17:43'),
(921, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 22:17:43'),
(922, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 22:33:30'),
(923, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 22:33:40'),
(924, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 22:49:35'),
(925, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 22:49:56'),
(926, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 22:50:05'),
(927, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 23:00:23'),
(928, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 23:00:24'),
(929, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 23:00:43'),
(930, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 23:16:19'),
(931, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 23:16:50'),
(932, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 23:33:42'),
(933, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 23:33:53'),
(934, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 23:49:49'),
(935, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 23:49:49'),
(936, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 23:50:08'),
(937, 1, 30.38, 0.005, 2.37, 30.3, '2025-11-30 23:50:20'),
(938, 1, 30.38, 0.005, 2.37, 30.3, '2025-12-01 00:05:56'),
(939, 1, 30.38, 0.005, 2.37, 30.3, '2025-12-01 00:19:08'),
(940, 1, 30.38, 0.005, 2.37, 30.3, '2025-12-01 00:19:19'),
(941, 1, 30.38, 0.005, 2.37, 30.3, '2025-12-01 00:35:15'),
(942, 1, 30.38, 0.005, 2.37, 30.3, '2025-12-01 00:35:16'),
(943, 1, 30.38, 0.005, 2.37, 30.3, '2025-12-01 00:35:33'),
(944, 1, 30.38, 0.005, 2.37, 30.3, '2025-12-01 00:51:58'),
(945, 1, 30.38, 0.005, 2.37, 30.3, '2025-12-01 01:20:16'),
(946, 1, 30.38, 0.005, 2.37, 30.3, '2025-12-01 01:21:36'),
(947, 1, 30.38, 0.005, 2.37, 30.3, '2025-12-01 01:53:27'),
(948, 1, 30.38, 0.005, 2.37, 30.3, '2025-12-01 02:09:52'),
(949, 1, 30.38, 0.005, 2.37, 30.3, '2025-12-01 02:21:46'),
(950, 1, 30.38, 0.005, 2.37, 30.3, '2025-12-01 02:53:37'),
(951, 1, 47.60, 0.004, 1.80, 31.3, '2025-12-01 07:24:34'),
(952, 1, 0.00, 0.007, 3.12, 31.3, '2025-12-01 07:32:13'),
(953, 1, 0.00, 0.007, 3.12, 31.2, '2025-12-01 07:32:43'),
(954, 1, 0.00, 0.007, 3.11, 31.2, '2025-12-01 07:33:13'),
(955, 1, 0.00, 0.007, 3.12, 31.2, '2025-12-01 07:33:43'),
(956, 1, 0.00, 0.007, 3.12, 31.2, '2025-12-01 07:34:13'),
(957, 1, 0.00, 0.007, 3.10, 31.2, '2025-12-01 07:34:43'),
(958, 1, 0.00, 0.007, 3.12, 31.3, '2025-12-01 07:35:13'),
(959, 1, 0.00, 0.007, 3.12, 31.3, '2025-12-01 07:36:22'),
(960, 1, 0.00, 0.007, 3.10, 31.4, '2025-12-01 07:37:22'),
(961, 1, 0.00, 0.007, 3.09, 31.4, '2025-12-01 07:38:22'),
(962, 1, 0.00, 0.007, 3.12, 31.3, '2025-12-01 07:39:22'),
(963, 1, 0.00, 0.007, 3.12, 31.2, '2025-12-01 07:40:22'),
(964, 1, 0.00, 0.007, 3.12, 31.1, '2025-12-01 07:41:22'),
(965, 1, 0.00, 0.007, 3.12, 31.1, '2025-12-01 07:42:22'),
(966, 1, 0.00, 0.007, 3.12, 31.1, '2025-12-01 07:43:17'),
(967, 1, 0.00, 0.007, 3.12, 31.3, '2025-12-01 07:49:14'),
(968, 1, 0.00, 0.007, 3.12, 31.3, '2025-12-01 07:49:44'),
(969, 1, 0.00, 0.007, 3.12, 31.4, '2025-12-01 07:50:14'),
(970, 1, 0.00, 0.007, 3.12, 31.3, '2025-12-01 07:50:44'),
(971, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 07:59:49'),
(972, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 07:59:57'),
(973, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 07:59:57'),
(974, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 07:59:58'),
(975, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 07:59:58'),
(976, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:00:48'),
(977, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:00:59'),
(978, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:00:59'),
(979, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:00:59'),
(980, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:01:00'),
(981, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:01:00'),
(982, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:01:00'),
(983, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:01:00'),
(984, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:01:01'),
(985, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:01:01'),
(986, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:01:01'),
(987, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:01:02'),
(988, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:01:02'),
(989, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:01:02'),
(990, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:01:03'),
(991, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:01:18'),
(992, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:01:48'),
(993, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:02:18'),
(994, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:02:48'),
(995, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:03:18'),
(996, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:03:48'),
(997, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:04:22'),
(998, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:05:22'),
(999, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:06:22'),
(1000, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:07:22'),
(1001, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:08:22'),
(1002, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:09:22'),
(1003, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:10:22'),
(1004, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:11:22'),
(1005, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:12:22'),
(1006, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:13:22'),
(1007, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:14:22'),
(1008, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:15:22'),
(1009, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:16:22'),
(1010, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:17:22'),
(1011, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:18:00'),
(1012, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:18:02'),
(1013, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:18:32'),
(1014, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:19:06'),
(1015, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:24:00'),
(1016, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:24:02'),
(1017, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:24:03'),
(1018, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:24:03'),
(1019, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:24:04'),
(1020, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:24:16'),
(1021, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:24:34'),
(1022, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:24:46'),
(1023, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:25:04'),
(1024, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:25:17'),
(1025, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:25:34'),
(1026, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:25:46'),
(1027, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:26:04'),
(1028, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:26:16'),
(1029, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:26:17'),
(1030, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:26:34'),
(1031, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:26:48'),
(1032, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:27:04'),
(1033, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:27:17'),
(1034, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:27:47'),
(1035, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:28:17'),
(1036, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:28:18'),
(1037, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:28:22'),
(1038, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:28:48'),
(1039, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:28:54'),
(1040, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:29:18'),
(1041, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:29:21'),
(1042, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:29:22'),
(1043, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:29:48'),
(1044, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:29:51'),
(1045, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:30:00'),
(1046, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:30:18'),
(1047, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:30:19'),
(1048, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:30:22'),
(1049, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:30:30'),
(1050, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:30:50'),
(1051, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:31:00'),
(1052, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:31:19'),
(1053, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:31:23'),
(1054, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:31:30'),
(1055, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:31:49'),
(1056, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:32:00'),
(1057, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:32:19'),
(1058, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:32:20'),
(1059, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:32:22'),
(1060, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:32:30'),
(1061, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:32:34'),
(1062, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:32:41'),
(1063, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:32:42'),
(1064, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:32:43'),
(1065, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:33:00'),
(1066, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:33:04'),
(1067, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:33:04'),
(1068, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:33:14'),
(1069, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:33:34'),
(1070, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:33:43'),
(1071, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:34:13'),
(1072, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:34:23'),
(1073, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:34:43'),
(1074, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:34:44'),
(1075, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:35:15'),
(1076, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:35:22'),
(1077, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:35:45'),
(1078, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:36:14'),
(1079, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:36:23'),
(1080, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:36:44'),
(1081, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:36:45'),
(1082, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:37:16'),
(1083, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:37:22'),
(1084, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:37:46'),
(1085, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:38:15'),
(1086, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:38:23'),
(1087, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:38:46'),
(1088, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:38:48'),
(1089, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:39:18'),
(1090, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:39:23'),
(1091, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:39:48'),
(1092, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:40:01'),
(1093, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:40:15'),
(1094, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:40:22'),
(1095, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:41:23'),
(1096, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:42:23'),
(1097, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:43:22'),
(1098, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:44:23'),
(1099, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:45:23'),
(1100, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:46:23'),
(1101, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:47:23'),
(1102, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:48:25'),
(1103, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:49:22'),
(1104, 1, 96.80, 0.001, 0.26, 31.1, '2025-12-01 08:49:53'),
(1105, 1, 100.00, 0.000, 0.14, 31.4, '2025-12-01 14:47:11'),
(1106, 1, 100.00, 0.000, 0.14, 31.4, '2025-12-01 14:47:12'),
(1107, 1, 100.00, 0.000, 0.14, 31.4, '2025-12-01 14:47:12'),
(1108, 1, 100.00, 0.000, 0.14, 31.4, '2025-12-01 14:47:13'),
(1109, 1, 100.00, 0.000, 0.14, 31.4, '2025-12-01 14:47:14'),
(1110, 1, 100.00, 0.000, 0.14, 27.5, '2025-12-02 07:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `streetlights`
--

CREATE TABLE `streetlights` (
  `light_id` int(11) NOT NULL,
  `node_name` varchar(50) NOT NULL,
  `location` varchar(255) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Maintenance') DEFAULT 'Active',
  `power_state` enum('ON','OFF') DEFAULT 'ON',
  `dimming_level` int(11) DEFAULT 70,
  `last_maintenance` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `streetlights`
--

INSERT INTO `streetlights` (`light_id`, `node_name`, `location`, `latitude`, `longitude`, `installation_date`, `status`, `power_state`, `dimming_level`, `last_maintenance`) VALUES
(1, 'SL-001', 'Coronado Street, Hulo, Mandaluyong', 14.56813300, 121.03370400, '2024-01-15', 'Active', 'ON', 75, NULL),
(2, 'SL-002', 'Coronado Street, Hulo, Mandaluyong', 14.56824313, 121.03358487, '2024-01-15', 'Active', 'ON', 75, NULL),
(3, 'SL-003', 'Coronado Street, Hulo, Mandaluyong', 14.56836726, 121.03347374, '2024-01-15', 'Active', 'ON', 75, NULL),
(4, 'SL-004', 'Coronado Street, Hulo, Mandaluyong', 14.56848139, 121.03335761, '2024-01-15', 'Active', 'ON', 75, NULL),
(5, 'SL-005', 'Coronado Street, Hulo, Mandaluyong', 14.56859194, 121.03322945, '2024-01-15', 'Active', 'ON', 75, NULL),
(6, 'SL-006', 'Coronado Street, Hulo, Mandaluyong', 14.56869042, 121.03313206, '2024-01-15', 'Active', 'ON', 75, NULL),
(7, 'SL-007', 'Coronado Street, Hulo, Mandaluyong', 14.56878190, 121.03302368, '2024-01-15', 'Active', 'ON', 75, NULL),
(8, 'SL-008', 'Coronado Street, Hulo, Mandaluyong', 14.56887439, 121.03291129, '2024-01-15', 'Active', 'ON', 75, NULL),
(9, 'SL-009', 'Coronado Street, Hulo, Mandaluyong', 14.56900319, 121.03278561, '2024-01-16', 'Active', 'ON', 75, NULL),
(10, 'SL-010', 'Coronado Street, Hulo, Mandaluyong', 14.56922897, 121.03258006, '2024-01-16', 'Active', 'ON', 75, NULL),
(11, 'SL-011', 'Coronado Street, Hulo, Mandaluyong', 14.56943674, 121.03239252, '2024-01-16', 'Active', 'ON', 75, NULL),
(12, 'SL-012', 'Coronado Street, Hulo, Mandaluyong', 14.56965852, 121.03219997, '2024-01-16', 'Active', 'ON', 75, NULL),
(13, 'SL-013', 'Coronado Street, Hulo, Mandaluyong', 14.56985771, 121.03201819, '2024-01-16', 'Active', 'ON', 75, NULL),
(14, 'SL-014', 'Coronado Street, Hulo, Mandaluyong', 14.57003894, 121.03183171, '2024-01-16', 'Active', 'ON', 75, NULL),
(15, 'SL-015', 'Coronado Street, Hulo, Mandaluyong', 14.57022716, 121.03166723, '2024-01-17', 'Active', 'ON', 75, NULL),
(16, 'SL-016', 'Coronado Street, Hulo, Mandaluyong', 14.57040239, 121.03149374, '2024-01-17', 'Active', 'ON', 75, NULL),
(17, 'SL-017', 'Coronado Street, Hulo, Mandaluyong', 14.57060052, 121.03127152, '2024-01-17', 'Active', 'ON', 75, NULL),
(18, 'SL-018', 'Coronado Street, Hulo, Mandaluyong', 14.57081255, 121.03099455, '2024-01-17', 'Active', 'ON', 75, NULL),
(19, 'SL-019', 'Coronado Street, Hulo, Mandaluyong', 14.57101758, 121.03071758, '2024-01-17', 'Active', 'ON', 75, NULL),
(20, 'SL-020', 'Coronado Street, Hulo, Mandaluyong', 14.57122861, 121.03045461, '2024-01-18', 'Active', 'ON', 75, NULL),
(21, 'SL-021', 'Coronado Street, Hulo, Mandaluyong', 14.57142029, 121.03026929, '2024-01-18', 'Active', 'ON', 75, NULL),
(22, 'SL-022', 'Coronado Street, Hulo, Mandaluyong', 14.57159135, 121.03012535, '2024-01-18', 'Active', 'ON', 75, NULL),
(23, 'SL-023', 'Coronado Street, Hulo, Mandaluyong', 14.57177742, 121.02998442, '2024-01-18', 'Active', 'ON', 75, NULL),
(24, 'SL-024', 'Coronado Street, Hulo, Mandaluyong', 14.57195348, 121.02983748, '2024-01-19', 'Active', 'ON', 75, NULL),
(25, 'SL-025', 'Coronado Street, Hulo, Mandaluyong', 14.57210358, 121.02969574, '2024-01-19', 'Active', 'ON', 75, NULL),
(26, 'SL-026', 'Coronado Street, Hulo, Mandaluyong', 14.57223335, 121.02954506, '2024-01-19', 'Active', 'ON', 75, NULL),
(27, 'SL-027', 'Coronado Street, Hulo, Mandaluyong', 14.57237913, 121.02938939, '2024-01-20', 'Active', 'ON', 75, NULL),
(28, 'SL-028', 'Coronado Street, Hulo, Mandaluyong', 14.57250490, 121.02923371, '2024-01-20', 'Active', 'ON', 75, NULL),
(29, 'SL-029', 'Coronado Street, Hulo, Mandaluyong', 14.57256539, 121.02905442, '2024-01-20', 'Active', 'ON', 75, NULL),
(30, 'SL-030', 'Coronado Street, Hulo, Mandaluyong', 14.57261826, 121.02887761, '2024-01-20', 'Active', 'ON', 75, NULL),
(31, 'SL-031', 'Coronado Street, Hulo, Mandaluyong', 14.57265413, 121.02868681, '2024-01-21', 'Active', 'ON', 75, NULL),
(32, 'SL-032', 'Coronado Street, Hulo, Mandaluyong', 14.57269300, 121.02850100, '2024-01-21', 'Active', 'ON', 75, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_config`
--

CREATE TABLE `system_config` (
  `config_id` int(11) NOT NULL,
  `config_key` varchar(100) NOT NULL,
  `config_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_config`
--

INSERT INTO `system_config` (`config_id`, `config_key`, `config_value`, `description`, `updated_at`, `updated_by`) VALUES
(1, 'lux_threshold_min', '50', 'Minimum brightness (lux) - WARNING level - turns KPI yellow', '2025-12-02 07:13:18', 1),
(2, 'current_threshold_max', '0.5', 'Maximum current (Amperes) - WARNING level', '2025-11-30 14:48:54', 1),
(3, 'temperature_threshold_max', '45', 'Maximum temperature (Celsius) - WARNING level - turns KPI yellow', '2025-11-30 14:48:54', 1),
(4, 'predictive_days_threshold', '3', 'Number of consecutive days below threshold to trigger predictive alert', '2025-11-11 16:48:22', 1),
(5, 'auto_dim_enabled', '1', 'Enable automatic dimming based on schedule', '2025-11-11 16:48:22', 1),
(6, 'default_dimming_level', '70', 'Default dimming level percentage', '2025-11-11 16:48:22', 1),
(7, 'cloud_backup_enabled', '1', 'Enable Firebase cloud backup', '2025-11-11 16:48:22', 1),
(8, 'alert_email_enabled', '1', 'Enable email notifications for alerts', '2025-11-11 16:48:22', 1),
(9, 'data_retention_days', '90', 'Number of days to retain sensor data', '2025-11-11 16:48:22', 1),
(10, 'footage_retention_days', '30', 'Number of days to retain CCTV footage', '2025-11-11 16:48:22', 1),
(11, 'lux_threshold_critical', '30', 'Critical brightness (lux) - CRITICAL level - predictive maintenance needed', '2025-12-02 07:13:18', 1),
(12, 'temperature_threshold_critical', '55', 'Critical temperature (Celsius) - CRITICAL level - immediate maintenance', '2025-11-30 14:48:54', 1),
(13, 'current_threshold_critical', '0.7', 'Critical current (Amperes) - CRITICAL level', '2025-11-30 14:48:54', 1),
(14, 'voltage_threshold_min', '2.0', 'Minimum voltage (V) - WARNING level', '2025-11-30 14:48:54', 1),
(15, 'voltage_threshold_critical', '1.5', 'Critical voltage (V) - CRITICAL level - battery replacement needed', '2025-11-30 14:48:54', 1),
(16, 'humidity_threshold_max', '80', 'Maximum humidity (%) - WARNING level', '2025-11-30 14:48:54', 1),
(17, 'humidity_threshold_critical', '90', 'Critical humidity (%) - CRITICAL level', '2025-11-30 14:48:54', 1),
(18, 'predictive_window_days', '7', 'Number of days to analyze for predictive maintenance trends', '2025-11-30 14:48:54', 1),
(19, 'predictive_threshold_hits', '3', 'Number of threshold hits in window to trigger predictive alert', '2025-11-30 14:48:54', 1),
(20, 'maintenance_prediction_days', '14', 'Days to predict until maintenance needed', '2025-11-30 14:48:54', 1),
(21, 'system_name', 'SHINEGUARD', 'System preference', '2026-02-24 04:37:44', 1),
(22, 'organization_name', 'Hulo', 'System preference', '2026-02-23 11:43:49', 1),
(23, 'location', 'Mandaluyong', 'System preference', '2026-02-23 11:43:49', 1),
(24, 'timezone', 'Asia/Singapore', 'System preference', '2025-11-30 15:20:17', 1),
(25, 'language', 'English', 'System preference', '2025-11-30 15:19:41', 1),
(26, 'theme_color', '#e61414', 'System preference', '2025-12-07 18:03:15', 1),
(27, 'logo_text', 'AQS💡', 'System preference', '2025-12-02 10:42:55', 1),
(28, 'contact_email', 'admin@hulo.barangay.ph', 'System preference', '2025-11-30 15:19:41', 1),
(29, 'contact_phone', '+63 XXX XXX XXXX', 'System preference', '2025-11-30 15:19:41', 1),
(30, 'map_center_lat', '14.6507', 'System preference', '2025-11-30 15:19:41', 1),
(31, 'map_center_lng', '121.0494', 'System preference', '2025-11-30 15:19:41', 1),
(32, 'map_zoom_level', '15', 'System preference', '2025-11-30 15:19:41', 1),
(172, 'logo_image_path', '/img/ShineGuard2.png', 'System preference', '2025-12-07 17:06:10', 1),
(173, 'footer_text', '© 2025 Shine Guard System', 'System preference', '2025-12-07 13:57:33', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('Admin','Operator','Maintenance') DEFAULT 'Operator',
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `email`, `full_name`, `role`, `phone`, `created_at`, `last_login`, `is_active`) VALUES
(1, 'admin', '$2y$10$Hk6HpA7uEHZSZyymjkGv4O51Z.WYVwbyPiJ791VsameOBxy3BC13S', 'admin@hulo.barangay.ph', 'System Administrator', 'Admin', NULL, '2025-11-11 16:48:22', '2026-02-24 18:34:58', 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_maintenance_summary`
-- (See below for the actual view)
--
CREATE TABLE `view_maintenance_summary` (
`log_id` int(11)
,`maintenance_date` datetime
,`action_taken` text
,`status` enum('Scheduled','In Progress','Completed','Cancelled')
,`cost` decimal(10,2)
,`node_name` varchar(50)
,`location` varchar(255)
,`technician` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_recent_alerts`
-- (See below for the actual view)
--
CREATE TABLE `view_recent_alerts` (
`alert_id` int(11)
,`alert_type` enum('Fault','Warning','Predictive')
,`severity` enum('Low','Medium','High')
,`description` text
,`status` enum('Open','Acknowledged','Resolved')
,`rul_estimate` varchar(50)
,`created_at` timestamp
,`node_name` varchar(50)
,`location` varchar(255)
,`acknowledged_by_name` varchar(50)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_streetlight_summary`
-- (See below for the actual view)
--
CREATE TABLE `view_streetlight_summary` (
`status` enum('Active','Inactive','Maintenance')
,`power_state` enum('ON','OFF')
,`count` bigint(21)
,`avg_dimming` decimal(14,4)
);

-- --------------------------------------------------------

--
-- Structure for view `view_maintenance_summary`
--
DROP TABLE IF EXISTS `view_maintenance_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `hulo`.`view_maintenance_summary`  AS SELECT `ml`.`log_id` AS `log_id`, `ml`.`maintenance_date` AS `maintenance_date`, `ml`.`action_taken` AS `action_taken`, `ml`.`status` AS `status`, `ml`.`cost` AS `cost`, `s`.`node_name` AS `node_name`, `s`.`location` AS `location`, `u`.`full_name` AS `technician` FROM ((`hulo`.`maintenance_logs` `ml` join `hulo`.`streetlights` `s` on(`ml`.`light_id` = `s`.`light_id`)) join `hulo`.`users` `u` on(`ml`.`user_id` = `u`.`user_id`)) WHERE `ml`.`maintenance_date` >= current_timestamp() - interval 30 day ORDER BY `ml`.`maintenance_date` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `view_recent_alerts`
--
DROP TABLE IF EXISTS `view_recent_alerts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `hulo`.`view_recent_alerts`  AS SELECT `a`.`alert_id` AS `alert_id`, `a`.`alert_type` AS `alert_type`, `a`.`severity` AS `severity`, `a`.`description` AS `description`, `a`.`status` AS `status`, `a`.`rul_estimate` AS `rul_estimate`, `a`.`created_at` AS `created_at`, `s`.`node_name` AS `node_name`, `s`.`location` AS `location`, `u`.`username` AS `acknowledged_by_name` FROM ((`hulo`.`alerts` `a` join `hulo`.`streetlights` `s` on(`a`.`light_id` = `s`.`light_id`)) left join `hulo`.`users` `u` on(`a`.`acknowledged_by` = `u`.`user_id`)) WHERE `a`.`created_at` >= current_timestamp() - interval 7 day ORDER BY `a`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `view_streetlight_summary`
--
DROP TABLE IF EXISTS `view_streetlight_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `hulo`.`view_streetlight_summary`  AS SELECT `hulo`.`streetlights`.`status` AS `status`, `hulo`.`streetlights`.`power_state` AS `power_state`, count(0) AS `count`, avg(`hulo`.`streetlights`.`dimming_level`) AS `avg_dimming` FROM `hulo`.`streetlights` GROUP BY `hulo`.`streetlights`.`status`, `hulo`.`streetlights`.`power_state` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `light_id` (`light_id`),
  ADD KEY `acknowledged_by` (`acknowledged_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `cameras`
--
ALTER TABLE `cameras`
  ADD PRIMARY KEY (`camera_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_location` (`location`);

--
-- Indexes for table `camera_events`
--
ALTER TABLE `camera_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `snapshot_id` (`snapshot_id`),
  ADD KEY `resolved_by` (`resolved_by`),
  ADD KEY `idx_camera_time` (`camera_id`,`event_time`),
  ADD KEY `idx_type` (`event_type`),
  ADD KEY `idx_resolved` (`resolved`);

--
-- Indexes for table `camera_snapshots`
--
ALTER TABLE `camera_snapshots`
  ADD PRIMARY KEY (`snapshot_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_camera_date` (`camera_id`,`created_at`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `cctv_cameras`
--
ALTER TABLE `cctv_cameras`
  ADD PRIMARY KEY (`camera_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `cctv_footage`
--
ALTER TABLE `cctv_footage`
  ADD PRIMARY KEY (`footage_id`),
  ADD KEY `idx_camera_time` (`camera_id`,`start_time`),
  ADD KEY `idx_event_type` (`event_type`);

--
-- Indexes for table `diagnostic_logs`
--
ALTER TABLE `diagnostic_logs`
  ADD PRIMARY KEY (`diagnostic_id`),
  ADD KEY `idx_light_tested` (`light_id`,`tested_at`),
  ADD KEY `idx_test_type` (`test_type`);

--
-- Indexes for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `light_id` (`light_id`),
  ADD KEY `alert_id` (`alert_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_maintenance_date` (`maintenance_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `schedule_presets`
--
ALTER TABLE `schedule_presets`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `sensor_data`
--
ALTER TABLE `sensor_data`
  ADD PRIMARY KEY (`data_id`),
  ADD KEY `idx_light_timestamp` (`light_id`,`timestamp`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indexes for table `streetlights`
--
ALTER TABLE `streetlights`
  ADD PRIMARY KEY (`light_id`),
  ADD UNIQUE KEY `node_name` (`node_name`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_node_name` (`node_name`);

--
-- Indexes for table `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`config_id`),
  ADD UNIQUE KEY `config_key` (`config_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=275;

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cameras`
--
ALTER TABLE `cameras`
  MODIFY `camera_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `camera_events`
--
ALTER TABLE `camera_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `camera_snapshots`
--
ALTER TABLE `camera_snapshots`
  MODIFY `snapshot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cctv_cameras`
--
ALTER TABLE `cctv_cameras`
  MODIFY `camera_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cctv_footage`
--
ALTER TABLE `cctv_footage`
  MODIFY `footage_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `diagnostic_logs`
--
ALTER TABLE `diagnostic_logs`
  MODIFY `diagnostic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `schedule_presets`
--
ALTER TABLE `schedule_presets`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sensor_data`
--
ALTER TABLE `sensor_data`
  MODIFY `data_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1111;

--
-- AUTO_INCREMENT for table `streetlights`
--
ALTER TABLE `streetlights`
  MODIFY `light_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `system_config`
--
ALTER TABLE `system_config`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=391;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`light_id`) REFERENCES `streetlights` (`light_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alerts_ibfk_2` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `camera_events`
--
ALTER TABLE `camera_events`
  ADD CONSTRAINT `camera_events_ibfk_1` FOREIGN KEY (`camera_id`) REFERENCES `cameras` (`camera_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `camera_events_ibfk_2` FOREIGN KEY (`snapshot_id`) REFERENCES `camera_snapshots` (`snapshot_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `camera_events_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `camera_snapshots`
--
ALTER TABLE `camera_snapshots`
  ADD CONSTRAINT `camera_snapshots_ibfk_1` FOREIGN KEY (`camera_id`) REFERENCES `cameras` (`camera_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `camera_snapshots_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `cctv_footage`
--
ALTER TABLE `cctv_footage`
  ADD CONSTRAINT `cctv_footage_ibfk_1` FOREIGN KEY (`camera_id`) REFERENCES `cctv_cameras` (`camera_id`) ON DELETE CASCADE;

--
-- Constraints for table `diagnostic_logs`
--
ALTER TABLE `diagnostic_logs`
  ADD CONSTRAINT `diagnostic_logs_ibfk_1` FOREIGN KEY (`light_id`) REFERENCES `streetlights` (`light_id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD CONSTRAINT `maintenance_logs_ibfk_1` FOREIGN KEY (`light_id`) REFERENCES `streetlights` (`light_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenance_logs_ibfk_2` FOREIGN KEY (`alert_id`) REFERENCES `alerts` (`alert_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `maintenance_logs_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedule_presets`
--
ALTER TABLE `schedule_presets`
  ADD CONSTRAINT `schedule_presets_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `sensor_data`
--
ALTER TABLE `sensor_data`
  ADD CONSTRAINT `sensor_data_ibfk_1` FOREIGN KEY (`light_id`) REFERENCES `streetlights` (`light_id`) ON DELETE CASCADE;

--
-- Constraints for table `system_config`
--
ALTER TABLE `system_config`
  ADD CONSTRAINT `system_config_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
