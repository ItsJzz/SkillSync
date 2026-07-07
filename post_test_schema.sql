-- Post-Test System Database Schema
-- Create tables for topic-based post-assessment with 20 questions per topic

-- Table for storing post-test questions (20 per topic)
CREATE TABLE topic_post_test_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    topic_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_answer ENUM('A', 'B', 'C', 'D') NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    question_order INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
    INDEX idx_topic_questions (topic_id, question_order)
);

-- Table for tracking user post-test attempts
CREATE TABLE user_post_test_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    topic_id INT NOT NULL,
    attempt_number INT DEFAULT 1,
    total_questions INT DEFAULT 20,
    correct_answers INT DEFAULT 0,
    score_percentage DECIMAL(5,2) DEFAULT 0.00,
    time_taken_minutes INT DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    status ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
    INDEX idx_user_topic_attempts (user_id, topic_id),
    INDEX idx_completion_status (status, completed_at)
);

-- Table for storing individual question responses
CREATE TABLE user_post_test_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    user_answer ENUM('A', 'B', 'C', 'D') NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    time_spent_seconds INT DEFAULT 0,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES user_post_test_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES topic_post_test_questions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attempt_question (attempt_id, question_id),
    INDEX idx_attempt_responses (attempt_id)
);

-- Table for tracking post-test eligibility (when user completes all 5 activity levels)
CREATE TABLE user_post_test_eligibility (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    topic_id INT NOT NULL,
    completed_all_levels BOOLEAN DEFAULT FALSE,
    completion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    post_test_available BOOLEAN DEFAULT TRUE,
    post_test_taken BOOLEAN DEFAULT FALSE,
    best_post_test_score DECIMAL(5,2) DEFAULT 0.00,
    skill_improvement_percentage DECIMAL(5,2) DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_topic_eligibility (user_id, topic_id),
    INDEX idx_user_eligibility (user_id, post_test_available)
);

-- Insert sample post-test questions for "Introduction to OOP" (topic_id = 13)
INSERT INTO topic_post_test_questions (topic_id, question_text, option_a, option_b, option_c, option_d, correct_answer, difficulty, question_order) VALUES

-- Basic OOP Concepts (Questions 1-5)
(13, 'What does OOP stand for?', 'Object Oriented Programming', 'Object Organized Programming', 'Operational Object Programming', 'Optional Object Processing', 'A', 'easy', 1),
(13, 'Which of the following is a fundamental principle of OOP?', 'Inheritance', 'Compilation', 'Debugging', 'Memory Management', 'A', 'easy', 2),
(13, 'In Java, what keyword is used to create a new object?', 'create', 'new', 'object', 'instance', 'B', 'easy', 3),
(13, 'What is a class in OOP?', 'A running program', 'A blueprint for creating objects', 'A database table', 'A programming language', 'B', 'easy', 4),
(13, 'What is an object in OOP?', 'A variable', 'An instance of a class', 'A method', 'A data type', 'B', 'easy', 5),

-- Class and Object Creation (Questions 6-10)
(13, 'How do you declare a class named "Student" in Java?', 'class Student {}', 'Class Student {}', 'new Student {}', 'object Student {}', 'A', 'medium', 6),
(13, 'What are attributes in a class?', 'Methods that perform actions', 'Variables that store data', 'Constructors', 'Access modifiers', 'B', 'medium', 7),
(13, 'What are methods in a class?', 'Variables that store data', 'Functions that define behavior', 'Class names', 'Object instances', 'B', 'medium', 8),
(13, 'Which statement correctly creates an object of class "Car"?', 'Car myCar = Car()', 'Car myCar = new Car()', 'new Car myCar = Car()', 'Car() myCar = new', 'B', 'medium', 9),
(13, 'What is the purpose of a constructor?', 'To destroy objects', 'To initialize objects when created', 'To declare variables', 'To define methods', 'B', 'medium', 10),

-- Advanced OOP Concepts (Questions 11-15)
(13, 'What is encapsulation in OOP?', 'Creating multiple objects', 'Hiding internal details and exposing only necessary parts', 'Inheriting from parent classes', 'Overriding methods', 'B', 'medium', 11),
(13, 'What access modifier makes a field accessible only within the same class?', 'public', 'protected', 'private', 'default', 'C', 'medium', 12),
(13, 'What is inheritance in OOP?', 'Creating new objects', 'A class acquiring properties from another class', 'Hiding data', 'Defining methods', 'B', 'hard', 13),
(13, 'What keyword is used to inherit from a class in Java?', 'inherits', 'extends', 'implements', 'derives', 'B', 'hard', 14),
(13, 'What is polymorphism in OOP?', 'Having multiple constructors', 'Same interface, different implementations', 'Creating private methods', 'Using static variables', 'B', 'hard', 15),

-- Practical Application (Questions 16-20)
(13, 'Which is the correct way to access a public field "name" of object "student"?', 'student->name', 'student.name', 'student::name', 'student[name]', 'B', 'medium', 16),
(13, 'What happens when you call a method on an object?', 'The object is destroyed', 'The method executes using the object''s data', 'A new object is created', 'The class is modified', 'B', 'medium', 17),
(13, 'In the code "Student s = new Student();", what is "s"?', 'A class', 'A reference to a Student object', 'A method', 'A constructor', 'B', 'medium', 18),
(13, 'What is the difference between a class and an object?', 'They are the same thing', 'Class is a blueprint, object is an instance', 'Class stores data, object stores methods', 'Object is bigger than class', 'B', 'hard', 19),
(13, 'Why is OOP considered beneficial in software development?', 'It makes programs run faster', 'It provides code reusability and better organization', 'It uses less memory', 'It requires fewer lines of code', 'B', 'hard', 20);