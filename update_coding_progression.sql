-- Update coding_problems table to include Intermediate difficulty level
ALTER TABLE `coding_problems` MODIFY `difficulty` enum('Easy','Medium','Intermediate','Hard') DEFAULT 'Easy';

-- Add more challenging problems for different difficulty levels

-- Intermediate Level Problems
INSERT INTO `coding_problems` (`title`, `description`, `difficulty`, `examples`, `test_cases`, `skeleton`) VALUES
('Binary Tree Inorder Traversal', 
'Given the root of a binary tree, return the inorder traversal of its nodes values. Inorder traversal visits nodes in the order: left subtree, root, right subtree.',
'Intermediate',
'[{"input": "root = [1,null,2,3]", "output": "[1,3,2]", "explanation": "Inorder traversal: left subtree (empty), root (1), right subtree (left: 3, root: 2, right: empty)"}]',
'[{"input": "[1,null,2,3]", "expected": "[1,3,2]"}, {"input": "[]", "expected": "[]"}, {"input": "[1]", "expected": "[1]"}, {"input": "[1,2,3,4,5]", "expected": "[4,2,5,1,3]"}]',
'{"javascript": "function inorderTraversal(root) {\n    // TreeNode structure: {val: number, left: TreeNode|null, right: TreeNode|null}\n    // Your code here\n    \n}", "python": "def inorder_traversal(root):\n    # TreeNode structure: TreeNode(val, left=None, right=None)\n    # Your code here\n    pass", "java": "public List<Integer> inorderTraversal(TreeNode root) {\n    // TreeNode structure: TreeNode(int val, TreeNode left, TreeNode right)\n    // Your code here\n    \n}", "cpp": "vector<int> inorderTraversal(TreeNode* root) {\n    // TreeNode structure: struct TreeNode { int val; TreeNode *left; TreeNode *right; }\n    // Your code here\n    \n}"}'),

('Longest Substring Without Repeating Characters',
'Given a string s, find the length of the longest substring without repeating characters. Use sliding window technique for optimal solution.',
'Intermediate', 
'[{"input": "s = \\"abcabcbb\\"", "output": "3", "explanation": "The answer is \\"abc\\", with the length of 3"}, {"input": "s = \\"bbbbb\\"", "output": "1", "explanation": "The answer is \\"b\\", with the length of 1"}]',
'[{"input": "\\"abcabcbb\\"", "expected": 3}, {"input": "\\"bbbbb\\"", "expected": 1}, {"input": "\\"pwwkew\\"", "expected": 3}, {"input": "\\"\\"", "expected": 0}, {"input": "\\" \\"", "expected": 1}]',
'{"javascript": "function lengthOfLongestSubstring(s) {\n    // Your code here - use sliding window\n    \n}", "python": "def length_of_longest_substring(s):\n    # Your code here - use sliding window\n    pass", "java": "public int lengthOfLongestSubstring(String s) {\n    // Your code here - use sliding window\n    \n}", "cpp": "int lengthOfLongestSubstring(string s) {\n    // Your code here - use sliding window\n    \n}"}');

-- Hard Level Problems  
INSERT INTO `coding_problems` (`title`, `description`, `difficulty`, `examples`, `test_cases`, `skeleton`) VALUES
('Merge k Sorted Lists',
'You are given an array of k linked-lists lists, each linked-list is sorted in ascending order. Merge all the linked-lists into one sorted linked-list and return it.',
'Hard',
'[{"input": "lists = [[1,4,5],[1,3,4],[2,6]]", "output": "[1,1,2,3,4,4,5,6]", "explanation": "The linked-lists are merged into one sorted list"}, {"input": "lists = []", "output": "[]"}, {"input": "lists = [[]]", "output": "[]"}]',
'[{"input": "[[1,4,5],[1,3,4],[2,6]]", "expected": "[1,1,2,3,4,4,5,6]"}, {"input": "[]", "expected": "[]"}, {"input": "[[]]", "expected": "[]"}, {"input": "[[1],[0]]", "expected": "[0,1]"}]',
'{"javascript": "function mergeKLists(lists) {\n    // ListNode structure: {val: number, next: ListNode|null}\n    // Your code here - consider using divide and conquer or priority queue\n    \n}", "python": "def merge_k_lists(lists):\n    # ListNode structure: ListNode(val=0, next=None)\n    # Your code here - consider using divide and conquer or priority queue\n    pass", "java": "public ListNode mergeKLists(ListNode[] lists) {\n    // ListNode structure: ListNode(int val, ListNode next)\n    // Your code here - consider using divide and conquer or priority queue\n    \n}", "cpp": "ListNode* mergeKLists(vector<ListNode*>& lists) {\n    // ListNode structure: struct ListNode { int val; ListNode *next; }\n    // Your code here - consider using divide and conquer or priority queue\n    \n}"}'),

('Regular Expression Matching',
'Given an input string s and a pattern p, implement regular expression matching with support for . and *. . matches any single character and * matches zero or more of the preceding element.',
'Hard',
'[{"input": "s = \\"aa\\", p = \\"a\\"", "output": "false", "explanation": "a does not match the entire string aa"}, {"input": "s = \\"aa\\", p = \\"a*\\"", "output": "true", "explanation": "a* means zero or more a characters"}, {"input": "s = \\"ab\\", p = \\".*\\"", "output": "true", "explanation": ".* means zero or more of any character"}]',
'[{"input": "s=\\"aa\\", p=\\"a\\"", "expected": false}, {"input": "s=\\"aa\\", p=\\"a*\\"", "expected": true}, {"input": "s=\\"ab\\", p=\\".*\\"", "expected": true}, {"input": "s=\\"aab\\", p=\\"c*a*b\\"", "expected": true}, {"input": "s=\\"mississippi\\", p=\\"mis*is*p*.\\"", "expected": false}]',
'{"javascript": "function isMatch(s, p) {\n    // Your code here - use dynamic programming\n    // Consider edge cases with . and * patterns\n    \n}", "python": "def is_match(s, p):\n    # Your code here - use dynamic programming\n    # Consider edge cases with . and * patterns\n    pass", "java": "public boolean isMatch(String s, String p) {\n    // Your code here - use dynamic programming\n    // Consider edge cases with . and * patterns\n    \n}", "cpp": "bool isMatch(string s, string p) {\n    // Your code here - use dynamic programming\n    // Consider edge cases with . and * patterns\n    \n}"}');

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