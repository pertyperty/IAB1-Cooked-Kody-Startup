<?php
require_once __DIR__ . '/includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO: Validate user input and check password hash from users table.
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter email and password.';
    } else {
        $error = 'Login logic not implemented yet.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
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
</body>
</html>l
<?php require_once __DIR__ . '/includes/footer.php'; ?>
