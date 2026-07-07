-- Update coding_problems table to include Intermediate difficulty level
ALTER TABLE `coding_problems` MODIFY `difficulty` enum('Easy','Medium','Intermediate','Hard') DEFAULT 'Easy';

-- Add Intermediate Level Problems
INSERT INTO `coding_problems` (`title`, `description`, `difficulty`, `examples`, `test_cases`, `skeleton`) VALUES
('Binary Tree Inorder Traversal', 
'Given the root of a binary tree, return the inorder traversal of its nodes values.',
'Intermediate',
'[{"input": "root = [1,null,2,3]", "output": "[1,3,2]"}]',
'[{"input": "[1,null,2,3]", "expected": "[1,3,2]"}, {"input": "[]", "expected": "[]"}, {"input": "[1]", "expected": "[1]"}]',
'{"javascript": "function inorderTraversal(root) {\\n    // Your code here\\n    return [];\\n}", "python": "def inorder_traversal(root):\\n    # Your code here\\n    return []", "java": "public List<Integer> inorderTraversal(TreeNode root) {\\n    // Your code here\\n    return new ArrayList<>();\\n}", "cpp": "vector<int> inorderTraversal(TreeNode* root) {\\n    // Your code here\\n    return {};\\n}"}');

INSERT INTO `coding_problems` (`title`, `description`, `difficulty`, `examples`, `test_cases`, `skeleton`) VALUES
('Longest Substring Without Repeating Characters',
'Given a string s, find the length of the longest substring without repeating characters.',
'Intermediate', 
'[{"input": "s = abcabcbb", "output": "3"}]',
'[{"input": "abcabcbb", "expected": 3}, {"input": "bbbbb", "expected": 1}, {"input": "pwwkew", "expected": 3}]',
'{"javascript": "function lengthOfLongestSubstring(s) {\\n    // Your code here\\n    return 0;\\n}", "python": "def length_of_longest_substring(s):\\n    # Your code here\\n    return 0", "java": "public int lengthOfLongestSubstring(String s) {\\n    // Your code here\\n    return 0;\\n}", "cpp": "int lengthOfLongestSubstring(string s) {\\n    // Your code here\\n    return 0;\\n}"}');

-- Add Hard Level Problems  
INSERT INTO `coding_problems` (`title`, `description`, `difficulty`, `examples`, `test_cases`, `skeleton`) VALUES
('Merge k Sorted Lists',
'You are given an array of k linked-lists, each sorted in ascending order. Merge all into one sorted list.',
'Hard',
'[{"input": "lists = [[1,4,5],[1,3,4],[2,6]]", "output": "[1,1,2,3,4,4,5,6]"}]',
'[{"input": "[[1,4,5],[1,3,4],[2,6]]", "expected": "[1,1,2,3,4,4,5,6]"}, {"input": "[]", "expected": "[]"}]',
'{"javascript": "function mergeKLists(lists) {\\n    // Your code here\\n    return null;\\n}", "python": "def merge_k_lists(lists):\\n    # Your code here\\n    return None", "java": "public ListNode mergeKLists(ListNode[] lists) {\\n    // Your code here\\n    return null;\\n}", "cpp": "ListNode* mergeKLists(vector<ListNode*>& lists) {\\n    // Your code here\\n    return nullptr;\\n}"}');

INSERT INTO `coding_problems` (`title`, `description`, `difficulty`, `examples`, `test_cases`, `skeleton`) VALUES
('Regular Expression Matching',
'Implement regular expression matching with support for . and *.',
'Hard',
'[{"input": "s = aa, p = a", "output": "false"}, {"input": "s = aa, p = a*", "output": "true"}]',
'[{"input": "s=aa, p=a", "expected": false}, {"input": "s=aa, p=a*", "expected": true}]',
'{"javascript": "function isMatch(s, p) {\\n    // Your code here\\n    return false;\\n}", "python": "def is_match(s, p):\\n    # Your code here\\n    return False", "java": "public boolean isMatch(String s, String p) {\\n    // Your code here\\n    return false;\\n}", "cpp": "bool isMatch(string s, string p) {\\n    // Your code here\\n    return false;\\n}"}');

-- Create user progression tracking table
CREATE TABLE IF NOT EXISTS `user_coding_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `difficulty` enum('Easy','Medium','Intermediate','Hard') NOT NULL,
  `problems_solved` int(11) DEFAULT 0,
  `total_problems` int(11) DEFAULT 0,
  `best_score` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_difficulty` (`user_id`, `difficulty`),
  FOREIGN KEY (`user_id`) REFERENCES `login_credentials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert initial progress data for existing users
INSERT IGNORE INTO `user_coding_progress` (`user_id`, `difficulty`, `problems_solved`, `total_problems`, `best_score`)
SELECT lc.id, 'Easy', 0, 
    (SELECT COUNT(*) FROM coding_problems WHERE difficulty = 'Easy'), 0
FROM login_credentials lc;

INSERT IGNORE INTO `user_coding_progress` (`user_id`, `difficulty`, `problems_solved`, `total_problems`, `best_score`)
SELECT lc.id, 'Medium', 0, 
    (SELECT COUNT(*) FROM coding_problems WHERE difficulty = 'Medium'), 0
FROM login_credentials lc;

INSERT IGNORE INTO `user_coding_progress` (`user_id`, `difficulty`, `problems_solved`, `total_problems`, `best_score`)
SELECT lc.id, 'Intermediate', 0, 
    (SELECT COUNT(*) FROM coding_problems WHERE difficulty = 'Intermediate'), 0
FROM login_credentials lc;

INSERT IGNORE INTO `user_coding_progress` (`user_id`, `difficulty`, `problems_solved`, `total_problems`, `best_score`)
SELECT lc.id, 'Hard', 0, 
    (SELECT COUNT(*) FROM coding_problems WHERE difficulty = 'Hard'), 0
FROM login_credentials lc;