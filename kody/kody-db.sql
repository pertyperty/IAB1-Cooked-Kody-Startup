-- Kody prototype database schema and seed data
-- SRS-aligned modules: A-G (Account, Content, Challenge, Gamification, Engagement, Finance, Governance)

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS kody_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kody_db;

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY,
    role_name VARCHAR(40) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash CHAR(64) NOT NULL,
    status ENUM('Unverified', 'Active', 'Archived', 'Suspended', 'Deleted') NOT NULL DEFAULT 'Unverified',
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    primary_role ENUM('learner', 'contributor', 'instructor', 'moderator', 'administrator') NOT NULL DEFAULT 'learner',
    failed_login_count INT NOT NULL DEFAULT 0,
    lockout_until DATETIME DEFAULT NULL,
    oauth_provider VARCHAR(50) DEFAULT NULL,
    oauth_subject VARCHAR(120) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    archived_at DATETIME DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL,
    UNIQUE KEY uq_oauth_identity (oauth_provider, oauth_subject)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    granted_at DATETIME NOT NULL,
    granted_by INT DEFAULT NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id),
    CONSTRAINT fk_user_roles_granted_by FOREIGN KEY (granted_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_sessions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(80) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_user_sessions_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(120) NOT NULL,
    success TINYINT(1) NOT NULL,
    ip_address VARCHAR(64) DEFAULT NULL,
    attempt_at DATETIME NOT NULL,
    INDEX idx_login_attempts_email_time (email, attempt_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS email_tokens (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(120) NOT NULL UNIQUE,
    token_type ENUM('verify', 'recover') NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_email_tokens_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contributor_requests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    requested_role ENUM('contributor', 'instructor') NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    notes TEXT,
    reviewed_by INT DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_contributor_requests_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_contributor_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS instructor_credentials (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    credential_title VARCHAR(120) NOT NULL,
    file_url VARCHAR(255) DEFAULT NULL,
    verification_status ENUM('Pending', 'Accepted', 'Rejected') NOT NULL DEFAULT 'Pending',
    validated_by INT DEFAULT NULL,
    validated_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_instructor_credentials_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_instructor_credentials_validator FOREIGN KEY (validated_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wallets (
    user_id INT PRIMARY KEY,
    kodebits_balance INT NOT NULL DEFAULT 0,
    xp_points INT NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_wallets_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS token_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_name VARCHAR(80) NOT NULL UNIQUE,
    php_amount DECIMAL(10,2) NOT NULL,
    kodebits_amount INT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payment_transactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    payment_ref VARCHAR(60) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    package_id INT DEFAULT NULL,
    php_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'success', 'failed') NOT NULL,
    provider_name VARCHAR(80) NOT NULL,
    provider_payload JSON DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_payment_transactions_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_payment_transactions_package FOREIGN KEY (package_id) REFERENCES token_packages(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS token_ledger (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_type ENUM('credit', 'debit') NOT NULL,
    amount_kb INT NOT NULL,
    reference_type VARCHAR(60) NOT NULL,
    reference_id VARCHAR(60) NOT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_token_ledger_event (user_id, transaction_type, reference_type, reference_id),
    CONSTRAINT fk_token_ledger_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS creator_earnings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    period_label VARCHAR(80) NOT NULL,
    gross_php DECIMAL(10,2) NOT NULL,
    creator_share_php DECIMAL(10,2) NOT NULL,
    platform_share_php DECIMAL(10,2) NOT NULL,
    payout_status ENUM('pending', 'processing', 'paid') NOT NULL DEFAULT 'pending',
    generated_at DATETIME NOT NULL,
    UNIQUE KEY uq_creator_earning_period (user_id, period_label),
    CONSTRAINT fk_creator_earnings_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payout_requests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    creator_earning_id BIGINT NOT NULL,
    request_amount_php DECIMAL(10,2) NOT NULL,
    payout_channel VARCHAR(50) NOT NULL,
    payout_status ENUM('pending', 'processing', 'paid', 'failed') NOT NULL,
    requested_at DATETIME NOT NULL,
    processed_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_payout_requests_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_payout_requests_earning FOREIGN KEY (creator_earning_id) REFERENCES creator_earnings(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS courses (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    course_type ENUM('free', 'premium') NOT NULL DEFAULT 'free',
    kodebits_cost INT NOT NULL DEFAULT 0,
    status ENUM('Draft', 'Published', 'Active', 'Archived', 'Deleted') NOT NULL DEFAULT 'Draft',
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_courses_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS learning_modules (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    body_content MEDIUMTEXT,
    module_type ENUM('course', 'standalone') NOT NULL DEFAULT 'course',
    difficulty_level ENUM('Beginner', 'Intermediate', 'Advanced') NOT NULL DEFAULT 'Beginner',
    status ENUM('Draft', 'Published', 'Active', 'Archived', 'Deleted') NOT NULL DEFAULT 'Draft',
    kodebits_cost INT NOT NULL DEFAULT 0,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_learning_modules_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS course_modules (
    course_id BIGINT NOT NULL,
    module_id BIGINT NOT NULL,
    sequence_no INT NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (course_id, module_id),
    UNIQUE KEY uq_course_modules_sequence (course_id, sequence_no),
    CONSTRAINT fk_course_modules_course FOREIGN KEY (course_id) REFERENCES courses(id),
    CONSTRAINT fk_course_modules_module FOREIGN KEY (module_id) REFERENCES learning_modules(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enrollments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id BIGINT NOT NULL,
    enrollment_status ENUM('Active', 'Completed', 'Archived') NOT NULL DEFAULT 'Active',
    enrolled_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_enrollments_user_course (user_id, course_id),
    CONSTRAINT fk_enrollments_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_enrollments_course FOREIGN KEY (course_id) REFERENCES courses(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS module_progress (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id BIGINT NOT NULL,
    course_id BIGINT DEFAULT NULL,
    completion_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    completed_at DATETIME DEFAULT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_module_progress_user_module_course (user_id, module_id, course_id),
    CONSTRAINT fk_module_progress_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_module_progress_module FOREIGN KEY (module_id) REFERENCES learning_modules(id),
    CONSTRAINT fk_module_progress_course FOREIGN KEY (course_id) REFERENCES courses(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS code_challenges (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    prompt_text MEDIUMTEXT NOT NULL,
    difficulty_level ENUM('Beginner', 'Intermediate', 'Advanced') NOT NULL DEFAULT 'Beginner',
    status ENUM('Draft', 'UnderReview', 'Approved', 'Published', 'Archived', 'Deleted') NOT NULL DEFAULT 'Draft',
    language_scope VARCHAR(120) NOT NULL,
    time_limit_ms INT NOT NULL DEFAULT 2000,
    memory_limit_kb INT NOT NULL DEFAULT 128000,
    kodebits_cost INT NOT NULL DEFAULT 0,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_code_challenges_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS challenge_test_cases (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    challenge_id BIGINT NOT NULL,
    input_data TEXT NOT NULL,
    expected_output TEXT NOT NULL,
    is_hidden TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 1,
    CONSTRAINT fk_challenge_test_cases_challenge FOREIGN KEY (challenge_id) REFERENCES code_challenges(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS challenge_approvals (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    challenge_id BIGINT NOT NULL,
    reviewed_by INT NOT NULL,
    review_status ENUM('Approved', 'Rejected') NOT NULL,
    review_notes TEXT,
    reviewed_at DATETIME NOT NULL,
    UNIQUE KEY uq_challenge_approvals_single (challenge_id, reviewed_by),
    CONSTRAINT fk_challenge_approvals_challenge FOREIGN KEY (challenge_id) REFERENCES code_challenges(id),
    CONSTRAINT fk_challenge_approvals_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS submissions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    challenge_id BIGINT NOT NULL,
    weekly_challenge_id BIGINT DEFAULT NULL,
    language_name VARCHAR(40) NOT NULL,
    source_code MEDIUMTEXT NOT NULL,
    context_type ENUM('STANDARD', 'WEEKLY') NOT NULL DEFAULT 'STANDARD',
    submitted_at DATETIME NOT NULL,
    CONSTRAINT fk_submissions_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_submissions_challenge FOREIGN KEY (challenge_id) REFERENCES code_challenges(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS submission_results (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    submission_id BIGINT NOT NULL UNIQUE,
    compile_status VARCHAR(40) NOT NULL,
    run_status VARCHAR(40) NOT NULL,
    score INT NOT NULL,
    passed_tests INT NOT NULL,
    total_tests INT NOT NULL,
    execution_time_ms INT NOT NULL,
    memory_used_kb INT NOT NULL,
    feedback_text TEXT,
    evaluated_at DATETIME NOT NULL,
    CONSTRAINT fk_submission_results_submission FOREIGN KEY (submission_id) REFERENCES submissions(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS game_presets (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    preset_name VARCHAR(120) NOT NULL UNIQUE,
    rules_json JSON NOT NULL,
    rewards_json JSON NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_game_presets_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS gamified_activities (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    preset_id BIGINT NOT NULL,
    activity_name VARCHAR(150) NOT NULL,
    target_type ENUM('course', 'module', 'challenge') NOT NULL,
    target_id BIGINT NOT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    status ENUM('Draft', 'Active', 'Completed', 'Archived') NOT NULL DEFAULT 'Draft',
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_gamified_activities_preset FOREIGN KEY (preset_id) REFERENCES game_presets(id),
    CONSTRAINT fk_gamified_activities_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS weekly_challenges (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    weekly_code VARCHAR(60) NOT NULL UNIQUE,
    challenge_id BIGINT NOT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    rules_json JSON NOT NULL,
    rewards_json JSON NOT NULL,
    status ENUM('Draft', 'Active', 'Closed', 'Published') NOT NULL DEFAULT 'Draft',
    configured_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_weekly_challenges_challenge FOREIGN KEY (challenge_id) REFERENCES code_challenges(id),
    CONSTRAINT fk_weekly_challenges_configured_by FOREIGN KEY (configured_by) REFERENCES users(id)
) ENGINE=InnoDB;

SET @fk_submissions_weekly_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'submissions'
      AND CONSTRAINT_NAME = 'fk_submissions_weekly'
);

SET @fk_submissions_weekly_sql := IF(
    @fk_submissions_weekly_exists = 0,
    'ALTER TABLE submissions ADD CONSTRAINT fk_submissions_weekly FOREIGN KEY (weekly_challenge_id) REFERENCES weekly_challenges(id)',
    'SELECT 1'
);

PREPARE stmt_fk_submissions_weekly FROM @fk_submissions_weekly_sql;
EXECUTE stmt_fk_submissions_weekly;
DEALLOCATE PREPARE stmt_fk_submissions_weekly;

CREATE TABLE IF NOT EXISTS weekly_results (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    weekly_challenge_id BIGINT NOT NULL,
    user_id INT NOT NULL,
    best_submission_id BIGINT NOT NULL,
    final_score INT NOT NULL,
    rank_position INT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_weekly_results_user (weekly_challenge_id, user_id),
    CONSTRAINT fk_weekly_results_weekly FOREIGN KEY (weekly_challenge_id) REFERENCES weekly_challenges(id),
    CONSTRAINT fk_weekly_results_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_weekly_results_submission FOREIGN KEY (best_submission_id) REFERENCES submissions(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reward_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    xp_awarded INT NOT NULL,
    kodebits_awarded INT NOT NULL,
    reference_type VARCHAR(60) NOT NULL,
    reference_id VARCHAR(60) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_reward_events_ref (user_id, reference_type, reference_id),
    CONSTRAINT fk_reward_events_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS leaderboard_snapshots (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    period_label VARCHAR(80) NOT NULL,
    user_id INT NOT NULL,
    xp_points INT NOT NULL,
    kodebits_balance INT NOT NULL,
    rank_position INT NOT NULL,
    snapshot_at DATETIME NOT NULL,
    UNIQUE KEY uq_leaderboard_period_user (period_label, user_id),
    CONSTRAINT fk_leaderboard_snapshots_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS content_reactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_type ENUM('course', 'module', 'challenge') NOT NULL,
    content_id BIGINT NOT NULL,
    reaction_value ENUM('like') NOT NULL DEFAULT 'like',
    reacted_at DATETIME NOT NULL,
    UNIQUE KEY uq_content_reactions (user_id, content_type, content_id),
    CONSTRAINT fk_content_reactions_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS faq_entries (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    status ENUM('Draft', 'Published', 'Archived') NOT NULL DEFAULT 'Published',
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_faq_entries_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    channel ENUM('inapp', 'email') NOT NULL,
    subject VARCHAR(150) NOT NULL,
    body TEXT NOT NULL,
    delivery_status ENUM('queued', 'sent', 'failed') NOT NULL,
    created_at DATETIME NOT NULL,
    read_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS content_reports (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    reporter_user_id INT NOT NULL,
    content_type ENUM('course', 'module', 'challenge', 'user') NOT NULL,
    content_id BIGINT NOT NULL,
    report_reason VARCHAR(255) NOT NULL,
    report_status ENUM('Open', 'Reviewed', 'Closed') NOT NULL DEFAULT 'Open',
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_content_reports_reporter FOREIGN KEY (reporter_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS moderation_actions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT DEFAULT NULL,
    moderator_user_id INT NOT NULL,
    action_type ENUM('approve', 'reject', 'archive', 'suspend', 'reinstate', 'remove') NOT NULL,
    target_type ENUM('course', 'module', 'challenge', 'user', 'request') NOT NULL,
    target_id BIGINT NOT NULL,
    notes TEXT,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_moderation_actions_report FOREIGN KEY (report_id) REFERENCES content_reports(id),
    CONSTRAINT fk_moderation_actions_moderator FOREIGN KEY (moderator_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    actor_user_id INT DEFAULT NULL,
    action_name VARCHAR(120) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id VARCHAR(80) NOT NULL,
    old_data JSON DEFAULT NULL,
    new_data JSON DEFAULT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_audit_logs_actor FOREIGN KEY (actor_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- Seed data (idempotent and transaction-safe)
START TRANSACTION;

INSERT INTO roles (id, role_name, description) VALUES
(1, 'learner', 'Primary learning role'),
(2, 'contributor', 'Can create and manage challenges'),
(3, 'instructor', 'Can create courses and modules'),
(4, 'moderator', 'Can review content and role requests'),
(5, 'administrator', 'Full governance and reporting access')
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT INTO users (id, full_name, email, password_hash, status, email_verified, primary_role, created_at, updated_at) VALUES
(1, 'Admin One', 'admin@kody.local', SHA2('admin123', 256), 'Active', 1, 'administrator', UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(2, 'Moderator Mia', 'moderator@kody.local', SHA2('moderator123', 256), 'Active', 1, 'moderator', UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(3, 'Instructor Ian', 'instructor@kody.local', SHA2('instructor123', 256), 'Active', 1, 'instructor', UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(4, 'Contributor Cara', 'contributor@kody.local', SHA2('contributor123', 256), 'Active', 1, 'contributor', UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(5, 'Learner Leo', 'learner@kody.local', SHA2('learner123', 256), 'Active', 1, 'learner', UTC_TIMESTAMP(), UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
full_name = VALUES(full_name),
password_hash = VALUES(password_hash),
status = VALUES(status),
email_verified = VALUES(email_verified),
primary_role = VALUES(primary_role),
updated_at = UTC_TIMESTAMP();

INSERT INTO user_roles (user_id, role_id, granted_at, granted_by) VALUES
(1, 5, UTC_TIMESTAMP(), 1),
(2, 4, UTC_TIMESTAMP(), 1),
(3, 3, UTC_TIMESTAMP(), 1),
(4, 2, UTC_TIMESTAMP(), 2),
(5, 1, UTC_TIMESTAMP(), 3)
ON DUPLICATE KEY UPDATE granted_at = VALUES(granted_at), granted_by = VALUES(granted_by);

INSERT INTO wallets (user_id, kodebits_balance, xp_points, updated_at) VALUES
(1, 500, 5000, UTC_TIMESTAMP()),
(2, 320, 3200, UTC_TIMESTAMP()),
(3, 260, 2400, UTC_TIMESTAMP()),
(4, 180, 1700, UTC_TIMESTAMP()),
(5, 120, 950, UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
kodebits_balance = VALUES(kodebits_balance),
xp_points = VALUES(xp_points),
updated_at = UTC_TIMESTAMP();

INSERT INTO token_packages (id, package_name, php_amount, kodebits_amount, is_active) VALUES
(1, 'Starter 105KB', 100.00, 105, 1),
(2, 'Builder 330KB', 300.00, 330, 1),
(3, 'Creator 700KB', 600.00, 700, 1)
ON DUPLICATE KEY UPDATE
package_name = VALUES(package_name),
php_amount = VALUES(php_amount),
kodebits_amount = VALUES(kodebits_amount),
is_active = VALUES(is_active);

INSERT INTO courses (id, title, description, course_type, kodebits_cost, status, created_by, created_at, updated_at) VALUES
(1, 'Python Basics for Absolute Beginners', 'Fundamental syntax, conditions, and loops.', 'free', 0, 'Published', 3, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(2, 'Data Structures with Java', 'Collections, stacks, queues, and maps.', 'premium', 80, 'Published', 3, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(3, 'C++ Problem Solving Sprint', 'Hands-on algorithmic drills.', 'premium', 120, 'Active', 4, UTC_TIMESTAMP(), UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
title = VALUES(title),
description = VALUES(description),
course_type = VALUES(course_type),
kodebits_cost = VALUES(kodebits_cost),
status = VALUES(status),
updated_at = UTC_TIMESTAMP();

INSERT INTO learning_modules (id, title, body_content, module_type, difficulty_level, status, kodebits_cost, created_by, created_at, updated_at) VALUES
(1, 'Variables and Data Types', 'Text lesson with examples.', 'course', 'Beginner', 'Published', 0, 3, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(2, 'Conditionals and Loops', 'Practice exercises with hints.', 'course', 'Beginner', 'Published', 0, 3, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(3, 'Arrays and Linked Lists', 'Visual walkthrough for structures.', 'course', 'Intermediate', 'Published', 25, 3, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(4, 'Standalone SQL Crash Module', 'Independent SQL mini module.', 'standalone', 'Beginner', 'Published', 15, 4, UTC_TIMESTAMP(), UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
title = VALUES(title),
module_type = VALUES(module_type),
difficulty_level = VALUES(difficulty_level),
status = VALUES(status),
kodebits_cost = VALUES(kodebits_cost),
updated_at = UTC_TIMESTAMP();

INSERT INTO course_modules (course_id, module_id, sequence_no, created_at) VALUES
(1, 1, 1, UTC_TIMESTAMP()),
(1, 2, 2, UTC_TIMESTAMP()),
(2, 3, 1, UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE sequence_no = VALUES(sequence_no);

INSERT INTO enrollments (id, user_id, course_id, enrollment_status, enrolled_at, updated_at) VALUES
(1, 5, 1, 'Active', UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(2, 5, 2, 'Active', UTC_TIMESTAMP(), UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE enrollment_status = VALUES(enrollment_status), updated_at = UTC_TIMESTAMP();

INSERT INTO module_progress (id, user_id, module_id, course_id, completion_percent, completed_at, updated_at) VALUES
(1, 5, 1, 1, 100.00, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(2, 5, 2, 1, 55.00, NULL, UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE completion_percent = VALUES(completion_percent), completed_at = VALUES(completed_at), updated_at = UTC_TIMESTAMP();

INSERT INTO code_challenges (id, title, prompt_text, difficulty_level, status, language_scope, time_limit_ms, memory_limit_kb, kodebits_cost, created_by, created_at, updated_at) VALUES
(1, 'FizzBuzz Plus', 'Print numbers with Fizz/Buzz rules and prime marker.', 'Beginner', 'Approved', 'Python,Java,C++,JavaScript,PHP', 2000, 128000, 0, 4, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(2, 'Two Sum Variant', 'Return indices of two numbers that sum to target.', 'Beginner', 'Approved', 'Python,Java,C++', 2000, 128000, 10, 4, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(3, 'Matrix Path Count', 'Count valid paths in a matrix with obstacles.', 'Intermediate', 'Published', 'Python,Java,C++', 3000, 256000, 20, 3, UTC_TIMESTAMP(), UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
prompt_text = VALUES(prompt_text),
difficulty_level = VALUES(difficulty_level),
status = VALUES(status),
language_scope = VALUES(language_scope),
kodebits_cost = VALUES(kodebits_cost),
updated_at = UTC_TIMESTAMP();

INSERT INTO challenge_test_cases (id, challenge_id, input_data, expected_output, is_hidden, sort_order) VALUES
(1, 1, '15', '1 2 Fizz 4 Buzz ...', 0, 1),
(2, 1, '30', '... FizzBuzzPrime ...', 1, 2),
(3, 2, '[2,7,11,15],9', '[0,1]', 0, 1),
(4, 3, '3x3 no obstacle', '6', 0, 1)
ON DUPLICATE KEY UPDATE expected_output = VALUES(expected_output), is_hidden = VALUES(is_hidden), sort_order = VALUES(sort_order);

INSERT INTO challenge_approvals (id, challenge_id, reviewed_by, review_status, review_notes, reviewed_at) VALUES
(1, 1, 2, 'Approved', 'Ready for learners.', UTC_TIMESTAMP()),
(2, 2, 2, 'Approved', 'Passes review checks.', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE review_status = VALUES(review_status), review_notes = VALUES(review_notes), reviewed_at = VALUES(reviewed_at);

INSERT INTO submissions (id, user_id, challenge_id, weekly_challenge_id, language_name, source_code, context_type, submitted_at) VALUES
(1, 5, 1, NULL, 'Python', 'def solve(n):\n    return n', 'STANDARD', UTC_TIMESTAMP()),
(2, 5, 2, NULL, 'Python', 'def two_sum(nums, target):\n    return [0,1]', 'STANDARD', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE source_code = VALUES(source_code), submitted_at = VALUES(submitted_at);

INSERT INTO submission_results (id, submission_id, compile_status, run_status, score, passed_tests, total_tests, execution_time_ms, memory_used_kb, feedback_text, evaluated_at) VALUES
(1, 1, 'ok', 'Passed', 100, 3, 3, 820, 5120, 'All tests passed.', UTC_TIMESTAMP()),
(2, 2, 'ok', 'Passed', 95, 2, 2, 780, 4980, 'Great logic and speed.', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
run_status = VALUES(run_status),
score = VALUES(score),
feedback_text = VALUES(feedback_text),
evaluated_at = VALUES(evaluated_at);

INSERT INTO game_presets (id, preset_name, rules_json, rewards_json, status, created_by, created_at, updated_at) VALUES
(1, 'Speed Run Preset', JSON_OBJECT('mode', 'speed', 'max_attempts', 3), JSON_OBJECT('xp', 80, 'kb', 10), 'Active', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(2, 'Accuracy Preset', JSON_OBJECT('mode', 'accuracy', 'min_score', 90), JSON_OBJECT('xp', 60, 'kb', 6), 'Active', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
rules_json = VALUES(rules_json),
rewards_json = VALUES(rewards_json),
status = VALUES(status),
updated_at = UTC_TIMESTAMP();

INSERT INTO gamified_activities (id, preset_id, activity_name, target_type, target_id, start_at, end_at, status, created_by, created_at) VALUES
(1, 1, 'Weekly Fast FizzBuzz', 'challenge', 1, UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 7 DAY), 'Active', 3, UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE status = VALUES(status), end_at = VALUES(end_at);

INSERT INTO weekly_challenges (id, weekly_code, challenge_id, start_at, end_at, rules_json, rewards_json, status, configured_by, created_at) VALUES
(1, 'WEEK-2026-17', 1, UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 7 DAY), JSON_OBJECT('max_attempts', 3), JSON_OBJECT('xp', 120, 'kb', 20), 'Active', 2, UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
challenge_id = VALUES(challenge_id),
status = VALUES(status),
end_at = VALUES(end_at);

UPDATE submissions
SET weekly_challenge_id = 1
WHERE id = 1;

INSERT INTO weekly_results (id, weekly_challenge_id, user_id, best_submission_id, final_score, rank_position, created_at) VALUES
(1, 1, 5, 1, 100, 1, UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
best_submission_id = VALUES(best_submission_id),
final_score = VALUES(final_score),
rank_position = VALUES(rank_position);

INSERT INTO reward_events (id, user_id, xp_awarded, kodebits_awarded, reference_type, reference_id, created_at) VALUES
(1, 5, 20, 3, 'challenge_pass', '1', UTC_TIMESTAMP()),
(2, 5, 120, 20, 'weekly_result', '1', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
xp_awarded = VALUES(xp_awarded),
kodebits_awarded = VALUES(kodebits_awarded),
created_at = VALUES(created_at);

INSERT INTO token_ledger (id, user_id, transaction_type, amount_kb, reference_type, reference_id, notes, created_at) VALUES
(1, 5, 'credit', 105, 'payment', 'PAY-SEED-001', 'Seed token purchase', UTC_TIMESTAMP()),
(2, 5, 'debit', 80, 'course_enroll', '2', 'Premium course enrollment', UTC_TIMESTAMP()),
(3, 5, 'credit', 3, 'challenge_pass', '1', 'Challenge reward', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
amount_kb = VALUES(amount_kb),
notes = VALUES(notes),
created_at = VALUES(created_at);

INSERT INTO payment_transactions (id, payment_ref, user_id, package_id, php_amount, payment_status, provider_name, provider_payload, created_at, updated_at) VALUES
(1, 'PAY-SEED-001', 5, 1, 100.00, 'success', 'xendit-sandbox-sim', JSON_OBJECT('note', 'seed payment'), UTC_TIMESTAMP(), UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
payment_status = VALUES(payment_status),
provider_payload = VALUES(provider_payload),
updated_at = UTC_TIMESTAMP();

INSERT INTO creator_earnings (id, user_id, period_label, gross_php, creator_share_php, platform_share_php, payout_status, generated_at) VALUES
(1, 3, '2026-04', 1200.00, 780.00, 420.00, 'pending', UTC_TIMESTAMP()),
(2, 4, '2026-04', 950.00, 617.50, 332.50, 'processing', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
gross_php = VALUES(gross_php),
creator_share_php = VALUES(creator_share_php),
platform_share_php = VALUES(platform_share_php),
payout_status = VALUES(payout_status),
generated_at = VALUES(generated_at);

INSERT INTO payout_requests (id, user_id, creator_earning_id, request_amount_php, payout_channel, payout_status, requested_at, processed_at) VALUES
(1, 4, 2, 617.50, 'GCash', 'processing', UTC_TIMESTAMP(), NULL)
ON DUPLICATE KEY UPDATE
request_amount_php = VALUES(request_amount_php),
payout_status = VALUES(payout_status),
processed_at = VALUES(processed_at);

INSERT INTO leaderboard_snapshots (id, period_label, user_id, xp_points, kodebits_balance, rank_position, snapshot_at) VALUES
(1, 'global-latest', 1, 5000, 500, 1, UTC_TIMESTAMP()),
(2, 'global-latest', 2, 3200, 320, 2, UTC_TIMESTAMP()),
(3, 'global-latest', 3, 2400, 260, 3, UTC_TIMESTAMP()),
(4, 'global-latest', 4, 1700, 180, 4, UTC_TIMESTAMP()),
(5, 'global-latest', 5, 950, 120, 5, UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
xp_points = VALUES(xp_points),
kodebits_balance = VALUES(kodebits_balance),
rank_position = VALUES(rank_position),
snapshot_at = VALUES(snapshot_at);

INSERT INTO content_reactions (id, user_id, content_type, content_id, reaction_value, reacted_at) VALUES
(1, 5, 'course', 1, 'like', UTC_TIMESTAMP()),
(2, 5, 'challenge', 1, 'like', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE reacted_at = VALUES(reacted_at);

INSERT INTO faq_entries (id, question, answer, status, created_by, created_at, updated_at) VALUES
(1, 'How do I earn KodeBits?', 'Complete validated activities or buy token packages.', 'Published', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
(2, 'How are weekly winners chosen?', 'Weekly results are computed from best scored submission in the active weekly challenge.', 'Published', 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
answer = VALUES(answer),
status = VALUES(status),
updated_at = UTC_TIMESTAMP();

INSERT INTO notifications (id, user_id, channel, subject, body, delivery_status, created_at, read_at) VALUES
(1, 5, 'email', 'Welcome to Kody', 'Your learner account is active.', 'sent', UTC_TIMESTAMP(), NULL),
(2, 4, 'email', 'Contributor Review Update', 'Your request is currently under moderation review.', 'sent', UTC_TIMESTAMP(), NULL)
ON DUPLICATE KEY UPDATE
body = VALUES(body),
delivery_status = VALUES(delivery_status),
created_at = VALUES(created_at);

INSERT INTO contributor_requests (id, user_id, requested_role, status, notes, reviewed_by, reviewed_at, created_at, updated_at) VALUES
(1, 5, 'contributor', 'Pending', 'Learner requested contributor role after challenge streak.', NULL, NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
status = VALUES(status),
notes = VALUES(notes),
reviewed_by = VALUES(reviewed_by),
reviewed_at = VALUES(reviewed_at),
updated_at = UTC_TIMESTAMP();

INSERT INTO instructor_credentials (id, user_id, credential_title, file_url, verification_status, validated_by, validated_at, created_at) VALUES
(1, 3, 'BSCS Transcript', 'https://example.local/files/instructor-credential.pdf', 'Accepted', 2, UTC_TIMESTAMP(), UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
verification_status = VALUES(verification_status),
validated_by = VALUES(validated_by),
validated_at = VALUES(validated_at);

INSERT INTO content_reports (id, reporter_user_id, content_type, content_id, report_reason, report_status, created_at) VALUES
(1, 5, 'challenge', 3, 'Challenge statement has unclear constraints.', 'Open', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
report_reason = VALUES(report_reason),
report_status = VALUES(report_status);

INSERT INTO moderation_actions (id, report_id, moderator_user_id, action_type, target_type, target_id, notes, created_at) VALUES
(1, 1, 2, 'approve', 'challenge', 3, 'Flag acknowledged; challenge retained with revision request.', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
action_type = VALUES(action_type),
notes = VALUES(notes),
created_at = VALUES(created_at);

INSERT INTO audit_logs (id, actor_user_id, action_name, entity_type, entity_id, old_data, new_data, created_at) VALUES
(1, 1, 'seed_init', 'system', 'bootstrap', NULL, JSON_OBJECT('message', 'Initial seed applied'), UTC_TIMESTAMP()),
(2, 2, 'review_request', 'contributor_request', '1', NULL, JSON_OBJECT('status', 'Pending'), UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
new_data = VALUES(new_data),
created_at = VALUES(created_at);

COMMIT;

-- Quick starter credentials (prototype only)
-- admin@kody.local / admin123
-- moderator@kody.local / moderator123
-- instructor@kody.local / instructor123
-- contributor@kody.local / contributor123
-- learner@kody.local / learner123
