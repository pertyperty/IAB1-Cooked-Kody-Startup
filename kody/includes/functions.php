<?php
require_once __DIR__ . '/db.php';

function enrollUser($userId, $courseId)
{
    $pdo = connectDB();
    $sql = 'INSERT INTO course_enrollment (user_id, course_id) VALUES (:user_id, :course_id)';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        'user_id' => $userId,
        'course_id' => $courseId,
    ]);
}

function awardXP($userId, $xp)
{
    $pdo = connectDB();
    $sql = 'INSERT INTO user_xp (user_id, total_xp, level)
            VALUES (:user_id, :xp, 1)
            ON DUPLICATE KEY UPDATE total_xp = total_xp + VALUES(total_xp)';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        'user_id' => $userId,
        'xp' => $xp,
    ]);
}

function getLeaderboard()
{
    $pdo = connectDB();
    $sql = 'SELECT u.user_id, u.first_name, u.last_name, u.email, u.account_status,
                   COALESCE(x.total_xp, 0) AS total_xp,
                   COALESCE(x.level, 1) AS level
            FROM users u
            LEFT JOIN user_xp x ON x.user_id = u.user_id
            ORDER BY total_xp DESC';
    return $pdo->query($sql)->fetchAll();
}

function getUserDashboard($userId)
{
    $pdo = connectDB();

    $userSql = 'SELECT u.user_id, u.email, u.first_name, u.last_name, u.account_status, u.created_at,
                       COALESCE(x.total_xp, 0) AS total_xp,
                       COALESCE(x.level, 1) AS level
                FROM users u
                LEFT JOIN user_xp x ON x.user_id = u.user_id
                WHERE u.user_id = :user_id
                LIMIT 1';
    $userStmt = $pdo->prepare($userSql);
    $userStmt->execute(['user_id' => $userId]);
    $user = $userStmt->fetch();

    $enrollSql = 'SELECT ce.enrollment_id, ce.course_id, ce.enrolled_at, ce.completion_status,
                         c.title, c.description, c.difficulty, c.is_archived, c.created_at
                  FROM course_enrollment ce
                  JOIN courses c ON c.course_id = ce.course_id
                  WHERE ce.user_id = :user_id
                  ORDER BY ce.enrolled_at DESC';
    $enrollStmt = $pdo->prepare($enrollSql);
    $enrollStmt->execute(['user_id' => $userId]);
    $enrollments = $enrollStmt->fetchAll();

    return [
        'user' => $user,
        'enrollments' => $enrollments,
    ];
}

function getAllCourses()
{
    $pdo = connectDB();
    $sql = 'SELECT c.course_id, c.title, c.description, c.difficulty, c.is_archived, c.created_at,
                   c.instructor_id, u.first_name AS instructor_first_name, u.last_name AS instructor_last_name
            FROM courses c
            LEFT JOIN users u ON u.user_id = c.instructor_id
            ORDER BY c.course_id ASC';
    return $pdo->query($sql)->fetchAll();
}

function getCourse($courseId)
{
    $pdo = connectDB();
    $sql = 'SELECT c.course_id, c.title, c.description, c.difficulty, c.is_archived, c.created_at,
                   c.instructor_id, u.first_name AS instructor_first_name, u.last_name AS instructor_last_name
            FROM courses c
            LEFT JOIN users u ON u.user_id = c.instructor_id
            WHERE c.course_id = :course_id
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['course_id' => $courseId]);
    return $stmt->fetch();
}

function getModules($courseId)
{
    $pdo = connectDB();
    $sql = 'SELECT m.module_id, m.course_id, m.title AS module_title, m.module_order, m.created_at,
                   l.lesson_id, l.title AS lesson_title, l.content, l.lesson_order
            FROM modules m
            LEFT JOIN lessons l ON l.module_id = m.module_id
            WHERE m.course_id = :course_id
            ORDER BY m.module_order ASC, l.lesson_order ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['course_id' => $courseId]);
    $rows = $stmt->fetchAll();

    $modules = [];
    foreach ($rows as $row) {
        $moduleId = (int) $row['module_id'];

        if (!isset($modules[$moduleId])) {
            $modules[$moduleId] = [
                'module_id' => $moduleId,
                'course_id' => (int) $row['course_id'],
                'module_title' => $row['module_title'],
                'module_order' => (int) $row['module_order'],
                'created_at' => $row['created_at'],
                'lessons' => [],
            ];
        }

        if (!empty($row['lesson_id'])) {
            $modules[$moduleId]['lessons'][] = [
                'lesson_id' => (int) $row['lesson_id'],
                'lesson_title' => $row['lesson_title'],
                'content' => $row['content'],
                'lesson_order' => (int) $row['lesson_order'],
            ];
        }
    }

    return array_values($modules);
}
