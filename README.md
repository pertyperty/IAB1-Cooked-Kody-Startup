# 📦 Kody Web Application (CRUD Demonstration System)

---

## 📌 Overview

This project is a web-based application developed using **PHP, MySQL, HTML, CSS, and JavaScript**. It demonstrates full **CRUD (Create, Read, Update, Delete)** functionality across all database tables defined in the Kody system.

### The system validates

* Database Design Quality
* Full CRUD Implementation
* Database Integration with UI
* Functional System Demonstration

---

## 🧱 System Architecture

The project follows a modular structure:

* **Core System Pages** → User-facing flows (login, dashboard, course interaction)
* **Admin/CRUD Pages** → Direct table manipulation for demonstration
* **Processing Files** → Handle database operations (INSERT, UPDATE, DELETE)
* **Shared Includes** → Database connection, authentication, reusable functions

---

## 📁 Project Structure

```bash
/kody/
│
├── index.php
├── login.php
├── register.php
├── logout.php
│
├── dashboard.php
│
├── course.php
├── enroll.php
├── progress.php
│
├── submit_code.php
├── process_submission.php
│
├── leaderboard.php
│
├── subscription.php
├── payment.php
│
├── admin/
│   ├── users_crud.php
│   ├── roles_crud.php
│   ├── user_roles_crud.php
│   ├── instructor_requests_crud.php
│   ├── courses_crud.php
│   ├── modules_crud.php
│   ├── lessons_crud.php
│   ├── challenges_crud.php
│   ├── testcases_crud.php
│   ├── submissions_crud.php
│   ├── user_xp_crud.php
│   ├── leaderboard_crud.php
│   ├── enrollment_crud.php
│   ├── progress_crud.php
│   ├── subscriptions_crud.php
│   ├── payments_crud.php
│   ├── notifications_crud.php
│
├── actions/
│   ├── create.php
│   ├── update.php
│   ├── delete.php
│
├── includes/
│   ├── db.php
│   ├── auth.php
│   ├── functions.php
│   ├── header.php
│   └── footer.php
│
├── assets/
│   ├── css/
│   ├── js/
│
└── README.md
```

---

## ⚙️ Core Functional Pages

### index.php

* Redirects user based on authentication state

### login.php

* Handles user login
* Function:

```php
loginUser(email, password)
```

### register.php

* Creates new user account
* Function:

```php
registerUser(data)
```

### dashboard.php

* Displays:

  * User XP
  * Enrolled courses
  * Progress summary
* Function:

```php
getUserDashboard(user_id)
```

---

## 🎯 Course & Learning Flow

### course.php

* Displays course → modules → lessons → challenges
* Functions:

```php
getCourse(course_id)
getModules(course_id)
```

### enroll.php

* Inserts into `course_enrollment`
* Function:

```php
enrollUser(user_id, course_id)
```

### progress.php

* Displays and updates progress
* Functions:

```php
getUserProgress(user_id)
updateProgress(...)
```

---

## 💻 Coding & Gamification

### submit_code.php

* UI for challenge submission

### process_submission.php

* Handles:

  * Insert submission
  * Update XP
  * Update progress

* Functions:

```php
submitCode(data)
awardXP(user_id, xp)
markChallengeComplete(user_id, challenge_id)
```

---

## 🏆 Leaderboard

### leaderboard.php

* Displays rankings using JOIN
* Function:

```php
getLeaderboard()
```

---

## 💰 Monetization

### subscription.php

* Displays subscription plans

### payment.php

* Simulates payment
* Function:

```php
createPayment(user_id, plan_id)
```

---

## 🔥 Admin CRUD Panel

Each CRUD page includes:

* Create form
* Read table view
* Update functionality
* Delete action

---

## 🧩 Shared Components

### db.php

```php
connectDB()
```

### auth.php

```php
checkAuth()
getCurrentUser()
```

### functions.php

* Contains reusable logic:

```php
enrollUser()
awardXP()
getLeaderboard()
```

---

## 📊 CRUD Coverage Summary

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

## 🚀 Key Features

* Full CRUD functionality across all tables
* Relational integrity via foreign keys
* Gamification system (XP, leaderboard)
* Course enrollment and progress tracking
* Monetization simulation
* Role-based system structure

---

## Database Setup

Run the SQL file:

```bash
database.sql
