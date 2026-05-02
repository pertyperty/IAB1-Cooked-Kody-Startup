<?php
require_once __DIR__ . '/db.php';

function enrollUser($userId, $courseId)
{
    $pdo = connectDB();

    $checkSql = 'SELECT enrollment_id
                 FROM course_enrollment
                 WHERE user_id = :user_id AND course_id = :course_id
                 LIMIT 1';
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([
        'user_id' => $userId,
        'course_id' => $courseId,
    ]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        return [
            'success' => false,
            'message' => 'You are already enrolled in this course.',
        ];
    }

    $sql = 'INSERT INTO course_enrollment (user_id, course_id, completion_status)
            VALUES (:user_id, :course_id, :completion_status)';
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        'user_id' => $userId,
        'course_id' => $courseId,
        'completion_status' => 'in_progress',
    ]);

    return [
        'success' => $ok,
        'message' => $ok ? 'Enrollment successful.' : 'Enrollment failed.',
    ];
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

function getUserEnrollments($userId)
{
    $pdo = connectDB();
    $sql = 'SELECT ce.enrollment_id, ce.user_id, ce.course_id, ce.enrolled_at, ce.completion_status,
                   c.title, c.description, c.difficulty, c.is_archived
            FROM course_enrollment ce
            JOIN courses c ON c.course_id = ce.course_id
            WHERE ce.user_id = :user_id
            ORDER BY ce.enrolled_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function getProgressRows($userId)
{
    $pdo = connectDB();
    $sql = 'SELECT up.progress_id, up.user_id, up.course_id, up.module_id, up.lesson_id, up.challenge_id,
                   up.status, up.completed_at,
                   c.title AS course_title,
                   m.title AS module_title,
                   l.title AS lesson_title,
                   ch.title AS challenge_title
            FROM user_progress up
            LEFT JOIN courses c ON c.course_id = up.course_id
            LEFT JOIN modules m ON m.module_id = up.module_id
            LEFT JOIN lessons l ON l.lesson_id = up.lesson_id
            LEFT JOIN challenges ch ON ch.challenge_id = up.challenge_id
            WHERE up.user_id = :user_id
            ORDER BY up.progress_id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function createProgressRow($userId, $courseId, $moduleId, $lessonId, $challengeId, $status)
{
    $pdo = connectDB();
    $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;

    $sql = 'INSERT INTO user_progress (user_id, course_id, module_id, lesson_id, challenge_id, status, completed_at)
            VALUES (:user_id, :course_id, :module_id, :lesson_id, :challenge_id, :status, :completed_at)';
    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        'user_id' => $userId,
        'course_id' => $courseId,
        'module_id' => $moduleId ?: null,
        'lesson_id' => $lessonId ?: null,
        'challenge_id' => $challengeId ?: null,
        'status' => $status,
        'completed_at' => $completedAt,
    ]);
}

function updateProgressStatus($userId, $progressId, $status)
{
    $pdo = connectDB();
    $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;

    $sql = 'UPDATE user_progress
            SET status = :status, completed_at = :completed_at
            WHERE progress_id = :progress_id AND user_id = :user_id';
    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        'status' => $status,
        'completed_at' => $completedAt,
        'progress_id' => $progressId,
        'user_id' => $userId,
    ]);
}

function getProgressOptionLists()
{
    $pdo = connectDB();

    $courses = $pdo->query('SELECT course_id, title FROM courses ORDER BY course_id ASC')->fetchAll();
    $modules = $pdo->query('SELECT module_id, course_id, title FROM modules ORDER BY module_id ASC')->fetchAll();
    $lessons = $pdo->query('SELECT lesson_id, module_id, title FROM lessons ORDER BY lesson_id ASC')->fetchAll();
    $challenges = $pdo->query('SELECT challenge_id, module_id, title FROM challenges ORDER BY challenge_id ASC')->fetchAll();

    return [
        'courses' => $courses,
        'modules' => $modules,
        'lessons' => $lessons,
        'challenges' => $challenges,
    ];
}

function getChallengeList()
{
    $pdo = connectDB();
    $sql = 'SELECT ch.challenge_id, ch.module_id, ch.title, ch.description, ch.programming_language,
                   ch.difficulty, ch.xp_reward, ch.created_by, ch.status, ch.created_at,
                   m.course_id, m.title AS module_title,
                   c.title AS course_title
            FROM challenges ch
            LEFT JOIN modules m ON m.module_id = ch.module_id
            LEFT JOIN courses c ON c.course_id = m.course_id
            ORDER BY ch.challenge_id ASC';
    return $pdo->query($sql)->fetchAll();
}

function getChallengeById($challengeId)
{
    $pdo = connectDB();
    $sql = 'SELECT ch.challenge_id, ch.module_id, ch.title, ch.description, ch.programming_language,
                   ch.difficulty, ch.xp_reward, ch.created_by, ch.status, ch.created_at,
                   m.course_id, m.title AS module_title,
                   c.title AS course_title
            FROM challenges ch
            LEFT JOIN modules m ON m.module_id = ch.module_id
            LEFT JOIN courses c ON c.course_id = m.course_id
            WHERE ch.challenge_id = :challenge_id
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['challenge_id' => $challengeId]);
    return $stmt->fetch();
}

function submitCode($data)
{
    $pdo = connectDB();
    $sql = 'INSERT INTO submissions (challenge_id, user_id, source_code, language, execution_status, score)
            VALUES (:challenge_id, :user_id, :source_code, :language, :execution_status, :score)';
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        'challenge_id' => $data['challenge_id'],
        'user_id' => $data['user_id'],
        'source_code' => $data['source_code'],
        'language' => $data['language'],
        'execution_status' => $data['execution_status'],
        'score' => $data['score'],
    ]);

    return [
        'success' => $ok,
        'submission_id' => $ok ? (int) $pdo->lastInsertId() : 0,
    ];
}

function markChallengeComplete($userId, $challengeId)
{
    $pdo = connectDB();
    $challenge = getChallengeById($challengeId);

    if (!$challenge) {
        return false;
    }

    $courseId = !empty($challenge['course_id']) ? (int) $challenge['course_id'] : null;
    $moduleId = !empty($challenge['module_id']) ? (int) $challenge['module_id'] : null;

    $checkSql = 'SELECT progress_id
                 FROM user_progress
                 WHERE user_id = :user_id AND challenge_id = :challenge_id
                 LIMIT 1';
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([
        'user_id' => $userId,
        'challenge_id' => $challengeId,
    ]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        return updateProgressStatus($userId, (int) $existing['progress_id'], 'completed');
    }

    return createProgressRow($userId, $courseId, $moduleId, null, $challengeId, 'completed');
}

function getSubscriptionPlans()
{
    $pdo = connectDB();
    $sql = 'SELECT plan_id, plan_name, price, billing_cycle
            FROM subscription_plans
            ORDER BY price ASC, plan_id ASC';
    return $pdo->query($sql)->fetchAll();
}

function getSubscriptionPlanById($planId)
{
    $pdo = connectDB();
    $sql = 'SELECT plan_id, plan_name, price, billing_cycle
            FROM subscription_plans
            WHERE plan_id = :plan_id
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['plan_id' => $planId]);
    return $stmt->fetch();
}

function getUserLatestSubscription($userId)
{
    $pdo = connectDB();
    $sql = 'SELECT us.subscription_id, us.user_id, us.plan_id, us.start_date, us.end_date, us.status,
                   sp.plan_name, sp.price, sp.billing_cycle
            FROM user_subscriptions us
            LEFT JOIN subscription_plans sp ON sp.plan_id = us.plan_id
            WHERE us.user_id = :user_id
            ORDER BY us.subscription_id DESC
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetch();
}

function upsertUserSubscription($userId, $planId, $billingCycle)
{
    $pdo = connectDB();
    $startDate = date('Y-m-d');
    $endDate = (new DateTime($startDate))
        ->modify($billingCycle === 'yearly' ? '+1 year' : '+1 month')
        ->format('Y-m-d');

    $latest = getUserLatestSubscription($userId);

    if ($latest) {
        $sql = 'UPDATE user_subscriptions
                SET plan_id = :plan_id,
                    start_date = :start_date,
                    end_date = :end_date,
                    status = :status
                WHERE subscription_id = :subscription_id';
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            'plan_id' => $planId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active',
            'subscription_id' => (int) $latest['subscription_id'],
        ]);

        return [
            'success' => $ok,
            'subscription_id' => (int) $latest['subscription_id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active',
        ];
    }

    $sql = 'INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, status)
            VALUES (:user_id, :plan_id, :start_date, :end_date, :status)';
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        'user_id' => $userId,
        'plan_id' => $planId,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'status' => 'active',
    ]);

    return [
        'success' => $ok,
        'subscription_id' => $ok ? (int) $pdo->lastInsertId() : 0,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'status' => 'active',
    ];
}

function createPaymentRecord($userId, $subscriptionId, $amount, $paymentMethod, $paymentStatus = 'completed')
{
    $pdo = connectDB();
    $sql = 'INSERT INTO payments (user_id, subscription_id, amount, payment_method, payment_status, paid_at)
            VALUES (:user_id, :subscription_id, :amount, :payment_method, :payment_status, :paid_at)';
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        'user_id' => $userId,
        'subscription_id' => $subscriptionId,
        'amount' => $amount,
        'payment_method' => $paymentMethod,
        'payment_status' => $paymentStatus,
        'paid_at' => date('Y-m-d H:i:s'),
    ]);

    return [
        'success' => $ok,
        'payment_id' => $ok ? (int) $pdo->lastInsertId() : 0,
    ];
}
