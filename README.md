# Kody Web Application (CRUD Demonstration System)

## Overview

This project is a web-based application developed using PHP, MySQL, HTML, CSS, and JavaScript. It demonstrates full **CRUD (Create, Read, Update, Delete)** functionality across all database tables defined in the Kody system.

The system is designed to validate:

* Database Design Quality
* Full CRUD Implementation
* Database Integration with UI
* Functional System Demonstration

---

## System Architecture

The project follows a modular structure:

* **Core System Pages** - User-facing flows (login, dashboard, course interaction)
* **Admin/CRUD Pages** - Direct table manipulation for demonstration
* **Processing Files** - Handle database operations (INSERT, UPDATE, DELETE)
* **Shared Includes** - Database connection, authentication, reusable functions

---

## Project Structure

/kody/
│
├── index.php                        # Entry point (redirects based on session)
├── login.php                        # User authentication (READ users)
├── register.php                     # Create user account (CREATE users)
├── logout.php                       # Destroy session
│
├── dashboard.php                    # Displays user data, XP, enrollments (READ multiple tables)
│
├── course.php                       # View course, modules, lessons (READ courses/modules/lessons)
├── enroll.php                       # Enroll user (CREATE course_enrollment)
├── progress.php                     # View/update progress (READ/UPDATE user_progress)
│
├── submit_code.php                  # Code submission UI
├── process_submission.php           # Handles submission, XP update, progress update
│
├── leaderboard.php                  # Displays rankings (READ user_xp JOIN users)
│
├── subscription.php                 # View subscription plans (READ subscription_plans)
├── payment.php                      # Simulate payment (CREATE payments + subscriptions)
│
├── admin/                           # FULL CRUD CONTROL PANEL (CRITICAL FOR GRADING)
│   ├── users_crud.php               # CRUD users
│   ├── roles_crud.php               # CRUD roles
│   ├── user_roles_crud.php          # CRUD user_roles
│   ├── instructor_requests_crud.php # CRUD instructor_requests
│   ├── courses_crud.php             # CRUD courses
│   ├── modules_crud.php             # CRUD modules
│   ├── lessons_crud.php             # CRUD lessons
│   ├── challenges_crud.php          # CRUD challenges
│   ├── testcases_crud.php           # CRUD challenge_testcases
│   ├── submissions_crud.php         # CRUD submissions
│   ├── user_xp_crud.php             # CRUD user_xp
│   ├── leaderboard_crud.php         # CRUD leaderboard
│   ├── enrollment_crud.php          # CRUD course_enrollment
│   ├── progress_crud.php            # CRUD user_progress
│   ├── subscriptions_crud.php       # CRUD user_subscriptions
│   ├── payments_crud.php            # CRUD payments
│   ├── notifications_crud.php       # CRUD notifications
│
├── actions/                         # Processing scripts (DB operations)
│   ├── create.php
│   ├── update.php
│   ├── delete.php
│
├── includes/
│   ├── db.php                       # Database connection
│   ├── auth.php                     # Session/auth handling
│   ├── functions.php                # Core  reusable functions
│   ├── header.php                   # UI header
│   └── footer.php                   # UI footer
│
├── assets/
│   ├── css/
│   ├── js/
│
└── README.md

---

## Core Functional Pages

### index.php

* Redirects user based on authentication state

### login.php

* Handles user login
* Function:

  * loginUser(email, password)

### register.php

* Creates new user account
* Function:

  * registerUser(data)

### dashboard.php

* Displays:

  * User XP
  * Enrolled courses
  * Progress summary
* Function:

  * getUserDashboard(user_id)

---

## Course & Learning Flow

### course.php

* Displays course → modules → lessons → challenges
* Functions:

  * getCourse(course_id)
  * getModules(course_id)

### enroll.php

* Inserts into `course_enrollment`
* Function:

  * enrollUser(user_id, course_id)

### progress.php

* Displays and updates progress
* Functions:

  * getUserProgress(user_id)
  * updateProgress(...)

---

## Coding & Gamification

### submit_code.php

* UI for challenge submission

### process_submission.php

* Core system logic:

  * Insert submission
  * Update XP
  * Update progress

* Functions:

  * submitCode(data)
  * awardXP(user_id, xp)
  * markChallengeComplete(user_id, challenge_id)

---

## Leaderboard

### leaderboard.php

* Displays rankings using JOIN (no redundancy)
* Function:

  * getLeaderboard()

---

## Monetization

### subscription.php

* Displays plans

### payment.php

* Simulates payment
* Functions:

  * createPayment(user_id, plan_id)

---

## Admin CRUD Panel

Each CRUD page includes:

* CREATE form
* READ table view
* UPDATE form
* DELETE button

---

## Shared Components

### db.php

* Function:

  * connectDB()

### auth.php

* Functions:

  * checkAuth()
  * getCurrentUser()

### functions.php

* Contains reusable DB logic:

  * enrollUser()
  * awardXP()
  * getLeaderboard()
  * etc.

---

## CRUD Coverage Summary

| Table               | Covered By                   |
| ------------------- | ---------------------------- |
| users               | users_crud.php               |
| roles               | roles_crud.php               |
| user_roles          | user_roles_crud.php          |
| instructor_requests | instructor_requests_crud.php |
| courses             | courses_crud.php             |
| modules             | modules_crud.php             |
| lessons             | lessons_crud.php             |
| challenges          | challenges_crud.php          |
| challenge_testcases | testcases_crud.php           |
| submissions         | submissions_crud.php         |
| user_xp             | user_xp_crud.php             |
| leaderboard         | leaderboard_crud.php         |
| course_enrollment   | enrollment_crud.php          |
| user_progress       | progress_crud.php            |
| subscription_plans  | subscription.php             |
| user_subscriptions  | subscriptions_crud.php       |
| payments            | payments_crud.php            |
| notifications       | notifications_crud.php       |

---

## Key Features Demonstrated

* Full CRUD functionality across all tables
* Relational integrity via foreign keys
* Gamification system (XP, leaderboard)
* Course enrollment and progress tracking
* Monetization simulation
* Role-based system structure

---

## Kody Database SQL Structure

CREATE DATABASE IF NOT EXISTS kody_db;
USE kody_db;

-- =========================
-- USERS
-- =========================

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    google_id VARCHAR(50) UNIQUE,
    email VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(60) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    profile_picture VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    account_status ENUM('active','suspended') DEFAULT 'active'
);

-- =========================
-- ROLES (RBAC)
-- =========================

CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(30) UNIQUE NOT NULL
);

INSERT INTO roles (role_name) VALUES
('admin'),
('instructor'),
('learner'),
('contributor'),
('moderator');

CREATE TABLE user_roles (
    user_role_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    role_id INT,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- =========================
-- INSTRUCTOR ROLE REQUEST
-- =========================

CREATE TABLE instructor_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    request_message VARCHAR(255),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    reviewed_by INT,
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME,

    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
);

-- =========================
-- COURSES
-- =========================

CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    difficulty ENUM('beginner','intermediate','advanced'),
    is_archived BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (instructor_id) REFERENCES users(user_id)
);

-- =========================
-- COURSE ENROLLMENT
-- =========================

CREATE TABLE course_enrollment (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    course_id INT,
    enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completion_status ENUM('in_progress','completed','dropped') DEFAULT 'in_progress', -- ADDED: supports UC18 + UC20 tracking

    UNIQUE(user_id, course_id), -- ADDED: prevents duplicate enrollments

    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
);

-- =========================
-- MODULES
-- =========================

CREATE TABLE modules (
    module_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    title VARCHAR(150),
    module_order INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (course_id) REFERENCES courses(course_id)
);

-- =========================
-- CONTENT (LESSONS)
-- =========================

CREATE TABLE lessons (
    lesson_id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT,
    title VARCHAR(150),
    content TEXT,
    lesson_order INT,

    FOREIGN KEY (module_id) REFERENCES modules(module_id)
);

-- =========================
-- CODING CHALLENGES
-- =========================

CREATE TABLE challenges (
    challenge_id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT,
    title VARCHAR(150),
    description TEXT,
    programming_language VARCHAR(30),
    difficulty ENUM('easy','medium','hard'),
    xp_reward INT DEFAULT 10,
    created_by INT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (module_id) REFERENCES modules(module_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- =========================
-- CHALLENGE TEST CASES
-- =========================

CREATE TABLE challenge_testcases (
    testcase_id INT AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT,
    input_data TEXT,
    expected_output TEXT,

    FOREIGN KEY (challenge_id) REFERENCES challenges(challenge_id)
);

-- =========================
-- CODE SUBMISSIONS
-- =========================

CREATE TABLE submissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT,
    user_id INT,
    source_code TEXT,
    language VARCHAR(30),
    execution_status ENUM('pending','passed','failed','error'),
    score INT,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (challenge_id) REFERENCES challenges(challenge_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- =========================
-- USER PROGRESS
-- =========================

CREATE TABLE user_progress (
    progress_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    course_id INT,
    module_id INT,
    lesson_id INT,
    challenge_id INT,
    status ENUM('not_started','in_progress','completed') DEFAULT 'not_started', -- ADDED: supports progress tracking (UC20)
    completed_at DATETIME, -- ADDED: enables completion timestamps

    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (module_id) REFERENCES modules(module_id),
    FOREIGN KEY (lesson_id) REFERENCES lessons(lesson_id),
    FOREIGN KEY (challenge_id) REFERENCES challenges(challenge_id)
);

-- =========================
-- XP / GAMIFICATION
-- =========================

CREATE TABLE user_xp (
    xp_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    total_xp INT DEFAULT 0,
    level INT DEFAULT 1,

    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- =========================
-- LEADERBOARD
-- =========================

CREATE TABLE leaderboard (
    leaderboard_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    rank_position INT,

    FOREIGN KEY (user_id) REFERENCES users(user_id)
); -- UPDATED: removed total_xp to avoid redundancy (now computed via JOIN)

-- =========================
-- MODERATION REVIEWS
-- =========================

CREATE TABLE moderation_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT,
    moderator_id INT,
    decision ENUM('approved','rejected'),
    review_notes VARCHAR(255),
    reviewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (challenge_id) REFERENCES challenges(challenge_id),
    FOREIGN KEY (moderator_id) REFERENCES users(user_id)
);

-- =========================
-- SUBSCRIPTION PLANS
-- =========================

CREATE TABLE subscription_plans (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(20),
    price DECIMAL(8,2), -- UPDATED: from DECIMAL(4,2) to support realistic pricing
    billing_cycle ENUM('monthly','yearly')
);

-- =========================
-- USER SUBSCRIPTIONS
-- =========================

CREATE TABLE user_subscriptions (
    subscription_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    plan_id INT,
    start_date DATE,
    end_date DATE,
    status ENUM('active','expired','cancelled') DEFAULT 'active',

    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(plan_id)
);

-- =========================
-- PAYMENTS
-- =========================

CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    subscription_id INT,
    amount DECIMAL(8,2), -- UPDATED: aligned with subscription pricing precision
    payment_method VARCHAR(50),
    payment_status ENUM('pending','completed','failed'),
    paid_at DATETIME,

    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(subscription_id)
);

-- =========================
-- NOTIFICATIONS
-- =========================

CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

---
