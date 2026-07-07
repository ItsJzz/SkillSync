-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 04, 2025 at 06:49 AM
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
-- Table structure for table `coding_practice_completed`
--

CREATE TABLE `coding_practice_completed` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `problem_id` int(11) NOT NULL,
  `language` varchar(20) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coding_practice_completed`
--

INSERT INTO `coding_practice_completed` (`id`, `user_id`, `problem_id`, `language`, `completed_at`) VALUES
(1, 6, 1, 'java', '2025-10-03 15:28:43'),
(2, 6, 2, 'java', '2025-10-03 15:29:15'),
(3, 6, 1, 'javascript', '2025-10-03 15:44:17');

-- --------------------------------------------------------

--
-- Table structure for table `coding_practice_leaderboard`
--

CREATE TABLE `coding_practice_leaderboard` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `total_score` int(11) DEFAULT 0,
  `problems_solved` int(11) DEFAULT 0,
  `best_score` int(11) DEFAULT 0,
  `average_score` decimal(5,2) DEFAULT 0.00,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coding_practice_scores`
--

CREATE TABLE `coding_practice_scores` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `problem_id` int(11) NOT NULL,
  `language` varchar(50) NOT NULL,
  `code` text NOT NULL,
  `score` int(11) DEFAULT 0,
  `test_cases_passed` int(11) DEFAULT 0,
  `total_test_cases` int(11) DEFAULT 0,
  `execution_time` decimal(10,3) DEFAULT NULL,
  `memory_used` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coding_problems`
--

CREATE TABLE `coding_problems` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `difficulty` enum('Easy','Medium','Intermediate','Hard') DEFAULT 'Easy',
  `examples` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`examples`)),
  `test_cases` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`test_cases`)),
  `skeleton` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skeleton`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coding_problems`
--

INSERT INTO `coding_problems` (`id`, `title`, `description`, `difficulty`, `examples`, `test_cases`, `skeleton`, `created_at`, `updated_at`) VALUES
(1, 'Two Sum', 'Given an array of integers nums and an integer target, return indices of the two numbers such that they add up to target. You may assume that each input would have exactly one solution, and you may not use the same element twice.', 'Easy', '[{\"input\": \"nums = [2,7,11,15], target = 9\", \"output\": \"[0,1]\", \"explanation\": \"Because nums[0] + nums[1] == 9, we return [0, 1]\"}]', '[{\"input\": \"[2,7,11,15]\", \"target\": 9, \"expected\": \"[0,1]\"}, {\"input\": \"[3,2,4]\", \"target\": 6, \"expected\": \"[1,2]\"}, {\"input\": \"[3,3]\", \"target\": 6, \"expected\": \"[0,1]\"}]', '{\"javascript\": \"function twoSum(nums, target) {\\n    // Your code here\\n    \\n}\", \"python\": \"def two_sum(nums, target):\\n    # Your code here\\n    pass\", \"java\": \"public int[] twoSum(int[] nums, int target) {\\n    // Your code here\\n    \\n}\", \"cpp\": \"vector<int> twoSum(vector<int>& nums, int target) {\\n    // Your code here\\n    \\n}\"}', '2025-09-29 17:18:31', '2025-09-29 17:18:31'),
(2, 'Reverse String', 'Write a function that reverses a string. The input string is given as an array of characters s. You must do this by modifying the input array in-place with O(1) extra memory.', 'Easy', '[{\"input\": \"s = [\\\"h\\\",\\\"e\\\",\\\"l\\\",\\\"l\\\",\\\"o\\\"]\", \"output\": \"[\\\"o\\\",\\\"l\\\",\\\"l\\\",\\\"e\\\",\\\"h\\\"]\"}, {\"input\": \"s = [\\\"H\\\",\\\"a\\\",\\\"n\\\",\\\"n\\\",\\\"a\\\",\\\"h\\\"]\", \"output\": \"[\\\"h\\\",\\\"a\\\",\\\"n\\\",\\\"n\\\",\\\"a\\\",\\\"H\\\"]\"}]', '[{\"input\": \"[\\\"h\\\",\\\"e\\\",\\\"l\\\",\\\"l\\\",\\\"o\\\"]\", \"expected\": \"[\\\"o\\\",\\\"l\\\",\\\"l\\\",\\\"e\\\",\\\"h\\\"]\"}, {\"input\": \"[\\\"H\\\",\\\"a\\\",\\\"n\\\",\\\"n\\\",\\\"a\\\",\\\"h\\\"]\", \"expected\": \"[\\\"h\\\",\\\"a\\\",\\\"n\\\",\\\"n\\\",\\\"a\\\",\\\"H\\\"]\"}, {\"input\": \"[\\\"A\\\"]\", \"expected\": \"[\\\"A\\\"]\"}]', '{\"javascript\": \"function reverseString(s) {\\n    // Your code here\\n    \\n}\", \"python\": \"def reverse_string(s):\\n    # Your code here\\n    pass\", \"java\": \"public void reverseString(char[] s) {\\n    // Your code here\\n    \\n}\", \"cpp\": \"void reverseString(vector<char>& s) {\\n    // Your code here\\n    \\n}\"}', '2025-09-29 17:18:31', '2025-09-29 17:18:31'),
(3, 'Palindrome Number', 'Given an integer x, return true if x is palindrome integer. An integer is a palindrome when it reads the same backward as forward.', 'Easy', '[{\"input\": \"x = 121\", \"output\": \"true\", \"explanation\": \"121 reads as 121 from left to right and from right to left\"}, {\"input\": \"x = -121\", \"output\": \"false\", \"explanation\": \"From left to right, it reads -121. From right to left, it becomes 121-\"}]', '[{\"input\": 121, \"expected\": true}, {\"input\": -121, \"expected\": false}, {\"input\": 10, \"expected\": false}, {\"input\": 0, \"expected\": true}]', '{\"javascript\": \"function isPalindrome(x) {\\n    // Your code here\\n    \\n}\", \"python\": \"def is_palindrome(x):\\n    # Your code here\\n    pass\", \"java\": \"public boolean isPalindrome(int x) {\\n    // Your code here\\n    \\n}\", \"cpp\": \"bool isPalindrome(int x) {\\n    // Your code here\\n    \\n}\"}', '2025-09-29 17:18:31', '2025-09-29 17:18:31'),
(4, 'Maximum Subarray', 'Given an integer array nums, find the contiguous subarray (containing at least one number) which has the largest sum and return its sum.', 'Medium', '[{\"input\": \"nums = [-2,1,-3,4,-1,2,1,-5,4]\", \"output\": \"6\", \"explanation\": \"[4,-1,2,1] has the largest sum = 6\"}, {\"input\": \"nums = [1]\", \"output\": \"1\"}, {\"input\": \"nums = [5,4,-1,7,8]\", \"output\": \"23\"}]', '[{\"input\": \"[-2,1,-3,4,-1,2,1,-5,4]\", \"expected\": 6}, {\"input\": \"[1]\", \"expected\": 1}, {\"input\": \"[5,4,-1,7,8]\", \"expected\": 23}, {\"input\": \"[-1]\", \"expected\": -1}]', '{\"javascript\": \"function maxSubArray(nums) {\\n    // Your code here\\n    \\n}\", \"python\": \"def max_sub_array(nums):\\n    # Your code here\\n    pass\", \"java\": \"public int maxSubArray(int[] nums) {\\n    // Your code here\\n    \\n}\", \"cpp\": \"int maxSubArray(vector<int>& nums) {\\n    // Your code here\\n    \\n}\"}', '2025-09-29 17:18:31', '2025-09-29 17:18:31'),
(5, 'Valid Parentheses', 'Given a string s containing just the characters \"(\" , \")\" , \"{\" , \"}\" , \"[\" and \"]\", determine if the input string is valid. An input string is valid if brackets are opened and closed in the correct order.', 'Easy', '[{\"input\": \"s = \\\"()\\\"\", \"output\": \"true\"}, {\"input\": \"s = \\\"()[]{}\\\"\", \"output\": \"true\"}, {\"input\": \"s = \\\"(]\\\"\", \"output\": \"false\"}]', '[{\"input\": \"\\\"()\\\"\", \"expected\": true}, {\"input\": \"\\\"()[]{}\\\"\", \"expected\": true}, {\"input\": \"\\\"(]\\\"\", \"expected\": false}, {\"input\": \"\\\"([)]\\\"\", \"expected\": false}, {\"input\": \"\\\"{[]}\\\"\", \"expected\": true}]', '{\"javascript\": \"function isValid(s) {\\n    // Your code here\\n    \\n}\", \"python\": \"def is_valid(s):\\n    # Your code here\\n    pass\", \"java\": \"public boolean isValid(String s) {\\n    // Your code here\\n    \\n}\", \"cpp\": \"bool isValid(string s) {\\n    // Your code here\\n    \\n}\"}', '2025-09-29 17:18:31', '2025-09-29 17:18:31'),
(6, 'Binary Tree Inorder Traversal', 'Given the root of a binary tree, return the inorder traversal of its nodes values.', 'Intermediate', '[{\"input\": \"root = [1,null,2,3]\", \"output\": \"[1,3,2]\"}]', '[{\"input\": \"[1,null,2,3]\", \"expected\": \"[1,3,2]\"}, {\"input\": \"[]\", \"expected\": \"[]\"}, {\"input\": \"[1]\", \"expected\": \"[1]\"}]', '{\"javascript\": \"function inorderTraversal(root) {\\n    // Your code here\\n    return [];\\n}\", \"python\": \"def inorder_traversal(root):\\n    # Your code here\\n    return []\", \"java\": \"public List<Integer> inorderTraversal(TreeNode root) {\\n    // Your code here\\n    return new ArrayList<>();\\n}\", \"cpp\": \"vector<int> inorderTraversal(TreeNode* root) {\\n    // Your code here\\n    return {};\\n}\"}', '2025-10-01 13:05:26', '2025-10-01 13:05:26'),
(7, 'Longest Substring Without Repeating Characters', 'Given a string s, find the length of the longest substring without repeating characters.', 'Intermediate', '[{\"input\": \"s = abcabcbb\", \"output\": \"3\"}]', '[{\"input\": \"abcabcbb\", \"expected\": 3}, {\"input\": \"bbbbb\", \"expected\": 1}, {\"input\": \"pwwkew\", \"expected\": 3}]', '{\"javascript\": \"function lengthOfLongestSubstring(s) {\\n    // Your code here\\n    return 0;\\n}\", \"python\": \"def length_of_longest_substring(s):\\n    # Your code here\\n    return 0\", \"java\": \"public int lengthOfLongestSubstring(String s) {\\n    // Your code here\\n    return 0;\\n}\", \"cpp\": \"int lengthOfLongestSubstring(string s) {\\n    // Your code here\\n    return 0;\\n}\"}', '2025-10-01 13:05:26', '2025-10-01 13:05:26'),
(8, 'Merge k Sorted Lists', 'You are given an array of k linked-lists, each sorted in ascending order. Merge all into one sorted list.', 'Hard', '[{\"input\": \"lists = [[1,4,5],[1,3,4],[2,6]]\", \"output\": \"[1,1,2,3,4,4,5,6]\"}]', '[{\"input\": \"[[1,4,5],[1,3,4],[2,6]]\", \"expected\": \"[1,1,2,3,4,4,5,6]\"}, {\"input\": \"[]\", \"expected\": \"[]\"}]', '{\"javascript\": \"function mergeKLists(lists) {\\n    // Your code here\\n    return null;\\n}\", \"python\": \"def merge_k_lists(lists):\\n    # Your code here\\n    return None\", \"java\": \"public ListNode mergeKLists(ListNode[] lists) {\\n    // Your code here\\n    return null;\\n}\", \"cpp\": \"ListNode* mergeKLists(vector<ListNode*>& lists) {\\n    // Your code here\\n    return nullptr;\\n}\"}', '2025-10-01 13:05:27', '2025-10-01 13:05:27'),
(9, 'Regular Expression Matching', 'Implement regular expression matching with support for . and *.', 'Hard', '[{\"input\": \"s = aa, p = a\", \"output\": \"false\"}, {\"input\": \"s = aa, p = a*\", \"output\": \"true\"}]', '[{\"input\": \"s=aa, p=a\", \"expected\": false}, {\"input\": \"s=aa, p=a*\", \"expected\": true}]', '{\"javascript\": \"function isMatch(s, p) {\\n    // Your code here\\n    return false;\\n}\", \"python\": \"def is_match(s, p):\\n    # Your code here\\n    return False\", \"java\": \"public boolean isMatch(String s, String p) {\\n    // Your code here\\n    return false;\\n}\", \"cpp\": \"bool isMatch(string s, string p) {\\n    // Your code here\\n    return false;\\n}\"}', '2025-10-01 13:05:27', '2025-10-01 13:05:27');

-- --------------------------------------------------------

--
-- Table structure for table `identified_weak_areas`
--

CREATE TABLE `identified_weak_areas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `weak_area_category` enum('logic_problem_solving','oop_concepts','data_structures','debugging','design_patterns','syntax','algorithms') NOT NULL,
  `weakness_score` decimal(5,2) NOT NULL,
  `evidence_sources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`evidence_sources`)),
  `improvement_suggestions` text DEFAULT NULL,
  `identified_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `identified_weak_areas`
--

INSERT INTO `identified_weak_areas` (`id`, `user_id`, `subject_id`, `topic_id`, `weak_area_category`, `weakness_score`, `evidence_sources`, `improvement_suggestions`, `identified_at`, `resolved_at`) VALUES
(1, 5, 3, NULL, 'design_patterns', 54.00, '[\"Pattern identified in academic records\"]', 'Study common design patterns and their applications', '2025-10-01 15:09:55', NULL),
(2, 5, 3, NULL, 'oop_concepts', 51.00, '[\"Pattern identified in academic records\"]', 'Study object-oriented programming principles with practical examples', '2025-10-01 16:02:54', NULL),
(3, 5, 3, NULL, 'oop_concepts', 50.00, '[\"Pattern identified in academic records\"]', 'Study object-oriented programming principles with practical examples', '2025-10-01 16:04:23', NULL),
(4, 5, 3, NULL, 'design_patterns', 38.00, '[\"Pattern identified in academic records\"]', 'Study common design patterns and their applications', '2025-10-01 16:27:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `learning_analytics`
--

CREATE TABLE `learning_analytics` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `study_time_minutes` int(11) DEFAULT 0,
  `activities_completed` int(11) DEFAULT 0,
  `quiz_attempts` int(11) DEFAULT 0,
  `average_quiz_score` decimal(5,2) DEFAULT NULL,
  `code_submissions` int(11) DEFAULT 0,
  `code_success_rate` decimal(5,2) DEFAULT NULL,
  `help_requests` int(11) DEFAULT 0,
  `engagement_score` decimal(5,2) DEFAULT NULL,
  `learning_velocity` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learning_materials`
--

CREATE TABLE `learning_materials` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `type` enum('video','pdf','simulation') NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `learning_materials`
--

INSERT INTO `learning_materials` (`id`, `topic_id`, `type`, `title`, `url`, `created_at`) VALUES
(2, 14, 'video', 'A YouTube Video About Classes and Objects', 'https://www.youtube.com/embed/sd-S0yu9qus', '2025-09-22 13:18:41'),
(4, 14, 'simulation', 'Classes and Objects Simulation', 'classes-objects-simulation.php', '2025-09-22 13:18:41'),
(6, 14, 'pdf', 'Programming in Business Analytics Syllabus', 'modules/programming_business_analytics.pdf', '2025-09-22 13:37:39'),
(11, 14, 'video', 'try', 'https://www.youtube.com/embed/VhdAZ2aQtBk', '2025-09-22 13:56:52'),
(12, 13, 'video', 'try', 'https://www.youtube.com/embed/VhdAZ2aQtBk', '2025-09-22 13:56:52'),
(13, 15, 'video', 'try', 'https://www.youtube.com/embed/VhdAZ2aQtBk', '2025-09-22 13:56:52'),
(14, 16, 'video', 'try', 'https://www.youtube.com/embed/VhdAZ2aQtBk', '2025-09-22 13:56:52'),
(15, 17, 'video', 'try', 'https://www.youtube.com/embed/VhdAZ2aQtBk', '2025-09-22 13:56:52'),
(16, 41, 'video', 'try', 'https://www.youtube.com/embed/VhdAZ2aQtBk', '2025-09-22 13:56:52'),
(17, 41, 'pdf', 'Programming in Business Analytics Syllabus', 'modules/programming_business_analytics.pdf', '2025-09-22 13:37:39');

-- --------------------------------------------------------

--
-- Table structure for table `level_promotion_attempts`
--

CREATE TABLE `level_promotion_attempts` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `current_level` varchar(50) NOT NULL,
  `target_level` varchar(50) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `total_questions` int(11) NOT NULL,
  `correct_count` int(11) NOT NULL,
  `answers_data` text NOT NULL,
  `attempt_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `level_promotion_attempts`
--

INSERT INTO `level_promotion_attempts` (`id`, `student_id`, `subject_id`, `current_level`, `target_level`, `score`, `passed`, `total_questions`, `correct_count`, `answers_data`, `attempt_date`) VALUES
(1, 6, 3, 'Beginner', 'Intermediate', 18.00, 0, 50, 9, '{\"answers\":{\"q318\":\"A\",\"q314\":\"A\",\"q317\":\"A\",\"q316\":\"A\",\"q322\":\"A\",\"q334\":\"A\",\"q315\":\"B\",\"q289\":\"B\",\"q294\":\"A\",\"q286\":\"A\",\"q392\":\"A\",\"q399\":\"B\",\"q372\":\"C\",\"q366\":\"A\",\"q359\":\"A\",\"q393\":\"A\",\"q382\":\"C\",\"q376\":\"C\",\"q352\":\"C\",\"q371\":\"C\",\"q446\":\"A\",\"q437\":\"C\",\"q420\":\"A\",\"q401\":\"C\",\"q438\":\"C\",\"q433\":\"C\",\"q436\":\"C\",\"q439\":\"C\",\"q404\":\"C\",\"q432\":\"C\",\"q515\":\"A\",\"q470\":\"C\",\"q477\":\"B\",\"q506\":\"B\",\"q505\":\"C\",\"q487\":\"A\",\"q501\":\"C\",\"q509\":\"A\",\"q493\":\"B\",\"q503\":\"B\",\"q571\":\"A\",\"q526\":\"C\",\"q579\":\"A\",\"q567\":\"C\",\"q551\":\"B\",\"q533\":\"B\",\"q576\":\"A\",\"q549\":\"B\",\"q554\":\"A\",\"q555\":\"A\"},\"details\":[{\"question_id\":\"318\",\"topic_id\":13,\"topic_name\":\"Introduction to OOP Concepts\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"314\",\"topic_id\":13,\"topic_name\":\"Introduction to OOP Concepts\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"317\",\"topic_id\":13,\"topic_name\":\"Introduction to OOP Concepts\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"316\",\"topic_id\":13,\"topic_name\":\"Introduction to OOP Concepts\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"322\",\"topic_id\":13,\"topic_name\":\"Introduction to OOP Concepts\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"334\",\"topic_id\":13,\"topic_name\":\"Introduction to OOP Concepts\",\"class_level\":\"Intermediate\",\"question_type\":\"Simulation question\",\"student_answer\":\"A\",\"correct_answer\":\"C\",\"is_correct\":false,\"points\":2},{\"question_id\":\"315\",\"topic_id\":13,\"topic_name\":\"Introduction to OOP Concepts\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"B\",\"correct_answer\":\"B\",\"is_correct\":true,\"points\":2},{\"question_id\":\"289\",\"topic_id\":13,\"topic_name\":\"Introduction to OOP Concepts\",\"class_level\":\"Beginner\",\"question_type\":\"Quiz question\",\"student_answer\":\"B\",\"correct_answer\":\"B\",\"is_correct\":true,\"points\":2},{\"question_id\":\"294\",\"topic_id\":13,\"topic_name\":\"Introduction to OOP Concepts\",\"class_level\":\"Beginner\",\"question_type\":\"Quiz question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"286\",\"topic_id\":13,\"topic_name\":\"Introduction to OOP Concepts\",\"class_level\":\"Beginner\",\"question_type\":\"Quiz question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"392\",\"topic_id\":14,\"topic_name\":\"Classes and Objects\",\"class_level\":\"Intermediate\",\"question_type\":\"Simulation question\",\"student_answer\":\"A\",\"correct_answer\":\"A\",\"is_correct\":true,\"points\":2},{\"question_id\":\"399\",\"topic_id\":14,\"topic_name\":\"Classes and Objects\",\"class_level\":\"Intermediate\",\"question_type\":\"Simulation question\",\"student_answer\":\"B\",\"correct_answer\":\"B\",\"is_correct\":true,\"points\":2},{\"question_id\":\"372\",\"topic_id\":14,\"topic_name\":\"Classes and Objects\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"366\",\"topic_id\":14,\"topic_name\":\"Classes and Objects\",\"class_level\":\"Beginner\",\"question_type\":\"Simulation question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"359\",\"topic_id\":14,\"topic_name\":\"Classes and Objects\",\"class_level\":\"Beginner\",\"question_type\":\"Simulation question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"393\",\"topic_id\":14,\"topic_name\":\"Classes and Objects\",\"class_level\":\"Intermediate\",\"question_type\":\"Simulation question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"382\",\"topic_id\":14,\"topic_name\":\"Classes and Objects\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"376\",\"topic_id\":14,\"topic_name\":\"Classes and Objects\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"352\",\"topic_id\":14,\"topic_name\":\"Classes and Objects\",\"class_level\":\"Beginner\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"371\",\"topic_id\":14,\"topic_name\":\"Classes and Objects\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"446\",\"topic_id\":15,\"topic_name\":\"Encapsulation\",\"class_level\":\"Intermediate\",\"question_type\":\"Simulation question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"437\",\"topic_id\":15,\"topic_name\":\"Encapsulation\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"420\",\"topic_id\":15,\"topic_name\":\"Encapsulation\",\"class_level\":\"Beginner\",\"question_type\":\"Simulation question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"401\",\"topic_id\":15,\"topic_name\":\"Encapsulation\",\"class_level\":\"Beginner\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"438\",\"topic_id\":15,\"topic_name\":\"Encapsulation\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"433\",\"topic_id\":15,\"topic_name\":\"Encapsulation\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"436\",\"topic_id\":15,\"topic_name\":\"Encapsulation\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"439\",\"topic_id\":15,\"topic_name\":\"Encapsulation\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"404\",\"topic_id\":15,\"topic_name\":\"Encapsulation\",\"class_level\":\"Beginner\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"432\",\"topic_id\":15,\"topic_name\":\"Encapsulation\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"515\",\"topic_id\":16,\"topic_name\":\"Inheritance\",\"class_level\":\"Intermediate\",\"question_type\":\"Simulation question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"470\",\"topic_id\":16,\"topic_name\":\"Inheritance\",\"class_level\":\"Beginner\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"477\",\"topic_id\":16,\"topic_name\":\"Inheritance\",\"class_level\":\"Beginner\",\"question_type\":\"Simulation question\",\"student_answer\":\"B\",\"correct_answer\":\"B\",\"is_correct\":true,\"points\":2},{\"question_id\":\"506\",\"topic_id\":16,\"topic_name\":\"Inheritance\",\"class_level\":\"Intermediate\",\"question_type\":\"Simulation question\",\"student_answer\":\"B\",\"correct_answer\":\"A\",\"is_correct\":false,\"points\":2},{\"question_id\":\"505\",\"topic_id\":16,\"topic_name\":\"Inheritance\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"487\",\"topic_id\":16,\"topic_name\":\"Inheritance\",\"class_level\":\"Beginner\",\"question_type\":\"Simulation question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"501\",\"topic_id\":16,\"topic_name\":\"Inheritance\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"509\",\"topic_id\":16,\"topic_name\":\"Inheritance\",\"class_level\":\"Intermediate\",\"question_type\":\"Simulation question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"493\",\"topic_id\":16,\"topic_name\":\"Inheritance\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"B\",\"correct_answer\":\"A\",\"is_correct\":false,\"points\":2},{\"question_id\":\"503\",\"topic_id\":16,\"topic_name\":\"Inheritance\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"B\",\"correct_answer\":\"B\",\"is_correct\":true,\"points\":2},{\"question_id\":\"571\",\"topic_id\":17,\"topic_name\":\"Polymorphism\",\"class_level\":\"Intermediate\",\"question_type\":\"Simulation question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"526\",\"topic_id\":17,\"topic_name\":\"Polymorphism\",\"class_level\":\"Beginner\",\"question_type\":\"Quiz question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"579\",\"topic_id\":17,\"topic_name\":\"Polymorphism\",\"class_level\":\"Intermediate\",\"question_type\":\"Simulation question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"567\",\"topic_id\":17,\"topic_name\":\"Polymorphism\",\"class_level\":\"Intermediate\",\"question_type\":\"Simulation question\",\"student_answer\":\"C\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"551\",\"topic_id\":17,\"topic_name\":\"Polymorphism\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"B\",\"correct_answer\":\"B\",\"is_correct\":true,\"points\":2},{\"question_id\":\"533\",\"topic_id\":17,\"topic_name\":\"Polymorphism\",\"class_level\":\"Beginner\",\"question_type\":\"Quiz question\",\"student_answer\":\"B\",\"correct_answer\":\"B\",\"is_correct\":true,\"points\":2},{\"question_id\":\"576\",\"topic_id\":17,\"topic_name\":\"Polymorphism\",\"class_level\":\"Intermediate\",\"question_type\":\"Simulation question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"549\",\"topic_id\":17,\"topic_name\":\"Polymorphism\",\"class_level\":\"Beginner\",\"question_type\":\"Simulation question\",\"student_answer\":\"B\",\"correct_answer\":\"B\",\"is_correct\":true,\"points\":2},{\"question_id\":\"554\",\"topic_id\":17,\"topic_name\":\"Polymorphism\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2},{\"question_id\":\"555\",\"topic_id\":17,\"topic_name\":\"Polymorphism\",\"class_level\":\"Intermediate\",\"question_type\":\"Quiz question\",\"student_answer\":\"A\",\"correct_answer\":\"B\",\"is_correct\":false,\"points\":2}],\"total_questions\":50,\"correct_count\":9}', '2025-10-02 15:27:05');

-- --------------------------------------------------------

--
-- Table structure for table `level_promotion_tests`
--

CREATE TABLE `level_promotion_tests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `from_level` enum('Beginner','Intermediate') NOT NULL,
  `to_level` enum('Intermediate','Expert') NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `passed` tinyint(1) NOT NULL,
  `test_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`test_data`)),
  `taken_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `level_promotion_tests`
--

INSERT INTO `level_promotion_tests` (`id`, `student_id`, `from_level`, `to_level`, `score`, `passed`, `test_data`, `taken_at`) VALUES
(1, 6, 'Beginner', 'Intermediate', 78.00, 1, '{\"answers\":{\"q297\":\"B\",\"q320\":\"B\",\"q285\":\"B\",\"q313\":\"B\",\"q335\":\"B\",\"q310\":\"A\",\"q316\":\"B\",\"q333\":\"B\",\"q324\":\"B\",\"q332\":\"C\",\"q354\":\"B\",\"q355\":\"B\",\"q373\":\"B\",\"q384\":\"B\",\"q383\":\"B\",\"q382\":\"B\",\"q378\":\"A\",\"q393\":\"B\",\"q400\":\"B\",\"q356\":\"B\",\"q439\":\"B\",\"q442\":\"B\",\"q406\":\"C\",\"q446\":\"B\",\"q426\":\"B\",\"q433\":\"B\",\"q441\":\"B\",\"q455\":\"B\",\"q450\":\"B\",\"q407\":\"B\",\"q514\":\"B\",\"q511\":\"A\",\"q477\":\"B\",\"q491\":\"A\",\"q510\":\"B\",\"q484\":\"B\",\"q507\":\"C\",\"q482\":\"B\",\"q501\":\"B\",\"q502\":\"B\",\"q562\":\"B\",\"q549\":\"B\",\"q564\":\"B\",\"q541\":\"B\",\"q523\":\"B\",\"q579\":\"B\",\"q561\":\"B\",\"q554\":\"B\",\"q572\":\"B\",\"q565\":\"B\"},\"total_questions\":50,\"correct_count\":39,\"subject_id\":3}', '2025-10-02 13:03:23'),
(2, 6, 'Beginner', 'Intermediate', 74.00, 0, '{\"answers\":{\"q290\":\"B\",\"q326\":\"B\",\"q331\":\"B\",\"q339\":\"B\",\"q313\":\"B\",\"q314\":\"B\",\"q315\":\"B\",\"q307\":\"B\",\"q301\":\"B\",\"q328\":\"B\",\"q373\":\"B\",\"q379\":\"B\",\"q389\":\"B\",\"q346\":\"B\",\"q341\":\"B\",\"q384\":\"B\",\"q352\":\"B\",\"q374\":\"B\",\"q377\":\"B\",\"q396\":\"B\",\"q430\":\"B\",\"q456\":\"C\",\"q428\":\"B\",\"q435\":\"B\",\"q438\":\"B\",\"q417\":\"B\",\"q432\":\"B\",\"q449\":\"B\",\"q458\":\"B\",\"q442\":\"A\",\"q463\":\"B\",\"q513\":\"B\",\"q461\":\"B\",\"q500\":\"B\",\"q502\":\"B\",\"q511\":\"B\",\"q485\":\"B\",\"q516\":\"B\",\"q504\":\"B\",\"q508\":\"B\",\"q576\":\"B\",\"q572\":\"B\",\"q577\":\"B\",\"q575\":\"B\",\"q552\":\"B\",\"q555\":\"B\",\"q522\":\"B\",\"q537\":\"B\",\"q571\":\"B\",\"q535\":\"B\"},\"total_questions\":50,\"correct_count\":37,\"subject_id\":3}', '2025-10-02 13:11:03'),
(3, 6, 'Beginner', 'Intermediate', 84.00, 1, '{\"answers\":{\"q326\":\"B\",\"q325\":\"B\",\"q328\":\"B\",\"q318\":\"B\",\"q332\":\"B\",\"q304\":\"B\",\"q284\":\"B\",\"q282\":\"B\",\"q331\":\"B\",\"q324\":\"B\",\"q364\":\"B\",\"q391\":\"B\",\"q397\":\"B\",\"q357\":\"B\",\"q377\":\"B\",\"q400\":\"B\",\"q392\":\"B\",\"q361\":\"B\",\"q384\":\"B\",\"q372\":\"B\",\"q426\":\"B\",\"q458\":\"B\",\"q417\":\"B\",\"q447\":\"B\",\"q459\":\"B\",\"q449\":\"B\",\"q457\":\"B\",\"q438\":\"B\",\"q416\":\"B\",\"q460\":\"B\",\"q514\":\"B\",\"q479\":\"B\",\"q465\":\"B\",\"q501\":\"B\",\"q464\":\"B\",\"q517\":\"B\",\"q504\":\"B\",\"q491\":\"B\",\"q494\":\"B\",\"q519\":\"B\",\"q576\":\"B\",\"q555\":\"B\",\"q525\":\"B\",\"q550\":\"A\",\"q570\":\"B\",\"q552\":\"B\",\"q569\":\"B\",\"q526\":\"B\",\"q553\":\"B\",\"q575\":\"B\"},\"total_questions\":50,\"correct_count\":42,\"subject_id\":3}', '2025-10-02 13:12:05');

-- --------------------------------------------------------

--
-- Table structure for table `login_credentials`
--

CREATE TABLE `login_credentials` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_preassessment` tinyint(1) DEFAULT 0,
  `role` enum('student','admin') NOT NULL DEFAULT 'student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_credentials`
--

INSERT INTO `login_credentials` (`id`, `username`, `email`, `password`, `created_at`, `completed_preassessment`, `role`) VALUES
(1, 'jamespaul', 'jamespaulbaylon@gmail.com', '$2y$10$TNJMxgoKT5DogNsiPgZMuOVAZX1PdO8R50rTHbCDDbNmqJLA9Mviu', '2025-09-10 11:49:51', 1, 'student'),
(2, 'empot', 'clintyasay@gmail.com', '$2y$10$DGEEOfWv4.yNB9N4k.btb.TpZgzT1RHIU4KDoY2eTmcYkV5aYe4Ga', '2025-09-13 13:02:49', 1, 'student'),
(3, 'admin', 'admin@skillsync.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-10-01 03:33:06', 1, 'admin'),
(4, 'james', 'jamespaulbaylon1@gmail.com', '$2y$10$HwGcNSM47mcSPxtu/NyNFO.JaAoNmtblgxBqQF8sxUagia.wbXyym', '2025-10-01 04:21:39', 1, 'student'),
(5, 'james', 'jamespaulbaylonss@gmail.com', '$2y$10$WOQSpSY6XpoCatZFtsGkEO4nuXPIVzfyLf1q1Zw8sj0IxML.5zQJu', '2025-10-01 13:55:24', 1, 'student'),
(6, 'jameszzz', 'eeeee@gmail.com', '$2y$10$ToPV0CJQYpa9l5YQargu2OFqK0UYx5GmGWx3apsCYAzEpj6n4fFs.', '2025-10-01 16:46:41', 1, 'student'),
(7, 'james', 'jamespaulbaylons@gmail.com', '$2y$10$DN1UB7ui0f7bwgzAajLZq.HchKCnprErAYuf8SpXCfvBOUrXkql/q', '2025-10-03 15:59:36', 0, 'student');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `code_snippet` text DEFAULT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) NOT NULL,
  `correct_option` varchar(255) DEFAULT NULL,
  `class_level` enum('Beginner','Intermediate','Expert') DEFAULT 'Beginner',
  `question_type` enum('Quiz question','Simulation question') DEFAULT 'Quiz question'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `topic_id`, `question_text`, `code_snippet`, `option_a`, `option_b`, `option_c`, `correct_option`, `class_level`, `question_type`) VALUES
(281, 13, 'What does OOP stand for?', '', 'Object Oriented Programming', 'Organized Object Programming', 'Optional Object Processing', 'A', 'Beginner', 'Quiz question'),
(282, 13, 'Which of the following is a fundamental principle of OOP?', '', 'Inheritance', 'Compilation', 'Debugging', 'A', 'Beginner', 'Quiz question'),
(283, 13, 'What is a class in OOP?', '', 'A running program', 'A blueprint for creating objects', 'A database table', 'B', 'Beginner', 'Quiz question'),
(284, 13, 'What is an object in OOP?', '', 'A variable', 'An instance of a class', 'A method', 'B', 'Beginner', 'Quiz question'),
(285, 13, 'In Java, what keyword is used to create a new object?', '', 'create', 'new', 'object', 'B', 'Beginner', 'Quiz question'),
(286, 13, 'What is the main benefit of using OOP?', '', 'Faster execution', 'Code reusability and organization', 'Less memory usage', 'B', 'Beginner', 'Quiz question'),
(287, 13, 'Which of these is NOT a pillar of OOP?', '', 'Encapsulation', 'Inheritance', 'Compilation', 'C', 'Beginner', 'Quiz question'),
(288, 13, 'What is encapsulation in OOP?', '', 'Creating multiple objects', 'Hiding internal details of a class', 'Inheriting from another class', 'B', 'Beginner', 'Quiz question'),
(289, 13, 'What is inheritance in OOP?', '', 'Creating new objects', 'A class acquiring properties from another class', 'Hiding data', 'B', 'Beginner', 'Quiz question'),
(290, 13, 'What is polymorphism in OOP?', '', 'Having multiple constructors', 'Same interface, different implementations', 'Creating private methods', 'B', 'Beginner', 'Quiz question'),
(291, 13, 'Which access modifier makes a member accessible only within the same class?', '', 'public', 'protected', 'private', 'C', 'Beginner', 'Quiz question'),
(292, 13, 'What is a constructor in OOP?', '', 'A method that destroys objects', 'A special method called when creating objects', 'A variable in a class', 'B', 'Beginner', 'Quiz question'),
(293, 13, 'What is the difference between a class and an object?', '', 'They are the same thing', 'Class is a blueprint, object is an instance', 'Class stores data, object stores methods', 'B', 'Beginner', 'Quiz question'),
(294, 13, 'Which keyword is used for inheritance in Java?', '', 'inherits', 'extends', 'implements', 'B', 'Beginner', 'Quiz question'),
(295, 13, 'What happens when you call a method on an object?', '', 'The object is destroyed', 'The method executes using the object\'s data', 'A new object is created', 'B', 'Beginner', 'Quiz question'),
(296, 13, 'Given this class definition, what will happen when you create an object? String name; int age; } Student s = new Student();', 'class Student {', 'Compilation error', 'Object created with default values', 'Runtime error', 'B', 'Beginner', 'Simulation question'),
(297, 13, 'What is the output of this code? String brand = \"Toyota\"; } Car myCar = new Car();', 'class Car {\nSystem.out.println(myCar.brand);', 'null', 'Toyota', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(298, 13, 'Which line correctly creates an object of class Book? String title; String author; }', 'class Book {', 'Book.title = \"Java Guide\";', 'Book myBook = new Book();', 'Book();', 'B', 'Beginner', 'Simulation question'),
(299, 13, 'What will this code print? String name; } Person p = new Person(); p.name = \"John\";', 'class Person {\nSystem.out.println(p.name);', 'null', 'John', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(300, 13, 'Given this class, what is the correct way to access the field \'price\'? } Product item = new Product();', 'class Product {\npublic double price;', 'item->price', 'item.price', 'item::price', 'B', 'Beginner', 'Simulation question'),
(301, 13, 'What happens when you run this code? void makeSound() { } }', 'class Animal {\nSystem.out.println(\"Some sound\");\nAnimal a = new Animal();', 'Prints \"Some sound\"', 'Compilation error', 'Runtime error', 'A', 'Beginner', 'Simulation question'),
(302, 13, 'Which constructor call is correct for this class? int width, height; Rectangle(int w, int h) { width = w; height = h; } }', 'class Rectangle {', 'Rectangle r = new Rectangle();', 'Rectangle r = new Rectangle(5, 10);', 'Rectangle r = Rectangle(5, 10);', 'B', 'Beginner', 'Simulation question'),
(303, 13, 'What will be the value of \'count\' after this code executes? int count = 0; void increment() { count++; } } Counter c = new Counter();', 'class Counter {', '0', '1', '2', 'C', 'Beginner', 'Simulation question'),
(304, 13, 'What is the output of this inheritance example? void display() { } } } Child c = new Child();', 'class Parent {\nSystem.out.println(\"Parent\");\nclass Child extends Parent {', 'Parent', 'Child', 'Compilation error', 'A', 'Beginner', 'Simulation question'),
(305, 13, 'Which method call is correct for this class? return a + b; } } Calculator calc = new Calculator();', 'class Calculator {\npublic int add(int a, int b) {', 'calc.add(5, 3)', 'Calculator.add(5, 3)', 'add(5, 3)', 'A', 'Beginner', 'Simulation question'),
(306, 13, 'What will this polymorphism example print? void sound() { System.out.println(\"Animal sound\"); } } void sound() { System.out.println(\"Bark\"); } }', 'class Animal {\nclass Dog extends Animal {\nAnimal a = new Dog();', 'Animal sound', 'Bark', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(307, 13, 'What is the result of accessing a private field from outside the class? } Example e = new Example();', 'class Example {\nprivate int value = 10;\nSystem.out.println(e.value);', 'Prints 10', 'Prints 0', 'Compilation error', 'C', 'Beginner', 'Simulation question'),
(308, 13, 'What happens with this encapsulation example? return balance; } } Account acc = new Account();', 'class Account {\nprivate double balance = 1000;\npublic double getBalance() {\nSystem.out.println(acc.getBalance());', 'Compilation error', 'Prints 1000.0', 'Prints 0.0', 'B', 'Beginner', 'Simulation question'),
(309, 13, 'Which object creation is valid for this class? String model; model = m; } }', 'class Phone {\nPhone(String m) {', 'Phone p = new Phone();', 'Phone p = new Phone(\"iPhone\");', 'Phone p = Phone(\"iPhone\");', 'B', 'Beginner', 'Simulation question'),
(310, 13, 'What will this method overriding example output? void start() { System.out.println(\"Vehicle starts\"); } } void start() { System.out.println(\"Car starts\"); } } Car c = new Car();', 'class Vehicle {\nclass Car extends Vehicle {', 'Vehicle starts', 'Car starts', 'Both messages', 'B', 'Beginner', 'Simulation question'),
(311, 13, 'What is method overloading in Java?', '', 'Overriding a method in subclass', 'Having multiple methods with same name but different parameters', 'Making a method private', 'B', 'Intermediate', 'Quiz question'),
(312, 13, 'Which of these demonstrates proper encapsulation?', '', 'Public fields with private methods', 'Private fields with public getter/setter methods', 'All fields and methods public', 'B', 'Intermediate', 'Quiz question'),
(313, 13, 'What is the super keyword used for in Java?', '', 'To create a new object', 'To call parent class constructor or methods', 'To make a method static', 'B', 'Intermediate', 'Quiz question'),
(314, 13, 'What is the difference between abstract class and interface?', '', 'No difference', 'Abstract class can have implementation, interface cannot (pre-Java 8)', 'Interface can have constructor, abstract class cannot', 'B', 'Intermediate', 'Quiz question'),
(315, 13, 'What is method overriding in inheritance?', '', 'Creating multiple methods with same name', 'Redefining a parent class method in child class', 'Making a method final', 'B', 'Intermediate', 'Quiz question'),
(316, 13, 'Which access modifier allows access within package and subclasses?', '', 'private', 'protected', 'default', 'B', 'Intermediate', 'Quiz question'),
(317, 13, 'What is composition in OOP?', '', 'Inheriting from multiple classes', 'Having objects as members of another class', 'Making all methods abstract', 'B', 'Intermediate', 'Quiz question'),
(318, 13, 'What does the final keyword do when applied to a class?', '', 'Makes all methods abstract', 'Prevents the class from being inherited', 'Makes the class static', 'B', 'Intermediate', 'Quiz question'),
(319, 13, 'What is the purpose of static methods?', '', 'They belong to the class, not instances', 'They can access private fields', 'They cannot be overridden', 'A', 'Intermediate', 'Quiz question'),
(320, 13, 'What is a singleton pattern?', '', 'A class that can have only one instance', 'A class with single method', 'A class that extends only one parent', 'A', 'Intermediate', 'Quiz question'),
(321, 13, 'What happens if you don\'t provide a constructor in a class?', '', 'Compilation error', 'Java provides a default constructor', 'Objects cannot be created', 'B', 'Intermediate', 'Quiz question'),
(322, 13, 'What is the instanceof operator used for?', '', 'Creating new instances', 'Checking if an object is an instance of a specific class', 'Comparing object values', 'B', 'Intermediate', 'Quiz question'),
(323, 13, 'What is the difference between == and .equals() in Java?', '', 'No difference', '== compares references, .equals() compares content', '== compares content, .equals() compares references', 'B', 'Intermediate', 'Quiz question'),
(324, 13, 'What is a nested class in Java?', '', 'A class that inherits from another class', 'A class defined inside another class', 'A class with multiple constructors', 'B', 'Intermediate', 'Quiz question'),
(325, 13, 'What is the purpose of the this keyword?', '', 'To call static methods', 'To refer to the current object instance', 'To create new objects', 'B', 'Intermediate', 'Quiz question'),
(326, 13, 'What will this method overloading example output? int add(int a, int b) { return a + b; } double add(double a, double b) { return a + b; } } Calculator calc = new Calculator();', 'class Calculator {\nSystem.out.println(calc.add(5, 3));', '8', '8.0', 'Compilation error', 'A', 'Intermediate', 'Simulation question'),
(327, 13, 'What is the output of this inheritance chain? void method() { System.out.println(\"A\"); } } void method() { System.out.println(\"B\"); } } void method() { System.out.println(\"C\"); } } C obj = new C(); obj.method();', 'class A {\nclass B extends A {\nclass C extends B {', 'A', 'B', 'C', 'C', 'Intermediate', 'Simulation question'),
(328, 13, 'What happens with this constructor chaining? Parent() { System.out.println(\"Parent\"); } } Child() { System.out.println(\"Child\"); } } Child c = new Child();', 'class Parent {\nclass Child extends Parent {', 'Child', 'Parent Child', 'Parent', 'B', 'Intermediate', 'Simulation question'),
(329, 13, 'What will this static method example print? static int count = 0; static void increment() { count++; } } Example.increment(); Example.increment();', 'class Example {\nSystem.out.println(Example.count);', '0', '1', '2', 'C', 'Intermediate', 'Simulation question'),
(330, 13, 'What is the result of this abstract class usage? abstract class Shape { abstract void draw(); } void draw() { System.out.println(\"Drawing circle\"); } } Circle c = new Circle();', 'class Circle extends Shape {', 'Compilation error', 'Drawing circle', 'Runtime error', 'B', 'Intermediate', 'Simulation question'),
(331, 13, 'What happens with this interface implementation? interface Drawable { void draw(); } } Square s = new Square(); s.draw();', 'class Square implements Drawable {\npublic void draw() { System.out.println(\"Drawing square\"); }', 'Drawing square', 'Compilation error', 'Runtime error', 'A', 'Intermediate', 'Simulation question'),
(332, 13, 'What will this super keyword example output? void display() { System.out.println(\"Parent display\"); } } void display() { super.display(); } } Child c = new Child();', 'class Parent {\nclass Child extends Parent {\nSystem.out.println(\"Child display\");', 'Child display', 'Parent display Child display', 'Parent display', 'B', 'Intermediate', 'Simulation question'),
(333, 13, 'What is the output of this encapsulation example? } BankAccount acc = new BankAccount(); acc.deposit(500);', 'class BankAccount {\nprivate double balance = 1000;\npublic void deposit(double amount) { balance += amount; }\npublic double getBalance() { return balance; }\nSystem.out.println(acc.getBalance());', '1000.0', '1500.0', '500.0', 'B', 'Intermediate', 'Simulation question'),
(334, 13, 'What happens with this final method override attempt? final void display() { System.out.println(\"Parent\"); } } void display() { System.out.println(\"Child\"); } }', 'class Parent {\nclass Child extends Parent {', 'Prints Child', 'Prints Parent', 'Compilation error', 'C', 'Intermediate', 'Simulation question'),
(335, 13, 'What will this instanceof example return? Dog d = new Dog();', 'class Animal {}\nclass Dog extends Animal {}\nSystem.out.println(d instanceof Animal);', 'true', 'false', 'Compilation error', 'A', 'Intermediate', 'Simulation question'),
(336, 13, 'What is the output of this method hiding example? static void display() { System.out.println(\"Parent static\"); } } static void display() { System.out.println(\"Child static\"); } } Child.display();', 'class Parent {\nclass Child extends Parent {', 'Parent static', 'Child static', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(337, 13, 'What happens with this constructor overloading? String name; Student() { name = \"Unknown\"; } Student(String n) { name = n; } } Student s1 = new Student(); Student s2 = new Student(\"John\");', 'class Student {\nSystem.out.println(s1.name + \" \" + s2.name);', 'null John', 'Unknown John', 'John Unknown', 'B', 'Intermediate', 'Simulation question'),
(338, 13, 'What will this composition example output? void start() { System.out.println(\"Engine started\"); } } Engine engine = new Engine(); void start() { engine.start(); } } Car c = new Car();', 'class Engine {\nclass Car {', 'Car started', 'Engine started', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(339, 13, 'What is the result of this equals() method override? String name; Person(String n) { name = n; } return name.equals(((Person)obj).name); } } Person p1 = new Person(\"John\"); Person p2 = new Person(\"John\");', 'class Person {\npublic boolean equals(Object obj) {\nSystem.out.println(p1.equals(p2));', 'true', 'false', 'Compilation error', 'A', 'Intermediate', 'Simulation question'),
(340, 13, 'What happens with this nested class example? void display() { System.out.println(\"Inner class\"); } } } Outer outer = new Outer(); Outer.Inner inner = outer.new Inner(); inner.display();', 'class Outer {\nclass Inner {', 'Compilation error', 'Inner class', 'Runtime error', 'B', 'Intermediate', 'Simulation question'),
(341, 14, 'What is a class in programming?', '', 'A function', 'A template for creating objects', 'A variable', 'B', 'Beginner', 'Quiz question'),
(342, 14, 'What is an object?', '', 'A class', 'An instance of a class', 'A method', 'B', 'Beginner', 'Quiz question'),
(343, 14, 'Which keyword is used to create a new object in Java?', '', 'create', 'new', 'make', 'B', 'Beginner', 'Quiz question'),
(344, 14, 'What are attributes in a class?', '', 'Methods', 'Variables that store data', 'Constructors', 'B', 'Beginner', 'Quiz question'),
(345, 14, 'What are methods in a class?', '', 'Variables', 'Functions that define behavior', 'Objects', 'B', 'Beginner', 'Quiz question'),
(346, 14, 'How do you access a public field of an object?', '', 'object->field', 'object.field', 'object::field', 'B', 'Beginner', 'Quiz question'),
(347, 14, 'What is a constructor?', '', 'A method that destroys objects', 'A special method that initializes objects', 'A regular method', 'B', 'Beginner', 'Quiz question'),
(348, 14, 'Can a class have multiple constructors?', '', 'No, only one', 'Yes, through constructor overloading', 'Only in special cases', 'B', 'Beginner', 'Quiz question'),
(349, 14, 'What happens when you don\'t define a constructor?', '', 'Compilation error', 'Java provides a default constructor', 'Objects cannot be created', 'B', 'Beginner', 'Quiz question'),
(350, 14, 'What is the difference between instance and static variables?', '', 'No difference', 'Instance variables belong to objects, static to class', 'Static variables are faster', 'B', 'Beginner', 'Quiz question'),
(351, 14, 'How do you call a static method?', '', 'object.method()', 'ClassName.method()', 'new ClassName().method()', 'B', 'Beginner', 'Quiz question'),
(352, 14, 'What is the this keyword used for?', '', 'Creating new objects', 'Referring to the current object', 'Calling static methods', 'B', 'Beginner', 'Quiz question'),
(353, 14, 'Can you create an object without using the new keyword?', '', 'Yes, always', 'No, never in Java', 'Only for primitive types', 'B', 'Beginner', 'Quiz question'),
(354, 14, 'What is object initialization?', '', 'Destroying an object', 'Setting initial values when creating an object', 'Copying an object', 'B', 'Beginner', 'Quiz question'),
(355, 14, 'What happens when an object goes out of scope in Java?', '', 'Manual deletion required', 'Garbage collector handles it', 'Memory leak occurs', 'B', 'Beginner', 'Quiz question'),
(356, 14, 'What will this class definition create? String name; int age; String course; }', 'class Student {', 'A template for student objects', 'A student object', 'A compilation error', 'A', 'Beginner', 'Simulation question'),
(357, 14, 'What is the output when creating this object? String brand = \"Toyota\"; Car() { } } Car myCar = new Car();', 'class Car {\nSystem.out.println(\"Car created\");', 'Nothing', 'Car created', 'Toyota', 'B', 'Beginner', 'Simulation question'),
(358, 14, 'Which object creation is correct for this class? String title; String author; int pages; }', 'class Book {', 'Book myBook = new Book();', 'Book myBook = Book();', 'new Book myBook = Book();', 'A', 'Beginner', 'Simulation question'),
(359, 14, 'What will this code print? String name; void introduce() { } } Person p = new Person(); p.name = \"Alice\"; p.introduce();', 'class Person {\nSystem.out.println(\"Hello, I\'m \" + name);', 'Hello, I\'m null', 'Hello, I\'m Alice', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(360, 14, 'What happens when you access an uninitialized String field? String message; } Example e = new Example();', 'class Example {\nSystem.out.println(e.message);', 'Empty string', 'null', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(361, 14, 'What is the result of this constructor call? int width, height; Rectangle(int w, int h) { width = w; height = h; } } Rectangle r = new Rectangle(10, 5);', 'class Rectangle {', 'width=10, height=5', 'width=5, height=10', 'Compilation error', 'A', 'Beginner', 'Simulation question'),
(362, 14, 'What will this static variable example output? static int count = 0; Counter() { count++; } } Counter c1 = new Counter(); Counter c2 = new Counter();', 'class Counter {\nSystem.out.println(Counter.count);', '0', '1', '2', 'C', 'Beginner', 'Simulation question'),
(363, 14, 'What is the output of this method call? int add(int a, int b) { return a + b; } } Calculator calc = new Calculator();', 'class Calculator {\nSystem.out.println(calc.add(3, 7));', '37', '10', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(364, 14, 'What happens with this object reference? String model; }', 'class Phone {\nPhone phone1 = new Phone();\nphone1.model = \"iPhone\";\nPhone phone2 = phone1;\nphone2.model = \"Samsung\";\nSystem.out.println(phone1.model);', 'iPhone', 'Samsung', 'null', 'B', 'Beginner', 'Simulation question'),
(365, 14, 'What will this constructor overloading example create? String name; int age; Student(String n) { name = n; age = 18; } Student(String n, int a) { name = n; age = a; } } Student s = new Student(\"John\", 20);', 'class Student {', 'name=\"John\", age=18', 'name=\"John\", age=20', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(366, 14, 'What is the result of calling this method? double radius; Circle(double r) { radius = r; } double getArea() { return 3.14 * radius * radius; } } Circle c = new Circle(5);', 'class Circle {\nSystem.out.println(c.getArea());', '15.7', '78.5', '25', 'B', 'Beginner', 'Simulation question'),
(367, 14, 'What happens with this this keyword usage? String name; Employee(String name) { this.name = name; } } Employee emp = new Employee(\"Alice\");', 'class Employee {\nSystem.out.println(emp.name);', 'null', 'Alice', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(368, 14, 'What will this static method call output? static int square(int n) { return n * n; } }', 'class MathUtils {\nSystem.out.println(MathUtils.square(4));', '4', '8', '16', 'C', 'Beginner', 'Simulation question'),
(369, 14, 'What is the result of this object comparison? int x, y; Point(int x, int y) { this.x = x; this.y = y; } } Point p1 = new Point(1, 2); Point p2 = new Point(1, 2);', 'class Point {\nSystem.out.println(p1 == p2);', 'true', 'false', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(370, 14, 'What happens when you modify an object through a method? double balance; Account(double b) { balance = b; } void deposit(double amount) { balance += amount; } } Account acc = new Account(100); acc.deposit(50);', 'class Account {\nSystem.out.println(acc.balance);', '100.0', '150.0', '50.0', 'B', 'Beginner', 'Simulation question'),
(371, 14, 'What is the difference between instance and class variables?', '', 'No difference', 'Instance variables are unique per object, class variables are shared', 'Class variables are faster', 'B', 'Intermediate', 'Quiz question'),
(372, 14, 'What is constructor chaining?', '', 'Creating multiple objects', 'Calling one constructor from another', 'Linking classes together', 'B', 'Intermediate', 'Quiz question'),
(373, 14, 'What is the purpose of the finalize() method?', '', 'To initialize objects', 'Called before garbage collection', 'To create constructors', 'B', 'Intermediate', 'Quiz question'),
(374, 14, 'What happens if you define a constructor with parameters but no default constructor?', '', 'Java creates a default constructor automatically', 'Objects can only be created with parameters', 'Compilation error occurs', 'B', 'Intermediate', 'Quiz question'),
(375, 14, 'What is object cloning in Java?', '', 'Creating identical object references', 'Creating a copy of an object', 'Destroying an object', 'B', 'Intermediate', 'Quiz question'),
(376, 14, 'What is the difference between shallow and deep copying?', '', 'No difference', 'Shallow copies references, deep copies actual objects', 'Deep copying is faster', 'B', 'Intermediate', 'Quiz question'),
(377, 14, 'What is the purpose of static initializer blocks?', '', 'To initialize instance variables', 'To initialize static variables when class is loaded', 'To create objects', 'B', 'Intermediate', 'Quiz question'),
(378, 14, 'What is method chaining?', '', 'Calling multiple methods sequentially', 'Returning \'this\' to allow consecutive method calls', 'Overloading methods', 'B', 'Intermediate', 'Quiz question'),
(379, 14, 'What is the singleton design pattern implementation requirement?', '', 'Multiple constructors', 'Private constructor and static instance method', 'Public constructor', 'B', 'Intermediate', 'Quiz question'),
(380, 14, 'What is the difference between composition and aggregation?', '', 'No difference', 'Composition implies ownership, aggregation implies usage', 'Aggregation is faster', 'B', 'Intermediate', 'Quiz question'),
(381, 14, 'What happens when you override the toString() method?', '', 'Object comparison changes', 'String representation of object changes', 'Object creation changes', 'B', 'Intermediate', 'Quiz question'),
(382, 14, 'What is the purpose of the equals() and hashCode() contract?', '', 'Performance optimization', 'Proper object comparison and hashing', 'Memory management', 'B', 'Intermediate', 'Quiz question'),
(383, 14, 'What is an immutable class?', '', 'A class that cannot be extended', 'A class whose objects cannot be modified after creation', 'A class with only static methods', 'B', 'Intermediate', 'Quiz question'),
(384, 14, 'What is the builder pattern used for?', '', 'Inheriting from multiple classes', 'Creating complex objects step by step', 'Destroying objects', 'B', 'Intermediate', 'Quiz question'),
(385, 14, 'What is dependency injection in object creation?', '', 'Creating objects manually', 'Providing dependencies from external source', 'Using static methods only', 'B', 'Intermediate', 'Quiz question'),
(386, 14, 'What will this constructor chaining example output? String type; this.type = type; } }', 'class Vehicle {\nVehicle() { this(\"Unknown\"); }\nVehicle(String type) {\nSystem.out.println(\"Vehicle: \" + type);\nVehicle v = new Vehicle();', 'Vehicle: null', 'Vehicle: Unknown', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(387, 14, 'What is the result of this static initializer block? static String value; static { value = \"Initialized\"; } }', 'class Config {\nSystem.out.println(\"Static block executed\");\nSystem.out.println(Config.value);', 'Static block executed, Initialized', 'Initialized', 'null', 'A', 'Intermediate', 'Simulation question'),
(388, 14, 'What will this method chaining example return? String content = \"\"; StringBuilder append(String s) { content += s; return this; } String build() { return content; } } StringBuilder sb = new StringBuilder(); String result = sb.append(\"Hello\").append(\" World\").build();', 'class StringBuilder {', 'Hello', 'Hello World', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(389, 14, 'What happens with this singleton implementation? if (instance == null) instance = new Singleton(); return instance; } } Singleton s1 = Singleton.getInstance(); Singleton s2 = Singleton.getInstance();', 'class Singleton {\nprivate static Singleton instance;\nprivate Singleton() {}\npublic static Singleton getInstance() {\nSystem.out.println(s1 == s2);', 'true', 'false', 'Compilation error', 'A', 'Intermediate', 'Simulation question'),
(390, 14, 'What is the output of this toString() override? String name; int age; Student(String n, int a) { name = n; age = a; } } Student s = new Student(\"Alice\", 20);', 'class Student {\npublic String toString() { return name + \"(\" + age + \")\"; }\nSystem.out.println(s);', 'Student@hashcode', 'Alice(20)', 'Alice 20', 'B', 'Intermediate', 'Simulation question'),
(391, 14, 'What will this composition example output? String type; Engine(String t) { type = t; } void start() { System.out.println(type + \" engine started\"); } } Engine engine; Car(String engineType) { engine = new Engine(engineType); } void start() { engine.start(); } } Car car = new Car(\"V8\"); car.start();', 'class Engine {\nclass Car {', 'Engine started', 'V8 engine started', 'Car started', 'B', 'Intermediate', 'Simulation question'),
(392, 14, 'What happens with this equals() method implementation? String name; Person(String n) { name = n; } if (obj instanceof Person) { return name.equals(((Person)obj).name); } return false; } } Person p1 = new Person(\"John\"); Person p2 = new Person(\"John\");', 'class Person {\npublic boolean equals(Object obj) {\nSystem.out.println(p1.equals(p2));', 'true', 'false', 'Compilation error', 'A', 'Intermediate', 'Simulation question'),
(393, 14, 'What is the result of this immutable class usage? Point(int x, int y) { this.x = x; this.y = y; } } Point p = new Point(3, 4);', 'class Point {\nprivate final int x, y;\npublic int getX() { return x; }\npublic int getY() { return y; }\nSystem.out.println(p.getX() + \",\" + p.getY());', '0,0', '3,4', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(394, 14, 'What will this factory method pattern output? static Shape createShape(String type) { if (type.equals(\"circle\")) return new Circle(); return new Square(); } }', 'class ShapeFactory {\nclass Circle { public String toString() { return \"Circle\"; } }\nclass Square { public String toString() { return \"Square\"; } }\nShape s = ShapeFactory.createShape(\"circle\");\nSystem.out.println(s);', 'Circle', 'Square', 'Compilation error', 'A', 'Intermediate', 'Simulation question'),
(395, 14, 'What happens with this object copying? int value; Data(int v) { value = v; } Data copy() { return new Data(value); } } Data d1 = new Data(10); Data d2 = d1.copy(); d2.value = 20;', 'class Data {\nSystem.out.println(d1.value + \",\" + d2.value);', '20,20', '10,20', '10,10', 'B', 'Intermediate', 'Simulation question'),
(396, 14, 'What is the output of this nested class example? void display() { System.out.println(message); } } void createInner() { new Inner().display(); } } Outer outer = new Outer(); outer.createInner();', 'class Outer {\nprivate String message = \"Hello\";\nclass Inner {', 'null', 'Hello', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(397, 14, 'What will this static nested class example output? static String message = \"Static Hello\"; static class StaticInner { void display() { System.out.println(message); } } } Outer.StaticInner inner = new Outer.StaticInner(); inner.display();', 'class Outer {', 'null', 'Static Hello', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(398, 14, 'What happens with this builder pattern implementation? String cpu, ram, storage; static class Builder { String cpu, ram, storage; Builder setCpu(String c) { cpu=c; return this; } Builder setRam(String r) { ram=r; return this; } Computer build() { return new Computer(this); } } } Computer c = new Computer.Builder().setCpu(\"Intel\").setRam(\"8GB\").build();', 'class Computer {\nprivate Computer(Builder b) { cpu=b.cpu; ram=b.ram; storage=b.storage; }\nSystem.out.println(c.cpu);', 'null', 'Intel', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(399, 14, 'What is the result of this anonymous class usage? abstract class Animal { abstract void makeSound(); } void makeSound() { System.out.println(\"Unknown sound\"); } };', 'Animal a = new Animal() {', 'Compilation error', 'Unknown sound', 'Nothing', 'B', 'Intermediate', 'Simulation question'),
(400, 14, 'What will this object initialization block output? String value; { value = \"Initialized\"; } Example() { System.out.println(\"Constructor executed\"); } } Example e = new Example();', 'class Example {\nSystem.out.println(\"Instance block executed\");', 'Constructor executed', 'Instance block executed, Constructor executed', 'Instance block executed', 'B', 'Intermediate', 'Simulation question'),
(401, 15, 'What is encapsulation in OOP?', '', 'Inheriting from multiple classes', 'Hiding internal implementation details', 'Creating multiple objects', 'B', 'Beginner', 'Quiz question'),
(402, 15, 'Which access modifier provides the highest level of data hiding?', '', 'public', 'protected', 'private', 'C', 'Beginner', 'Quiz question'),
(403, 15, 'What is the main purpose of encapsulation?', '', 'To make code run faster', 'To protect data from unauthorized access', 'To reduce memory usage', 'B', 'Beginner', 'Quiz question'),
(404, 15, 'What are getter methods used for?', '', 'Setting values of private fields', 'Retrieving values of private fields', 'Creating new objects', 'B', 'Beginner', 'Quiz question'),
(405, 15, 'What are setter methods used for?', '', 'Getting values of private fields', 'Setting values of private fields', 'Destroying objects', 'B', 'Beginner', 'Quiz question'),
(406, 15, 'Which keyword makes a field accessible only within the same class?', '', 'public', 'private', 'protected', 'B', 'Beginner', 'Quiz question'),
(407, 15, 'What is data hiding in encapsulation?', '', 'Deleting data permanently', 'Making data members private', 'Storing data in files', 'B', 'Beginner', 'Quiz question'),
(408, 15, 'What happens when you try to access a private field directly from outside the class?', '', 'The value is returned', 'Compilation error occurs', 'Runtime error occurs', 'B', 'Beginner', 'Quiz question'),
(409, 15, 'What is the benefit of using private fields with public methods?', '', 'Faster execution', 'Controlled access to data', 'Less memory usage', 'B', 'Beginner', 'Quiz question'),
(410, 15, 'Which access modifier allows access within the same package?', '', 'private', 'default (package-private)', 'protected', 'B', 'Beginner', 'Quiz question'),
(411, 15, 'What is the principle behind encapsulation?', '', '\"Hide what you can, expose what you must\"', '\"Make everything public\"', '\"Use only static methods\"', 'A', 'Beginner', 'Quiz question'),
(412, 15, 'Can private methods be accessed from outside the class?', '', 'Yes, always', 'No, never', 'Only with special keywords', 'B', 'Beginner', 'Quiz question'),
(413, 15, 'What is the purpose of validation in setter methods?', '', 'To make code slower', 'To ensure data integrity', 'To create more objects', 'B', 'Beginner', 'Quiz question'),
(414, 15, 'What does the protected access modifier allow?', '', 'Access only within the same class', 'Access within package and subclasses', 'Public access everywhere', 'B', 'Beginner', 'Quiz question'),
(415, 15, 'Which practice best demonstrates encapsulation?', '', 'Making all fields public', 'Making fields private and providing public methods', 'Using only static variables', 'B', 'Beginner', 'Quiz question'),
(416, 15, 'What happens when you try to access this private field? } Student s = new Student();', 'class Student {\nprivate String name;\nSystem.out.println(s.name);', 'Prints null', 'Compilation error', 'Prints empty string', 'B', 'Beginner', 'Simulation question'),
(417, 15, 'What is the output of this encapsulated class? return balance; } } BankAccount acc = new BankAccount();', 'class BankAccount {\nprivate double balance = 1000.0;\npublic double getBalance() {\nSystem.out.println(acc.getBalance());', '0.0', '1000.0', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(418, 15, 'What will this setter method do? if (age > 0) this.age = age; } } Person p = new Person(); p.setAge(-5);', 'class Person {\nprivate int age;\npublic void setAge(int age) {\npublic int getAge() { return age; }\nSystem.out.println(p.getAge());', '-5', '0', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(419, 15, 'What happens with this protected field access? } void setSpecies() { species = \"Canine\"; } } Dog d = new Dog(); d.setSpecies();', 'class Animal {\nprotected String species;\nclass Dog extends Animal {', 'Compilation error', 'Sets species to \"Canine\"', 'Runtime error', 'B', 'Beginner', 'Simulation question'),
(420, 15, 'What is the result of this getter method call? } Circle c = new Circle();', 'class Circle {\nprivate double radius = 5.0;\npublic double getRadius() { return radius; }\nSystem.out.println(c.getRadius());', '0.0', '5.0', 'null', 'B', 'Beginner', 'Simulation question'),
(421, 15, 'What will this validation in setter do? if (temp >= -273) celsius = temp; } } Temperature t = new Temperature(); t.setCelsius(-300);', 'class Temperature {\nprivate int celsius;\npublic void setCelsius(int temp) {\npublic int getCelsius() { return celsius; }\nSystem.out.println(t.getCelsius());', '-300', '0', '-273', 'B', 'Beginner', 'Simulation question'),
(422, 15, 'What happens when accessing package-private field? package com.example; int value = 10; } // In same package Example e = new Example();', 'class Example {\nSystem.out.println(e.value);', 'Compilation error', '10', '0', 'B', 'Beginner', 'Simulation question'),
(423, 15, 'What is the output of this encapsulation example? } Counter c = new Counter();', 'class Counter {\nprivate int count = 0;\npublic void increment() { count++; }\npublic int getCount() { return count; }\nSystem.out.println(c.getCount());', '0', '1', '2', 'C', 'Beginner', 'Simulation question'),
(424, 15, 'What will this private method call result in? } Helper h = new Helper(); h.assist();', 'class Helper {\nprivate void assist() { System.out.println(\"Helping\"); }', 'Prints \"Helping\"', 'Compilation error', 'Runtime error', 'B', 'Beginner', 'Simulation question'),
(425, 15, 'What happens with this field initialization? } Product p = new Product();', 'class Product {\nprivate String name = \"Unknown\";\npublic String getName() { return name; }\npublic void setName(String name) { this.name = name; }\nSystem.out.println(p.getName());', 'null', 'Unknown', 'Empty string', 'B', 'Beginner', 'Simulation question'),
(426, 15, 'What is the result of this boolean setter validation? } User u = new User(); u.setActive(true);', 'class User {\nprivate boolean active;\npublic void setActive(boolean active) { this.active = active; }\npublic boolean isActive() { return active; }\nSystem.out.println(u.isActive());', 'false', 'true', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(427, 15, 'What happens with this read-only property? } Book b = new Book(\"123456\");', 'class Book {\nprivate final String isbn;\npublic Book(String isbn) { this.isbn = isbn; }\npublic String getIsbn() { return isbn; }\nSystem.out.println(b.getIsbn());', 'null', '123456', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(428, 15, 'What will this encapsulated calculation return? } Rectangle r = new Rectangle();', 'class Rectangle {\nprivate int length = 5, width = 3;\npublic int getArea() { return length * width; }\nSystem.out.println(r.getArea());', '8', '15', '0', 'B', 'Beginner', 'Simulation question'),
(429, 15, 'What is the output of this string encapsulation? this.text = text != null ? text : \"Default\"; } } Message m = new Message(); m.setText(null);', 'class Message {\nprivate String text;\npublic void setText(String text) {\npublic String getText() { return text; }\nSystem.out.println(m.getText());', 'null', 'Default', 'Empty string', 'B', 'Beginner', 'Simulation question'),
(430, 15, 'What happens with this method chaining in encapsulation? } Builder b = new Builder();', 'class Builder {\nprivate String value = \"\";\npublic Builder append(String s) { value += s; return this; }\npublic String build() { return value; }\nSystem.out.println(b.append(\"Hello\").append(\" World\").build());', 'Hello', 'Hello World', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(431, 15, 'What is the difference between encapsulation and data hiding?', '', 'They are the same concept', 'Encapsulation is broader, data hiding is one aspect', 'Data hiding is broader than encapsulation', 'B', 'Intermediate', 'Quiz question'),
(432, 15, 'What is the purpose of immutable classes in encapsulation?', '', 'To allow unlimited modifications', 'To prevent object state changes after creation', 'To improve performance only', 'B', 'Intermediate', 'Quiz question'),
(433, 15, 'How do you make a class immutable?', '', 'Use only public fields', 'Make fields final and provide no setters', 'Use only static methods', 'B', 'Intermediate', 'Quiz question'),
(434, 15, 'What is defensive copying in encapsulation?', '', 'Copying public fields only', 'Creating copies to prevent external modification', 'Copying static variables', 'B', 'Intermediate', 'Quiz question'),
(435, 15, 'What is the Law of Demeter in encapsulation?', '', 'A class should know as little as possible about other classes', 'All methods should be public', 'All fields should be private', 'A', 'Intermediate', 'Quiz question'),
(436, 15, 'What is tight coupling and how does encapsulation help?', '', 'Encapsulation increases coupling', 'Encapsulation reduces dependencies between classes', 'Coupling is not related to encapsulation', 'B', 'Intermediate', 'Quiz question'),
(437, 15, 'What is the purpose of package-private access?', '', 'To hide from all other classes', 'To allow access within the same package only', 'To make everything public', 'B', 'Intermediate', 'Quiz question'),
(438, 15, 'What is a data transfer object (DTO) in encapsulation?', '', 'An object with business logic', 'An object that carries data between processes', 'An object with only static methods', 'B', 'Intermediate', 'Quiz question'),
(439, 15, 'How does encapsulation support the Single Responsibility Principle?', '', 'By making all methods public', 'By keeping related data and behavior together', 'By using only inheritance', 'B', 'Intermediate', 'Quiz question'),
(440, 15, 'What is the difference between composition and encapsulation?', '', 'They are the same', 'Composition is about relationships, encapsulation is about hiding', 'Composition is faster than encapsulation', 'B', 'Intermediate', 'Quiz question'),
(441, 15, 'What is property-based encapsulation?', '', 'Using only public fields', 'Using getter/setter methods to control field access', 'Using only constructors', 'B', 'Intermediate', 'Quiz question'),
(442, 15, 'What is the benefit of using interfaces in encapsulation?', '', 'They expose implementation details', 'They define contracts without revealing implementation', 'They make code slower', 'B', 'Intermediate', 'Quiz question'),
(443, 15, 'What is information hiding principle?', '', 'Hide all information from everyone', 'Hide implementation details, expose only necessary interface', 'Make everything public for transparency', 'B', 'Intermediate', 'Quiz question'),
(444, 15, 'How does encapsulation support code maintainability?', '', 'By making all fields public', 'By allowing internal changes without affecting external code', 'By using only static methods', 'B', 'Intermediate', 'Quiz question'),
(445, 15, 'What is the role of access modifiers in encapsulation design?', '', 'They are not important', 'They control the level of access to class members', 'They only affect performance', 'B', 'Intermediate', 'Quiz question'),
(446, 15, 'What will this immutable class example output? } ImmutablePoint p = new ImmutablePoint(3, 4);', 'class ImmutablePoint {\nprivate final int x, y;\npublic ImmutablePoint(int x, int y) { this.x = x; this.y = y; }\npublic int getX() { return x; }\npublic int getY() { return y; }\nSystem.out.println(p.getX() + \",\" + p.getY());', '0,0', '3,4', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(447, 15, 'What happens with this defensive copying? data = array.clone(); } } int[] original = {1, 2, 3}; SafeArray safe = new SafeArray(original); original[0] = 999;', 'class SafeArray {\nprivate int[] data;\npublic SafeArray(int[] array) {\npublic int[] getData() { return data.clone(); }\nSystem.out.println(safe.getData()[0]);', '999', '1', '0', 'B', 'Intermediate', 'Simulation question'),
(448, 15, 'What is the output of this encapsulation validation? if (addr != null && addr.contains(\"@\")) { address = addr; } } } Email e = new Email(); e.setAddress(\"invalid-email\");', 'class Email {\nprivate String address;\npublic void setAddress(String addr) {\npublic String getAddress() { return address; }\nSystem.out.println(e.getAddress());', 'invalid-email', 'null', 'Empty string', 'B', 'Intermediate', 'Simulation question'),
(449, 15, 'What will this lazy initialization pattern output? if (data == null) { data = \"Initialized\"; } return data; } } ExpensiveObject obj = new ExpensiveObject();', 'class ExpensiveObject {\nprivate String data;\npublic String getData() {\nSystem.out.println(obj.getData());', 'null', 'Initialized', 'Empty string', 'B', 'Intermediate', 'Simulation question'),
(450, 15, 'What happens with this builder pattern encapsulation? static class Builder { } } Person p = new Person.Builder().setName(\"John\").build();', 'class Person {\nprivate String name, email;\nprivate Person(Builder b) { name = b.name; email = b.email; }\nprivate String name, email;\npublic Builder setName(String n) { name = n; return this; }\npublic Builder setEmail(String e) { email = e; return this; }\npublic Person build() { return new Person(this); }\npublic String getName() { return name; }\nSystem.out.println(p.getName());', 'null', 'John', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(451, 15, 'What is the result of this fluent interface? } Calculator calc = new Calculator();', 'class Calculator {\nprivate int value = 0;\npublic Calculator add(int n) { value += n; return this; }\npublic Calculator multiply(int n) { value *= n; return this; }\npublic int result() { return value; }\nSystem.out.println(calc.add(5).multiply(2).result());', '7', '10', '0', 'B', 'Intermediate', 'Simulation question'),
(452, 15, 'What will this singleton with encapsulation output? if (instance == null) instance = new DatabaseConnection(); return instance; } } DatabaseConnection db = DatabaseConnection.getInstance();', 'class DatabaseConnection {\nprivate static DatabaseConnection instance;\nprivate String connectionString = \"Connected\";\nprivate DatabaseConnection() {}\npublic static DatabaseConnection getInstance() {\npublic String getStatus() { return connectionString; }\nSystem.out.println(db.getStatus());', 'null', 'Connected', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(453, 15, 'What happens with this encapsulated collection? } Students class = new Students(); List<String> list = class.getStudents(); list.add(\"Bob\");', 'class Students {\nprivate List<String> names = new ArrayList<>();\npublic void addStudent(String name) { names.add(name); }\npublic List<String> getStudents() { return new ArrayList<>(names); }\nclass.addStudent(\"Alice\");\nSystem.out.println(class.getStudents().size());', '1', '2', '0', 'A', 'Intermediate', 'Simulation question'),
(454, 15, 'What will this factory method with encapsulation create? return new Circle(radius); } } Circle(double r) { radius = r; } }', 'class ShapeFactory {\npublic static Shape createCircle(double radius) {\nclass Circle implements Shape {\nprivate double radius;\npublic double getArea() { return 3.14 * radius * radius; }\nShape s = ShapeFactory.createCircle(5);\nSystem.out.println(s.getArea());', '25.0', '78.5', '15.7', 'B', 'Intermediate', 'Simulation question'),
(455, 15, 'What is the output of this method with parameter validation? years = Math.max(0, Math.min(150, y)); } } Age age = new Age(); age.setYears(200);', 'class Age {\nprivate int years;\npublic void setYears(int y) {\npublic int getYears() { return years; }\nSystem.out.println(age.getYears());', '200', '150', '0', 'B', 'Intermediate', 'Simulation question'),
(456, 15, 'What happens with this encapsulated enum? enum Status { ACTIVE(\"Running\"), INACTIVE(\"Stopped\"); Status(String desc) { description = desc; } }', 'private final String description;\npublic String getDescription() { return description; }\nSystem.out.println(Status.ACTIVE.getDescription());', 'ACTIVE', 'Running', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(457, 15, 'What will this nested class encapsulation output? } } Outer outer = new Outer(); Outer.Inner inner = outer.createInner();', 'class Outer {\nprivate String message = \"Hello\";\npublic class Inner {\npublic String getMessage() { return message; }\npublic Inner createInner() { return new Inner(); }\nSystem.out.println(inner.getMessage());', 'null', 'Hello', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(458, 15, 'What is the result of this template method pattern? abstract class DataProcessor { load(); transform(); save(); } } } new JsonProcessor().process();', 'public final void process() {\nprotected abstract void transform();\nprivate void load() { System.out.print(\"Load \"); }\nprivate void save() { System.out.print(\"Save\"); }\nclass JsonProcessor extends DataProcessor {\nprotected void transform() { System.out.print(\"Transform \"); }', 'Transform Load Save', 'Load Transform Save', 'Save Load Transform', 'B', 'Intermediate', 'Simulation question'),
(459, 15, 'What happens with this readonly property pattern? } Configuration config = new Configuration();', 'class Configuration {\nprivate final Properties props = new Properties();\npublic Configuration() { props.setProperty(\"key\", \"value\"); }\npublic String getProperty(String key) { return props.getProperty(key); }\nSystem.out.println(config.getProperty(\"key\"));', 'null', 'value', 'key', 'B', 'Intermediate', 'Simulation question'),
(460, 15, 'What will this observer pattern encapsulation output? state = s; observers.forEach(o -> o.update(state)); } } interface Observer { void update(String state); } Subject s = new Subject(); s.addObserver(state -> System.out.println(state)); s.setState(\"Changed\");', 'class Subject {\nprivate String state = \"Initial\";\nprivate List<Observer> observers = new ArrayList<>();\npublic void addObserver(Observer o) { observers.add(o); }\npublic void setState(String s) {', 'Initial', 'Changed', 'Nothing', 'B', 'Intermediate', 'Simulation question'),
(461, 16, 'What is inheritance in OOP?', '', 'Creating multiple objects', 'A mechanism where one class acquires properties of another', 'Hiding data from other classes', 'B', 'Beginner', 'Quiz question'),
(462, 16, 'Which keyword is used for inheritance in Java?', '', 'inherits', 'extends', 'implements', 'B', 'Beginner', 'Quiz question'),
(463, 16, 'What is the parent class also called?', '', 'Child class', 'Super class', 'Sub class', 'B', 'Beginner', 'Quiz question'),
(464, 16, 'What is the child class also called?', '', 'Super class', 'Parent class', 'Sub class', 'C', 'Beginner', 'Quiz question'),
(465, 16, 'What does a child class inherit from its parent?', '', 'Only methods', 'Only variables', 'Both methods and variables (except private)', 'C', 'Beginner', 'Quiz question'),
(466, 16, 'Can a child class access private members of its parent class?', '', 'Yes, always', 'No, never', 'Only with special keywords', 'B', 'Beginner', 'Quiz question'),
(467, 16, 'What is the super keyword used for?', '', 'Creating new objects', 'Accessing parent class members', 'Making methods private', 'B', 'Beginner', 'Quiz question'),
(468, 16, 'What type of inheritance does Java support?', '', 'Multiple inheritance', 'Single inheritance', 'Both single and multiple', 'B', 'Beginner', 'Quiz question'),
(469, 16, 'What is method overriding?', '', 'Creating multiple methods with same name', 'Redefining a parent method in child class', 'Making methods private', 'B', 'Beginner', 'Quiz question');
INSERT INTO `questions` (`id`, `topic_id`, `question_text`, `code_snippet`, `option_a`, `option_b`, `option_c`, `correct_option`, `class_level`, `question_type`) VALUES
(470, 16, 'What happens when a child class method has the same name as parent method?', '', 'Compilation error', 'Child method overrides parent method', 'Both methods execute', 'B', 'Beginner', 'Quiz question'),
(471, 16, 'What is the IS-A relationship in inheritance?', '', 'Child IS-A parent', 'Parent IS-A child', 'Objects IS-A class', 'A', 'Beginner', 'Quiz question'),
(472, 16, 'Which access modifier allows inheritance but restricts outside access?', '', 'private', 'protected', 'public', 'B', 'Beginner', 'Quiz question'),
(473, 16, 'What is multilevel inheritance?', '', 'Multiple children from one parent', 'Child inheriting from parent, which inherits from grandparent', 'One child from multiple parents', 'B', 'Beginner', 'Quiz question'),
(474, 16, 'What is hierarchical inheritance?', '', 'One parent, multiple children', 'Multiple parents, one child', 'Chain of inheritance', 'A', 'Beginner', 'Quiz question'),
(475, 16, 'What happens when you don\'t explicitly call super() in constructor?', '', 'Compilation error', 'Java automatically calls it', 'Parent constructor is not called', 'B', 'Beginner', 'Quiz question'),
(476, 16, 'What will this inheritance example output? void sound() { System.out.println(\"Animal makes sound\"); } } } Dog d = new Dog(); d.sound();', 'class Animal {\nclass Dog extends Animal {', 'Compilation error', 'Animal makes sound', 'Nothing', 'B', 'Beginner', 'Simulation question'),
(477, 16, 'What happens with this method overriding? void start() { System.out.println(\"Vehicle starts\"); } } void start() { System.out.println(\"Car starts\"); } } Car c = new Car();', 'class Vehicle {\nclass Car extends Vehicle {', 'Vehicle starts', 'Car starts', 'Both messages', 'B', 'Beginner', 'Simulation question'),
(478, 16, 'What is the output of this super keyword usage? void display() { System.out.println(\"Parent\"); } } void display() { super.display(); } } Child c = new Child();', 'class Parent {\nclass Child extends Parent {\nSystem.out.println(\"Child\");', 'Child', 'Parent Child', 'Parent', 'B', 'Beginner', 'Simulation question'),
(479, 16, 'What will this constructor inheritance show? } Dog() { System.out.println(\"Dog created\"); } } Dog d = new Dog();', 'class Animal {\nAnimal() { System.out.println(\"Animal created\"); }\nclass Dog extends Animal {', 'Dog created', 'Animal created Dog created', 'Animal created', 'B', 'Beginner', 'Simulation question'),
(480, 16, 'What happens with this field access? } void printName() { System.out.println(name); } } Child c = new Child();', 'class Parent {\nprotected String name = \"Parent\";\nclass Child extends Parent {', 'null', 'Parent', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(481, 16, 'What is the result of this multilevel inheritance? void method() { System.out.println(\"GrandParent\"); } } } } Child c = new Child();', 'class GrandParent {\nclass Parent extends GrandParent {\nclass Child extends Parent {', 'Compilation error', 'GrandParent', 'Child', 'B', 'Beginner', 'Simulation question'),
(482, 16, 'What will this polymorphic behavior output? void move() { System.out.println(\"Animal moves\"); } } void move() { System.out.println(\"Bird flies\"); } }', 'class Animal {\nclass Bird extends Animal {\nAnimal a = new Bird();', 'Animal moves', 'Bird flies', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(483, 16, 'What happens with this private member access attempt? } void getValue() { System.out.println(value); } }', 'class Parent {\nprivate int value = 10;\nclass Child extends Parent {', 'Prints 10', 'Compilation error', 'Prints 0', 'B', 'Beginner', 'Simulation question'),
(484, 16, 'What is the output of this method inheritance? int add(int a, int b) { return a + b; } } int multiply(int a, int b) { return a * b; } } AdvancedCalculator calc = new AdvancedCalculator();', 'class Calculator {\nclass AdvancedCalculator extends Calculator {\nSystem.out.println(calc.add(3, 4));', '7', '12', 'Compilation error', 'A', 'Beginner', 'Simulation question'),
(485, 16, 'What will this hierarchical inheritance show? void draw() { System.out.println(\"Drawing shape\"); } } } } Circle c = new Circle();', 'class Shape {\nclass Circle extends Shape {\nclass Rectangle extends Shape {', 'Drawing shape', 'Drawing circle', 'Compilation error', 'A', 'Beginner', 'Simulation question'),
(486, 16, 'What happens with this constructor chaining? A(int x) { System.out.println(\"A: \" + x); } } B() { super(5); System.out.println(\"B\"); } } B b = new B();', 'class A {\nclass B extends A {', 'B A: 5', 'A: 5 B', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(487, 16, 'What is the result of this field hiding? String name = \"Parent\"; } String name = \"Child\"; void printNames() { } } Child c = new Child();', 'class Parent {\nclass Child extends Parent {\nSystem.out.println(name + \" \" + super.name);', 'Parent Parent', 'Child Parent', 'Child Child', 'B', 'Beginner', 'Simulation question'),
(488, 16, 'What will this instanceof check return? Dog d = new Dog();', 'class Animal {}\nclass Dog extends Animal {}\nSystem.out.println(d instanceof Animal);', 'true', 'false', 'Compilation error', 'A', 'Beginner', 'Simulation question'),
(489, 16, 'What happens with this method parameter overriding? void print(String msg) { System.out.println(\"Parent: \" + msg); } } void print(String msg) { System.out.println(\"Child: \" + msg); } } Child c = new Child();', 'class Parent {\nclass Child extends Parent {', 'Parent: Hello', 'Child: Hello', 'Both messages', 'B', 'Beginner', 'Simulation question'),
(490, 16, 'What is the output of this return type covariance? } Dog getAnimal() { return new Dog(); } } Dog d = new Dog();', 'class Animal {\nAnimal getAnimal() { return new Animal(); }\nclass Dog extends Animal {\nSystem.out.println(d.getAnimal().getClass().getSimpleName());', 'Animal', 'Dog', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(491, 16, 'What is the difference between method overriding and method overloading?', '', 'They are the same', 'Overriding redefines inherited method, overloading creates multiple versions', 'Overloading is faster than overriding', 'B', 'Intermediate', 'Quiz question'),
(492, 16, 'What is early binding vs late binding in inheritance?', '', 'Early binding is at compile time, late binding at runtime', 'Late binding is faster', 'They are the same concept', 'A', 'Intermediate', 'Quiz question'),
(493, 16, 'What is the Liskov Substitution Principle?', '', 'Child classes should be substitutable for parent classes', 'Parent classes should inherit from children', 'All methods should be overridden', 'A', 'Intermediate', 'Quiz question'),
(494, 16, 'What is the diamond problem in inheritance?', '', 'A problem with single inheritance', 'Ambiguity arising from multiple inheritance', 'A performance issue', 'B', 'Intermediate', 'Quiz question'),
(495, 16, 'How does Java solve the diamond problem?', '', 'By allowing multiple inheritance', 'By supporting only single inheritance of classes', 'By using only interfaces', 'B', 'Intermediate', 'Quiz question'),
(496, 16, 'What is abstract class inheritance?', '', 'Classes that cannot have abstract methods', 'Classes that cannot be instantiated directly', 'Classes that are always final', 'B', 'Intermediate', 'Quiz question'),
(497, 16, 'What is the difference between composition and inheritance?', '', 'No difference', 'Composition is \"has-a\", inheritance is \"is-a\"', 'Inheritance is always better', 'B', 'Intermediate', 'Quiz question'),
(498, 16, 'What is method hiding in inheritance?', '', 'Making methods private', 'Static method in child with same signature as parent', 'Removing methods from parent', 'B', 'Intermediate', 'Quiz question'),
(499, 16, 'What is covariant return types?', '', 'Returning same type always', 'Child method can return subtype of parent\'s return type', 'Not allowed in Java', 'B', 'Intermediate', 'Quiz question'),
(500, 16, 'What is the template method pattern?', '', 'Creating object templates', 'Defining algorithm skeleton with overridable steps', 'Using only abstract methods', 'B', 'Intermediate', 'Quiz question'),
(501, 16, 'What is dynamic method dispatch?', '', 'Calling methods at compile time', 'Runtime determination of which method to call', 'Creating methods dynamically', 'B', 'Intermediate', 'Quiz question'),
(502, 16, 'What is the fragile base class problem?', '', 'Base classes breaking easily', 'Changes in base class breaking derived classes', 'Derived classes are weak', 'B', 'Intermediate', 'Quiz question'),
(503, 16, 'What is favor composition over inheritance principle?', '', 'Always use inheritance', 'Prefer has-a relationships over is-a when possible', 'Never use inheritance', 'B', 'Intermediate', 'Quiz question'),
(504, 16, 'What is sealed classes concept?', '', 'Classes that cannot be opened', 'Classes that restrict which classes can inherit from them', 'Classes that are always abstract', 'B', 'Intermediate', 'Quiz question'),
(505, 16, 'What is the purpose of protected constructors?', '', 'To prevent object creation', 'To allow only subclass instantiation', 'To make constructors faster', 'B', 'Intermediate', 'Quiz question'),
(506, 16, 'What will this abstract class inheritance output? abstract class Animal { abstract void sound(); void sleep() { System.out.println(\"Sleeping\"); } } void sound() { System.out.println(\"Meow\"); } } Cat c = new Cat();', 'class Cat extends Animal {', 'Meow Sleeping', 'Sleeping Meow', 'Compilation error', 'A', 'Intermediate', 'Simulation question'),
(507, 16, 'What happens with this method hiding vs overriding? static void staticMethod() { System.out.println(\"Parent static\"); } void instanceMethod() { System.out.println(\"Parent instance\"); } } static void staticMethod() { System.out.println(\"Child static\"); } void instanceMethod() { System.out.println(\"Child instance\"); } } Parent p = new Child(); p.staticMethod(); p.instanceMethod();', 'class Parent {\nclass Child extends Parent {', 'Parent static Child instance', 'Child static Child instance', 'Parent static Parent instance', 'A', 'Intermediate', 'Simulation question'),
(508, 16, 'What is the result of this covariant return type? } Dog reproduce() { return new Dog(); } }', 'class Animal {\nAnimal reproduce() { return new Animal(); }\nclass Dog extends Animal {\nAnimal a = new Dog();\nSystem.out.println(a.reproduce().getClass().getSimpleName());', 'Animal', 'Dog', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(509, 16, 'What will this template method pattern output? abstract class DataProcessor { read(); transform(); write(); } } } new XMLProcessor().process();', 'public final void process() {\nprotected abstract void transform();\nprivate void read() { System.out.print(\"Read \"); }\nprivate void write() { System.out.print(\"Write\"); }\nclass XMLProcessor extends DataProcessor {\nprotected void transform() { System.out.print(\"XML \"); }', 'XML Read Write', 'Read XML Write', 'Read Write XML', 'B', 'Intermediate', 'Simulation question'),
(510, 16, 'What happens with this constructor inheritance? String type; this.type = type; } } Car() { super(\"Car\"); } } Car c = new Car();', 'class Vehicle {\nVehicle(String type) {\nSystem.out.println(\"Vehicle: \" + type);\nclass Car extends Vehicle {\nSystem.out.println(\"Car created\");', 'Car created Vehicle: Car', 'Vehicle: Car Car created', 'Vehicle: Car', 'B', 'Intermediate', 'Simulation question'),
(511, 16, 'What is the output of this multiple interface inheritance? interface Flyable { default void fly() { System.out.println(\"Flying\"); } } interface Swimmable { default void swim() { System.out.println(\"Swimming\"); } } } Duck d = new Duck(); d.fly(); d.swim();', 'class Duck implements Flyable, Swimmable {', 'Flying Swimming', 'Swimming Flying', 'Compilation error', 'A', 'Intermediate', 'Simulation question'),
(512, 16, 'What will this super constructor call output? A() { System.out.println(\"A default\"); } A(String s) { System.out.println(\"A: \" + s); } } B() { super(\"B\"); System.out.println(\"B default\"); } } B b = new B();', 'class A {\nclass B extends A {', 'A default B default', 'A: B B default', 'B default A: B', 'B', 'Intermediate', 'Simulation question'),
(513, 16, 'What happens with this final method inheritance? final void display() { System.out.println(\"Parent display\"); } } // Attempting to override } Child c = new Child();', 'class Parent {\nclass Child extends Parent {', 'Compilation error if trying to override', 'Parent display', 'Child display', 'B', 'Intermediate', 'Simulation question'),
(514, 16, 'What is the result of this protected access inheritance? package p1; } package p2; import p1.Parent; void test() { method(); } } Child c = new Child();', 'public class Parent {\nprotected void method() { System.out.println(\"Protected method\"); }\nclass Child extends Parent {', 'Compilation error', 'Protected method', 'Runtime error', 'B', 'Intermediate', 'Simulation question'),
(515, 16, 'What will this composition vs inheritance example show? void start() { System.out.println(\"Engine started\"); } } void start() { engine.start(); } } // vs inheritance } SportsCar sc = new SportsCar(); sc.start();', 'class Engine {\nclass Car {\nprivate Engine engine = new Engine();\nclass SportsCar extends Car {', 'Compilation error', 'Engine started', 'SportsCar started', 'B', 'Intermediate', 'Simulation question'),
(516, 16, 'What happens with this diamond problem simulation using interfaces? interface A { default void method() { System.out.println(\"A\"); } } interface B extends A { default void method() { System.out.println(\"B\"); } } interface C extends A { default void method() { System.out.println(\"C\"); } } } D d = new D(); d.method();', 'class D implements B, C {\npublic void method() { B.super.method(); }', 'A', 'B', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(517, 16, 'What is the output of this overriding with different access modifiers? } } Parent p = new Child(); p.method();', 'class Parent {\nprotected void method() { System.out.println(\"Parent protected\"); }\nclass Child extends Parent {\npublic void method() { System.out.println(\"Child public\"); }', 'Parent protected', 'Child public', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(518, 16, 'What will this instanceof chain return? Dog d = new Dog();', 'class Animal {}\nclass Mammal extends Animal {}\nclass Dog extends Mammal {}\nSystem.out.println(d instanceof Animal);\nSystem.out.println(d instanceof Mammal);\nSystem.out.println(d instanceof Dog);', 'false false true', 'true true true', 'true false true', 'B', 'Intermediate', 'Simulation question'),
(519, 16, 'What happens with this method resolution in inheritance hierarchy? void method() { System.out.println(\"A\"); } } void method() { System.out.println(\"B\"); } } } C c = new C();', 'class A {\nclass B extends A {\nclass C extends B {', 'A', 'B', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(520, 16, 'What is the result of this anonymous class inheritance? abstract class Shape { abstract void draw(); } void draw() { System.out.println(\"Anonymous shape\"); } }; s.draw();', 'Shape s = new Shape() {', 'Compilation error', 'Anonymous shape', 'Shape', 'B', 'Intermediate', 'Simulation question'),
(521, 17, 'What is polymorphism in OOP?', '', 'Having multiple classes', 'One interface, multiple implementations', 'Creating many objects', 'B', 'Beginner', 'Quiz question'),
(522, 17, 'What does the word \"polymorphism\" mean?', '', 'Many forms', 'Single form', 'No form', 'A', 'Beginner', 'Quiz question'),
(523, 17, 'What are the two main types of polymorphism?', '', 'Static and Dynamic', 'Public and Private', 'Early and Late', 'A', 'Beginner', 'Quiz question'),
(524, 17, 'What is method overloading an example of?', '', 'Dynamic polymorphism', 'Static polymorphism', 'Runtime polymorphism', 'B', 'Beginner', 'Quiz question'),
(525, 17, 'What is method overriding an example of?', '', 'Static polymorphism', 'Dynamic polymorphism', 'Compile-time polymorphism', 'B', 'Beginner', 'Quiz question'),
(526, 17, 'When is the actual method determined in dynamic polymorphism?', '', 'At compile time', 'At runtime', 'During method declaration', 'B', 'Beginner', 'Quiz question'),
(527, 17, 'What enables runtime polymorphism in Java?', '', 'Method overloading', 'Method overriding with inheritance', 'Static methods', 'B', 'Beginner', 'Quiz question'),
(528, 17, 'What is the key requirement for polymorphism to work?', '', 'Same method name and signature', 'Different class names', 'Public methods only', 'A', 'Beginner', 'Quiz question'),
(529, 17, 'Which keyword helps achieve polymorphism through interfaces?', '', 'extends', 'implements', 'super', 'B', 'Beginner', 'Quiz question'),
(530, 17, 'What is duck typing in polymorphism?', '', 'Creating duck objects', '\"If it walks like a duck and quacks like a duck, it\'s a duck\"', 'Using only animal classes', 'B', 'Beginner', 'Quiz question'),
(531, 17, 'What happens when you call an overridden method on a parent reference?', '', 'Parent method is called', 'Child method is called', 'Compilation error', 'B', 'Beginner', 'Quiz question'),
(532, 17, 'What is the benefit of polymorphism?', '', 'Faster execution', 'Code flexibility and extensibility', 'Less memory usage', 'B', 'Beginner', 'Quiz question'),
(533, 17, 'What is virtual method invocation?', '', 'Creating virtual methods', 'Runtime method selection based on object type', 'Static method calling', 'B', 'Beginner', 'Quiz question'),
(534, 17, 'Can constructors be polymorphic?', '', 'Yes, always', 'No, never', 'Only in special cases', 'B', 'Beginner', 'Quiz question'),
(535, 17, 'What is operator overloading a form of?', '', 'Dynamic polymorphism', 'Static polymorphism', 'Runtime polymorphism', 'B', 'Beginner', 'Quiz question'),
(536, 17, 'What will this polymorphism example output? void sound() { System.out.println(\"Animal sound\"); } } void sound() { System.out.println(\"Bark\"); } }', 'class Animal {\nclass Dog extends Animal {\nAnimal a = new Dog();', 'Animal sound', 'Bark', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(537, 17, 'What happens with this method overloading? int add(int a, int b) { return a + b; } double add(double a, double b) { return a + b; } } Calculator c = new Calculator();', 'class Calculator {\nSystem.out.println(c.add(5, 3));', '8', '8.0', 'Compilation error', 'A', 'Beginner', 'Simulation question'),
(538, 17, 'What is the output of this interface polymorphism? interface Shape { void draw(); } } s.draw();', 'class Circle implements Shape {\npublic void draw() { System.out.println(\"Drawing Circle\"); }\nShape s = new Circle();', 'Compilation error', 'Drawing Circle', 'Drawing Shape', 'B', 'Beginner', 'Simulation question'),
(539, 17, 'What will this array of polymorphic objects output? void sound() { System.out.println(\"Animal\"); } } void sound() { System.out.println(\"Meow\"); } } void sound() { System.out.println(\"Bark\"); } }', 'class Animal {\nclass Cat extends Animal {\nclass Dog extends Animal {\nAnimal[] animals = {new Cat(), new Dog()};\nanimals[0].sound();', 'Animal', 'Meow', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(540, 17, 'What happens with this polymorphic method call? void start() { System.out.println(\"Vehicle starts\"); } } void start() { System.out.println(\"Car starts\"); } } void start() { System.out.println(\"Bike starts\"); } } v.start();', 'class Vehicle {\nclass Car extends Vehicle {\nclass Bike extends Vehicle {\nVehicle v = new Bike();', 'Vehicle starts', 'Bike starts', 'Car starts', 'B', 'Beginner', 'Simulation question'),
(541, 17, 'What is the result of this overloaded constructor polymorphism? String name; Person() { name = \"Unknown\"; } Person(String n) { name = n; } } Person p1 = new Person(); Person p2 = new Person(\"John\");', 'class Person {\nSystem.out.println(p1.name + \" \" + p2.name);', 'null John', 'Unknown John', 'John Unknown', 'B', 'Beginner', 'Simulation question'),
(542, 17, 'What will this polymorphic return type output? } Dog getAnimal() { return this; } }', 'class Animal {\nAnimal getAnimal() { return this; }\nclass Dog extends Animal {\nAnimal a = new Dog();\nSystem.out.println(a.getAnimal().getClass().getSimpleName());', 'Animal', 'Dog', 'Object', 'B', 'Beginner', 'Simulation question'),
(543, 17, 'What happens with this multiple interface implementation? interface Drawable { void draw(); } interface Printable { void print(); } } Drawable d = new Document(); d.draw();', 'class Document implements Drawable, Printable {\npublic void draw() { System.out.println(\"Drawing document\"); }\npublic void print() { System.out.println(\"Printing document\"); }', 'Drawing document', 'Printing document', 'Compilation error', 'A', 'Beginner', 'Simulation question'),
(544, 17, 'What is the output of this polymorphic parameter? void print(Object obj) { } } Printer p = new Printer(); p.print(\"Hello\"); p.print(123);', 'class Printer {\nSystem.out.println(\"Printing: \" + obj.toString());', 'Only first print works', 'Printing: Hello Printing: 123', 'Compilation error', 'B', 'Beginner', 'Simulation question'),
(545, 17, 'What will this generic polymorphism show? T item; void setItem(T item) { this.item = item; } T getItem() { return item; } } Container<String> c = new Container<>();', 'class Container<T> {\nSystem.out.println(c.getItem());', 'Hello', 'null', 'Compilation error', 'A', 'Beginner', 'Simulation question'),
(546, 17, 'What happens with this instanceof polymorphic check? if (a instanceof Dog) { } else if (a instanceof Cat) { }', 'class Animal {}\nclass Dog extends Animal {}\nclass Cat extends Animal {}\nAnimal a = new Dog();\nSystem.out.println(\"It\'s a Dog\");\nSystem.out.println(\"It\'s a Cat\");', 'It\'s a Cat', 'It\'s a Dog', 'Nothing prints', 'B', 'Beginner', 'Simulation question'),
(547, 17, 'What is the result of this method overriding chain? void method() { System.out.println(\"A\"); } } void method() { System.out.println(\"B\"); } } void method() { System.out.println(\"C\"); } } A obj = new C(); obj.method();', 'class A {\nclass B extends A {\nclass C extends B {', 'A', 'C', 'B', 'C', 'Beginner', 'Simulation question'),
(548, 17, 'What will this abstract class polymorphism output? abstract class Shape { abstract void draw(); void info() { System.out.println(\"This is a shape\"); } } void draw() { System.out.println(\"Drawing rectangle\"); } } s.draw(); s.info();', 'class Rectangle extends Shape {\nShape s = new Rectangle();', 'Drawing rectangle This is a shape', 'This is a shape Drawing rectangle', 'Compilation error', 'A', 'Beginner', 'Simulation question'),
(549, 17, 'What happens with this collection polymorphism? import java.util.*; List<Object> list = new ArrayList<>(); list.add(\"String\"); list.add(42); list.add(3.14);', 'System.out.println(list.get(1));', 'String', '42', '3.14', 'B', 'Beginner', 'Simulation question'),
(550, 17, 'What is the output of this lambda polymorphism? interface Operation { int perform(int a, int b); } Operation add = (a, b) -> a + b; Operation multiply = (a, b) -> a * b;', 'System.out.println(add.perform(3, 4));', '7', '12', 'Compilation error', 'A', 'Beginner', 'Simulation question'),
(551, 17, 'What is the difference between early binding and late binding?', '', 'Early binding is at runtime, late binding at compile time', 'Early binding is at compile time, late binding at runtime', 'They are the same concept', 'B', 'Intermediate', 'Quiz question'),
(552, 17, 'What is method dispatch table in polymorphism?', '', 'A table listing all methods', 'Runtime structure mapping method calls to implementations', 'A debugging tool', 'B', 'Intermediate', 'Quiz question'),
(553, 17, 'What is parametric polymorphism?', '', 'Using parameters in methods', 'Type parameterization using generics', 'Creating multiple parameters', 'B', 'Intermediate', 'Quiz question'),
(554, 17, 'What is ad-hoc polymorphism?', '', 'Random polymorphism', 'Overloading allowing different types with same interface', 'Polymorphism without inheritance', 'B', 'Intermediate', 'Quiz question'),
(555, 17, 'What is subtype polymorphism?', '', 'Creating subtypes only', 'Using inheritance hierarchy for polymorphic behavior', 'Avoiding inheritance', 'B', 'Intermediate', 'Quiz question'),
(556, 17, 'What is the Visitor pattern an example of?', '', 'Static polymorphism', 'Double dispatch polymorphism', 'Single dispatch polymorphism', 'B', 'Intermediate', 'Quiz question'),
(557, 17, 'What is covariance and contravariance in polymorphism?', '', 'Same type relationships', 'How subtyping relates to more complex types', 'Unrelated concepts', 'B', 'Intermediate', 'Quiz question'),
(558, 17, 'What is the Strategy pattern\'s relationship to polymorphism?', '', 'No relationship', 'Encapsulates algorithms using polymorphic interfaces', 'Replaces polymorphism', 'B', 'Intermediate', 'Quiz question'),
(559, 17, 'What is duck typing in dynamic languages?', '', 'Creating duck objects', 'Type checking based on method availability', 'Static type checking', 'B', 'Intermediate', 'Quiz question'),
(560, 17, 'What is the Open/Closed Principle\'s relation to polymorphism?', '', 'No relation', 'Polymorphism enables extension without modification', 'Polymorphism violates the principle', 'B', 'Intermediate', 'Quiz question'),
(561, 17, 'What is bounded type parameters in generics?', '', 'Unlimited type parameters', 'Type parameters with constraints', 'Fixed type parameters', 'B', 'Intermediate', 'Quiz question'),
(562, 17, 'What is type erasure in Java generics?', '', 'Deleting types permanently', 'Runtime removal of generic type information', 'Compile-time type checking only', 'B', 'Intermediate', 'Quiz question'),
(563, 17, 'What is the difference between overriding and hiding?', '', 'They are the same', 'Overriding is for instance methods, hiding for static methods', 'Hiding is for instance methods', 'B', 'Intermediate', 'Quiz question'),
(564, 17, 'What is virtual function table (vtable)?', '', 'A debugging table', 'Implementation mechanism for dynamic dispatch', 'A storage table', 'B', 'Intermediate', 'Quiz question'),
(565, 17, 'What is the problem with covariant arrays in Java?', '', 'No problem exists', 'Can lead to ArrayStoreException at runtime', 'Arrays cannot be covariant', 'B', 'Intermediate', 'Quiz question'),
(566, 17, 'What will this double dispatch pattern output? interface Visitor { void visit(Circle c); void visit(Rectangle r); } abstract class Shape { abstract void accept(Visitor v); } void accept(Visitor v) { v.visit(this); } } } s.accept(new DrawVisitor());', 'class Circle extends Shape {\nclass DrawVisitor implements Visitor {\npublic void visit(Circle c) { System.out.println(\"Drawing circle\"); }\npublic void visit(Rectangle r) { System.out.println(\"Drawing rectangle\"); }\nShape s = new Circle();', 'Drawing rectangle', 'Drawing circle', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(567, 17, 'What happens with this generic wildcard polymorphism? import java.util.*; List<? extends Number> numbers = new ArrayList<Integer>(); // numbers.add(42); // This would cause compilation error List<Integer> ints = Arrays.asList(1, 2, 3); numbers = ints;', 'System.out.println(numbers.get(0));', 'Compilation error', '1', 'null', 'B', 'Intermediate', 'Simulation question'),
(568, 17, 'What is the output of this strategy pattern polymorphism? interface SortStrategy { void sort(int[] array); } } } } Sorter s = new Sorter(new QuickSort()); s.sort(new int[]{3,1,2});', 'class BubbleSort implements SortStrategy {\npublic void sort(int[] array) { System.out.println(\"Bubble sorting\"); }\nclass QuickSort implements SortStrategy {\npublic void sort(int[] array) { System.out.println(\"Quick sorting\"); }\nclass Sorter {\nprivate SortStrategy strategy;\npublic Sorter(SortStrategy strategy) { this.strategy = strategy; }\npublic void sort(int[] array) { strategy.sort(array); }', 'Bubble sorting', 'Quick sorting', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(569, 17, 'What will this method overloading with inheritance output? void sound(Object o) { System.out.println(\"Animal-Object\"); } void sound(Animal a) { System.out.println(\"Animal-Animal\"); } } void sound(Object o) { System.out.println(\"Dog-Object\"); } void sound(Dog d) { System.out.println(\"Dog-Dog\"); } }', 'class Animal {\nclass Dog extends Animal {\nAnimal a = new Dog();', 'Animal-Animal', 'Dog-Object', 'Dog-Dog', 'B', 'Intermediate', 'Simulation question'),
(570, 17, 'What happens with this covariant return type chain? } Mammal reproduce() { return new Mammal(); } } Dog reproduce() { return new Dog(); } }', 'class Animal {\nAnimal reproduce() { return new Animal(); }\nclass Mammal extends Animal {\nclass Dog extends Mammal {\nAnimal a = new Dog();\nSystem.out.println(a.reproduce().getClass().getSimpleName());', 'Animal', 'Dog', 'Mammal', 'B', 'Intermediate', 'Simulation question'),
(571, 17, 'What is the result of this template method with polymorphism? abstract class GameAI { collectResources(); buildStructures(); buildUnits(); attack(); } } } new OrcsAI().takeTurn();', 'public final void takeTurn() {\nprotected abstract void buildStructures();\nprotected abstract void buildUnits();\nprivate void collectResources() { System.out.print(\"Collect \"); }\nprivate void attack() { System.out.print(\"Attack\"); }\nclass OrcsAI extends GameAI {\nprotected void buildStructures() { System.out.print(\"Stronghold \"); }\nprotected void buildUnits() { System.out.print(\"Warriors \"); }', 'Stronghold Warriors Collect Attack', 'Collect Stronghold Warriors Attack', 'Collect Attack Stronghold Warriors', 'B', 'Intermediate', 'Simulation question'),
(572, 17, 'What will this factory method polymorphism create? abstract class Creator { abstract Product factoryMethod(); Product p = factoryMethod(); p.use(); } } interface Product { void use(); } } Product factoryMethod() { return new ConcreteProduct(); } } new ConcreteCreator().operation();', 'public void operation() {\nclass ConcreteProduct implements Product {\npublic void use() { System.out.println(\"Using concrete product\"); }\nclass ConcreteCreator extends Creator {', 'Compilation error', 'Using concrete product', 'Nothing', 'B', 'Intermediate', 'Simulation question'),
(573, 17, 'What happens with this observer pattern polymorphism? interface Observer { void update(String message); } observers.forEach(o -> o.update(msg)); } } ConcreteObserver(String name) { this.name = name; } } Subject s = new Subject(); s.addObserver(new ConcreteObserver(\"Observer1\")); s.notifyObservers(\"Hello\");', 'class Subject {\nprivate List<Observer> observers = new ArrayList<>();\npublic void addObserver(Observer o) { observers.add(o); }\npublic void notifyObservers(String msg) {\nclass ConcreteObserver implements Observer {\nprivate String name;\npublic void update(String message) { System.out.println(name + \": \" + message); }', 'Hello', 'Observer1: Hello', 'Nothing', 'B', 'Intermediate', 'Simulation question'),
(574, 17, 'What is the output of this polymorphic collection iteration? import java.util.*; List<Shape> shapes = Arrays.asList(new Circle(), new Rectangle()); interface Shape { void draw(); }', 'class Circle implements Shape { public void draw() { System.out.print(\"Circle \"); } }\nclass Rectangle implements Shape { public void draw() { System.out.print(\"Rectangle\"); } }\nshapes.forEach(Shape::draw);', 'Circle Rectangle', 'Rectangle Circle', 'Compilation error', 'A', 'Intermediate', 'Simulation question'),
(575, 17, 'What will this command pattern polymorphism execute? interface Command { void execute(); } void turnOn() { System.out.println(\"Light on\"); } void turnOff() { System.out.println(\"Light off\"); } } LightOnCommand(Light light) { this.light = light; } } void setCommand(Command command) { this.command = command; } void pressButton() { command.execute(); } } RemoteControl remote = new RemoteControl(); remote.setCommand(new LightOnCommand(new Light())); remote.pressButton();', 'class Light {\nclass LightOnCommand implements Command {\nprivate Light light;\npublic void execute() { light.turnOn(); }\nclass RemoteControl {\nprivate Command command;', 'Light off', 'Light on', 'Nothing', 'B', 'Intermediate', 'Simulation question'),
(576, 17, 'What happens with this polymorphic exception handling? CustomException(String msg) { super(msg); } } SpecificException(String msg) { super(msg); } } try { throw new SpecificException(\"Error\"); } catch (CustomException e) { }', 'class CustomException extends Exception {\nclass SpecificException extends CustomException {\nSystem.out.println(\"Caught: \" + e.getClass().getSimpleName());', 'Caught: CustomException', 'Caught: SpecificException', 'Compilation error', 'B', 'Intermediate', 'Simulation question'),
(577, 17, 'What is the result of this decorator pattern polymorphism? interface Coffee { double cost(); String description(); } } abstract class CoffeeDecorator implements Coffee { CoffeeDecorator(Coffee coffee) { this.coffee = coffee; } } MilkDecorator(Coffee coffee) { super(coffee); } } Coffee coffee = new MilkDecorator(new SimpleCoffee());', 'class SimpleCoffee implements Coffee {\npublic double cost() { return 2.0; }\npublic String description() { return \"Simple coffee\"; }\nprotected Coffee coffee;\npublic double cost() { return coffee.cost(); }\npublic String description() { return coffee.description(); }\nclass MilkDecorator extends CoffeeDecorator {\npublic double cost() { return coffee.cost() + 0.5; }\npublic String description() { return coffee.description() + \" + milk\"; }\nSystem.out.println(coffee.cost());', '2.0', '2.5', '0.5', 'B', 'Intermediate', 'Simulation question'),
(578, 17, 'What will this state pattern polymorphism output? interface State { void handle(); } } } } Context context = new Context(); context.setState(new ConcreteStateA()); context.request();', 'class Context {\nprivate State state;\npublic void setState(State state) { this.state = state; }\npublic void request() { state.handle(); }\nclass ConcreteStateA implements State {\npublic void handle() { System.out.println(\"Handling in State A\"); }\nclass ConcreteStateB implements State {\npublic void handle() { System.out.println(\"Handling in State B\"); }', 'Handling in State B', 'Handling in State A', 'Nothing', 'B', 'Intermediate', 'Simulation question'),
(579, 17, 'What happens with this chain of responsibility polymorphism? abstract class Handler { } if (request.equals(\"A\")) { } else if (nextHandler != null) { nextHandler.handleRequest(request); } } } if (request.equals(\"B\")) { } } } Handler h1 = new ConcreteHandlerA(); Handler h2 = new ConcreteHandlerB(); h1.setNext(h2); h1.handleRequest(\"B\");', 'protected Handler nextHandler;\npublic void setNext(Handler handler) { this.nextHandler = handler; }\npublic abstract void handleRequest(String request);\nclass ConcreteHandlerA extends Handler {\npublic void handleRequest(String request) {\nSystem.out.println(\"Handler A processing\");\nclass ConcreteHandlerB extends Handler {\npublic void handleRequest(String request) {\nSystem.out.println(\"Handler B processing\");', 'Handler A processing', 'Handler B processing', 'Nothing', 'B', 'Intermediate', 'Simulation question'),
(580, 17, 'What is the output of this bridge pattern polymorphism? interface DrawingAPI { void drawCircle(int x, int y, int radius); } } } abstract class Shape { } Circle(int x, int y, int radius, DrawingAPI drawingAPI) { super(drawingAPI); this.x = x; this.y = y; this.radius = radius; } } circle.draw();', 'class DrawingAPI1 implements DrawingAPI {\npublic void drawCircle(int x, int y, int radius) {\nSystem.out.println(\"API1 drawing circle\");\nprotected DrawingAPI drawingAPI;\nShape(DrawingAPI drawingAPI) { this.drawingAPI = drawingAPI; }\npublic abstract void draw();\nclass Circle extends Shape {\nprivate int x, y, radius;\npublic void draw() { drawingAPI.drawCircle(x, y, radius); }\nShape circle = new Circle(1, 2, 3, new DrawingAPI1());', 'Drawing circle', 'API1 drawing circle', 'Compilation error', 'B', 'Intermediate', 'Simulation question');

-- --------------------------------------------------------

--
-- Table structure for table `regression_analysis_results`
--

CREATE TABLE `regression_analysis_results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `analysis_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `model_type` enum('linear','polynomial','multiple') DEFAULT 'multiple',
  `input_variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`input_variables`)),
  `predicted_outcomes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`predicted_outcomes`)),
  `r_squared_value` decimal(5,4) DEFAULT NULL,
  `coefficients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`coefficients`)),
  `analysis_summary` text DEFAULT NULL,
  `recommendations_generated` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `save_progress`
--

CREATE TABLE `save_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `level` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `save_progress`
--

INSERT INTO `save_progress` (`id`, `user_id`, `topic_id`, `level`, `score`, `attempt_time`) VALUES
(2, 1, 15, 1, 100, '2025-09-26 06:14:46'),
(17, 1, 15, 2, 80, '2025-09-29 06:48:33'),
(18, 1, 15, 3, 100, '2025-09-29 06:48:52'),
(19, 1, 15, 4, 100, '2025-09-29 06:49:24'),
(20, 1, 15, 5, 100, '2025-09-29 06:49:51'),
(21, 1, 14, 1, 100, '2025-09-29 08:24:16'),
(22, 1, 30, 1, 100, '2025-09-29 08:51:12'),
(23, 1, 30, 1, 100, '2025-09-29 08:52:28'),
(24, 1, 13, 1, 100, '2025-09-29 08:59:50'),
(25, 1, 13, 1, 80, '2025-09-29 09:01:40'),
(26, 1, 13, 2, 100, '2025-09-29 12:57:08'),
(27, 1, 13, 3, 100, '2025-09-29 12:57:22'),
(28, 1, 13, 4, 100, '2025-09-29 12:57:59'),
(29, 1, 13, 5, 100, '2025-09-29 12:58:18'),
(30, 1, 14, 2, 100, '2025-09-29 17:07:11'),
(31, 1, 14, 3, 100, '2025-09-29 17:07:34'),
(32, 1, 14, 4, 100, '2025-09-29 17:07:56'),
(33, 1, 14, 5, 100, '2025-09-29 17:08:22'),
(34, 2, 16, 1, 100, '2025-10-01 04:09:26'),
(35, 2, 16, 2, 100, '2025-10-01 04:09:36'),
(36, 2, 16, 3, 100, '2025-10-01 04:09:47'),
(37, 2, 16, 4, 100, '2025-10-01 04:09:58'),
(38, 2, 16, 5, 100, '2025-10-01 04:10:11'),
(64, 6, 16, 1, 100, '2025-10-01 04:09:26'),
(65, 6, 16, 2, 100, '2025-10-01 04:09:36'),
(66, 6, 16, 3, 100, '2025-10-01 04:09:47'),
(67, 6, 16, 4, 100, '2025-10-01 04:09:58'),
(68, 6, 16, 5, 100, '2025-10-01 04:10:11');

-- --------------------------------------------------------

--
-- Table structure for table `skill_progression_tracking`
--

CREATE TABLE `skill_progression_tracking` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `skill_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `attempts_count` int(11) DEFAULT 0,
  `best_score` decimal(5,2) DEFAULT 0.00,
  `average_score` decimal(5,2) DEFAULT 0.00,
  `time_spent_minutes` int(11) DEFAULT 0,
  `last_activity_date` timestamp NULL DEFAULT NULL,
  `mastery_achieved` tinyint(1) DEFAULT 0,
  `improvement_rate` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skill_progression_tracking`
--

INSERT INTO `skill_progression_tracking` (`id`, `student_id`, `topic_id`, `skill_level`, `attempts_count`, `best_score`, `average_score`, `time_spent_minutes`, `last_activity_date`, `mastery_achieved`, `improvement_rate`, `created_at`, `updated_at`) VALUES
(1, 1, 13, 'beginner', 1, 100.00, 100.00, 0, '2025-09-29 12:58:18', 0, 0.00, '2025-09-29 12:58:18', '2025-09-29 12:58:18'),
(2, 1, 14, 'beginner', 1, 100.00, 100.00, 0, '2025-09-29 17:08:22', 0, 0.00, '2025-09-29 17:08:22', '2025-09-29 17:08:22'),
(3, 2, 16, 'beginner', 1, 100.00, 100.00, 0, '2025-10-01 04:10:11', 0, 0.00, '2025-10-01 04:10:11', '2025-10-01 04:10:11');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `year_level` int(11) DEFAULT NULL,
  `assessment_data` longtext DEFAULT NULL,
  `assessment_details` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `first_name`, `last_name`, `email`, `student_number`, `program`, `year_level`, `assessment_data`, `assessment_details`, `created_at`, `updated_at`) VALUES
(1, 6, NULL, NULL, NULL, NULL, NULL, NULL, '{\"format\":\"scalable\",\"total_score\":115,\"max_total_score\":150,\"overall_percentage\":76.67,\"topic_scores\":{\"13\":{\"name\":\"Introduction to OOP Concepts\",\"quiz_correct\":7,\"quiz_total\":8,\"simulation_correct\":6,\"simulation_total\":8,\"hands_on_score\":9,\"hands_on_max\":10,\"percentage\":80,\"hasActivity\":true},\"14\":{\"name\":\"Classes and Objects\",\"quiz_correct\":6,\"quiz_total\":8,\"simulation_correct\":7,\"simulation_total\":8,\"hands_on_score\":8,\"hands_on_max\":10,\"percentage\":76,\"hasActivity\":true},\"15\":{\"name\":\"Encapsulation\",\"quiz_correct\":6,\"quiz_total\":8,\"simulation_correct\":6,\"simulation_total\":8,\"hands_on_score\":7,\"hands_on_max\":10,\"percentage\":73,\"hasActivity\":true},\"16\":{\"name\":\"Inheritance\",\"quiz_correct\":7,\"quiz_total\":8,\"simulation_correct\":6,\"simulation_total\":8,\"hands_on_score\":8,\"hands_on_max\":10,\"percentage\":78,\"hasActivity\":true},\"17\":{\"name\":\"Polymorphism\",\"quiz_correct\":6,\"quiz_total\":8,\"simulation_correct\":7,\"simulation_total\":8,\"hands_on_score\":7,\"hands_on_max\":10,\"percentage\":75,\"hasActivity\":true}}}', '{\"class_level\":\"Beginner\",\"promotion_attempts\":[{\"from\":\"Beginner\",\"to\":\"Intermediate\",\"score\":48,\"date\":\"2025-10-02 17:15:22\",\"passed\":false},{\"from\":\"Beginner\",\"to\":\"Intermediate\",\"score\":18,\"date\":\"2025-10-02 17:27:05\",\"passed\":false}]}', '2025-10-02 06:40:22', '2025-10-02 15:27:05'),
(2, 3, NULL, NULL, NULL, NULL, NULL, NULL, '{\"format\":\"scalable\",\"total_score\":12.74,\"max_total_score\":150,\"overall_percentage\":8.49,\"topic_scores\":{\"13\":{\"questionScore\":12.74,\"handsOnScore\":0,\"totalScore\":12.74,\"maxScore\":30,\"percentage\":42.47,\"name\":\"Introduction to OOP Concepts\",\"hasActivity\":true,\"quiz_correct\":6,\"quiz_total\":8,\"simulation_correct\":4,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10},\"14\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Classes and Objects\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10},\"15\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Encapsulation\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10},\"16\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Inheritance\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10},\"17\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Polymorphism\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10}},\"scoring_data\":{\"maxScorePerTopic\":30,\"questionPointsPerTopic\":20,\"handsOnPointsPerTopic\":10,\"beginnerPoints\":0.91,\"intermediatePoints\":1.82},\"activities\":{\"13\":{\"code\":\"class Employee {\\n  \\/\\/ TODO: Add private fields for id (int), name (String), salary (double)\\n  \\/\\/ TODO: Add a public method to get the employee\'s name\\n}\",\"requirements\":{\"Must have private id field\":\"\\/private\\\\s+int\\\\s+id\\/\",\"Must have private name field\":\"\\/private\\\\s+String\\\\s+name\\/\",\"Must have public getName method\":\"\\/public\\\\s+String\\\\s+getName\\/\"},\"maxPoints\":10},\"14\":{\"code\":\"class BankAccount {\\n  private String accountNumber;\\n  private double balance;\\n  \\n  \\/\\/ TODO: Add constructor that takes accountNumber and initial balance\\n  \\/\\/ TODO: Add getter methods for both fields\\n}\",\"requirements\":{\"Must have constructor\":\"\\/BankAccount\\\\s*\\\\(\\\\s*String\\\\s+\\\\w+\\\\s*,\\\\s*double\\\\s+\\\\w+\\\\s*\\\\)\\/\",\"Must have getter for accountNumber\":\"\\/String\\\\s+getAccountNumber\\\\s*\\\\(\\\\s*\\\\)\\/\",\"Must have getter for balance\":\"\\/double\\\\s+getBalance\\\\s*\\\\(\\\\s*\\\\)\\/\"},\"maxPoints\":10},\"15\":{\"code\":\"class Money {\\n  \\/\\/ TODO: Add final fields for amount and currency\\n  \\/\\/ TODO: Add constructor with validation\\n  \\/\\/ TODO: Add methods to add\\/subtract that return new Money objects\\n  \\/\\/ TODO: Add getters and toString method\\n}\",\"requirements\":{\"Must have final amount\":\"\\/private\\\\s+final\\\\s+double\\\\s+amount\\/\",\"Must have final currency\":\"\\/private\\\\s+final\\\\s+String\\\\s+currency\\/\",\"Must return new Money\":\"\\/return\\\\s+new\\\\s+Money\\/\",\"Must validate amount\":\"\\/amount.*>=.*0|amount.*>.*0\\/\"},\"maxPoints\":10},\"16\":{\"code\":\"class Person {\\n  protected String name;\\n  protected int age;\\n  \\n  public void introduce() {\\n    System.out.println(\\\"Hi, I\'m \\\" + name);\\n  }\\n}\\n\\nclass Employee extends Person {\\n  protected String employeeId;\\n  protected double salary;\\n}\\n\\n\\/\\/ TODO: Create Manager class that extends Employee\\n\\/\\/ TODO: Add team size and department fields\\n\\/\\/ TODO: Override introduce() to include management info\",\"requirements\":{\"Must extend Employee\":\"\\/class\\\\s+Manager\\\\s+extends\\\\s+Employee\\/\",\"Must have team management fields\":\"\\/teamSize|department\\/\",\"Must override introduce\":\"\\/@Override.*introduce|introduce.*@Override\\/\"},\"maxPoints\":10},\"17\":{\"code\":\"class Transport {\\n  protected String route;\\n  protected double distance;\\n  \\n  public Transport(String route, double distance) {\\n    this.route = route;\\n    this.distance = distance;\\n  }\\n  \\n  public double calculateFare() {\\n    return distance * 1.0; \\/\\/ Base rate per km\\n  }\\n  \\n  public String getTransportInfo() {\\n    return \\\"Transport on \\\" + route + \\\" (\\\" + distance + \\\" km)\\\";\\n  }\\n}\\n\\n\\/\\/ TODO: Create Bus class with fixed fare + distance pricing\\n\\/\\/ TODO: Create Taxi class with surge pricing capability\\n\\/\\/ TODO: Create a FareCalculator that works with Transport references\",\"requirements\":{\"Must extend Transport\":\"\\/class\\\\s+(Bus|Taxi)\\\\s+extends\\\\s+Transport\\/\",\"Must override calculateFare\":\"\\/@Override.*calculateFare\\/\",\"Must have FareCalculator\":\"\\/class\\\\s+FareCalculator\\/\",\"Must use polymorphic calls\":\"\\/Transport.*calculateFare\\/\"},\"maxPoints\":10}},\"assessment_type\":\"scalable_pretest\"}', '{\"class_level\":\"Beginner\",\"progress_to_next\":11.1,\"completion_date\":\"2025-10-02 09:09:01\"}', '2025-10-02 07:09:01', '2025-10-02 07:09:01'),
(3, 4, NULL, NULL, NULL, NULL, NULL, NULL, '{\"format\":\"scalable\",\"total_score\":1.82,\"max_total_score\":150,\"overall_percentage\":1.21,\"topic_scores\":{\"13\":{\"questionScore\":1.82,\"handsOnScore\":0,\"totalScore\":1.82,\"maxScore\":30,\"percentage\":6.07,\"name\":\"Introduction to OOP Concepts\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":1,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10},\"14\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Classes and Objects\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10},\"15\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Encapsulation\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10},\"16\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Inheritance\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10},\"17\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Polymorphism\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10}},\"scoring_data\":{\"maxScorePerTopic\":30,\"questionPointsPerTopic\":20,\"handsOnPointsPerTopic\":10,\"beginnerPoints\":0.91,\"intermediatePoints\":1.82},\"activities\":{\"13\":{\"code\":\"class BankAccount {\\n  \\/\\/ TODO: Add private fields for accountNumber (String) and balance (double)\\n  \\/\\/ TODO: Add a public method to get the current balance\\n}\",\"requirements\":{\"Must have private accountNumber\":\"\\/private\\\\s+String\\\\s+accountNumber\\/\",\"Must have private balance\":\"\\/private\\\\s+double\\\\s+balance\\/\",\"Must have public getBalance method\":\"\\/public\\\\s+double\\\\s+getBalance\\/\"},\"maxPoints\":10},\"14\":{\"code\":\"class Temperature {\\n  private double celsius;\\n  \\n  \\/\\/ TODO: Add constructor that validates temperature (above absolute zero: -273.15\\u00b0C)\\n  \\/\\/ TODO: Add method to convert to Fahrenheit\\n  \\/\\/ TODO: Add method to convert to Kelvin\\n  \\/\\/ TODO: Add setter with validation\\n}\",\"requirements\":{\"Must validate temperature\":\"\\/celsius.*>.*-273|celsius.*>=.*-273\\/\",\"Must have toFahrenheit\":\"\\/double\\\\s+toFahrenheit\\\\s*\\\\(\\\\s*\\\\)\\/\",\"Must have toKelvin\":\"\\/double\\\\s+toKelvin\\\\s*\\\\(\\\\s*\\\\)\\/\"},\"maxPoints\":10},\"15\":{\"code\":\"class Temperature {\\n  private double celsius;\\n  private String scale;\\n  \\n  \\/\\/ TODO: Add setCelsius that validates temperature above absolute zero (-273.15)\\n  \\/\\/ TODO: Add methods to convert to Fahrenheit and Kelvin\\n  \\/\\/ TODO: Add validation for temperature scale (Celsius, Fahrenheit, Kelvin)\\n}\",\"requirements\":{\"Must validate absolute zero\":\"\\/celsius.*>.*-273|celsius.*>=.*-273\\/\",\"Must have conversion methods\":\"\\/(toFahrenheit|toKelvin|fahrenheit|kelvin)\\/\",\"Must validate scale\":\"\\/scale.*equals.*Celsius|Fahrenheit|Kelvin\\/\"},\"maxPoints\":10},\"16\":{\"code\":\"\\/\\/ TODO: Create abstract class Shape with abstract methods\\n\\/\\/ Must have abstract calculateArea() method\\n\\/\\/ Must have concrete method displayInfo()\\n\\n\\/\\/ TODO: Create Rectangle class that extends Shape\\n\\/\\/ TODO: Implement calculateArea() for rectangle\\n\\/\\/ TODO: Create Circle class that extends Shape\\n\\/\\/ TODO: Implement calculateArea() for circle\",\"requirements\":{\"Must be abstract class\":\"\\/abstract\\\\s+class\\\\s+Shape\\/\",\"Must have abstract method\":\"\\/abstract.*calculateArea|calculateArea.*abstract\\/\",\"Must implement in Rectangle\":\"\\/class\\\\s+Rectangle\\\\s+extends\\\\s+Shape.*calculateArea\\/s\",\"Must implement in Circle\":\"\\/class\\\\s+Circle\\\\s+extends\\\\s+Shape.*calculateArea\\/s\"},\"maxPoints\":10},\"17\":{\"code\":\"abstract class Vehicle {\\n  protected String licensePlate;\\n  protected int year;\\n  \\n  public Vehicle(String licensePlate, int year) {\\n    this.licensePlate = licensePlate;\\n    this.year = year;\\n  }\\n  \\n  public abstract boolean inspect();\\n  public abstract String getVehicleType();\\n}\\n\\nclass Car extends Vehicle {\\n  private int numberOfDoors;\\n  \\n  public Car(String licensePlate, int year, int numberOfDoors) {\\n    super(licensePlate, year);\\n    this.numberOfDoors = numberOfDoors;\\n  }\\n  \\n  public void checkAirConditioner() {\\n    System.out.println(\\\"Checking car AC system\\\");\\n  }\\n  \\n  \\/\\/ TODO: Implement inspect() and getVehicleType()\\n}\\n\\n\\/\\/ TODO: Create Motorcycle class with specific motorcycle checks\\n\\/\\/ TODO: Create VehicleInspector class that uses instanceof for specific inspections\",\"requirements\":{\"Must use instanceof\":\"\\/instanceof\\\\s+(Car|Motorcycle)\\/\",\"Must extend Vehicle\":\"\\/class\\\\s+Motorcycle\\\\s+extends\\\\s+Vehicle\\/\",\"Must have VehicleInspector\":\"\\/class\\\\s+VehicleInspector\\/\",\"Must perform specific checks\":\"\\/checkAirConditioner|check.*specific\\/\"},\"maxPoints\":10}},\"assessment_type\":\"scalable_pretest\"}', '{\"class_level\":\"Beginner\",\"progress_to_next\":1.6,\"completion_date\":\"2025-10-03 17:46:27\"}', '2025-10-03 15:46:27', '2025-10-03 15:46:27');

-- --------------------------------------------------------

--
-- Table structure for table `student_activity_scores`
--

CREATE TABLE `student_activity_scores` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `avg_score` decimal(5,2) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_activity_scores`
--

INSERT INTO `student_activity_scores` (`id`, `student_id`, `topic_id`, `module`, `avg_score`, `date_created`, `last_updated`) VALUES
(8, 1, 14, 'classes_objects_overall', 96.00, '2025-09-15 08:21:22', '2025-09-15 08:21:22'),
(10, 1, 15, 'encapsulation_overall', 96.00, '2025-09-15 10:20:46', '2025-09-15 10:20:46'),
(11, 1, 16, 'inheritance_overall', 100.00, '2025-09-15 10:36:41', '2025-09-15 10:36:41'),
(12, 1, 17, 'polymorphism_overall', 100.00, '2025-09-15 12:24:36', '2025-09-15 12:24:36'),
(13, 1, 13, 'intro_overall', 100.00, '2025-09-15 14:24:24', '2025-09-15 14:24:24'),
(14, 1, 14, 'classes_objects_overall', 80.00, '2025-09-15 14:55:58', '2025-09-15 14:55:58'),
(15, 1, 14, 'classes_objects_overall', 80.00, '2025-09-15 14:57:59', '2025-09-15 14:57:59'),
(16, 1, 14, 'classes_objects_overall', 100.00, '2025-09-15 14:59:26', '2025-09-15 14:59:26'),
(17, 1, 15, 'encapsulation_overall', 100.00, '2025-09-17 07:25:23', '2025-09-17 07:25:23'),
(18, 1, 15, 'encapsulation_overall', 92.00, '2025-09-17 07:27:21', '2025-09-17 07:27:21'),
(19, 1, 13, 'intro_overall', 95.00, '2025-09-17 07:47:01', '2025-09-17 07:47:01'),
(20, 1, 13, 'intro_overall', 88.00, '2025-09-17 07:47:01', '2025-09-17 07:47:01'),
(21, 1, 13, 'intro_overall', 100.00, '2025-09-17 07:47:01', '2025-09-17 07:47:01'),
(22, 1, 13, 'intro_overall', 90.00, '2025-09-17 07:47:01', '2025-09-17 07:47:01'),
(23, 2, 14, 'classes_objects_overall', 92.00, '2025-09-17 07:54:34', '2025-09-17 07:54:34'),
(24, 1, 14, 'classes_objects_overall', 96.00, '2025-09-17 12:25:24', '2025-09-17 12:25:24'),
(25, 1, 14, 'topic_14', 96.00, '2025-09-26 06:55:41', '2025-09-26 06:55:41'),
(26, 1, 15, 'topic_15', 96.00, '2025-09-29 06:49:51', '2025-09-29 06:49:51'),
(27, 1, 13, 'topic_13', 100.00, '2025-09-29 12:58:18', '2025-09-29 12:58:18'),
(28, 1, 14, 'topic_14', 100.00, '2025-09-29 17:08:22', '2025-09-29 17:08:22'),
(29, 2, 16, 'topic_16', 100.00, '2025-10-01 04:10:11', '2025-10-01 04:10:11');

--
-- Triggers `student_activity_scores`
--
DELIMITER $$
CREATE TRIGGER `update_skill_progression_after_activity` AFTER INSERT ON `student_activity_scores` FOR EACH ROW BEGIN
    
    INSERT INTO skill_progression_tracking 
        (student_id, topic_id, attempts_count, best_score, average_score, last_activity_date)
    VALUES 
        (NEW.student_id, NEW.topic_id, 1, NEW.avg_score, NEW.avg_score, NOW())
    ON DUPLICATE KEY UPDATE
        attempts_count = attempts_count + 1,
        best_score = GREATEST(best_score, NEW.avg_score),
        average_score = (average_score * (attempts_count - 1) + NEW.avg_score) / attempts_count,
        last_activity_date = NOW(),
        mastery_achieved = CASE WHEN NEW.avg_score >= 80 THEN TRUE ELSE mastery_achieved END,
        skill_level = CASE 
            WHEN NEW.avg_score >= 80 THEN 'advanced'
            WHEN NEW.avg_score >= 60 THEN 'intermediate' 
            ELSE 'beginner'
        END;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `student_progress_history`
--

CREATE TABLE `student_progress_history` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `level` varchar(50) NOT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `progress_percentage` decimal(5,2) DEFAULT NULL,
  `assessment_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`assessment_data`)),
  `activity_scores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`activity_scores`)),
  `post_test_scores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`post_test_scores`)),
  `achievements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`achievements`)),
  `promoted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_progress_history`
--

INSERT INTO `student_progress_history` (`id`, `student_id`, `level`, `overall_score`, `progress_percentage`, `assessment_data`, `activity_scores`, `post_test_scores`, `achievements`, `promoted_at`) VALUES
(1, 6, 'Beginner', 9.10, 0.00, '{\"format\":\"scalable\",\"total_score\":13.65,\"max_total_score\":150,\"overall_percentage\":9.1,\"topic_scores\":{\"13\":{\"questionScore\":13.65,\"handsOnScore\":0,\"totalScore\":13.65,\"maxScore\":30,\"percentage\":45.5,\"name\":\"Introduction to OOP Concepts\",\"hasActivity\":true,\"quiz_correct\":7,\"quiz_total\":8,\"simulation_correct\":4,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10},\"14\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Classes and Objects\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10},\"15\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Encapsulation\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10},\"16\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Inheritance\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10},\"17\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Polymorphism\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10}},\"scoring_data\":{\"maxScorePerTopic\":30,\"questionPointsPerTopic\":20,\"handsOnPointsPerTopic\":10,\"beginnerPoints\":0.91,\"intermediatePoints\":1.82},\"activities\":{\"13\":{\"code\":\"class Rectangle {\\n  private double width;\\n  private double height;\\n  \\n  \\/\\/ TODO: Add constructor that initializes width and height\\n  \\/\\/ TODO: Add a method to calculate area\\n}\",\"requirements\":{\"Must have constructor\":\"\\/Rectangle\\\\s*\\\\(\\\\s*double\\\\s+\\\\w+\\\\s*,\\\\s*double\\\\s+\\\\w+\\\\s*\\\\)\\/\",\"Must have area method\":\"\\/public\\\\s+double\\\\s+\\\\w*[Aa]rea\\/\"},\"maxPoints\":10},\"14\":{\"code\":\"class Employee {\\n  private String name;\\n  private int id;\\n  private double salary;\\n  \\n  \\/\\/ TODO: Add constructor that initializes all fields\\n  \\/\\/ TODO: Add getter and setter methods for salary\\n}\",\"requirements\":{\"Must have constructor\":\"\\/Employee\\\\s*\\\\(\\\\s*String\\\\s+\\\\w+\\\\s*,\\\\s*int\\\\s+\\\\w+\\\\s*,\\\\s*double\\\\s+\\\\w+\\\\s*\\\\)\\/\",\"Must have getSalary\":\"\\/double\\\\s+getSalary\\\\s*\\\\(\\\\s*\\\\)\\/\",\"Must have setSalary\":\"\\/void\\\\s+setSalary\\\\s*\\\\(\\\\s*double\\\\s+\\\\w+\\\\s*\\\\)\\/\"},\"maxPoints\":10},\"15\":{\"code\":\"class User {\\n  private String email;\\n  private String password;\\n  private int loginAttempts;\\n  \\n  \\/\\/ TODO: Add setEmail with email format validation (must contain @)\\n  \\/\\/ TODO: Add setPassword with minimum length requirement (8+ characters)\\n  \\/\\/ TODO: Add method to increment login attempts with maximum limit\\n}\",\"requirements\":{\"Must validate email\":\"\\/email.*contains.*@|@.*email\\/\",\"Must validate password length\":\"\\/password.*length.*>=.*8|password.*length.*>.*7\\/\",\"Must limit login attempts\":\"\\/loginAttempts.*<|if.*loginAttempts\\/\"},\"maxPoints\":10},\"16\":{\"code\":\"class Person {\\n  protected String name;\\n  protected int age;\\n  \\n  public void introduce() {\\n    System.out.println(\\\"Hi, I\'m \\\" + name);\\n  }\\n}\\n\\nclass Employee extends Person {\\n  protected String employeeId;\\n  protected double salary;\\n}\\n\\n\\/\\/ TODO: Create Manager class that extends Employee\\n\\/\\/ TODO: Add team size and department fields\\n\\/\\/ TODO: Override introduce() to include management info\",\"requirements\":{\"Must extend Employee\":\"\\/class\\\\s+Manager\\\\s+extends\\\\s+Employee\\/\",\"Must have team management fields\":\"\\/teamSize|department\\/\",\"Must override introduce\":\"\\/@Override.*introduce|introduce.*@Override\\/\"},\"maxPoints\":10},\"17\":{\"code\":\"abstract class Vehicle {\\n  protected String licensePlate;\\n  protected int year;\\n  \\n  public Vehicle(String licensePlate, int year) {\\n    this.licensePlate = licensePlate;\\n    this.year = year;\\n  }\\n  \\n  public abstract boolean inspect();\\n  public abstract String getVehicleType();\\n}\\n\\nclass Car extends Vehicle {\\n  private int numberOfDoors;\\n  \\n  public Car(String licensePlate, int year, int numberOfDoors) {\\n    super(licensePlate, year);\\n    this.numberOfDoors = numberOfDoors;\\n  }\\n  \\n  public void checkAirConditioner() {\\n    System.out.println(\\\"Checking car AC system\\\");\\n  }\\n  \\n  \\/\\/ TODO: Implement inspect() and getVehicleType()\\n}\\n\\n\\/\\/ TODO: Create Motorcycle class with specific motorcycle checks\\n\\/\\/ TODO: Create VehicleInspector class that uses instanceof for specific inspections\",\"requirements\":{\"Must use instanceof\":\"\\/instanceof\\\\s+(Car|Motorcycle)\\/\",\"Must extend Vehicle\":\"\\/class\\\\s+Motorcycle\\\\s+extends\\\\s+Vehicle\\/\",\"Must have VehicleInspector\":\"\\/class\\\\s+VehicleInspector\\/\",\"Must perform specific checks\":\"\\/checkAirConditioner|check.*specific\\/\"},\"maxPoints\":10}},\"assessment_type\":\"scalable_pretest\"}', '[]', '[{\"topic_id\":14,\"score\":\"88.00\",\"completed_at\":\"2025-10-02 17:34:56\"},{\"topic_id\":14,\"score\":\"0.00\",\"completed_at\":null},{\"topic_id\":13,\"score\":\"83.33\",\"completed_at\":\"2025-10-02 19:26:56\"},{\"topic_id\":15,\"score\":\"96.67\",\"completed_at\":\"2025-10-02 19:47:13\"},{\"topic_id\":16,\"score\":\"96.00\",\"completed_at\":\"2025-10-02 19:55:19\"},{\"topic_id\":17,\"score\":\"66.67\",\"completed_at\":\"2025-10-02 20:05:48\"}]', '{\"activities_completed\":0,\"post_tests_taken\":6,\"promotion_score\":78,\"promotion_test_date\":\"2025-10-02 15:35:36\"}', '2025-10-02 13:35:36'),
(2, 6, 'Beginner', 0.00, 0.00, '{\"format\":\"scalable\",\"total_score\":13.65,\"max_total_score\":150,\"overall_percentage\":9.1,\"topic_scores\":{\"13\":{\"questionScore\":13.65,\"handsOnScore\":0,\"totalScore\":13.65,\"maxScore\":30,\"percentage\":0,\"name\":\"Introduction to OOP Concepts\",\"hasActivity\":true,\"quiz_correct\":7,\"quiz_total\":8,\"simulation_correct\":4,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10,\"score\":0},\"14\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Classes and Objects\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10,\"score\":0},\"15\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Encapsulation\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10,\"score\":0},\"16\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Inheritance\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10,\"score\":0},\"17\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Polymorphism\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10,\"score\":0}},\"scoring_data\":{\"maxScorePerTopic\":30,\"questionPointsPerTopic\":20,\"handsOnPointsPerTopic\":10,\"beginnerPoints\":0.91,\"intermediatePoints\":1.82},\"activities\":{\"13\":{\"code\":\"class Rectangle {\\n  private double width;\\n  private double height;\\n  \\n  \\/\\/ TODO: Add constructor that initializes width and height\\n  \\/\\/ TODO: Add a method to calculate area\\n}\",\"requirements\":{\"Must have constructor\":\"\\/Rectangle\\\\s*\\\\(\\\\s*double\\\\s+\\\\w+\\\\s*,\\\\s*double\\\\s+\\\\w+\\\\s*\\\\)\\/\",\"Must have area method\":\"\\/public\\\\s+double\\\\s+\\\\w*[Aa]rea\\/\"},\"maxPoints\":10},\"14\":{\"code\":\"class Employee {\\n  private String name;\\n  private int id;\\n  private double salary;\\n  \\n  \\/\\/ TODO: Add constructor that initializes all fields\\n  \\/\\/ TODO: Add getter and setter methods for salary\\n}\",\"requirements\":{\"Must have constructor\":\"\\/Employee\\\\s*\\\\(\\\\s*String\\\\s+\\\\w+\\\\s*,\\\\s*int\\\\s+\\\\w+\\\\s*,\\\\s*double\\\\s+\\\\w+\\\\s*\\\\)\\/\",\"Must have getSalary\":\"\\/double\\\\s+getSalary\\\\s*\\\\(\\\\s*\\\\)\\/\",\"Must have setSalary\":\"\\/void\\\\s+setSalary\\\\s*\\\\(\\\\s*double\\\\s+\\\\w+\\\\s*\\\\)\\/\"},\"maxPoints\":10},\"15\":{\"code\":\"class User {\\n  private String email;\\n  private String password;\\n  private int loginAttempts;\\n  \\n  \\/\\/ TODO: Add setEmail with email format validation (must contain @)\\n  \\/\\/ TODO: Add setPassword with minimum length requirement (8+ characters)\\n  \\/\\/ TODO: Add method to increment login attempts with maximum limit\\n}\",\"requirements\":{\"Must validate email\":\"\\/email.*contains.*@|@.*email\\/\",\"Must validate password length\":\"\\/password.*length.*>=.*8|password.*length.*>.*7\\/\",\"Must limit login attempts\":\"\\/loginAttempts.*<|if.*loginAttempts\\/\"},\"maxPoints\":10},\"16\":{\"code\":\"class Person {\\n  protected String name;\\n  protected int age;\\n  \\n  public void introduce() {\\n    System.out.println(\\\"Hi, I\'m \\\" + name);\\n  }\\n}\\n\\nclass Employee extends Person {\\n  protected String employeeId;\\n  protected double salary;\\n}\\n\\n\\/\\/ TODO: Create Manager class that extends Employee\\n\\/\\/ TODO: Add team size and department fields\\n\\/\\/ TODO: Override introduce() to include management info\",\"requirements\":{\"Must extend Employee\":\"\\/class\\\\s+Manager\\\\s+extends\\\\s+Employee\\/\",\"Must have team management fields\":\"\\/teamSize|department\\/\",\"Must override introduce\":\"\\/@Override.*introduce|introduce.*@Override\\/\"},\"maxPoints\":10},\"17\":{\"code\":\"abstract class Vehicle {\\n  protected String licensePlate;\\n  protected int year;\\n  \\n  public Vehicle(String licensePlate, int year) {\\n    this.licensePlate = licensePlate;\\n    this.year = year;\\n  }\\n  \\n  public abstract boolean inspect();\\n  public abstract String getVehicleType();\\n}\\n\\nclass Car extends Vehicle {\\n  private int numberOfDoors;\\n  \\n  public Car(String licensePlate, int year, int numberOfDoors) {\\n    super(licensePlate, year);\\n    this.numberOfDoors = numberOfDoors;\\n  }\\n  \\n  public void checkAirConditioner() {\\n    System.out.println(\\\"Checking car AC system\\\");\\n  }\\n  \\n  \\/\\/ TODO: Implement inspect() and getVehicleType()\\n}\\n\\n\\/\\/ TODO: Create Motorcycle class with specific motorcycle checks\\n\\/\\/ TODO: Create VehicleInspector class that uses instanceof for specific inspections\",\"requirements\":{\"Must use instanceof\":\"\\/instanceof\\\\s+(Car|Motorcycle)\\/\",\"Must extend Vehicle\":\"\\/class\\\\s+Motorcycle\\\\s+extends\\\\s+Vehicle\\/\",\"Must have VehicleInspector\":\"\\/class\\\\s+VehicleInspector\\/\",\"Must perform specific checks\":\"\\/checkAirConditioner|check.*specific\\/\"},\"maxPoints\":10}},\"assessment_type\":\"scalable_pretest\"}', '[]', '[]', '{\"activities_completed\":0,\"post_tests_taken\":0,\"promotion_score\":78,\"promotion_test_date\":\"2025-10-02 15:35:48\"}', '2025-10-02 13:35:48'),
(3, 6, 'Beginner', 0.00, 0.00, '{\"format\":\"scalable\",\"total_score\":13.65,\"max_total_score\":150,\"overall_percentage\":9.1,\"topic_scores\":{\"13\":{\"questionScore\":13.65,\"handsOnScore\":0,\"totalScore\":13.65,\"maxScore\":30,\"percentage\":0,\"name\":\"Introduction to OOP Concepts\",\"hasActivity\":true,\"quiz_correct\":7,\"quiz_total\":8,\"simulation_correct\":4,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10,\"score\":0},\"14\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Classes and Objects\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10,\"score\":0},\"15\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Encapsulation\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10,\"score\":0},\"16\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Inheritance\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10,\"score\":0},\"17\":{\"questionScore\":0,\"handsOnScore\":0,\"totalScore\":0,\"maxScore\":30,\"percentage\":0,\"name\":\"Polymorphism\",\"hasActivity\":true,\"quiz_correct\":0,\"quiz_total\":8,\"simulation_correct\":0,\"simulation_total\":8,\"hands_on_score\":0,\"hands_on_max\":10,\"score\":0}},\"scoring_data\":{\"maxScorePerTopic\":30,\"questionPointsPerTopic\":20,\"handsOnPointsPerTopic\":10,\"beginnerPoints\":0.91,\"intermediatePoints\":1.82},\"activities\":{\"13\":{\"code\":\"class Rectangle {\\n  private double width;\\n  private double height;\\n  \\n  \\/\\/ TODO: Add constructor that initializes width and height\\n  \\/\\/ TODO: Add a method to calculate area\\n}\",\"requirements\":{\"Must have constructor\":\"\\/Rectangle\\\\s*\\\\(\\\\s*double\\\\s+\\\\w+\\\\s*,\\\\s*double\\\\s+\\\\w+\\\\s*\\\\)\\/\",\"Must have area method\":\"\\/public\\\\s+double\\\\s+\\\\w*[Aa]rea\\/\"},\"maxPoints\":10},\"14\":{\"code\":\"class Employee {\\n  private String name;\\n  private int id;\\n  private double salary;\\n  \\n  \\/\\/ TODO: Add constructor that initializes all fields\\n  \\/\\/ TODO: Add getter and setter methods for salary\\n}\",\"requirements\":{\"Must have constructor\":\"\\/Employee\\\\s*\\\\(\\\\s*String\\\\s+\\\\w+\\\\s*,\\\\s*int\\\\s+\\\\w+\\\\s*,\\\\s*double\\\\s+\\\\w+\\\\s*\\\\)\\/\",\"Must have getSalary\":\"\\/double\\\\s+getSalary\\\\s*\\\\(\\\\s*\\\\)\\/\",\"Must have setSalary\":\"\\/void\\\\s+setSalary\\\\s*\\\\(\\\\s*double\\\\s+\\\\w+\\\\s*\\\\)\\/\"},\"maxPoints\":10},\"15\":{\"code\":\"class User {\\n  private String email;\\n  private String password;\\n  private int loginAttempts;\\n  \\n  \\/\\/ TODO: Add setEmail with email format validation (must contain @)\\n  \\/\\/ TODO: Add setPassword with minimum length requirement (8+ characters)\\n  \\/\\/ TODO: Add method to increment login attempts with maximum limit\\n}\",\"requirements\":{\"Must validate email\":\"\\/email.*contains.*@|@.*email\\/\",\"Must validate password length\":\"\\/password.*length.*>=.*8|password.*length.*>.*7\\/\",\"Must limit login attempts\":\"\\/loginAttempts.*<|if.*loginAttempts\\/\"},\"maxPoints\":10},\"16\":{\"code\":\"class Person {\\n  protected String name;\\n  protected int age;\\n  \\n  public void introduce() {\\n    System.out.println(\\\"Hi, I\'m \\\" + name);\\n  }\\n}\\n\\nclass Employee extends Person {\\n  protected String employeeId;\\n  protected double salary;\\n}\\n\\n\\/\\/ TODO: Create Manager class that extends Employee\\n\\/\\/ TODO: Add team size and department fields\\n\\/\\/ TODO: Override introduce() to include management info\",\"requirements\":{\"Must extend Employee\":\"\\/class\\\\s+Manager\\\\s+extends\\\\s+Employee\\/\",\"Must have team management fields\":\"\\/teamSize|department\\/\",\"Must override introduce\":\"\\/@Override.*introduce|introduce.*@Override\\/\"},\"maxPoints\":10},\"17\":{\"code\":\"abstract class Vehicle {\\n  protected String licensePlate;\\n  protected int year;\\n  \\n  public Vehicle(String licensePlate, int year) {\\n    this.licensePlate = licensePlate;\\n    this.year = year;\\n  }\\n  \\n  public abstract boolean inspect();\\n  public abstract String getVehicleType();\\n}\\n\\nclass Car extends Vehicle {\\n  private int numberOfDoors;\\n  \\n  public Car(String licensePlate, int year, int numberOfDoors) {\\n    super(licensePlate, year);\\n    this.numberOfDoors = numberOfDoors;\\n  }\\n  \\n  public void checkAirConditioner() {\\n    System.out.println(\\\"Checking car AC system\\\");\\n  }\\n  \\n  \\/\\/ TODO: Implement inspect() and getVehicleType()\\n}\\n\\n\\/\\/ TODO: Create Motorcycle class with specific motorcycle checks\\n\\/\\/ TODO: Create VehicleInspector class that uses instanceof for specific inspections\",\"requirements\":{\"Must use instanceof\":\"\\/instanceof\\\\s+(Car|Motorcycle)\\/\",\"Must extend Vehicle\":\"\\/class\\\\s+Motorcycle\\\\s+extends\\\\s+Vehicle\\/\",\"Must have VehicleInspector\":\"\\/class\\\\s+VehicleInspector\\/\",\"Must perform specific checks\":\"\\/checkAirConditioner|check.*specific\\/\"},\"maxPoints\":10}},\"assessment_type\":\"scalable_pretest\"}', '[]', '[]', '{\"activities_completed\":0,\"post_tests_taken\":0,\"promotion_score\":86,\"promotion_test_date\":\"2025-10-02 15:35:59\"}', '2025-10-02 13:35:59'),
(5, 2, 'Beginner', 86.00, 100.00, '{}', '[]', '[]', '{\"activities_completed\": 0, \"post_tests_taken\": 5, \"promotion_score\": 86}', '2025-10-02 13:43:44');

-- --------------------------------------------------------

--
-- Table structure for table `student_tests`
--

CREATE TABLE `student_tests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `test_type` enum('pre','post','practice') DEFAULT 'practice',
  `score` decimal(5,2) NOT NULL,
  `attempt_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_tests`
--

INSERT INTO `student_tests` (`id`, `student_id`, `topic_id`, `test_type`, `score`, `attempt_date`) VALUES
(51, 1, 13, 'pre', 7.00, '2025-09-29 04:01:31'),
(52, 1, 14, 'pre', 3.00, '2025-09-29 04:01:31'),
(53, 1, 15, 'pre', 6.00, '2025-09-29 04:01:31'),
(54, 1, 16, 'pre', 5.00, '2025-09-29 04:01:31'),
(55, 1, 17, 'pre', 6.00, '2025-09-29 04:01:31'),
(76, 1, 13, 'post', 38.00, '2025-09-22 02:00:00'),
(77, 1, 14, 'post', 30.00, '2025-09-22 02:05:00'),
(78, 1, 15, 'post', 22.00, '2025-09-22 02:10:00'),
(79, 1, 16, 'post', 15.00, '2025-09-22 02:15:00'),
(80, 1, 17, 'post', 25.00, '2025-09-22 02:20:00'),
(81, 2, 13, 'pre', 6.00, '2025-09-29 05:58:21'),
(82, 2, 14, 'pre', 3.00, '2025-09-29 05:58:21'),
(83, 2, 15, 'pre', 6.00, '2025-09-29 05:58:21'),
(84, 2, 16, 'pre', 4.00, '2025-09-29 05:58:21'),
(85, 2, 17, 'pre', 7.00, '2025-09-29 05:58:21'),
(86, 5, 13, 'pre', 0.00, '2025-10-01 10:05:49'),
(87, 5, 14, 'pre', 0.00, '2025-10-01 10:05:49'),
(88, 5, 15, 'pre', 0.00, '2025-10-01 10:05:49'),
(89, 5, 16, 'pre', 2.00, '2025-10-01 10:05:49'),
(90, 5, 17, 'pre', 1.00, '2025-10-01 10:05:49'),
(91, 6, 13, 'pre', 0.00, '2025-10-01 23:38:26'),
(92, 6, 14, 'pre', 0.00, '2025-10-01 23:38:26'),
(93, 6, 15, 'pre', 0.00, '2025-10-01 23:38:26'),
(94, 6, 16, 'pre', 0.00, '2025-10-01 23:38:26'),
(95, 6, 17, 'pre', 0.00, '2025-10-01 23:38:26');

--
-- Triggers `student_tests`
--
DELIMITER $$
CREATE TRIGGER `update_journey_after_test` AFTER INSERT ON `student_tests` FOR EACH ROW BEGIN
    
    IF NEW.test_type = 'pre' THEN
        INSERT INTO user_learning_journey 
            (student_id, subject_id, pre_assessment_score, pre_assessment_date, journey_status)
        SELECT 
            NEW.student_id,
            t.subject_id,
            (SELECT AVG(score) FROM student_tests st2 WHERE st2.student_id = NEW.student_id AND st2.test_type = 'pre'),
            NOW(),
            'pre_assessment_only'
        FROM topics t WHERE t.id = NEW.topic_id
        ON DUPLICATE KEY UPDATE
            pre_assessment_score = VALUES(pre_assessment_score),
            pre_assessment_date = VALUES(pre_assessment_date);
    ELSEIF NEW.test_type = 'post' THEN
        UPDATE user_learning_journey 
        SET 
            post_assessment_score = (SELECT AVG(score) FROM student_tests st2 WHERE st2.student_id = NEW.student_id AND st2.test_type = 'post'),
            post_assessment_date = NOW(),
            journey_status = 'post_assessment_taken',
            improvement_percentage = CASE 
                WHEN pre_assessment_score > 0 THEN 
                    ((SELECT AVG(score) FROM student_tests st3 WHERE st3.student_id = NEW.student_id AND st3.test_type = 'post') - pre_assessment_score) / pre_assessment_score * 100
                ELSE 0 
            END
        WHERE student_id = NEW.student_id 
          AND subject_id = (SELECT subject_id FROM topics WHERE id = NEW.topic_id);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `student_test_attempts`
--

CREATE TABLE `student_test_attempts` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `score` decimal(6,2) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_test_attempts`
--

INSERT INTO `student_test_attempts` (`id`, `student_id`, `test_id`, `score`, `attempt_time`) VALUES
(0, 1, 1, 9.00, '2025-09-18 08:48:15'),
(0, 1, 1, 0.00, '2025-09-18 18:58:27'),
(0, 1, 1, 8.00, '2025-09-18 19:00:41'),
(0, 1, 1, 9.00, '2025-09-18 19:08:07'),
(0, 1, 1, 9.00, '2025-09-18 19:10:06'),
(0, 1, 1, 1.00, '2025-09-18 20:39:27'),
(0, 1, 1, 1.00, '2025-09-20 01:45:29'),
(0, 1, 1, 1.00, '2025-09-20 01:50:13'),
(0, 1, 1, 0.00, '2025-09-20 01:54:00'),
(0, 1, 1, 0.00, '2025-09-20 02:25:45'),
(0, 1, 1, 4.00, '2025-09-20 02:36:26'),
(0, 1, 1, 2.00, '2025-09-20 06:36:45'),
(0, 1, 1, 7.00, '2025-09-21 05:40:13'),
(0, 1, 1, 9.00, '2025-09-22 04:29:31'),
(0, 1, 1, 9.00, '2025-09-29 03:39:26'),
(0, 1, 1, 27.00, '2025-09-29 04:01:31'),
(0, 2, 1, 26.00, '2025-09-29 05:58:21'),
(0, 5, 1, 3.00, '2025-10-01 10:05:49'),
(0, 6, 1, 0.00, '2025-10-01 23:29:27'),
(0, 6, 1, 0.00, '2025-10-01 23:29:30'),
(0, 6, 1, 0.00, '2025-10-01 23:29:32'),
(0, 6, 1, 0.00, '2025-10-01 23:29:52'),
(0, 6, 1, 0.00, '2025-10-01 23:38:26');

-- --------------------------------------------------------

--
-- Table structure for table `student_video_progress`
--

CREATE TABLE `student_video_progress` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `watched_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_video_progress`
--

INSERT INTO `student_video_progress` (`id`, `student_id`, `material_id`, `watched_at`) VALUES
(1, 1, 2, '2025-09-23 10:48:47'),
(2, 1, 11, '2025-09-23 10:49:25'),
(4, 3, 12, '2025-10-01 03:56:37'),
(5, 1, 12, '2025-10-01 12:27:39'),
(6, 1, 15, '2025-10-01 12:27:45');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `code`, `name`) VALUES
(3, 'OOP1', 'Object Oriented Programming 1'),
(4, 'OOP2', 'Object Oriented Programming 2'),
(5, 'WEB1', 'Web Development 1'),
(6, 'WEB2', 'Web Development 2');

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
(42, 6, 'Introduction to Web APIs', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_coding_progress`
--

CREATE TABLE `user_coding_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `difficulty` enum('Easy','Medium','Intermediate','Hard') NOT NULL,
  `problems_solved` int(11) DEFAULT 0,
  `total_problems` int(11) DEFAULT 0,
  `best_score` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_coding_progress`
--

INSERT INTO `user_coding_progress` (`id`, `user_id`, `difficulty`, `problems_solved`, `total_problems`, `best_score`, `last_updated`) VALUES
(1, 3, 'Easy', 0, 4, 0, '2025-10-01 13:04:09'),
(2, 2, 'Easy', 0, 4, 0, '2025-10-01 13:04:09'),
(3, 4, 'Easy', 0, 4, 0, '2025-10-01 13:04:09'),
(4, 1, 'Easy', 0, 4, 0, '2025-10-01 13:04:09'),
(8, 3, 'Medium', 0, 1, 0, '2025-10-01 13:04:09'),
(9, 2, 'Medium', 0, 1, 0, '2025-10-01 13:04:09'),
(10, 4, 'Medium', 0, 1, 0, '2025-10-01 13:04:09'),
(11, 1, 'Medium', 0, 1, 0, '2025-10-01 13:04:09'),
(15, 3, 'Intermediate', 0, 2, 0, '2025-10-01 13:26:57'),
(16, 2, 'Intermediate', 0, 0, 0, '2025-10-01 13:04:10'),
(17, 4, 'Intermediate', 0, 0, 0, '2025-10-01 13:04:10'),
(18, 1, 'Intermediate', 0, 2, 0, '2025-10-01 13:05:38'),
(22, 3, 'Hard', 0, 2, 0, '2025-10-01 13:26:57'),
(23, 2, 'Hard', 0, 0, 0, '2025-10-01 13:04:10'),
(24, 4, 'Hard', 0, 0, 0, '2025-10-01 13:04:10'),
(25, 1, 'Hard', 0, 2, 0, '2025-10-01 13:05:38'),
(33, 5, 'Easy', 0, 4, 0, '2025-10-01 15:16:08'),
(34, 5, 'Medium', 0, 1, 0, '2025-10-01 15:16:09'),
(35, 5, 'Intermediate', 0, 2, 0, '2025-10-01 15:16:09'),
(36, 5, 'Hard', 0, 2, 0, '2025-10-01 15:16:09'),
(37, 6, 'Easy', 0, 4, 0, '2025-10-02 12:34:57'),
(38, 6, 'Medium', 0, 1, 0, '2025-10-02 12:34:57'),
(39, 6, 'Intermediate', 0, 2, 0, '2025-10-02 12:34:57'),
(40, 6, 'Hard', 0, 2, 0, '2025-10-02 12:34:57');

-- --------------------------------------------------------

--
-- Table structure for table `user_learning_journey`
--

CREATE TABLE `user_learning_journey` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `pre_assessment_score` decimal(5,2) DEFAULT NULL,
  `post_assessment_score` decimal(5,2) DEFAULT NULL,
  `improvement_percentage` decimal(5,2) DEFAULT NULL,
  `journey_status` enum('pre_assessment_only','in_progress','completed','post_assessment_taken') DEFAULT 'pre_assessment_only',
  `pre_assessment_date` timestamp NULL DEFAULT NULL,
  `post_assessment_date` timestamp NULL DEFAULT NULL,
  `activities_completed` int(11) DEFAULT 0,
  `total_activities` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_learning_journey`
--

INSERT INTO `user_learning_journey` (`id`, `student_id`, `subject_id`, `pre_assessment_score`, `post_assessment_score`, `improvement_percentage`, `journey_status`, `pre_assessment_date`, `post_assessment_date`, `activities_completed`, `total_activities`, `created_at`, `updated_at`) VALUES
(1, 2, 3, 5.20, NULL, NULL, 'pre_assessment_only', '2025-09-29 11:58:22', NULL, 0, 0, '2025-09-29 11:58:21', '2025-09-29 11:58:22'),
(6, 5, 3, 0.60, NULL, NULL, 'pre_assessment_only', '2025-10-01 16:05:49', NULL, 0, 0, '2025-10-01 16:05:49', '2025-10-01 16:05:49'),
(11, 6, 3, 0.00, NULL, NULL, 'pre_assessment_only', '2025-10-02 05:29:28', NULL, 0, 0, '2025-10-02 05:29:27', '2025-10-02 05:29:28');

-- --------------------------------------------------------

--
-- Table structure for table `user_post_test_attempts`
--

CREATE TABLE `user_post_test_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `attempt_number` int(11) DEFAULT 1,
  `total_questions` int(11) DEFAULT 20,
  `correct_answers` int(11) DEFAULT 0,
  `score` decimal(5,2) DEFAULT 0.00,
  `time_spent_minutes` int(11) DEFAULT 30,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `question_ids` text DEFAULT NULL COMMENT 'JSON array of question IDs in the order they appear for this attempt',
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_post_test_attempts`
--

INSERT INTO `user_post_test_attempts` (`id`, `user_id`, `topic_id`, `attempt_number`, `total_questions`, `correct_answers`, `score`, `time_spent_minutes`, `started_at`, `question_ids`, `completed_at`) VALUES
(1, 1, 13, 1, 20, 0, 32.22, 30, '2025-09-29 12:58:48', NULL, '2025-09-29 13:03:52'),
(2, 1, 13, 2, 20, 0, 32.22, 30, '2025-09-29 13:04:07', NULL, '2025-09-29 13:04:41'),
(3, 1, 13, 3, 20, 0, 32.22, 30, '2025-09-29 13:04:55', NULL, '2025-09-29 13:05:22'),
(4, 1, 13, 4, 20, 0, 32.22, 30, '2025-09-29 13:12:26', NULL, '2025-09-29 13:13:00'),
(5, 1, 13, 5, 20, 15, 82.22, 30, '2025-09-29 13:17:58', NULL, '2025-09-29 13:18:40'),
(8, 1, 14, 1, 20, 4, 46.66, 30, '2025-09-29 17:08:30', NULL, '2025-09-29 17:09:17'),
(9, 2, 16, 1, 20, 0, 33.33, 30, '2025-10-01 04:10:19', NULL, '2025-10-01 04:17:45'),
(10, 2, 16, 2, 20, 0, 33.33, 30, '2025-10-01 04:18:54', '[215,218,231,221,224,223,222,219,217,220,225,227,214,226,230,228,232,233,229,216]', '2025-10-01 04:19:13'),
(11, 2, 16, 3, 20, 0, 33.33, 30, '2025-10-01 04:19:13', '[222,219,215,228,229,217,225,227,232,231,216,230,221,233,214,224,223,220,218,226]', '2025-10-01 04:19:21'),
(12, 2, 16, 4, 20, 0, 33.33, 30, '2025-10-01 04:19:21', '[223,215,221,226,233,222,228,227,224,219,232,217,220,229,218,230,231,214,225,216]', '2025-10-01 04:19:23'),
(13, 2, 16, 5, 20, 0, 33.33, 30, '2025-10-01 04:19:23', '[226,229,227,214,220,228,216,232,215,225,217,219,223,230,221,222,218,224,233,231]', '2025-10-01 04:19:35'),
(14, 2, 16, 6, 20, 0, 33.33, 30, '2025-10-01 04:19:35', '[227,224,230,214,232,217,229,220,219,225,218,215,231,233,226,221,216,228,222,223]', '2025-10-01 04:19:59'),
(15, 2, 16, 7, 20, 0, 0.00, 30, '2025-10-01 04:19:59', '[223,224,230,225,215,220,233,221,227,232,228,229,216,214,231,218,226,219,217,222]', NULL),
(22, 6, 16, 1, 20, 0, 0.00, 30, '2025-10-02 14:23:28', '[479,487,489,488,476,485,490,473,462,465,463,484,470,471,469,477,475,472,478,474]', '2025-10-02 14:29:24'),
(23, 6, 16, 2, 20, 0, 0.00, 30, '2025-10-02 14:29:24', '[520,509,516,513,492,501,495,503,511,507,491,500,514,494,496,493,508,519,497,506]', '2025-10-02 14:29:25'),
(24, 6, 16, 3, 20, 17, 90.00, 30, '2025-10-02 14:29:25', '[511,516,503,507,495,508,506,494,510,515,509,493,496,498,505,491,518,499,502,512]', '2025-10-02 14:30:02');

-- --------------------------------------------------------

--
-- Table structure for table `user_post_test_eligibility`
--

CREATE TABLE `user_post_test_eligibility` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `completed_all_levels` tinyint(1) DEFAULT 1,
  `post_test_available` tinyint(1) DEFAULT 1,
  `post_test_taken` tinyint(1) DEFAULT 0,
  `best_post_test_score` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_post_test_eligibility`
--

INSERT INTO `user_post_test_eligibility` (`id`, `user_id`, `topic_id`, `completed_all_levels`, `post_test_available`, `post_test_taken`, `best_post_test_score`, `created_at`, `updated_at`) VALUES
(1, 1, 13, 1, 1, 1, 82.22, '2025-09-29 13:03:52', '2025-10-02 10:51:52'),
(7, 1, 14, 1, 1, 1, 46.66, '2025-09-29 17:09:17', '2025-10-02 10:51:52'),
(8, 6, 14, 1, 1, 1, 90.00, '2025-10-02 09:34:56', '2025-10-02 09:34:56'),
(9, 6, 13, 1, 1, 1, 83.33, '2025-10-02 11:26:56', '2025-10-02 11:26:56'),
(10, 6, 15, 1, 1, 1, 96.67, '2025-10-02 11:47:13', '2025-10-02 11:47:13'),
(11, 6, 16, 1, 1, 1, 96.00, '2025-10-02 11:55:19', '2025-10-02 14:30:02'),
(12, 6, 17, 1, 1, 1, 66.67, '2025-10-02 12:05:48', '2025-10-02 12:05:48');

-- --------------------------------------------------------

--
-- Table structure for table `user_post_test_responses`
--

CREATE TABLE `user_post_test_responses` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_answer` varchar(10) NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `answered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_post_test_responses`
--

INSERT INTO `user_post_test_responses` (`id`, `attempt_id`, `question_id`, `selected_answer`, `is_correct`, `answered_at`) VALUES
(203, 22, 479, 'B', 1, '2025-10-02 14:23:34'),
(204, 22, 487, 'B', 1, '2025-10-02 14:23:35'),
(205, 22, 489, 'B', 1, '2025-10-02 14:23:37'),
(206, 22, 488, 'B', 0, '2025-10-02 14:23:38'),
(207, 22, 476, 'B', 1, '2025-10-02 14:23:39'),
(208, 22, 476, 'B', 1, '2025-10-02 14:23:40'),
(209, 22, 490, 'B', 1, '2025-10-02 14:23:42'),
(210, 22, 473, 'B', 1, '2025-10-02 14:23:43'),
(211, 22, 462, 'B', 1, '2025-10-02 14:23:45'),
(212, 24, 511, 'B', 0, '2025-10-02 14:29:26'),
(213, 24, 516, 'B', 1, '2025-10-02 14:29:28'),
(214, 24, 503, 'B', 1, '2025-10-02 14:29:30'),
(215, 24, 507, 'B', 0, '2025-10-02 14:29:32'),
(216, 24, 495, 'B', 1, '2025-10-02 14:29:33'),
(217, 24, 508, 'B', 1, '2025-10-02 14:29:36'),
(218, 24, 506, 'B', 0, '2025-10-02 14:29:37'),
(219, 24, 494, 'B', 1, '2025-10-02 14:29:39'),
(220, 24, 510, 'B', 1, '2025-10-02 14:29:46'),
(221, 24, 515, 'B', 1, '2025-10-02 14:29:48'),
(222, 24, 515, 'B', 1, '2025-10-02 14:29:48'),
(223, 24, 509, 'B', 1, '2025-10-02 14:29:49'),
(224, 24, 493, 'B', 0, '2025-10-02 14:29:50'),
(225, 24, 496, 'B', 1, '2025-10-02 14:29:50'),
(226, 24, 498, 'B', 1, '2025-10-02 14:29:51'),
(227, 24, 505, 'B', 1, '2025-10-02 14:29:52'),
(228, 24, 491, 'B', 1, '2025-10-02 14:29:52'),
(229, 24, 499, 'B', 1, '2025-10-02 14:29:54'),
(230, 24, 502, 'B', 1, '2025-10-02 14:29:55'),
(231, 24, 512, 'B', 1, '2025-10-02 14:29:57'),
(232, 24, 518, 'B', 1, '2025-10-02 14:29:59');

-- --------------------------------------------------------

--
-- Table structure for table `user_preassessment_status`
--

CREATE TABLE `user_preassessment_status` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completion_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `full_name` varchar(200) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `learning_style` varchar(50) DEFAULT 'Visual & Hands-on',
  `study_schedule` varchar(50) DEFAULT 'Evenings',
  `difficulty_preference` varchar(20) DEFAULT 'Intermediate',
  `notifications_enabled` tinyint(1) DEFAULT 1,
  `profile_picture` varchar(255) DEFAULT 'student.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `topic_id`, `title`, `url`, `description`, `file_path`) VALUES
(1, 13, 'Intro to OOP Concepts', 'https://www.youtube.com/embed/abcd123', NULL, NULL),
(2, 14, 'Classes and Objects in OOP', 'https://www.youtube.com/embed/xyz456', NULL, NULL),
(3, 13, 'Real-world objects to program objects', '', 'Watch to Learn', 'uploads/videos/1758099247_Real-world objects to program objects.mp4'),
(4, 16, 'sample', 'https://www.youtube.com/watch?v=wFeQBcTJJf4&list=RDsBum0Prrnmo&index=5', 'sample', NULL),
(5, 16, 's', 'https://www.youtube.com/watch?v=wFeQBcTJJf4&list=RDsBum0Prrnmo', 's', NULL),
(6, 13, 'link from youtube', 'https://www.youtube.com/watch?v=wFeQBcTJJf4&list=RDsBum0Prrnmo', 'sample', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `coding_practice_completed`
--
ALTER TABLE `coding_practice_completed`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_completion` (`user_id`,`problem_id`,`language`),
  ADD KEY `idx_user_language` (`user_id`,`language`);

--
-- Indexes for table `coding_practice_leaderboard`
--
ALTER TABLE `coding_practice_leaderboard`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`),
  ADD KEY `idx_total_score` (`total_score`),
  ADD KEY `idx_problems_solved` (`problems_solved`);

--
-- Indexes for table `coding_practice_scores`
--
ALTER TABLE `coding_practice_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_scores` (`user_id`,`score`),
  ADD KEY `idx_problem_scores` (`problem_id`,`score`),
  ADD KEY `idx_coding_scores_user_problem` (`user_id`,`problem_id`),
  ADD KEY `idx_coding_scores_created` (`created_at`);

--
-- Indexes for table `coding_problems`
--
ALTER TABLE `coding_problems`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coding_problems_difficulty` (`difficulty`);

--
-- Indexes for table `identified_weak_areas`
--
ALTER TABLE `identified_weak_areas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `idx_user_weaknesses` (`user_id`,`subject_id`),
  ADD KEY `idx_weakness_category` (`weak_area_category`),
  ADD KEY `idx_weakness_score` (`weakness_score`);

--
-- Indexes for table `learning_analytics`
--
ALTER TABLE `learning_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`session_date`),
  ADD KEY `idx_user_analytics` (`user_id`,`session_date`),
  ADD KEY `idx_engagement` (`engagement_score`);

--
-- Indexes for table `learning_materials`
--
ALTER TABLE `learning_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Indexes for table `level_promotion_attempts`
--
ALTER TABLE `level_promotion_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_student_passed` (`student_id`,`passed`);

--
-- Indexes for table `level_promotion_tests`
--
ALTER TABLE `level_promotion_tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_level` (`student_id`,`from_level`);

--
-- Indexes for table `login_credentials`
--
ALTER TABLE `login_credentials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Indexes for table `regression_analysis_results`
--
ALTER TABLE `regression_analysis_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_analysis` (`user_id`,`analysis_date`),
  ADD KEY `idx_model_accuracy` (`r_squared_value`);

--
-- Indexes for table `save_progress`
--
ALTER TABLE `save_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_topic` (`user_id`,`topic_id`);

--
-- Indexes for table `skill_progression_tracking`
--
ALTER TABLE `skill_progression_tracking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_topic` (`student_id`,`topic_id`),
  ADD KEY `idx_skill_level` (`skill_level`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_activity_scores`
--
ALTER TABLE `student_activity_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_activity_student` (`student_id`),
  ADD KEY `fk_activity_topic` (`topic_id`),
  ADD KEY `idx_activity_date` (`date_created`);

--
-- Indexes for table `student_progress_history`
--
ALTER TABLE `student_progress_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_tests`
--
ALTER TABLE `student_tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_test_student` (`student_id`),
  ADD KEY `fk_test_topic` (`topic_id`),
  ADD KEY `idx_test_type` (`test_type`);

--
-- Indexes for table `student_video_progress`
--
ALTER TABLE `student_video_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_watch` (`student_id`,`material_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `user_coding_progress`
--
ALTER TABLE `user_coding_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_difficulty` (`user_id`,`difficulty`);

--
-- Indexes for table `user_learning_journey`
--
ALTER TABLE `user_learning_journey`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_subject` (`student_id`,`subject_id`),
  ADD KEY `idx_journey_status` (`journey_status`);

--
-- Indexes for table `user_post_test_attempts`
--
ALTER TABLE `user_post_test_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Indexes for table `user_post_test_eligibility`
--
ALTER TABLE `user_post_test_eligibility`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_topic` (`user_id`,`topic_id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Indexes for table `user_post_test_responses`
--
ALTER TABLE `user_post_test_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `user_preassessment_status`
--
ALTER TABLE `user_preassessment_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_subject` (`user_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `coding_practice_completed`
--
ALTER TABLE `coding_practice_completed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `coding_practice_leaderboard`
--
ALTER TABLE `coding_practice_leaderboard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coding_practice_scores`
--
ALTER TABLE `coding_practice_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coding_problems`
--
ALTER TABLE `coding_problems`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `identified_weak_areas`
--
ALTER TABLE `identified_weak_areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `learning_analytics`
--
ALTER TABLE `learning_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `learning_materials`
--
ALTER TABLE `learning_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `level_promotion_attempts`
--
ALTER TABLE `level_promotion_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `level_promotion_tests`
--
ALTER TABLE `level_promotion_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `login_credentials`
--
ALTER TABLE `login_credentials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=581;

--
-- AUTO_INCREMENT for table `regression_analysis_results`
--
ALTER TABLE `regression_analysis_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `save_progress`
--
ALTER TABLE `save_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `skill_progression_tracking`
--
ALTER TABLE `skill_progression_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_activity_scores`
--
ALTER TABLE `student_activity_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `student_progress_history`
--
ALTER TABLE `student_progress_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_tests`
--
ALTER TABLE `student_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `student_video_progress`
--
ALTER TABLE `student_video_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `user_coding_progress`
--
ALTER TABLE `user_coding_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `user_learning_journey`
--
ALTER TABLE `user_learning_journey`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_post_test_attempts`
--
ALTER TABLE `user_post_test_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `user_post_test_eligibility`
--
ALTER TABLE `user_post_test_eligibility`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_post_test_responses`
--
ALTER TABLE `user_post_test_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=233;

--
-- AUTO_INCREMENT for table `user_preassessment_status`
--
ALTER TABLE `user_preassessment_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `coding_practice_leaderboard`
--
ALTER TABLE `coding_practice_leaderboard`
  ADD CONSTRAINT `coding_practice_leaderboard_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coding_practice_scores`
--
ALTER TABLE `coding_practice_scores`
  ADD CONSTRAINT `coding_practice_scores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coding_practice_scores_ibfk_2` FOREIGN KEY (`problem_id`) REFERENCES `coding_problems` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `identified_weak_areas`
--
ALTER TABLE `identified_weak_areas`
  ADD CONSTRAINT `identified_weak_areas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `identified_weak_areas_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `identified_weak_areas_ibfk_3` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `learning_analytics`
--
ALTER TABLE `learning_analytics`
  ADD CONSTRAINT `learning_analytics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `learning_materials`
--
ALTER TABLE `learning_materials`
  ADD CONSTRAINT `learning_materials_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `level_promotion_attempts`
--
ALTER TABLE `level_promotion_attempts`
  ADD CONSTRAINT `level_promotion_attempts_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `level_promotion_tests`
--
ALTER TABLE `level_promotion_tests`
  ADD CONSTRAINT `level_promotion_tests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `regression_analysis_results`
--
ALTER TABLE `regression_analysis_results`
  ADD CONSTRAINT `regression_analysis_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_activity_scores`
--
ALTER TABLE `student_activity_scores`
  ADD CONSTRAINT `fk_activity_student` FOREIGN KEY (`student_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_activity_topic` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_progress_history`
--
ALTER TABLE `student_progress_history`
  ADD CONSTRAINT `student_progress_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_tests`
--
ALTER TABLE `student_tests`
  ADD CONSTRAINT `fk_test_student` FOREIGN KEY (`student_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_test_topic` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_video_progress`
--
ALTER TABLE `student_video_progress`
  ADD CONSTRAINT `student_video_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_video_progress_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `learning_materials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `topics`
--
ALTER TABLE `topics`
  ADD CONSTRAINT `topics_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_coding_progress`
--
ALTER TABLE `user_coding_progress`
  ADD CONSTRAINT `user_coding_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_post_test_attempts`
--
ALTER TABLE `user_post_test_attempts`
  ADD CONSTRAINT `user_post_test_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_post_test_attempts_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_post_test_eligibility`
--
ALTER TABLE `user_post_test_eligibility`
  ADD CONSTRAINT `user_post_test_eligibility_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_post_test_eligibility_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_post_test_responses`
--
ALTER TABLE `user_post_test_responses`
  ADD CONSTRAINT `user_post_test_responses_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `user_post_test_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_post_test_responses_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_preassessment_status`
--
ALTER TABLE `user_preassessment_status`
  ADD CONSTRAINT `user_preassessment_status_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_preassessment_status_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
