USE kody_db;

-- =====================================================
-- Kody Phase 0.5 Seed Data (Rerun-Safe)
-- Run this after database.sql
-- Safe to run multiple times (tries to avoid duplicates)
-- =====================================================

START TRANSACTION;

-- 1) Roles
INSERT IGNORE INTO roles (role_name) VALUES
('admin'),
('instructor'),
('learner'),
('contributor'),
('moderator');

-- 2) Users (2 users: 1 admin, 1 learner)
-- Password for both users: password
-- Bcrypt hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO users (email, password_hash, first_name, last_name, account_status)
VALUES
('admin@kody.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kody', 'Admin', 'active'),
('learner@kody.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sample', 'Learner', 'active')
ON DUPLICATE KEY UPDATE
first_name = VALUES(first_name),
last_name = VALUES(last_name),
account_status = VALUES(account_status);

-- Cache user IDs
SELECT user_id INTO @admin_id FROM users WHERE email = 'admin@kody.local' LIMIT 1;
SELECT user_id INTO @learner_id FROM users WHERE email = 'learner@kody.local' LIMIT 1;

-- 3) User role mapping (avoid duplicates)
INSERT INTO user_roles (user_id, role_id)
SELECT @admin_id, r.role_id
FROM roles r
WHERE r.role_name = 'admin'
AND NOT EXISTS (
    SELECT 1 FROM user_roles ur
    WHERE ur.user_id = @admin_id AND ur.role_id = r.role_id
);

INSERT INTO user_roles (user_id, role_id)
SELECT @learner_id, r.role_id
FROM roles r
WHERE r.role_name = 'learner'
AND NOT EXISTS (
    SELECT 1 FROM user_roles ur
    WHERE ur.user_id = @learner_id AND ur.role_id = r.role_id
);

-- 4) Courses (2 courses)
INSERT INTO courses (instructor_id, title, description, difficulty, is_archived)
SELECT @admin_id, 'Python Fundamentals', 'Learn Python basics, variables, loops, and functions.', 'beginner', 0
WHERE NOT EXISTS (
    SELECT 1 FROM courses WHERE title = 'Python Fundamentals'
);

INSERT INTO courses (instructor_id, title, description, difficulty, is_archived)
SELECT @admin_id, 'Web Development Basics', 'Learn HTML, CSS, JavaScript, and simple backend concepts.', 'beginner', 0
WHERE NOT EXISTS (
    SELECT 1 FROM courses WHERE title = 'Web Development Basics'
);

-- Cache course IDs
SELECT course_id INTO @course_python FROM courses WHERE title = 'Python Fundamentals' LIMIT 1;
SELECT course_id INTO @course_web FROM courses WHERE title = 'Web Development Basics' LIMIT 1;

-- 5) Modules (2 modules per course)
INSERT INTO modules (course_id, title, module_order)
SELECT @course_python, 'Python Module 1: Basics', 1
WHERE NOT EXISTS (
    SELECT 1 FROM modules WHERE course_id = @course_python AND module_order = 1
);

INSERT INTO modules (course_id, title, module_order)
SELECT @course_python, 'Python Module 2: Functions', 2
WHERE NOT EXISTS (
    SELECT 1 FROM modules WHERE course_id = @course_python AND module_order = 2
);

INSERT INTO modules (course_id, title, module_order)
SELECT @course_web, 'Web Module 1: HTML and CSS', 1
WHERE NOT EXISTS (
    SELECT 1 FROM modules WHERE course_id = @course_web AND module_order = 1
);

INSERT INTO modules (course_id, title, module_order)
SELECT @course_web, 'Web Module 2: JavaScript Intro', 2
WHERE NOT EXISTS (
    SELECT 1 FROM modules WHERE course_id = @course_web AND module_order = 2
);

-- Cache module IDs
SELECT module_id INTO @m_py_1 FROM modules WHERE course_id = @course_python AND module_order = 1 LIMIT 1;
SELECT module_id INTO @m_py_2 FROM modules WHERE course_id = @course_python AND module_order = 2 LIMIT 1;
SELECT module_id INTO @m_web_1 FROM modules WHERE course_id = @course_web AND module_order = 1 LIMIT 1;
SELECT module_id INTO @m_web_2 FROM modules WHERE course_id = @course_web AND module_order = 2 LIMIT 1;

-- 6) Lessons (2 lessons per module)
INSERT INTO lessons (module_id, title, content, lesson_order)
SELECT @m_py_1, 'Lesson 1: Variables and Data Types', 'Python variables and primitive data types.', 1
WHERE NOT EXISTS (
    SELECT 1 FROM lessons WHERE module_id = @m_py_1 AND lesson_order = 1
);

INSERT INTO lessons (module_id, title, content, lesson_order)
SELECT @m_py_1, 'Lesson 2: Conditional Statements', 'if, elif, else decision flow.', 2
WHERE NOT EXISTS (
    SELECT 1 FROM lessons WHERE module_id = @m_py_1 AND lesson_order = 2
);

INSERT INTO lessons (module_id, title, content, lesson_order)
SELECT @m_py_2, 'Lesson 1: Defining Functions', 'How to create and call Python functions.', 1
WHERE NOT EXISTS (
    SELECT 1 FROM lessons WHERE module_id = @m_py_2 AND lesson_order = 1
);

INSERT INTO lessons (module_id, title, content, lesson_order)
SELECT @m_py_2, 'Lesson 2: Function Arguments', 'Parameters, return values, and defaults.', 2
WHERE NOT EXISTS (
    SELECT 1 FROM lessons WHERE module_id = @m_py_2 AND lesson_order = 2
);

INSERT INTO lessons (module_id, title, content, lesson_order)
SELECT @m_web_1, 'Lesson 1: HTML Structure', 'Basic tags, headings, paragraphs, and links.', 1
WHERE NOT EXISTS (
    SELECT 1 FROM lessons WHERE module_id = @m_web_1 AND lesson_order = 1
);

INSERT INTO lessons (module_id, title, content, lesson_order)
SELECT @m_web_1, 'Lesson 2: CSS Styling', 'Selectors, colors, spacing, and layout basics.', 2
WHERE NOT EXISTS (
    SELECT 1 FROM lessons WHERE module_id = @m_web_1 AND lesson_order = 2
);

INSERT INTO lessons (module_id, title, content, lesson_order)
SELECT @m_web_2, 'Lesson 1: Variables in JS', 'Declare and use JavaScript variables.', 1
WHERE NOT EXISTS (
    SELECT 1 FROM lessons WHERE module_id = @m_web_2 AND lesson_order = 1
);

INSERT INTO lessons (module_id, title, content, lesson_order)
SELECT @m_web_2, 'Lesson 2: Functions in JS', 'Write simple JavaScript functions.', 2
WHERE NOT EXISTS (
    SELECT 1 FROM lessons WHERE module_id = @m_web_2 AND lesson_order = 2
);

-- 7) Challenges (2 challenges per module)
INSERT INTO challenges (module_id, title, description, programming_language, difficulty, xp_reward, created_by, status)
SELECT @m_py_1, 'Challenge A', 'Create a simple solution for this module.', 'Python', 'easy', 15, @admin_id, 'approved'
WHERE NOT EXISTS (
    SELECT 1 FROM challenges WHERE module_id = @m_py_1 AND title = 'Challenge A'
);

INSERT INTO challenges (module_id, title, description, programming_language, difficulty, xp_reward, created_by, status)
SELECT @m_py_1, 'Challenge B', 'Solve a slightly harder problem for this module.', 'Python', 'medium', 25, @admin_id, 'approved'
WHERE NOT EXISTS (
    SELECT 1 FROM challenges WHERE module_id = @m_py_1 AND title = 'Challenge B'
);

INSERT INTO challenges (module_id, title, description, programming_language, difficulty, xp_reward, created_by, status)
SELECT @m_py_2, 'Challenge A', 'Create a simple solution for this module.', 'Python', 'easy', 15, @admin_id, 'approved'
WHERE NOT EXISTS (
    SELECT 1 FROM challenges WHERE module_id = @m_py_2 AND title = 'Challenge A'
);

INSERT INTO challenges (module_id, title, description, programming_language, difficulty, xp_reward, created_by, status)
SELECT @m_py_2, 'Challenge B', 'Solve a slightly harder problem for this module.', 'Python', 'medium', 25, @admin_id, 'approved'
WHERE NOT EXISTS (
    SELECT 1 FROM challenges WHERE module_id = @m_py_2 AND title = 'Challenge B'
);

INSERT INTO challenges (module_id, title, description, programming_language, difficulty, xp_reward, created_by, status)
SELECT @m_web_1, 'Challenge A', 'Create a simple solution for this module.', 'JavaScript', 'easy', 15, @admin_id, 'approved'
WHERE NOT EXISTS (
    SELECT 1 FROM challenges WHERE module_id = @m_web_1 AND title = 'Challenge A'
);

INSERT INTO challenges (module_id, title, description, programming_language, difficulty, xp_reward, created_by, status)
SELECT @m_web_1, 'Challenge B', 'Solve a slightly harder problem for this module.', 'JavaScript', 'medium', 25, @admin_id, 'approved'
WHERE NOT EXISTS (
    SELECT 1 FROM challenges WHERE module_id = @m_web_1 AND title = 'Challenge B'
);

INSERT INTO challenges (module_id, title, description, programming_language, difficulty, xp_reward, created_by, status)
SELECT @m_web_2, 'Challenge A', 'Create a simple solution for this module.', 'JavaScript', 'easy', 15, @admin_id, 'approved'
WHERE NOT EXISTS (
    SELECT 1 FROM challenges WHERE module_id = @m_web_2 AND title = 'Challenge A'
);

INSERT INTO challenges (module_id, title, description, programming_language, difficulty, xp_reward, created_by, status)
SELECT @m_web_2, 'Challenge B', 'Solve a slightly harder problem for this module.', 'JavaScript', 'medium', 25, @admin_id, 'approved'
WHERE NOT EXISTS (
    SELECT 1 FROM challenges WHERE module_id = @m_web_2 AND title = 'Challenge B'
);

-- 8) Course enrollment (helps dashboard not be empty)
INSERT IGNORE INTO course_enrollment (user_id, course_id, completion_status)
VALUES
(@learner_id, @course_python, 'in_progress'),
(@learner_id, @course_web, 'in_progress');

-- 9) XP sample data
INSERT INTO user_xp (user_id, total_xp, level)
VALUES
(@admin_id, 320, 3),
(@learner_id, 140, 2)
ON DUPLICATE KEY UPDATE
total_xp = VALUES(total_xp),
level = VALUES(level);

-- 10) Subscription plans sample data (avoid duplicates)
INSERT INTO subscription_plans (plan_name, price, billing_cycle)
SELECT 'Basic', 199.00, 'monthly'
WHERE NOT EXISTS (
    SELECT 1 FROM subscription_plans
    WHERE plan_name = 'Basic' AND price = 199.00 AND billing_cycle = 'monthly'
);

INSERT INTO subscription_plans (plan_name, price, billing_cycle)
SELECT 'Pro', 399.00, 'monthly'
WHERE NOT EXISTS (
    SELECT 1 FROM subscription_plans
    WHERE plan_name = 'Pro' AND price = 399.00 AND billing_cycle = 'monthly'
);

INSERT INTO subscription_plans (plan_name, price, billing_cycle)
SELECT 'Basic', 1999.00, 'yearly'
WHERE NOT EXISTS (
    SELECT 1 FROM subscription_plans
    WHERE plan_name = 'Basic' AND price = 1999.00 AND billing_cycle = 'yearly'
);

INSERT INTO subscription_plans (plan_name, price, billing_cycle)
SELECT 'Pro', 3999.00, 'yearly'
WHERE NOT EXISTS (
    SELECT 1 FROM subscription_plans
    WHERE plan_name = 'Pro' AND price = 3999.00 AND billing_cycle = 'yearly'
);

INSERT INTO subscription_plans (plan_name, price, billing_cycle)
SELECT 'Free', 0.00, 'monthly'
WHERE NOT EXISTS (
    SELECT 1 FROM subscription_plans
    WHERE plan_name = 'Free' AND price = 0.00 AND billing_cycle = 'monthly'
);

SELECT plan_id INTO @free_plan_id
FROM subscription_plans
WHERE plan_name = 'Free' AND billing_cycle = 'monthly'
LIMIT 1;

INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, status)
SELECT @admin_id, @free_plan_id, CURDATE(), NULL, 'active'
WHERE NOT EXISTS (
    SELECT 1 FROM user_subscriptions
    WHERE user_id = @admin_id AND plan_id = @free_plan_id AND status = 'active'
);

INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, status)
SELECT @learner_id, @free_plan_id, CURDATE(), NULL, 'active'
WHERE NOT EXISTS (
    SELECT 1 FROM user_subscriptions
    WHERE user_id = @learner_id AND plan_id = @free_plan_id AND status = 'active'
);

-- 11) Optional leaderboard sample (avoid duplicates)
INSERT INTO leaderboard (user_id, rank_position)
SELECT @admin_id, 1
WHERE NOT EXISTS (
    SELECT 1 FROM leaderboard WHERE user_id = @admin_id
);

INSERT INTO leaderboard (user_id, rank_position)
SELECT @learner_id, 2
WHERE NOT EXISTS (
    SELECT 1 FROM leaderboard WHERE user_id = @learner_id
);

COMMIT;
