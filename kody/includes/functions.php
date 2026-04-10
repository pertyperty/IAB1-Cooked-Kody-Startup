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
    $sql = 'SELECT u.user_id, u.first_name, u.last_name, COALESCE(x.total_xp, 0) AS total_xp
            FROM users u
            LEFT JOIN user_xp x ON x.user_id = u.user_id
            ORDER BY total_xp DESC';
    return $pdo->query($sql)->fetchAll();
}
