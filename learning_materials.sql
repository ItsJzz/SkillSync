-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 06, 2025 at 05:32 AM
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
-- Database: `skillsync`
--

-- --------------------------------------------------------

--
-- Table structure for table `learning_materials`
--

CREATE TABLE `learning_materials` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `type` enum('video','pdf','simulation') NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `learning_materials`
--

INSERT INTO `learning_materials` (`id`, `topic_id`, `type`, `title`, `url`, `file_path`, `file_size`, `duration`, `created_at`) VALUES
(4, 14, 'simulation', 'Classes and Objects Simulation', 'classes-objects-simulation.php', NULL, NULL, NULL, '2025-09-22 13:18:41'),
(6, 14, 'pdf', 'Programming in Business Analytics Syllabus', 'modules/programming_business_analytics.pdf', NULL, NULL, NULL, '2025-09-22 13:37:39'),
(17, 41, 'pdf', 'Programming in Business Analytics Syllabus', 'modules/programming_business_analytics.pdf', NULL, NULL, NULL, '2025-09-22 13:37:39'),
(18, 13, 'video', 'Introduction To OOP Concept', NULL, 'uploads/videos/video_1759593541_68e1444503376.mp4', 11586643, NULL, '2025-10-04 15:59:01'),
(22, 14, 'video', 'Classes and Objects', NULL, 'uploads/videos/video_1759636810_68e1ed4a5d83a.mp4', 10582561, NULL, '2025-10-05 04:00:10'),
(23, 15, 'video', 'Encapsulation', NULL, 'uploads/videos/video_1759636853_68e1ed757ea74.mp4', 10920592, NULL, '2025-10-05 04:00:53'),
(24, 16, 'video', 'Inheritance', NULL, 'uploads/videos/video_1759636954_68e1edda10f02.mp4', 5768535, NULL, '2025-10-05 04:02:34'),
(25, 17, 'video', 'Polymorphism', NULL, 'uploads/videos/video_1759637040_68e1ee300e458.mp4', 5831000, NULL, '2025-10-05 04:04:00'),
(26, 36, 'video', 'Advance CSS', NULL, 'uploads/videos/video_1759663807_68e256bff0308.mp4', 4447668, NULL, '2025-10-05 11:30:07'),
(27, 35, 'video', 'Advanced HTML', NULL, 'uploads/videos/video_1759663846_68e256e6050c4.mp4', 4968298, NULL, '2025-10-05 11:30:46'),
(29, 41, 'video', 'AJAX and Fetch API', NULL, 'uploads/videos/video_1759663970_68e2576261450.mp4', 4461307, NULL, '2025-10-05 11:32:50'),
(30, 40, 'video', 'Asychronous JavaScript', NULL, 'uploads/videos/video_1759664054_68e257b657e0f.mp4', 5148029, NULL, '2025-10-05 11:34:14'),
(31, 30, 'video', 'CSS Basics', NULL, 'uploads/videos/video_1759664136_68e2580852dc2.mp4', 4535232, NULL, '2025-10-05 11:35:36'),
(32, 31, 'video', 'CSS Box Model And Layout', NULL, 'uploads/videos/video_1759664171_68e2582b3ef45.mp4', 4803009, NULL, '2025-10-05 11:36:11'),
(33, 45, 'video', 'Advance Swing Components', NULL, 'uploads/videos/video_1759664202_68e2584ac9f8d.mp4', 6170355, NULL, '2025-10-05 11:36:42'),
(34, 39, 'video', 'ES6 Features', NULL, 'uploads/videos/video_1759664253_68e2587d3ac26.mp4', 5104627, NULL, '2025-10-05 11:37:33'),
(36, 44, 'video', 'Event Handling In AWT and Swing', NULL, 'uploads/videos/video_1759664413_68e2591dd0e23.mp4', 6382514, NULL, '2025-10-05 11:40:13'),
(37, 34, 'video', 'Event Handling In JavaScript', NULL, 'uploads/videos/video_1759664446_68e2593e034a6.mp4', 4565052, NULL, '2025-10-05 11:40:46'),
(38, 49, 'video', 'Exception Handling And Best Practices', NULL, 'uploads/videos/video_1759664489_68e25969444ab.mp4', 5748852, NULL, '2025-10-05 11:41:29'),
(39, 28, 'video', 'HTML Basics', NULL, 'uploads/videos/video_1759665196_68e25c2c2fe12.mp4', 4392023, NULL, '2025-10-05 11:53:16'),
(40, 29, 'video', 'HTML Forms And Input Elements', NULL, 'uploads/videos/video_1759665231_68e25c4f80af8.mp4', 4402553, NULL, '2025-10-05 11:53:51'),
(41, 42, 'video', 'Introduction To Web APIs', NULL, 'uploads/videos/video_1759665283_68e25c83438e4.mp4', 4334686, NULL, '2025-10-05 11:54:43'),
(42, 27, 'video', 'Introduction To Web Development', NULL, 'uploads/videos/video_1759665329_68e25cb144149.mp4', 5641428, NULL, '2025-10-05 11:55:29'),
(43, 47, 'video', 'Introduction To DataBase', NULL, 'uploads/videos/video_1759665380_68e25ce414b68.mp4', 6141978, NULL, '2025-10-05 11:56:20'),
(44, 43, 'video', 'Introduction To Event Driven Progamming', NULL, 'uploads/videos/video_1759665424_68e25d10ec9d3.mp4', 6884653, NULL, '2025-10-05 11:57:04'),
(45, 32, 'video', 'Introduction To JavaScript', NULL, 'uploads/videos/video_1759665477_68e25d45584c7.mp4', 4267945, NULL, '2025-10-05 11:57:57'),
(46, 33, 'video', 'JavaScript DOM Manipulation', NULL, 'uploads/videos/video_1759665517_68e25d6da549e.mp4', 4656238, NULL, '2025-10-05 11:58:37'),
(47, 37, 'video', 'JavaScript Function And Scope', NULL, 'uploads/videos/video_1759665584_68e25db09d3dd.mp4', 4292634, NULL, '2025-10-05 11:59:44'),
(48, 38, 'video', 'JavaScript Objects And Arrays', NULL, 'uploads/videos/video_1759665652_68e25df4f18ba.mp4', 5191144, NULL, '2025-10-05 12:00:52'),
(49, 46, 'video', 'Layout Management', NULL, 'uploads/videos/video_1759665675_68e25e0b5350b.mp4', 5230187, NULL, '2025-10-05 12:01:15'),
(50, 48, 'video', 'CRUD Operations Using JDBC', NULL, 'uploads/videos/video_1759666457_68e2611905ed4.mp4', 6340557, NULL, '2025-10-05 12:14:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `learning_materials`
--
ALTER TABLE `learning_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `learning_materials`
--
ALTER TABLE `learning_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `learning_materials`
--
ALTER TABLE `learning_materials`
  ADD CONSTRAINT `learning_materials_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
