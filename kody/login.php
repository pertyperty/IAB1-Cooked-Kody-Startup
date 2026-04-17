<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';
if (checkAuth()) {
    $landingPath = isAdmin() ? '/kody/admin/users_crud.php' : '/kody/dashboard.php';
    header('Location: ' . $landingPath);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $pdo = connectDB();
            $sql = 'SELECT user_id, first_name, last_name, email, password_hash
                    FROM users
                    WHERE email = :email
                    LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $rolesSql = 'SELECT r.role_name
                             FROM user_roles ur
                             JOIN roles r ON r.role_id = ur.role_id
                             WHERE ur.user_id = :user_id';
                $rolesStmt = $pdo->prepare($rolesSql);
                $rolesStmt->execute(['user_id' => (int) $user['user_id']]);
                $roleNames = $rolesStmt->fetchAll(PDO::FETCH_COLUMN);

                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = in_array('admin', $roleNames, true);

                $landingPath = $_SESSION['is_admin'] ? '/kody/admin/users_crud.php' : '/kody/dashboard.php';
                header('Location: ' . $landingPath);
                exit;
            }

            $error = 'Invalid email or password.';
        } catch (PDOException $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<h2>Login</h2>
<?php if ($error): ?>
    <p class="notice"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<form method="post">
    <label>Email</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Login</button>
</form>

<p class="auth-switch">No account yet? <a href="/kody/register.php">Create one here</a>.</p>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
