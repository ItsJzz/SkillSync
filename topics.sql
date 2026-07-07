-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 06, 2025 at 05:33 AM
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
-- Table structure for table `topics`
--

CREATE TABLE `topics` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `redirect_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `topics`
--

INSERT INTO `topics` (`id`, `subject_id`, `name`, `description`, `redirect_url`) VALUES
(13, 3, 'Introduction to OOP Concepts', NULL, 'Activity/intro.php?topic=introduction'),
(14, 3, 'Classes and Objects', NULL, 'Activity/intro.php?topic=classes_objects'),
(15, 3, 'Encapsulation', NULL, 'Activity/intro.php?topic=encapsulation'),
(16, 3, 'Inheritance', NULL, 'Activity/intro.php?topic=inheritance'),
(17, 3, 'Polymorphism', NULL, 'Activity/intro.php?topic=polymorphism'),
(20, 4, 'Abstract Classes and Interfaces', NULL, NULL),
(21, 4, 'Exception Handling', NULL, NULL),
(22, 4, 'File I/O in OOP', NULL, NULL),
(23, 4, 'Generics and Collections', NULL, NULL),
(24, 4, 'Delegates and Events', NULL, NULL),
(25, 4, 'LINQ Basics', NULL, NULL),
(26, 4, 'Design Patterns Introduction', NULL, NULL),
(27, 5, 'Introduction to Web Development', NULL, NULL),
(28, 5, 'HTML Basics', NULL, NULL),
(29, 5, 'HTML Forms and Input Elements', NULL, NULL),
(30, 5, 'CSS Basics', '', 'Activity/intro.php?topic=css_basics'),
(31, 5, 'CSS Box Model and Layout', NULL, NULL),
(32, 5, 'Introduction to JavaScript', NULL, NULL),
(33, 5, 'JavaScript DOM Manipulation', NULL, NULL),
(34, 5, 'Event Handling in JavaScript', NULL, NULL),
(35, 6, 'Advanced HTML5 Features', NULL, NULL),
(36, 6, 'Advanced CSS', NULL, NULL),
(37, 6, 'JavaScript Functions and Scope', NULL, NULL),
(38, 6, 'JavaScript Objects and Arrays', NULL, NULL),
(39, 6, 'ES6 Features', NULL, NULL),
(40, 6, 'Asynchronous JavaScript', NULL, NULL),
(41, 6, 'AJAX and Fetch API', NULL, NULL),
(42, 6, 'Introduction to Web APIs', NULL, NULL),
(43, 7, 'Introduction to Event Driven Programming', '', NULL),
(44, 7, 'Event Handling in AWT and  Swing ', '', NULL),
(45, 7, 'Advanced Swing Components', '', NULL),
(46, 7, 'Layout Management ', '', NULL),
(47, 7, 'Introduction to Databases ', '', NULL),
(48, 7, 'CRUD Operations Using JDBC ', '', NULL),
(49, 7, 'Exception Handling and Best  Practices ', '', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `topics`
--
ALTER TABLE `topics`
  ADD CONSTRAINT `topics_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
