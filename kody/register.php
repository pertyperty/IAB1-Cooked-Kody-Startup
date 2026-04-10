<?php
require_once __DIR__ . '/includes/db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO: Save user to users table with password_hash.
    $message = 'Registration logic not implemented yet.';
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
    <input type="password" name="password" required><br><br>

    <button type="submit">Create Account</button>
</form>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
