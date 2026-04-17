<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$message = '';
if (checkAuth()) {
    $landingPath = isAdmin() ? '/kody/admin/users_crud.php' : '/kody/dashboard.php';
    header('Location: ' . $landingPath);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
        $message = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
    } else {
        try {
            $pdo = connectDB();
            $pdo->beginTransaction();

            $sql = 'INSERT INTO users (email, password_hash, first_name, last_name)
                    VALUES (:email, :password_hash, :first_name, :last_name)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);

            $newUserId = (int) $pdo->lastInsertId();

            $roleSql = 'SELECT role_id FROM roles WHERE role_name = :role_name LIMIT 1';
            $roleStmt = $pdo->prepare($roleSql);
            $roleStmt->execute(['role_name' => 'learner']);
            $learnerRole = $roleStmt->fetch();

            if (!$learnerRole) {
                throw new Exception('Learner role is missing. Seed roles first.');
            }

            $assignRoleSql = 'INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)';
            $assignRoleStmt = $pdo->prepare($assignRoleSql);
            $assignRoleStmt->execute([
                'user_id' => $newUserId,
                'role_id' => (int) $learnerRole['role_id'],
            ]);

            $xpSql = 'INSERT INTO user_xp (user_id, total_xp, level) VALUES (:user_id, 0, 1)';
            $xpStmt = $pdo->prepare($xpSql);
            $xpStmt->execute(['user_id' => $newUserId]);

            $freePlanUpsertSql = "INSERT INTO subscription_plans (plan_name, price, billing_cycle)
                                  SELECT 'Free', 0.00, 'monthly'
                                  WHERE NOT EXISTS (
                                      SELECT 1 FROM subscription_plans
                                      WHERE plan_name = 'Free' AND billing_cycle = 'monthly'
                                  )";
            $pdo->exec($freePlanUpsertSql);

            $planLookupSql = "SELECT plan_id
                              FROM subscription_plans
                              WHERE plan_name = 'Free' AND billing_cycle = 'monthly'
                              LIMIT 1";
            $freePlan = $pdo->query($planLookupSql)->fetch();

            if (!$freePlan) {
                throw new Exception('Free plan is missing and could not be created.');
            }

            $subscriptionSql = 'INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, status)
                                VALUES (:user_id, :plan_id, CURDATE(), NULL, :status)';
            $subscriptionStmt = $pdo->prepare($subscriptionSql);
            $subscriptionStmt->execute([
                'user_id' => $newUserId,
                'plan_id' => (int) $freePlan['plan_id'],
                'status' => 'active',
            ]);

            $pdo->commit();

            $_SESSION['user_id'] = $newUserId;
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['email'] = $email;
            $_SESSION['is_admin'] = false;

            header('Location: /kody/dashboard.php');
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ((string) $e->getCode() === '23000') {
                $message = 'This email is already registered. Please login instead.';
            } elseif ($e->getMessage() === 'Learner role is missing. Seed roles first.') {
                $message = 'Setup issue: learner role is missing. Run seed script first.';
            } elseif ($e->getMessage() === 'Free plan is missing and could not be created.') {
                $message = 'Setup issue: unable to provision the Free plan.';
            } else {
                $message = 'Registration failed. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<h2>Register</h2>
<?php if ($message): ?>
    <p class="notice"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>
<form method="post">
    <label>First Name</label><br>
    <input type="text" name="first_name" required><br><br>

    <label>Last Name</label><br>
    <input type="text" name="last_name" required><br><br>

    <label>Email</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password</label><br>
    <input type="password" name="password" minlength="6" required><br><br>

    <button type="submit">Create Account</button>
</form>

<p class="auth-switch">Already have an account? <a href="/kody/login.php">Login here</a>.</p>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
