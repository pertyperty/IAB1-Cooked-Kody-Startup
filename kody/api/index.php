<?php
declare(strict_types=1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/response.php';

const ROLE_LEARNER = 'learner';
const ROLE_CONTRIBUTOR = 'contributor';
const ROLE_INSTRUCTOR = 'instructor';
const ROLE_MODERATOR = 'moderator';
const ROLE_ADMIN = 'administrator';

try {
    $pdo = getPdo();
    applyRuntimeMigrations($pdo);
} catch (Throwable $e) {
    jsonResponse(false, 'Database connection failed.', ['error' => $e->getMessage()], 500);
}

$module = strtolower(trim((string) ($_GET['module'] ?? '')));
$action = strtolower(trim((string) ($_GET['action'] ?? '')));
$input = getJsonInput();

$publicAuthActions = ['register', 'login', 'verify_email', 'request_recovery', 'reset_recovery'];
$auth = null;

if (!($module === 'auth' && in_array($action, $publicAuthActions, true))) {
    $auth = requireSession($pdo, $input);
}

try {
    switch ($module) {
        case 'auth':
            handleAuth($pdo, $action, $input, $auth);
            break;
        case 'dashboard':
            handleDashboard($pdo, $action, $auth);
            break;
        case 'content':
            handleContent($pdo, $action, $input, $auth);
            break;
        case 'challenge':
            handleChallenge($pdo, $action, $input, $auth);
            break;
        case 'gamification':
            handleGamification($pdo, $action, $input, $auth);
            break;
        case 'interaction':
            handleInteraction($pdo, $action, $input, $auth);
            break;
        case 'finance':
            handleFinance($pdo, $action, $input, $auth);
            break;
        case 'admin':
            handleAdmin($pdo, $action, $input, $auth);
            break;
        default:
            jsonResponse(false, 'Unknown module.', ['module' => $module], 404);
    }
} catch (Throwable $e) {
    jsonResponse(false, 'Operation failed.', ['error' => $e->getMessage()], 500);
}

function applyRuntimeMigrations(PDO $pdo): void
{
    try {
        $pdo->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS failed_login_count INT NOT NULL DEFAULT 0');
        $pdo->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS lockout_until DATETIME NULL');
    } catch (Throwable $e) {
        // Best effort for compatibility with existing databases.
    }
}

function assertValidEmail(string $email, string $field = 'email'): void
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Invalid email format.', ['field' => $field], 422);
    }
}

function assertStrongPassword(string $password, string $field = 'password'): void
{
    if (strlen($password) < 8) {
        jsonResponse(false, 'Password must be at least 8 characters.', ['field' => $field], 422);
    }

    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        jsonResponse(false, 'Password must include letters and numbers.', ['field' => $field], 422);
    }
}

function learnerPlus(): array
{
    return [ROLE_LEARNER, ROLE_CONTRIBUTOR, ROLE_INSTRUCTOR, ROLE_MODERATOR, ROLE_ADMIN];
}

function contributorPlus(): array
{
    return [ROLE_CONTRIBUTOR, ROLE_INSTRUCTOR, ROLE_MODERATOR, ROLE_ADMIN];
}

function instructorPlus(): array
{
    return [ROLE_INSTRUCTOR, ROLE_MODERATOR, ROLE_ADMIN];
}

function moderatorPlus(): array
{
    return [ROLE_MODERATOR, ROLE_ADMIN];
}

function adminOnly(): array
{
    return [ROLE_ADMIN];
}

function requireRoles(array $auth, array $allowedRoles, string $message = 'Not authorized for this action.'): void
{
    if (!in_array($auth['role'], $allowedRoles, true)) {
        jsonResponse(false, $message, ['role' => $auth['role']], 403);
    }
}

function extractSessionToken(array $input): string
{
    if (!empty($input['session_token'])) {
        return trim((string) $input['session_token']);
    }

    $headerToken = '';
    $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
    if (isset($headers['Authorization'])) {
        $headerToken = (string) $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $headerToken = (string) $headers['authorization'];
    }

    if ($headerToken !== '' && stripos($headerToken, 'Bearer ') === 0) {
        return trim(substr($headerToken, 7));
    }

    return '';
}

function requireSession(PDO $pdo, array $input): array
{
    $token = extractSessionToken($input);
    if ($token === '') {
        jsonResponse(false, 'Session token is required.', [], 401);
    }

    $stmt = $pdo->prepare('SELECT us.id AS session_id, us.user_id, us.expires_at, us.revoked_at, u.full_name, u.email, u.primary_role, u.status, u.email_verified
        FROM user_sessions us
        JOIN users u ON u.id = us.user_id
        WHERE us.session_token = :session_token
        LIMIT 1');
    $stmt->execute(['session_token' => $token]);
    $row = $stmt->fetch();

    if (!$row) {
        jsonResponse(false, 'Invalid session.', [], 401);
    }

    if ($row['revoked_at'] !== null) {
        jsonResponse(false, 'Session is revoked. Please login again.', [], 401);
    }

    if (strtotime((string) $row['expires_at']) <= time()) {
        jsonResponse(false, 'Session expired. Please login again.', [], 401);
    }

    if ((string) $row['status'] !== 'Active') {
        jsonResponse(false, 'Account is not active.', ['status' => $row['status']], 403);
    }

    if ((int) $row['email_verified'] !== 1) {
        jsonResponse(false, 'Email verification required.', [], 403);
    }

    return [
        'token' => $token,
        'session_id' => (int) $row['session_id'],
        'id' => (int) $row['user_id'],
        'full_name' => (string) $row['full_name'],
        'email' => (string) $row['email'],
        'role' => (string) $row['primary_role'],
    ];
}

function handleAuth(PDO $pdo, string $action, array $input, ?array $auth): void
{
    if ($action === 'register') {
        $missing = requiredFields($input, ['full_name', 'email', 'password']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $email = strtolower(trim((string) $input['email']));
        assertValidEmail($email);
        assertStrongPassword((string) $input['password']);

        if (strlen(trim((string) $input['full_name'])) < 2) {
            jsonResponse(false, 'Full name must be at least 2 characters.', ['field' => 'full_name'], 422);
        }

        $requestedRole = strtolower(trim((string) ($input['requested_role'] ?? ROLE_LEARNER)));
        if (!in_array($requestedRole, [ROLE_LEARNER, ROLE_INSTRUCTOR], true)) {
            $requestedRole = ROLE_LEARNER;
        }

        if ($requestedRole === ROLE_INSTRUCTOR) {
            $instructorMissing = requiredFields($input, ['credential_title', 'file_url', 'expertise_summary']);
            if ($instructorMissing !== null) {
                jsonResponse(false, 'Missing field for instructor registration.', ['field' => $instructorMissing], 422);
            }
        }

        $exists = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $exists->execute(['email' => $email]);
        if ($exists->fetch()) {
            jsonResponse(false, 'Email already registered.', [], 409);
        }

        $pdo->beginTransaction();

        $insert = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, status, email_verified, primary_role, failed_login_count, lockout_until, created_at, updated_at)
            VALUES (:full_name, :email, :password_hash, :status, 0, :primary_role, 0, NULL, :created_at, :updated_at)');
        $insert->execute([
            'full_name' => trim((string) $input['full_name']),
            'email' => $email,
            'password_hash' => hashPasswordPrototype((string) $input['password']),
            'status' => 'Unverified',
            'primary_role' => ROLE_LEARNER,
            'created_at' => nowUtc(),
            'updated_at' => nowUtc(),
        ]);

        $userId = (int) $pdo->lastInsertId();

        ensureWallet($pdo, $userId);

        if ($requestedRole !== ROLE_LEARNER) {
            $req = $pdo->prepare('INSERT INTO contributor_requests (user_id, requested_role, status, notes, created_at, updated_at)
                VALUES (:user_id, :requested_role, :status, :notes, :created_at, :updated_at)');
            $req->execute([
                'user_id' => $userId,
                'requested_role' => 'instructor',
                'status' => 'Pending',
                'notes' => trim((string) ($input['expertise_summary'] ?? 'Requested during registration')),
                'created_at' => nowUtc(),
                'updated_at' => nowUtc(),
            ]);

            $credentialInsert = $pdo->prepare('INSERT INTO instructor_credentials (user_id, credential_title, file_url, verification_status, validated_by, validated_at, created_at)
                VALUES (:user_id, :credential_title, :file_url, :verification_status, NULL, NULL, :created_at)');
            $credentialInsert->execute([
                'user_id' => $userId,
                'credential_title' => trim((string) $input['credential_title']),
                'file_url' => trim((string) $input['file_url']),
                'verification_status' => 'Pending',
                'created_at' => nowUtc(),
            ]);
        }

        $verifyToken = bin2hex(random_bytes(16));
        $token = $pdo->prepare('INSERT INTO email_tokens (user_id, token, token_type, expires_at, used_at, created_at)
            VALUES (:user_id, :token, :token_type, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 HOUR), NULL, :created_at)');
        $token->execute([
            'user_id' => $userId,
            'token' => $verifyToken,
            'token_type' => 'verify',
            'created_at' => nowUtc(),
        ]);

        createNotification($pdo, $userId, 'email', 'Verify your Kody account', 'Verification token: ' . $verifyToken, 'sent');
        writeAudit($pdo, $userId, 'register_account', 'users', (string) $userId, null, ['requested_role' => $requestedRole]);

        $pdo->commit();

        jsonResponse(true, 'Account registered. Verify email before login.', [
            'user_id' => $userId,
            'verification_token' => $verifyToken,
            'requested_role' => $requestedRole,
        ]);
    }

    if ($action === 'verify_email') {
        $missing = requiredFields($input, ['token']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $stmt = $pdo->prepare('SELECT id, user_id, expires_at, used_at FROM email_tokens WHERE token = :token AND token_type = :token_type LIMIT 1');
        $stmt->execute([
            'token' => trim((string) $input['token']),
            'token_type' => 'verify',
        ]);
        $row = $stmt->fetch();

        if (!$row) {
            jsonResponse(false, 'Invalid verification token.', [], 404);
        }

        if ($row['used_at'] !== null) {
            jsonResponse(false, 'Token already used.', [], 409);
        }

        if (strtotime((string) $row['expires_at']) < time()) {
            jsonResponse(false, 'Token expired.', [], 410);
        }

        $pdo->beginTransaction();

        $updateUser = $pdo->prepare('UPDATE users SET email_verified = 1, status = :status, updated_at = :updated_at WHERE id = :id');
        $updateUser->execute([
            'status' => 'Active',
            'updated_at' => nowUtc(),
            'id' => (int) $row['user_id'],
        ]);

        $markToken = $pdo->prepare('UPDATE email_tokens SET used_at = :used_at WHERE id = :id');
        $markToken->execute([
            'used_at' => nowUtc(),
            'id' => (int) $row['id'],
        ]);

        writeAudit($pdo, (int) $row['user_id'], 'verify_email', 'users', (string) $row['user_id'], null, ['verified' => true]);

        $pdo->commit();
        jsonResponse(true, 'Email verified successfully.', []);
    }

    if ($action === 'login') {
        $missing = requiredFields($input, ['email', 'password']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $email = strtolower(trim((string) $input['email']));
        $password = (string) $input['password'];

        $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, status, primary_role, email_verified, failed_login_count, lockout_until
            FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && $user['lockout_until'] !== null && strtotime((string) $user['lockout_until']) > time()) {
            $remaining = strtotime((string) $user['lockout_until']) - time();
            jsonResponse(false, 'Account temporarily locked due to failed login attempts.', [
                'lockout_until' => $user['lockout_until'],
                'seconds_remaining' => $remaining,
            ], 423);
        }

        if (!$user || !verifyPasswordPrototype($password, (string) $user['password_hash'])) {
            registerFailedAttempt($pdo, $email, $user ? (int) $user['id'] : null);
            jsonResponse(false, 'Invalid email or password.', [], 401);
        }

        if ((string) $user['status'] !== 'Active') {
            jsonResponse(false, 'Account is not active.', ['status' => $user['status']], 403);
        }

        if ((int) $user['email_verified'] !== 1) {
            jsonResponse(false, 'Email is not verified.', [], 403);
        }

        $pdo->beginTransaction();

        $reset = $pdo->prepare('UPDATE users SET failed_login_count = 0, lockout_until = NULL, updated_at = :updated_at WHERE id = :id');
        $reset->execute([
            'updated_at' => nowUtc(),
            'id' => (int) $user['id'],
        ]);

        if (passwordNeedsUpgrade((string) $user['password_hash'])) {
            $hashUpgrade = $pdo->prepare('UPDATE users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id');
            $hashUpgrade->execute([
                'password_hash' => hashPasswordPrototype($password),
                'updated_at' => nowUtc(),
                'id' => (int) $user['id'],
            ]);
        }

        $sessionToken = bin2hex(random_bytes(24));
        $session = $pdo->prepare('INSERT INTO user_sessions (user_id, session_token, expires_at, revoked_at, created_at)
            VALUES (:user_id, :session_token, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 12 HOUR), NULL, :created_at)');
        $session->execute([
            'user_id' => (int) $user['id'],
            'session_token' => $sessionToken,
            'created_at' => nowUtc(),
        ]);

        $attempt = $pdo->prepare('INSERT INTO login_attempts (email, success, ip_address, attempt_at) VALUES (:email, 1, :ip_address, :attempt_at)');
        $attempt->execute([
            'email' => $email,
            'ip_address' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'attempt_at' => nowUtc(),
        ]);

        writeAudit($pdo, (int) $user['id'], 'login', 'users', (string) $user['id'], null, ['session_created' => true]);

        $pdo->commit();

        jsonResponse(true, 'Login successful.', [
            'session_token' => $sessionToken,
            'user' => [
                'id' => (int) $user['id'],
                'full_name' => (string) $user['full_name'],
                'email' => (string) $user['email'],
                'role' => (string) $user['primary_role'],
            ],
        ]);
    }

    if ($action === 'request_recovery') {
        $missing = requiredFields($input, ['email']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $email = strtolower(trim((string) $input['email']));
        assertValidEmail($email);
        $stmt = $pdo->prepare('SELECT id, primary_role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        $recoveryToken = null;

        if ($user && (string) $user['primary_role'] !== ROLE_ADMIN) {
            $recoveryToken = bin2hex(random_bytes(16));

            $insert = $pdo->prepare('INSERT INTO email_tokens (user_id, token, token_type, expires_at, used_at, created_at)
                VALUES (:user_id, :token, :token_type, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 MINUTE), NULL, :created_at)');
            $insert->execute([
                'user_id' => (int) $user['id'],
                'token' => $recoveryToken,
                'token_type' => 'recover',
                'created_at' => nowUtc(),
            ]);

            createNotification($pdo, (int) $user['id'], 'email', 'Kody account recovery', 'Recovery token: ' . $recoveryToken, 'sent');
            writeAudit($pdo, (int) $user['id'], 'request_recovery', 'users', (string) $user['id'], null, ['recovery_requested' => true]);
        }

        jsonResponse(true, 'If the account is eligible, a recovery token has been generated.', [
            'recovery_token' => $recoveryToken,
        ]);
    }

    if ($action === 'reset_recovery') {
        $missing = requiredFields($input, ['token', 'new_password']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $stmt = $pdo->prepare('SELECT id, user_id, expires_at, used_at FROM email_tokens WHERE token = :token AND token_type = :token_type LIMIT 1');
        assertStrongPassword((string) $input['new_password'], 'new_password');

        $stmt->execute([
            'token' => trim((string) $input['token']),
            'token_type' => 'recover',
        ]);
        $tokenRow = $stmt->fetch();

        if (!$tokenRow) {
            jsonResponse(false, 'Invalid recovery token.', [], 404);
        }

        if ($tokenRow['used_at'] !== null) {
            jsonResponse(false, 'Recovery token already used.', [], 409);
        }

        if (strtotime((string) $tokenRow['expires_at']) < time()) {
            jsonResponse(false, 'Recovery token expired.', [], 410);
        }

        $roleStmt = $pdo->prepare('SELECT primary_role FROM users WHERE id = :id LIMIT 1');
        $roleStmt->execute(['id' => (int) $tokenRow['user_id']]);
        $userRoleRow = $roleStmt->fetch();
        if ($userRoleRow && (string) $userRoleRow['primary_role'] === ROLE_ADMIN) {
            jsonResponse(false, 'Account recovery is disabled for administrator accounts.', [], 403);
        }

        $pdo->beginTransaction();

        $userUpdate = $pdo->prepare('UPDATE users SET password_hash = :password_hash, status = :status, failed_login_count = 0, lockout_until = NULL, updated_at = :updated_at WHERE id = :id');
        $userUpdate->execute([
            'password_hash' => hashPasswordPrototype((string) $input['new_password']),
            'status' => 'Active',
            'updated_at' => nowUtc(),
            'id' => (int) $tokenRow['user_id'],
        ]);

        $tokenUpdate = $pdo->prepare('UPDATE email_tokens SET used_at = :used_at WHERE id = :id');
        $tokenUpdate->execute([
            'used_at' => nowUtc(),
            'id' => (int) $tokenRow['id'],
        ]);

        $sessionRevoke = $pdo->prepare('UPDATE user_sessions SET revoked_at = :revoked_at WHERE user_id = :user_id AND revoked_at IS NULL');
        $sessionRevoke->execute([
            'revoked_at' => nowUtc(),
            'user_id' => (int) $tokenRow['user_id'],
        ]);

        writeAudit($pdo, (int) $tokenRow['user_id'], 'reset_recovery', 'users', (string) $tokenRow['user_id'], null, ['password_reset' => true]);

        $pdo->commit();

        jsonResponse(true, 'Password reset complete. Please login again.', []);
    }

    if (!$auth) {
        jsonResponse(false, 'Authentication required.', [], 401);
    }

    if ($action === 'me' || $action === 'view_account') {
        $userStmt = $pdo->prepare('SELECT status, email_verified, failed_login_count, lockout_until, created_at, archived_at, deleted_at
            FROM users
            WHERE id = :user_id
            LIMIT 1');
        $userStmt->execute(['user_id' => $auth['id']]);
        $userRow = $userStmt->fetch() ?: [
            'status' => 'Unknown',
            'email_verified' => 0,
            'failed_login_count' => 0,
            'lockout_until' => null,
            'created_at' => null,
            'archived_at' => null,
            'deleted_at' => null,
        ];

        $walletStmt = $pdo->prepare('SELECT kodebits_balance, xp_points FROM wallets WHERE user_id = :user_id LIMIT 1');
        $walletStmt->execute(['user_id' => $auth['id']]);
        $wallet = $walletStmt->fetch() ?: ['kodebits_balance' => 0, 'xp_points' => 0];

        jsonResponse(true, 'Account profile loaded.', [
            'user' => [
                'id' => $auth['id'],
                'full_name' => $auth['full_name'],
                'email' => $auth['email'],
                'role' => $auth['role'],
                'status' => (string) $userRow['status'],
                'email_verified' => (int) $userRow['email_verified'],
                'failed_login_count' => (int) $userRow['failed_login_count'],
                'lockout_until' => $userRow['lockout_until'],
                'created_at' => $userRow['created_at'],
                'archived_at' => $userRow['archived_at'],
                'deleted_at' => $userRow['deleted_at'],
                'kodebits_balance' => (int) $wallet['kodebits_balance'],
                'xp_points' => (int) $wallet['xp_points'],
            ],
        ]);
    }

    if ($action === 'my_requests') {
        $stmt = $pdo->prepare('SELECT id, requested_role, status, notes, reviewed_by, reviewed_at, created_at, updated_at
            FROM contributor_requests
            WHERE user_id = :user_id
            ORDER BY id DESC');
        $stmt->execute(['user_id' => $auth['id']]);
        jsonResponse(true, 'Role request history loaded.', ['rows' => $stmt->fetchAll()]);
    }

    if ($action === 'my_credentials') {
        $stmt = $pdo->prepare('SELECT id, credential_title, file_url, verification_status, validated_by, validated_at, created_at
            FROM instructor_credentials
            WHERE user_id = :user_id
            ORDER BY id DESC');
        $stmt->execute(['user_id' => $auth['id']]);
        jsonResponse(true, 'Instructor credentials loaded.', ['rows' => $stmt->fetchAll()]);
    }

    if ($action === 'logout') {
        $stmt = $pdo->prepare('UPDATE user_sessions SET revoked_at = :revoked_at WHERE id = :session_id');
        $stmt->execute([
            'revoked_at' => nowUtc(),
            'session_id' => $auth['session_id'],
        ]);

        jsonResponse(true, 'Logged out.', []);
    }

    if ($action === 'edit_account') {
        $fullName = trim((string) ($input['full_name'] ?? ''));
        $newEmail = strtolower(trim((string) ($input['new_email'] ?? '')));
        $newPassword = (string) ($input['new_password'] ?? '');
        $currentPassword = (string) ($input['current_password'] ?? '');

        if ($fullName === '' && $newEmail === '' && $newPassword === '') {
            jsonResponse(false, 'No changes provided.', [], 422);
        }

        $userStmt = $pdo->prepare('SELECT id, email, password_hash FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute(['id' => $auth['id']]);
        $userRow = $userStmt->fetch();
        if (!$userRow) {
            jsonResponse(false, 'User account not found.', [], 404);
        }

        $isSensitive = $newEmail !== '' || $newPassword !== '';

        if ($newEmail !== '') {
            assertValidEmail($newEmail, 'new_email');
        }

        if ($newPassword !== '') {
            assertStrongPassword($newPassword, 'new_password');
        }

        if ($isSensitive && $currentPassword === '') {
            jsonResponse(false, 'Current password is required for sensitive updates.', [], 422);
        }

        if ($isSensitive && !verifyPasswordPrototype($currentPassword, (string) $userRow['password_hash'])) {
            jsonResponse(false, 'Current password is invalid.', [], 403);
        }

        $pdo->beginTransaction();
        $verificationToken = null;

        $updates = [];
        $params = ['id' => $auth['id']];

        if ($fullName !== '') {
            $updates[] = 'full_name = :full_name';
            $params['full_name'] = $fullName;
        }

        if ($newPassword !== '') {
            $updates[] = 'password_hash = :password_hash';
            $params['password_hash'] = hashPasswordPrototype($newPassword);
        }

        if ($newEmail !== '' && $newEmail !== (string) $userRow['email']) {
            $exists = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
            $exists->execute([
                'email' => $newEmail,
                'id' => $auth['id'],
            ]);
            if ($exists->fetch()) {
                $pdo->rollBack();
                jsonResponse(false, 'Email is already in use.', [], 409);
            }

            $updates[] = 'email = :email';
            $updates[] = 'email_verified = 0';
            $updates[] = 'status = :status';
            $params['email'] = $newEmail;
            $params['status'] = 'Unverified';

            $verificationToken = bin2hex(random_bytes(16));
            $tokenInsert = $pdo->prepare('INSERT INTO email_tokens (user_id, token, token_type, expires_at, used_at, created_at)
                VALUES (:user_id, :token, :token_type, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 HOUR), NULL, :created_at)');
            $tokenInsert->execute([
                'user_id' => $auth['id'],
                'token' => $verificationToken,
                'token_type' => 'verify',
                'created_at' => nowUtc(),
            ]);

            createNotification($pdo, $auth['id'], 'email', 'Verify your updated email', 'Verification token: ' . $verificationToken, 'sent');
        }

        $updates[] = 'updated_at = :updated_at';
        $params['updated_at'] = nowUtc();

        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $update = $pdo->prepare($sql);
        $update->execute($params);

        writeAudit($pdo, $auth['id'], 'edit_account', 'users', (string) $auth['id'], null, ['updated' => true]);
        $pdo->commit();

        jsonResponse(true, 'Account updated successfully.', [
            'verification_token' => $verificationToken,
        ]);
    }

    if ($action === 'request_contributor_role') {
        requireRoles($auth, [ROLE_LEARNER], 'Only learner accounts can request contributor role from the profile page.');

        $requestedRole = strtolower(trim((string) ($input['requested_role'] ?? 'contributor')));
        if ($requestedRole !== 'contributor') {
            jsonResponse(false, 'Invalid requested role.', [], 422);
        }

        $pendingCheck = $pdo->prepare('SELECT id FROM contributor_requests WHERE user_id = :user_id AND status = :status LIMIT 1');
        $pendingCheck->execute([
            'user_id' => $auth['id'],
            'status' => 'Pending',
        ]);

        if ($pendingCheck->fetch()) {
            jsonResponse(false, 'You already have a pending role request.', [], 409);
        }

        $insert = $pdo->prepare('INSERT INTO contributor_requests (user_id, requested_role, status, notes, created_at, updated_at)
            VALUES (:user_id, :requested_role, :status, :notes, :created_at, :updated_at)');
        $insert->execute([
            'user_id' => $auth['id'],
            'requested_role' => $requestedRole,
            'status' => 'Pending',
            'notes' => trim((string) ($input['notes'] ?? 'Requested from account page.')),
            'created_at' => nowUtc(),
            'updated_at' => nowUtc(),
        ]);

        writeAudit($pdo, $auth['id'], 'request_contributor_role', 'contributor_requests', (string) $pdo->lastInsertId(), null, ['requested_role' => $requestedRole]);

        jsonResponse(true, 'Role request submitted.', []);
    }

    if ($action === 'submit_instructor_credentials') {
        $missing = requiredFields($input, ['credential_title', 'file_url']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $insert = $pdo->prepare('INSERT INTO instructor_credentials (user_id, credential_title, file_url, verification_status, validated_by, validated_at, created_at)
            VALUES (:user_id, :credential_title, :file_url, :verification_status, NULL, NULL, :created_at)');
        $insert->execute([
            'user_id' => $auth['id'],
            'credential_title' => trim((string) $input['credential_title']),
            'file_url' => trim((string) $input['file_url']),
            'verification_status' => 'Pending',
            'created_at' => nowUtc(),
        ]);

        writeAudit($pdo, $auth['id'], 'submit_instructor_credentials', 'instructor_credentials', (string) $pdo->lastInsertId(), null, ['submitted' => true]);
        jsonResponse(true, 'Instructor credentials submitted for verification.', []);
    }

    if ($action === 'archive_account') {
        $missing = requiredFields($input, ['password']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $auth['id']]);
        $user = $stmt->fetch();

        if (!$user || !verifyPasswordPrototype((string) $input['password'], (string) $user['password_hash'])) {
            jsonResponse(false, 'Password confirmation failed.', [], 403);
        }

        $pdo->beginTransaction();
        $update = $pdo->prepare('UPDATE users SET status = :status, archived_at = :archived_at, updated_at = :updated_at WHERE id = :id');
        $update->execute([
            'status' => 'Archived',
            'archived_at' => nowUtc(),
            'updated_at' => nowUtc(),
            'id' => $auth['id'],
        ]);

        revokeAllSessions($pdo, $auth['id']);
        writeAudit($pdo, $auth['id'], 'archive_account', 'users', (string) $auth['id'], null, ['status' => 'Archived']);
        $pdo->commit();

        jsonResponse(true, 'Account archived. Login is disabled until recovery.', []);
    }

    if ($action === 'delete_account') {
        $missing = requiredFields($input, ['confirmation_phrase', 'password']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        if (trim((string) $input['confirmation_phrase']) !== 'DELETE MY ACCOUNT') {
            jsonResponse(false, 'Invalid confirmation phrase.', [], 422);
        }

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $auth['id']]);
        $user = $stmt->fetch();

        if (!$user || !verifyPasswordPrototype((string) $input['password'], (string) $user['password_hash'])) {
            jsonResponse(false, 'Password confirmation failed.', [], 403);
        }

        $anonEmail = 'deleted_' . $auth['id'] . '_' . gmdate('YmdHis') . '@deleted.local';

        $pdo->beginTransaction();

        $update = $pdo->prepare('UPDATE users SET full_name = :full_name, email = :email, status = :status, email_verified = 0, primary_role = :primary_role, deleted_at = :deleted_at, updated_at = :updated_at WHERE id = :id');
        $update->execute([
            'full_name' => 'Deleted User #' . $auth['id'],
            'email' => $anonEmail,
            'status' => 'Deleted',
            'primary_role' => ROLE_LEARNER,
            'deleted_at' => nowUtc(),
            'updated_at' => nowUtc(),
            'id' => $auth['id'],
        ]);

        revokeAllSessions($pdo, $auth['id']);
        writeAudit($pdo, $auth['id'], 'delete_account', 'users', (string) $auth['id'], null, ['status' => 'Deleted']);

        $pdo->commit();

        jsonResponse(true, 'Account deletion completed.', []);
    }

    jsonResponse(false, 'Unknown auth action.', ['action' => $action], 404);
}

function handleDashboard(PDO $pdo, string $action, array $auth): void
{
    requireRoles($auth, learnerPlus());

    if ($action !== 'summary') {
        jsonResponse(false, 'Unknown dashboard action.', ['action' => $action], 404);
    }

    $summary = [
        'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'courses' => (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
        'modules' => (int) $pdo->query('SELECT COUNT(*) FROM learning_modules')->fetchColumn(),
        'challenges' => (int) $pdo->query('SELECT COUNT(*) FROM code_challenges')->fetchColumn(),
        'enrollments' => (int) $pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn(),
        'pending_requests' => (int) $pdo->query("SELECT COUNT(*) FROM contributor_requests WHERE status = 'Pending'")->fetchColumn(),
        'my_enrollments' => 0,
        'my_submissions' => 0,
        'my_notifications' => 0,
        'open_reports' => 0,
        'pending_credentials' => 0,
    ];

    $walletStmt = $pdo->prepare('SELECT kodebits_balance, xp_points FROM wallets WHERE user_id = :user_id LIMIT 1');
    $walletStmt->execute(['user_id' => $auth['id']]);
    $wallet = $walletStmt->fetch() ?: ['kodebits_balance' => 0, 'xp_points' => 0];

    $summaryStmt = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE user_id = :user_id AND enrollment_status = :status');
    $summaryStmt->execute([
        'user_id' => $auth['id'],
        'status' => 'Active',
    ]);
    $summary['my_enrollments'] = (int) $summaryStmt->fetchColumn();

    $summaryStmt = $pdo->prepare('SELECT COUNT(*) FROM submissions WHERE user_id = :user_id');
    $summaryStmt->execute(['user_id' => $auth['id']]);
    $summary['my_submissions'] = (int) $summaryStmt->fetchColumn();

    $summaryStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id');
    $summaryStmt->execute(['user_id' => $auth['id']]);
    $summary['my_notifications'] = (int) $summaryStmt->fetchColumn();

    if (in_array($auth['role'], moderatorPlus(), true)) {
        $summary['open_reports'] = (int) $pdo->query("SELECT COUNT(*) FROM content_reports WHERE report_status = 'Open'")->fetchColumn();
        $summary['pending_credentials'] = (int) $pdo->query("SELECT COUNT(*) FROM instructor_credentials WHERE verification_status = 'Pending'")->fetchColumn();
    }

    $topUsers = $pdo->query('SELECT u.id, u.full_name, u.primary_role, w.xp_points, w.kodebits_balance
        FROM users u
        JOIN wallets w ON w.user_id = u.id
        WHERE u.status = "Active"
        ORDER BY w.xp_points DESC, w.kodebits_balance DESC
        LIMIT 10')->fetchAll();

    jsonResponse(true, 'Dashboard summary loaded.', [
        'summary' => $summary,
        'current_user' => [
            'id' => $auth['id'],
            'full_name' => $auth['full_name'],
            'role' => $auth['role'],
            'xp_points' => (int) $wallet['xp_points'],
            'kodebits_balance' => (int) $wallet['kodebits_balance'],
        ],
        'rows' => $topUsers,
    ]);
}

function handleContent(PDO $pdo, string $action, array $input, array $auth): void
{
    if ($action === 'list_courses') {
        requireRoles($auth, learnerPlus());
        $rows = $pdo->query('SELECT c.id, c.title, c.description, c.course_type, c.kodebits_cost, c.status, c.created_by, u.full_name AS creator_name,
            COALESCE((SELECT COUNT(*) FROM content_reactions cr WHERE cr.content_type = "course" AND cr.content_id = c.id AND cr.reaction_value = "like"), 0) AS likes_count
            FROM courses c
            JOIN users u ON u.id = c.created_by
            ORDER BY c.id DESC')->fetchAll();
        jsonResponse(true, 'Courses loaded.', ['rows' => $rows]);
    }

    if ($action === 'list_modules') {
        requireRoles($auth, learnerPlus());
        $rows = $pdo->query('SELECT lm.id, lm.title, lm.module_type, lm.difficulty_level, lm.status, lm.kodebits_cost, lm.created_by, u.full_name AS creator_name,
            COALESCE((SELECT COUNT(*) FROM content_reactions cr WHERE cr.content_type = "module" AND cr.content_id = lm.id AND cr.reaction_value = "like"), 0) AS likes_count
            FROM learning_modules lm
            JOIN users u ON u.id = lm.created_by
            ORDER BY lm.id DESC')->fetchAll();
        jsonResponse(true, 'Modules loaded.', ['rows' => $rows]);
    }

    if ($action === 'create_course') {
        requireRoles($auth, instructorPlus());
        $missing = requiredFields($input, ['title']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $stmt = $pdo->prepare('INSERT INTO courses (title, description, course_type, kodebits_cost, status, created_by, created_at, updated_at)
            VALUES (:title, :description, :course_type, :kodebits_cost, :status, :created_by, :created_at, :updated_at)');
        $stmt->execute([
            'title' => trim((string) $input['title']),
            'description' => trim((string) ($input['description'] ?? '')),
            'course_type' => (string) ($input['course_type'] ?? 'free'),
            'kodebits_cost' => (int) ($input['kodebits_cost'] ?? 0),
            'status' => 'Draft',
            'created_by' => $auth['id'],
            'created_at' => nowUtc(),
            'updated_at' => nowUtc(),
        ]);

        writeAudit($pdo, $auth['id'], 'create_course', 'courses', (string) $pdo->lastInsertId(), null, ['title' => $input['title']]);
        jsonResponse(true, 'Course created as Draft.', ['course_id' => (int) $pdo->lastInsertId()]);
    }

    if ($action === 'edit_course') {
        requireRoles($auth, instructorPlus());
        $missing = requiredFields($input, ['course_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $courseId = (int) $input['course_id'];
        assertOwnerOrAdmin($pdo, 'courses', 'id', $courseId, $auth, 'created_by');

        $fields = [];
        $params = ['id' => $courseId];

        if (isset($input['title']) && trim((string) $input['title']) !== '') {
            $fields[] = 'title = :title';
            $params['title'] = trim((string) $input['title']);
        }

        if (isset($input['description']) && trim((string) $input['description']) !== '') {
            $fields[] = 'description = :description';
            $params['description'] = trim((string) $input['description']);
        }

        if (isset($input['status']) && trim((string) $input['status']) !== '') {
            $fields[] = 'status = :status';
            $params['status'] = trim((string) $input['status']);
        }

        if (count($fields) === 0) {
            jsonResponse(false, 'No update fields provided.', [], 422);
        }

        $fields[] = 'updated_at = :updated_at';
        $params['updated_at'] = nowUtc();

        $sql = 'UPDATE courses SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        writeAudit($pdo, $auth['id'], 'edit_course', 'courses', (string) $courseId, null, $params);
        jsonResponse(true, 'Course updated.', []);
    }

    if ($action === 'archive_course') {
        requireRoles($auth, instructorPlus());
        $missing = requiredFields($input, ['course_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $courseId = (int) $input['course_id'];
        assertOwnerOrAdmin($pdo, 'courses', 'id', $courseId, $auth, 'created_by');

        $stmt = $pdo->prepare('UPDATE courses SET status = :status, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'status' => 'Archived',
            'updated_at' => nowUtc(),
            'id' => $courseId,
        ]);

        writeAudit($pdo, $auth['id'], 'archive_course', 'courses', (string) $courseId, null, ['status' => 'Archived']);
        jsonResponse(true, 'Course archived.', []);
    }

    if ($action === 'delete_course') {
        requireRoles($auth, instructorPlus());
        $missing = requiredFields($input, ['course_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $courseId = (int) $input['course_id'];
        assertOwnerOrAdmin($pdo, 'courses', 'id', $courseId, $auth, 'created_by');

        $dep = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id = :course_id AND enrollment_status = :status');
        $dep->execute([
            'course_id' => $courseId,
            'status' => 'Active',
        ]);
        if ((int) $dep->fetchColumn() > 0) {
            jsonResponse(false, 'Course has active enrollments. Archive instead.', [], 409);
        }

        $stmt = $pdo->prepare('UPDATE courses SET status = :status, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'status' => 'Deleted',
            'updated_at' => nowUtc(),
            'id' => $courseId,
        ]);

        writeAudit($pdo, $auth['id'], 'delete_course', 'courses', (string) $courseId, null, ['status' => 'Deleted']);
        jsonResponse(true, 'Course marked as Deleted.', []);
    }

    if ($action === 'create_module') {
        requireRoles($auth, instructorPlus());
        $missing = requiredFields($input, ['title']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $stmt = $pdo->prepare('INSERT INTO learning_modules (title, body_content, module_type, difficulty_level, status, kodebits_cost, created_by, created_at, updated_at)
            VALUES (:title, :body_content, :module_type, :difficulty_level, :status, :kodebits_cost, :created_by, :created_at, :updated_at)');
        $stmt->execute([
            'title' => trim((string) $input['title']),
            'body_content' => trim((string) ($input['body_content'] ?? '')),
            'module_type' => (string) ($input['module_type'] ?? 'course'),
            'difficulty_level' => (string) ($input['difficulty_level'] ?? 'Beginner'),
            'status' => 'Draft',
            'kodebits_cost' => (int) ($input['kodebits_cost'] ?? 0),
            'created_by' => $auth['id'],
            'created_at' => nowUtc(),
            'updated_at' => nowUtc(),
        ]);

        writeAudit($pdo, $auth['id'], 'create_module', 'learning_modules', (string) $pdo->lastInsertId(), null, ['title' => $input['title']]);
        jsonResponse(true, 'Module created as Draft.', ['module_id' => (int) $pdo->lastInsertId()]);
    }

    if ($action === 'edit_module') {
        requireRoles($auth, instructorPlus());
        $missing = requiredFields($input, ['module_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $moduleId = (int) $input['module_id'];
        assertOwnerOrAdmin($pdo, 'learning_modules', 'id', $moduleId, $auth, 'created_by');

        $fields = [];
        $params = ['id' => $moduleId];

        if (isset($input['title']) && trim((string) $input['title']) !== '') {
            $fields[] = 'title = :title';
            $params['title'] = trim((string) $input['title']);
        }

        if (isset($input['body_content']) && trim((string) $input['body_content']) !== '') {
            $fields[] = 'body_content = :body_content';
            $params['body_content'] = trim((string) $input['body_content']);
        }

        if (isset($input['module_type']) && trim((string) $input['module_type']) !== '') {
            $fields[] = 'module_type = :module_type';
            $params['module_type'] = trim((string) $input['module_type']);
        }

        if (isset($input['difficulty_level']) && trim((string) $input['difficulty_level']) !== '') {
            $fields[] = 'difficulty_level = :difficulty_level';
            $params['difficulty_level'] = trim((string) $input['difficulty_level']);
        }

        if (isset($input['kodebits_cost']) && trim((string) $input['kodebits_cost']) !== '') {
            $fields[] = 'kodebits_cost = :kodebits_cost';
            $params['kodebits_cost'] = (int) $input['kodebits_cost'];
        }

        if (isset($input['status']) && trim((string) $input['status']) !== '') {
            $fields[] = 'status = :status';
            $params['status'] = trim((string) $input['status']);
        }

        if (count($fields) === 0) {
            jsonResponse(false, 'No update fields provided.', [], 422);
        }

        $fields[] = 'updated_at = :updated_at';
        $params['updated_at'] = nowUtc();

        $sql = 'UPDATE learning_modules SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        writeAudit($pdo, $auth['id'], 'edit_module', 'learning_modules', (string) $moduleId, null, $params);
        jsonResponse(true, 'Module updated.', []);
    }

    if ($action === 'archive_module') {
        requireRoles($auth, instructorPlus());
        $missing = requiredFields($input, ['module_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $moduleId = (int) $input['module_id'];
        assertOwnerOrAdmin($pdo, 'learning_modules', 'id', $moduleId, $auth, 'created_by');

        $stmt = $pdo->prepare('UPDATE learning_modules SET status = :status, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'status' => 'Archived',
            'updated_at' => nowUtc(),
            'id' => $moduleId,
        ]);

        writeAudit($pdo, $auth['id'], 'archive_module', 'learning_modules', (string) $moduleId, null, ['status' => 'Archived']);
        jsonResponse(true, 'Module archived.', []);
    }

    if ($action === 'delete_module') {
        requireRoles($auth, instructorPlus());
        $missing = requiredFields($input, ['module_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $moduleId = (int) $input['module_id'];
        assertOwnerOrAdmin($pdo, 'learning_modules', 'id', $moduleId, $auth, 'created_by');

        $dep = $pdo->prepare('SELECT COUNT(*) FROM course_modules WHERE module_id = :module_id');
        $dep->execute(['module_id' => $moduleId]);
        if ((int) $dep->fetchColumn() > 0) {
            jsonResponse(false, 'Module is assigned to course(s). Archive instead.', [], 409);
        }

        $stmt = $pdo->prepare('UPDATE learning_modules SET status = :status, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'status' => 'Deleted',
            'updated_at' => nowUtc(),
            'id' => $moduleId,
        ]);

        writeAudit($pdo, $auth['id'], 'delete_module', 'learning_modules', (string) $moduleId, null, ['status' => 'Deleted']);
        jsonResponse(true, 'Module marked as Deleted.', []);
    }

    if ($action === 'assign_module') {
        requireRoles($auth, instructorPlus());
        $missing = requiredFields($input, ['course_id', 'module_id', 'sequence_no']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $courseId = (int) $input['course_id'];
        $moduleId = (int) $input['module_id'];
        $sequence = (int) $input['sequence_no'];

        assertOwnerOrAdmin($pdo, 'courses', 'id', $courseId, $auth, 'created_by');

        $moduleState = $pdo->prepare('SELECT status FROM learning_modules WHERE id = :id LIMIT 1');
        $moduleState->execute(['id' => $moduleId]);
        $module = $moduleState->fetch();
        if (!$module || in_array((string) $module['status'], ['Deleted', 'Archived'], true)) {
            jsonResponse(false, 'Module is not eligible for assignment.', [], 409);
        }

        $stmt = $pdo->prepare('INSERT INTO course_modules (course_id, module_id, sequence_no, created_at)
            VALUES (:course_id, :module_id, :sequence_no, :created_at)
            ON DUPLICATE KEY UPDATE sequence_no = VALUES(sequence_no)');
        $stmt->execute([
            'course_id' => $courseId,
            'module_id' => $moduleId,
            'sequence_no' => $sequence,
            'created_at' => nowUtc(),
        ]);

        writeAudit($pdo, $auth['id'], 'assign_module', 'course_modules', $courseId . ':' . $moduleId, null, ['sequence_no' => $sequence]);
        jsonResponse(true, 'Module assigned to course.', []);
    }

    jsonResponse(false, 'Unknown content action.', ['action' => $action], 404);
}

function handleChallenge(PDO $pdo, string $action, array $input, array $auth): void
{
    if ($action === 'list') {
        requireRoles($auth, learnerPlus());
        $rows = $pdo->query('SELECT c.id, c.title, c.prompt_text, c.difficulty_level, c.status, c.language_scope, c.time_limit_ms, c.memory_limit_kb, c.kodebits_cost, c.created_by, u.full_name AS creator_name,
            ca.review_status,
            ca.review_notes,
            ca.reviewed_at,
            COALESCE((SELECT COUNT(*) FROM content_reactions cr WHERE cr.content_type = "challenge" AND cr.content_id = c.id AND cr.reaction_value = "like"), 0) AS likes_count
            FROM code_challenges c
            JOIN users u ON u.id = c.created_by
            LEFT JOIN challenge_approvals ca ON ca.challenge_id = c.id
            ORDER BY c.id DESC')->fetchAll();
        jsonResponse(true, 'Challenges loaded.', ['rows' => $rows]);
    }

    if ($action === 'review') {
        requireRoles($auth, moderatorPlus());
        $missing = requiredFields($input, ['challenge_id', 'review_status']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $challengeId = (int) $input['challenge_id'];
        $reviewStatus = ucfirst(strtolower(trim((string) $input['review_status'])));
        if (!in_array($reviewStatus, ['Approved', 'Rejected'], true)) {
            jsonResponse(false, 'Invalid review status.', ['review_status' => $reviewStatus], 422);
        }

        $targetChallengeStatus = $reviewStatus === 'Approved' ? 'Approved' : 'UnderReview';

        $pdo->beginTransaction();

        $statusUpdate = $pdo->prepare('UPDATE code_challenges SET status = :status, updated_at = :updated_at WHERE id = :id');
        $statusUpdate->execute([
            'status' => $targetChallengeStatus,
            'updated_at' => nowUtc(),
            'id' => $challengeId,
        ]);

        if ($statusUpdate->rowCount() === 0) {
            $pdo->rollBack();
            jsonResponse(false, 'Challenge not found.', [], 404);
        }

        $approval = $pdo->prepare('INSERT INTO challenge_approvals (challenge_id, reviewed_by, review_status, review_notes, reviewed_at)
            VALUES (:challenge_id, :reviewed_by, :review_status, :review_notes, :reviewed_at)
            ON DUPLICATE KEY UPDATE review_status = VALUES(review_status), review_notes = VALUES(review_notes), reviewed_at = VALUES(reviewed_at)');
        $approval->execute([
            'challenge_id' => $challengeId,
            'reviewed_by' => $auth['id'],
            'review_status' => $reviewStatus,
            'review_notes' => trim((string) ($input['review_notes'] ?? 'Reviewed from governance interface.')),
            'reviewed_at' => nowUtc(),
        ]);

        writeAudit($pdo, $auth['id'], 'review_challenge', 'code_challenges', (string) $challengeId, null, ['review_status' => $reviewStatus]);
        $pdo->commit();

        jsonResponse(true, 'Challenge review decision applied.', [
            'challenge_id' => $challengeId,
            'review_status' => $reviewStatus,
            'status' => $targetChallengeStatus,
        ]);
    }

    if ($action === 'create') {
        requireRoles($auth, contributorPlus());
        $missing = requiredFields($input, ['title', 'prompt_text']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $stmt = $pdo->prepare('INSERT INTO code_challenges (title, prompt_text, difficulty_level, status, language_scope, time_limit_ms, memory_limit_kb, kodebits_cost, created_by, created_at, updated_at)
            VALUES (:title, :prompt_text, :difficulty_level, :status, :language_scope, :time_limit_ms, :memory_limit_kb, :kodebits_cost, :created_by, :created_at, :updated_at)');
        $stmt->execute([
            'title' => trim((string) $input['title']),
            'prompt_text' => trim((string) $input['prompt_text']),
            'difficulty_level' => (string) ($input['difficulty_level'] ?? 'Beginner'),
            'status' => 'Draft',
            'language_scope' => (string) ($input['language_scope'] ?? 'Python,Java,C++'),
            'time_limit_ms' => (int) ($input['time_limit_ms'] ?? 2000),
            'memory_limit_kb' => (int) ($input['memory_limit_kb'] ?? 128000),
            'kodebits_cost' => (int) ($input['kodebits_cost'] ?? 0),
            'created_by' => $auth['id'],
            'created_at' => nowUtc(),
            'updated_at' => nowUtc(),
        ]);

        writeAudit($pdo, $auth['id'], 'create_challenge', 'code_challenges', (string) $pdo->lastInsertId(), null, ['title' => $input['title']]);
        jsonResponse(true, 'Challenge created as Draft.', ['challenge_id' => (int) $pdo->lastInsertId()]);
    }

    if ($action === 'edit') {
        requireRoles($auth, contributorPlus());
        $missing = requiredFields($input, ['challenge_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $challengeId = (int) $input['challenge_id'];
        assertOwnerOrAdmin($pdo, 'code_challenges', 'id', $challengeId, $auth, 'created_by');

        $fields = [];
        $params = ['id' => $challengeId];

        if (isset($input['title']) && trim((string) $input['title']) !== '') {
            $fields[] = 'title = :title';
            $params['title'] = trim((string) $input['title']);
        }

        if (isset($input['prompt_text']) && trim((string) $input['prompt_text']) !== '') {
            $fields[] = 'prompt_text = :prompt_text';
            $params['prompt_text'] = trim((string) $input['prompt_text']);
        }

        if (isset($input['difficulty_level']) && trim((string) $input['difficulty_level']) !== '') {
            $fields[] = 'difficulty_level = :difficulty_level';
            $params['difficulty_level'] = trim((string) $input['difficulty_level']);
        }

        if (isset($input['language_scope']) && trim((string) $input['language_scope']) !== '') {
            $fields[] = 'language_scope = :language_scope';
            $params['language_scope'] = trim((string) $input['language_scope']);
        }

        if (isset($input['time_limit_ms']) && trim((string) $input['time_limit_ms']) !== '') {
            $fields[] = 'time_limit_ms = :time_limit_ms';
            $params['time_limit_ms'] = (int) $input['time_limit_ms'];
        }

        if (isset($input['memory_limit_kb']) && trim((string) $input['memory_limit_kb']) !== '') {
            $fields[] = 'memory_limit_kb = :memory_limit_kb';
            $params['memory_limit_kb'] = (int) $input['memory_limit_kb'];
        }

        if (isset($input['kodebits_cost']) && trim((string) $input['kodebits_cost']) !== '') {
            $fields[] = 'kodebits_cost = :kodebits_cost';
            $params['kodebits_cost'] = (int) $input['kodebits_cost'];
        }

        if (isset($input['status']) && trim((string) $input['status']) !== '') {
            $fields[] = 'status = :status';
            $params['status'] = trim((string) $input['status']);
        }

        if (count($fields) === 0) {
            jsonResponse(false, 'No update fields provided.', [], 422);
        }

        $fields[] = 'updated_at = :updated_at';
        $params['updated_at'] = nowUtc();

        $sql = 'UPDATE code_challenges SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        writeAudit($pdo, $auth['id'], 'edit_challenge', 'code_challenges', (string) $challengeId, null, $params);
        jsonResponse(true, 'Challenge updated.', []);
    }

    if ($action === 'archive') {
        requireRoles($auth, contributorPlus());
        $missing = requiredFields($input, ['challenge_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $challengeId = (int) $input['challenge_id'];
        assertOwnerOrAdmin($pdo, 'code_challenges', 'id', $challengeId, $auth, 'created_by');

        $stmt = $pdo->prepare('UPDATE code_challenges SET status = :status, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'status' => 'Archived',
            'updated_at' => nowUtc(),
            'id' => $challengeId,
        ]);

        writeAudit($pdo, $auth['id'], 'archive_challenge', 'code_challenges', (string) $challengeId, null, ['status' => 'Archived']);
        jsonResponse(true, 'Challenge archived.', []);
    }

    if ($action === 'delete') {
        requireRoles($auth, contributorPlus());
        $missing = requiredFields($input, ['challenge_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $challengeId = (int) $input['challenge_id'];
        assertOwnerOrAdmin($pdo, 'code_challenges', 'id', $challengeId, $auth, 'created_by');

        $dep = $pdo->prepare('SELECT COUNT(*) FROM submissions WHERE challenge_id = :challenge_id');
        $dep->execute(['challenge_id' => $challengeId]);
        if ((int) $dep->fetchColumn() > 0) {
            jsonResponse(false, 'Challenge already has submissions. Archive instead.', [], 409);
        }

        $stmt = $pdo->prepare('UPDATE code_challenges SET status = :status, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'status' => 'Deleted',
            'updated_at' => nowUtc(),
            'id' => $challengeId,
        ]);

        writeAudit($pdo, $auth['id'], 'delete_challenge', 'code_challenges', (string) $challengeId, null, ['status' => 'Deleted']);
        jsonResponse(true, 'Challenge marked as Deleted.', []);
    }

    if ($action === 'approve') {
        requireRoles($auth, moderatorPlus());
        $missing = requiredFields($input, ['challenge_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $challengeId = (int) $input['challenge_id'];

        $pdo->beginTransaction();

        $statusUpdate = $pdo->prepare('UPDATE code_challenges SET status = :status, updated_at = :updated_at WHERE id = :id');
        $statusUpdate->execute([
            'status' => 'Approved',
            'updated_at' => nowUtc(),
            'id' => $challengeId,
        ]);

        $approval = $pdo->prepare('INSERT INTO challenge_approvals (challenge_id, reviewed_by, review_status, review_notes, reviewed_at)
            VALUES (:challenge_id, :reviewed_by, :review_status, :review_notes, :reviewed_at)
            ON DUPLICATE KEY UPDATE review_status = VALUES(review_status), review_notes = VALUES(review_notes), reviewed_at = VALUES(reviewed_at)');
        $approval->execute([
            'challenge_id' => $challengeId,
            'reviewed_by' => $auth['id'],
            'review_status' => 'Approved',
            'review_notes' => trim((string) ($input['review_notes'] ?? 'Approved by moderator/admin.')),
            'reviewed_at' => nowUtc(),
        ]);

        writeAudit($pdo, $auth['id'], 'approve_challenge', 'code_challenges', (string) $challengeId, null, ['status' => 'Approved']);

        $pdo->commit();
        jsonResponse(true, 'Challenge approved and ready for participation.', []);
    }

    if ($action === 'submit') {
        requireRoles($auth, learnerPlus());
        $missing = requiredFields($input, ['challenge_id', 'language_name', 'source_code']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $challengeId = (int) $input['challenge_id'];
        $language = trim((string) $input['language_name']);
        $sourceCode = (string) $input['source_code'];

        $challengeStmt = $pdo->prepare('SELECT id, status, kodebits_cost FROM code_challenges WHERE id = :id LIMIT 1');
        $challengeStmt->execute(['id' => $challengeId]);
        $challenge = $challengeStmt->fetch();

        if (!$challenge) {
            jsonResponse(false, 'Challenge not found.', [], 404);
        }

        if (!in_array((string) $challenge['status'], ['Approved', 'Published'], true)) {
            jsonResponse(false, 'Challenge is not open for submission.', [], 409);
        }

        $attemptState = buildChallengeAttemptState($pdo, $auth['id'], $challengeId);
        if ((bool) $attemptState['is_evaluated']) {
            jsonResponse(false, 'This challenge is already evaluated for your account. New submissions are locked.', $attemptState, 409);
        }

        if ((int) $attemptState['attempts_used'] >= 3) {
            jsonResponse(false, 'Submission limit reached (3 attempts).', [], 409);
        }

        $pdo->beginTransaction();

        $hasChallengeAccessCharge = hasDebitLedgerEntry($pdo, $auth['id'], 'challenge_access', (string) $challengeId);
        if ((int) $challenge['kodebits_cost'] > 0 && !$hasChallengeAccessCharge) {
            spendKodebits($pdo, $auth['id'], (int) $challenge['kodebits_cost'], 'challenge_access', (string) $challengeId);
        }

        $submission = $pdo->prepare('INSERT INTO submissions (user_id, challenge_id, weekly_challenge_id, language_name, source_code, context_type, submitted_at)
            VALUES (:user_id, :challenge_id, NULL, :language_name, :source_code, :context_type, :submitted_at)');
        $submission->execute([
            'user_id' => $auth['id'],
            'challenge_id' => $challengeId,
            'language_name' => $language,
            'source_code' => $sourceCode,
            'context_type' => 'STANDARD',
            'submitted_at' => nowUtc(),
        ]);

        $submissionId = (int) $pdo->lastInsertId();
        $attemptNumber = (int) $attemptState['attempts_used'] + 1;
        $autoEvaluated = $attemptNumber >= 3;
        $result = null;

        if ($autoEvaluated) {
            $result = evaluateSubmission($pdo, $submissionId, $sourceCode);
        }

        writeAudit($pdo, $auth['id'], 'submit_challenge_solution', 'submissions', (string) $submissionId, null, [
            'challenge_id' => $challengeId,
            'attempt_number' => $attemptNumber,
            'auto_evaluated' => $autoEvaluated,
        ]);
        $pdo->commit();

        $updatedState = buildChallengeAttemptState($pdo, $auth['id'], $challengeId);

        jsonResponse(true, $autoEvaluated ? 'Submission accepted and auto-evaluated (attempt 3).' : 'Submission accepted. Click Evaluate Submission when ready.', [
            'submission_id' => $submissionId,
            'attempt_number' => $attemptNumber,
            'attempts_remaining' => max(0, 3 - $attemptNumber),
            'auto_evaluated' => $autoEvaluated,
            'evaluated' => $autoEvaluated,
            'evaluation' => $result,
            'attempt_state' => $updatedState,
        ]);
    }

    if ($action === 'evaluate') {
        requireRoles($auth, learnerPlus());
        $missing = requiredFields($input, ['submission_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $submissionId = (int) $input['submission_id'];

        $ownerStmt = $pdo->prepare('SELECT id, user_id, challenge_id, context_type, source_code FROM submissions WHERE id = :id LIMIT 1');
        $ownerStmt->execute(['id' => $submissionId]);
        $submission = $ownerStmt->fetch();

        if (!$submission) {
            jsonResponse(false, 'Submission not found.', [], 404);
        }

        if ((int) $submission['user_id'] !== $auth['id'] && !in_array($auth['role'], moderatorPlus(), true)) {
            jsonResponse(false, 'You cannot evaluate another user submission.', [], 403);
        }

        if ((string) $submission['context_type'] !== 'STANDARD') {
            jsonResponse(false, 'Only standard challenge submissions can be manually evaluated from this flow.', [], 409);
        }

        $evaluationLockStmt = $pdo->prepare('SELECT s.id
            FROM submissions s
            JOIN submission_results sr ON sr.submission_id = s.id
            WHERE s.user_id = :user_id
              AND s.challenge_id = :challenge_id
              AND s.context_type = :context_type
            ORDER BY s.id DESC
            LIMIT 1');
        $evaluationLockStmt->execute([
            'user_id' => (int) $submission['user_id'],
            'challenge_id' => (int) $submission['challenge_id'],
            'context_type' => 'STANDARD',
        ]);
        $existingEvaluation = $evaluationLockStmt->fetch();

        if ($existingEvaluation) {
            if ((int) $existingEvaluation['id'] === $submissionId) {
                jsonResponse(false, 'This submission is already evaluated.', [], 409);
            }

            jsonResponse(false, 'Another submission for this challenge is already evaluated. Re-evaluation is locked for this challenge flow.', [], 409);
        }

        $pdo->beginTransaction();
        $result = evaluateSubmission($pdo, $submissionId, (string) $submission['source_code']);
        writeAudit($pdo, $auth['id'], 'evaluate_submission', 'submissions', (string) $submissionId, null, ['re_evaluated' => false]);
        $pdo->commit();

        $attemptState = buildChallengeAttemptState($pdo, (int) $submission['user_id'], (int) $submission['challenge_id']);

        jsonResponse(true, 'Submission evaluated.', [
            'submission_id' => $submissionId,
            'evaluation' => $result,
            'attempt_state' => $attemptState,
        ]);
    }

    if ($action === 'attempt_state') {
        requireRoles($auth, learnerPlus());
        $missing = requiredFields($input, ['challenge_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $challengeId = (int) $input['challenge_id'];
        if ($challengeId <= 0) {
            jsonResponse(false, 'Invalid challenge_id.', [], 422);
        }

        $state = buildChallengeAttemptState($pdo, $auth['id'], $challengeId);
        jsonResponse(true, 'Challenge attempt state loaded.', ['state' => $state]);
    }

    if ($action === 'list_feedback') {
        requireRoles($auth, learnerPlus());

        $rowsStmt = $pdo->prepare('SELECT s.id AS submission_id, s.challenge_id, c.title AS challenge_title, sr.run_status, sr.score, sr.passed_tests, sr.total_tests, sr.execution_time_ms, sr.memory_used_kb, sr.feedback_text, sr.evaluated_at
            FROM submissions s
            JOIN code_challenges c ON c.id = s.challenge_id
            JOIN submission_results sr ON sr.submission_id = s.id
            WHERE s.user_id = :user_id
            ORDER BY s.id DESC');
        $rowsStmt->execute(['user_id' => $auth['id']]);
        $rows = $rowsStmt->fetchAll();

        jsonResponse(true, 'Execution feedback loaded.', ['rows' => $rows]);
    }

    jsonResponse(false, 'Unknown challenge action.', ['action' => $action], 404);
}

function handleGamification(PDO $pdo, string $action, array $input, array $auth): void
{
    if ($action === 'leaderboard') {
        requireRoles($auth, learnerPlus());
        $rows = $pdo->query('SELECT u.id, u.full_name, u.primary_role, w.xp_points, w.kodebits_balance
            FROM users u
            JOIN wallets w ON w.user_id = u.id
            WHERE u.status = "Active"
            ORDER BY w.xp_points DESC, w.kodebits_balance DESC
            LIMIT 50')->fetchAll();
        jsonResponse(true, 'Leaderboard loaded.', ['rows' => $rows]);
    }

    if ($action === 'list_presets') {
        requireRoles($auth, learnerPlus());
        $rows = $pdo->query('SELECT id, preset_name, rules_json, rewards_json, status, created_by, created_at FROM game_presets ORDER BY id DESC')->fetchAll();
        jsonResponse(true, 'Game presets loaded.', ['rows' => $rows]);
    }

    if ($action === 'list_weekly') {
        requireRoles($auth, learnerPlus());
        $rows = $pdo->query('SELECT wc.id, wc.weekly_code, wc.challenge_id, cc.title AS challenge_title, wc.start_at, wc.end_at, wc.status, wc.configured_by, wc.created_at,
            COALESCE((SELECT COUNT(*) FROM submissions s WHERE s.weekly_challenge_id = wc.id), 0) AS submission_count,
            COALESCE((SELECT COUNT(*) FROM weekly_results wr WHERE wr.weekly_challenge_id = wc.id), 0) AS result_count
            FROM weekly_challenges wc
            JOIN code_challenges cc ON cc.id = wc.challenge_id
            ORDER BY wc.id DESC')->fetchAll();
        jsonResponse(true, 'Weekly challenge queue loaded.', ['rows' => $rows]);
    }

    if ($action === 'weekly_leaderboard') {
        requireRoles($auth, learnerPlus());

        $rows = $pdo->query('SELECT wr.weekly_challenge_id, wc.weekly_code, wc.challenge_id, cc.title AS challenge_title,
            wr.user_id, u.full_name, wr.best_submission_id, wr.final_score, wr.rank_position, wr.created_at
            FROM weekly_results wr
            JOIN weekly_challenges wc ON wc.id = wr.weekly_challenge_id
            JOIN code_challenges cc ON cc.id = wc.challenge_id
            JOIN users u ON u.id = wr.user_id
            WHERE wc.status IN ("Active", "Published", "Closed")
            ORDER BY wc.id DESC, wr.rank_position ASC, wr.final_score DESC
            LIMIT 120')->fetchAll();

        jsonResponse(true, 'Weekly leaderboard loaded.', ['rows' => $rows]);
    }

    if ($action === 'create_activity') {
        requireRoles($auth, contributorPlus());
        $missing = requiredFields($input, ['preset_id', 'activity_name', 'target_type', 'target_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $stmt = $pdo->prepare('INSERT INTO gamified_activities (preset_id, activity_name, target_type, target_id, start_at, end_at, status, created_by, created_at)
            VALUES (:preset_id, :activity_name, :target_type, :target_id, UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 7 DAY), :status, :created_by, :created_at)');
        $stmt->execute([
            'preset_id' => (int) $input['preset_id'],
            'activity_name' => trim((string) $input['activity_name']),
            'target_type' => trim((string) $input['target_type']),
            'target_id' => (int) $input['target_id'],
            'status' => 'Active',
            'created_by' => $auth['id'],
            'created_at' => nowUtc(),
        ]);

        writeAudit($pdo, $auth['id'], 'create_gamified_activity', 'gamified_activities', (string) $pdo->lastInsertId(), null, ['activity_name' => $input['activity_name']]);
        jsonResponse(true, 'Gamified activity created.', ['activity_id' => (int) $pdo->lastInsertId()]);
    }

    if ($action === 'grant_reward') {
        requireRoles($auth, moderatorPlus());
        $missing = requiredFields($input, ['user_id', 'xp_awarded', 'kodebits_awarded', 'reference_type', 'reference_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $pdo->beginTransaction();
        grantReward(
            $pdo,
            (int) $input['user_id'],
            (int) $input['xp_awarded'],
            (int) $input['kodebits_awarded'],
            (string) $input['reference_type'],
            (string) $input['reference_id']
        );
        writeAudit($pdo, $auth['id'], 'grant_rewards', 'reward_events', (string) $input['reference_id'], null, [
            'user_id' => (int) $input['user_id'],
            'xp' => (int) $input['xp_awarded'],
            'kodebits' => (int) $input['kodebits_awarded'],
        ]);
        $pdo->commit();

        jsonResponse(true, 'Rewards granted.', []);
    }

    if ($action === 'create_weekly') {
        requireRoles($auth, moderatorPlus());
        $missing = requiredFields($input, ['challenge_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $weeklyCode = 'WEEK-' . gmdate('Ymd-His');

        $stmt = $pdo->prepare('INSERT INTO weekly_challenges (weekly_code, challenge_id, start_at, end_at, rules_json, rewards_json, status, configured_by, created_at)
            VALUES (:weekly_code, :challenge_id, UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 7 DAY), :rules_json, :rewards_json, :status, :configured_by, :created_at)');
        $stmt->execute([
            'weekly_code' => $weeklyCode,
            'challenge_id' => (int) $input['challenge_id'],
            'rules_json' => json_encode(['max_attempts' => 3], JSON_UNESCAPED_SLASHES),
            'rewards_json' => json_encode(['xp' => 120, 'kb' => 20], JSON_UNESCAPED_SLASHES),
            'status' => 'Active',
            'configured_by' => $auth['id'],
            'created_at' => nowUtc(),
        ]);

        writeAudit($pdo, $auth['id'], 'configure_weekly_challenge', 'weekly_challenges', (string) $pdo->lastInsertId(), null, ['weekly_code' => $weeklyCode]);
        jsonResponse(true, 'Weekly challenge configured.', [
            'weekly_code' => $weeklyCode,
            'weekly_challenge_id' => (int) $pdo->lastInsertId(),
        ]);
    }

    if ($action === 'evaluate_weekly') {
        requireRoles($auth, moderatorPlus());
        $missing = requiredFields($input, ['weekly_challenge_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $weeklyId = (int) $input['weekly_challenge_id'];

        $sql = 'SELECT s.user_id, MAX(sr.score) AS best_score, SUBSTRING_INDEX(GROUP_CONCAT(s.id ORDER BY sr.score DESC), ",", 1) AS best_submission_id
            FROM submissions s
            JOIN submission_results sr ON sr.submission_id = s.id
            WHERE s.weekly_challenge_id = :weekly_challenge_id
            GROUP BY s.user_id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['weekly_challenge_id' => $weeklyId]);
        $results = $stmt->fetchAll();

        $pdo->beginTransaction();
        foreach ($results as $row) {
            $insert = $pdo->prepare('INSERT INTO weekly_results (weekly_challenge_id, user_id, best_submission_id, final_score, rank_position, created_at)
                VALUES (:weekly_challenge_id, :user_id, :best_submission_id, :final_score, NULL, :created_at)
                ON DUPLICATE KEY UPDATE best_submission_id = VALUES(best_submission_id), final_score = VALUES(final_score), created_at = VALUES(created_at)');
            $insert->execute([
                'weekly_challenge_id' => $weeklyId,
                'user_id' => (int) $row['user_id'],
                'best_submission_id' => (int) $row['best_submission_id'],
                'final_score' => (int) $row['best_score'],
                'created_at' => nowUtc(),
            ]);
        }

        $rankRows = $pdo->prepare('SELECT id FROM weekly_results WHERE weekly_challenge_id = :weekly_challenge_id ORDER BY final_score DESC, id ASC');
        $rankRows->execute(['weekly_challenge_id' => $weeklyId]);
        $ordered = $rankRows->fetchAll();

        $position = 1;
        foreach ($ordered as $entry) {
            $rankUpdate = $pdo->prepare('UPDATE weekly_results SET rank_position = :rank_position WHERE id = :id');
            $rankUpdate->execute([
                'rank_position' => $position,
                'id' => (int) $entry['id'],
            ]);
            $position++;
        }

        writeAudit($pdo, $auth['id'], 'evaluate_weekly_submissions', 'weekly_results', (string) $weeklyId, null, ['count' => count($results)]);
        $pdo->commit();

        jsonResponse(true, 'Weekly submissions evaluated.', ['processed_users' => count($results)]);
    }

    if ($action === 'publish_weekly') {
        requireRoles($auth, moderatorPlus());
        $missing = requiredFields($input, ['weekly_challenge_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $weeklyId = (int) $input['weekly_challenge_id'];

        $pdo->beginTransaction();

        $update = $pdo->prepare('UPDATE weekly_challenges SET status = :status WHERE id = :id');
        $update->execute([
            'status' => 'Published',
            'id' => $weeklyId,
        ]);

        $topStmt = $pdo->prepare('SELECT user_id, rank_position FROM weekly_results WHERE weekly_challenge_id = :weekly_challenge_id AND rank_position IS NOT NULL ORDER BY rank_position ASC LIMIT 3');
        $topStmt->execute(['weekly_challenge_id' => $weeklyId]);
        $top = $topStmt->fetchAll();

        foreach ($top as $winner) {
            $xp = match ((int) $winner['rank_position']) {
                1 => 100,
                2 => 70,
                default => 50,
            };
            $kb = match ((int) $winner['rank_position']) {
                1 => 20,
                2 => 12,
                default => 8,
            };
            grantReward($pdo, (int) $winner['user_id'], $xp, $kb, 'weekly_publish', $weeklyId . '-' . $winner['rank_position']);
        }

        writeAudit($pdo, $auth['id'], 'publish_weekly_results', 'weekly_challenges', (string) $weeklyId, null, ['published' => true]);
        $pdo->commit();

        jsonResponse(true, 'Weekly results published.', ['awarded_users' => count($top)]);
    }

    jsonResponse(false, 'Unknown gamification action.', ['action' => $action], 404);
}

function handleInteraction(PDO $pdo, string $action, array $input, array $auth): void
{
    if ($action === 'browse_courses') {
        requireRoles($auth, learnerPlus());
        $rows = $pdo->query("SELECT c.id, c.title, c.description, c.course_type, c.kodebits_cost, c.status,
            COALESCE((SELECT COUNT(*) FROM content_reactions cr WHERE cr.content_type = 'course' AND cr.content_id = c.id AND cr.reaction_value = 'like'), 0) AS likes_count
            FROM courses c
            WHERE c.status IN ('Published','Active')
            ORDER BY c.id DESC")->fetchAll();
        jsonResponse(true, 'Courses loaded for browsing.', ['rows' => $rows]);
    }

    if ($action === 'standalone_modules') {
        requireRoles($auth, learnerPlus());
        $rows = $pdo->query("SELECT lm.id, lm.title, lm.body_content, lm.difficulty_level, lm.kodebits_cost, lm.status,
            COALESCE((SELECT COUNT(*) FROM content_reactions cr WHERE cr.content_type = 'module' AND cr.content_id = lm.id AND cr.reaction_value = 'like'), 0) AS likes_count
            FROM learning_modules lm
            WHERE lm.module_type = 'standalone' AND lm.status IN ('Published','Active')
            ORDER BY lm.id DESC")->fetchAll();
        jsonResponse(true, 'Standalone modules loaded.', ['rows' => $rows]);
    }

    if ($action === 'browse_challenges') {
        requireRoles($auth, learnerPlus());
        $rows = $pdo->query("SELECT c.id, c.title, c.difficulty_level, c.status, c.language_scope, c.kodebits_cost,
            COALESCE((SELECT COUNT(*) FROM content_reactions cr WHERE cr.content_type = 'challenge' AND cr.content_id = c.id AND cr.reaction_value = 'like'), 0) AS likes_count
            FROM code_challenges c
            WHERE c.status IN ('Approved','Published')
            ORDER BY c.id DESC")->fetchAll();
        jsonResponse(true, 'Challenges loaded for browsing.', ['rows' => $rows]);
    }

    if ($action === 'enroll_course') {
        requireRoles($auth, learnerPlus());
        $missing = requiredFields($input, ['course_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $courseId = (int) $input['course_id'];
        $courseStmt = $pdo->prepare('SELECT id, course_type, kodebits_cost, status FROM courses WHERE id = :id LIMIT 1');
        $courseStmt->execute(['id' => $courseId]);
        $course = $courseStmt->fetch();

        if (!$course) {
            jsonResponse(false, 'Course not found.', [], 404);
        }

        if (!in_array((string) $course['status'], ['Published', 'Active'], true)) {
            jsonResponse(false, 'Course cannot be enrolled right now.', [], 409);
        }

        $pdo->beginTransaction();

        $enroll = $pdo->prepare('INSERT INTO enrollments (user_id, course_id, enrollment_status, enrolled_at, updated_at)
            VALUES (:user_id, :course_id, :enrollment_status, :enrolled_at, :updated_at)
            ON DUPLICATE KEY UPDATE enrollment_status = VALUES(enrollment_status), updated_at = VALUES(updated_at)');
        $enroll->execute([
            'user_id' => $auth['id'],
            'course_id' => $courseId,
            'enrollment_status' => 'Active',
            'enrolled_at' => nowUtc(),
            'updated_at' => nowUtc(),
        ]);

        // A fresh insert means first-time enrollment and is the only case we charge for premium.
        $isFirstEnrollment = $enroll->rowCount() === 1;
        $chargedKodebits = 0;
        if ($isFirstEnrollment && (string) $course['course_type'] === 'premium' && (int) $course['kodebits_cost'] > 0) {
            $chargedKodebits = (int) $course['kodebits_cost'];
            spendKodebits($pdo, $auth['id'], $chargedKodebits, 'course_enroll', (string) $courseId);
        }

        writeAudit($pdo, $auth['id'], 'enroll_course', 'enrollments', $auth['id'] . ':' . $courseId, null, ['course_id' => $courseId]);
        $pdo->commit();

        jsonResponse(true, 'Course enrollment successful.', [
            'course_id' => $courseId,
            'already_enrolled' => !$isFirstEnrollment,
            'charged_kodebits' => $chargedKodebits,
        ]);
    }

    if ($action === 'access_course_module') {
        requireRoles($auth, learnerPlus());
        $missing = requiredFields($input, ['course_id', 'module_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $courseId = (int) $input['course_id'];
        $moduleId = (int) $input['module_id'];

        $enrolled = $pdo->prepare('SELECT id FROM enrollments WHERE user_id = :user_id AND course_id = :course_id AND enrollment_status = :status LIMIT 1');
        $enrolled->execute([
            'user_id' => $auth['id'],
            'course_id' => $courseId,
            'status' => 'Active',
        ]);

        if (!$enrolled->fetch()) {
            jsonResponse(false, 'You are not enrolled in this course.', [], 403);
        }

        $module = $pdo->prepare('SELECT lm.id, lm.title, lm.body_content, lm.status, cm.sequence_no
            , COALESCE((SELECT COUNT(*) FROM content_reactions cr WHERE cr.content_type = "module" AND cr.content_id = lm.id AND cr.reaction_value = "like"), 0) AS likes_count
            FROM learning_modules lm
            JOIN course_modules cm ON cm.module_id = lm.id
            WHERE cm.course_id = :course_id AND cm.module_id = :module_id
            LIMIT 1');
        $module->execute([
            'course_id' => $courseId,
            'module_id' => $moduleId,
        ]);

        $row = $module->fetch();
        if (!$row) {
            jsonResponse(false, 'Module is not part of this course.', [], 404);
        }

        if (!in_array((string) $row['status'], ['Published', 'Active'], true)) {
            jsonResponse(false, 'Module is not accessible.', [], 409);
        }

        jsonResponse(true, 'Course module access granted.', ['row' => $row]);
    }

    if ($action === 'course_modules') {
        requireRoles($auth, learnerPlus());
        $missing = requiredFields($input, ['course_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $courseId = (int) $input['course_id'];

        $enrolled = $pdo->prepare('SELECT id FROM enrollments WHERE user_id = :user_id AND course_id = :course_id AND enrollment_status = :status LIMIT 1');
        $enrolled->execute([
            'user_id' => $auth['id'],
            'course_id' => $courseId,
            'status' => 'Active',
        ]);

        if (!$enrolled->fetch()) {
            jsonResponse(false, 'Enroll in the course first before viewing course modules.', [], 403);
        }

        $stmt = $pdo->prepare('SELECT lm.id, lm.title, lm.body_content, lm.difficulty_level, lm.status, lm.kodebits_cost, cm.sequence_no
            , COALESCE((SELECT COUNT(*) FROM content_reactions cr WHERE cr.content_type = "module" AND cr.content_id = lm.id AND cr.reaction_value = "like"), 0) AS likes_count
            FROM course_modules cm
            JOIN learning_modules lm ON lm.id = cm.module_id
            WHERE cm.course_id = :course_id AND lm.status IN (\'Published\',\'Active\')
            ORDER BY cm.sequence_no ASC');
        $stmt->execute(['course_id' => $courseId]);

        jsonResponse(true, 'Course modules loaded.', ['rows' => $stmt->fetchAll()]);
    }

    if ($action === 'access_standalone_module') {
        requireRoles($auth, learnerPlus());
        $missing = requiredFields($input, ['module_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $moduleId = (int) $input['module_id'];
        $moduleStmt = $pdo->prepare('SELECT lm.id, lm.title, lm.body_content, lm.status, lm.module_type, lm.kodebits_cost,
            COALESCE((SELECT COUNT(*) FROM content_reactions cr WHERE cr.content_type = "module" AND cr.content_id = lm.id AND cr.reaction_value = "like"), 0) AS likes_count
            FROM learning_modules lm
            WHERE lm.id = :id
            LIMIT 1');
        $moduleStmt->execute(['id' => $moduleId]);
        $module = $moduleStmt->fetch();

        if (!$module) {
            jsonResponse(false, 'Module not found.', [], 404);
        }

        if ((string) $module['module_type'] !== 'standalone') {
            jsonResponse(false, 'This module is not standalone.', [], 409);
        }

        if (!in_array((string) $module['status'], ['Published', 'Active'], true)) {
            jsonResponse(false, 'Module is not accessible.', [], 409);
        }

        $pdo->beginTransaction();

        $hasAccessCharge = hasDebitLedgerEntry($pdo, $auth['id'], 'standalone_module_access', (string) $moduleId);
        if ((int) $module['kodebits_cost'] > 0 && !$hasAccessCharge) {
            spendKodebits($pdo, $auth['id'], (int) $module['kodebits_cost'], 'standalone_module_access', (string) $moduleId);
        }

        $pdo->commit();

        jsonResponse(true, 'Standalone module access granted.', ['row' => $module]);
    }

    if ($action === 'participate_challenge') {
        // Reuse challenge submit flow for UC E07.
        handleChallenge($pdo, 'submit', $input, $auth);
    }

    if ($action === 'view_feedback') {
        requireRoles($auth, learnerPlus());
        $missing = requiredFields($input, ['submission_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $submissionId = (int) $input['submission_id'];

        $stmt = $pdo->prepare('SELECT s.id, s.user_id, s.challenge_id, sr.compile_status, sr.run_status, sr.score, sr.passed_tests, sr.total_tests, sr.execution_time_ms, sr.memory_used_kb, sr.feedback_text, sr.evaluated_at
            FROM submissions s
            JOIN submission_results sr ON sr.submission_id = s.id
            WHERE s.id = :submission_id
            LIMIT 1');
        $stmt->execute(['submission_id' => $submissionId]);
        $row = $stmt->fetch();

        if (!$row) {
            jsonResponse(false, 'Feedback not found.', [], 404);
        }

        if ((int) $row['user_id'] !== $auth['id'] && !in_array($auth['role'], moderatorPlus(), true)) {
            jsonResponse(false, 'You cannot view another user feedback.', [], 403);
        }

        jsonResponse(true, 'Execution feedback loaded.', ['row' => $row]);
    }

    if ($action === 'view_leaderboard') {
        requireRoles($auth, learnerPlus());
        $rows = $pdo->query('SELECT u.id, u.full_name, w.xp_points, w.kodebits_balance
            FROM users u
            JOIN wallets w ON w.user_id = u.id
            WHERE u.status = "Active"
            ORDER BY w.xp_points DESC, w.kodebits_balance DESC
            LIMIT 20')->fetchAll();
        jsonResponse(true, 'Leaderboard loaded.', ['rows' => $rows]);
    }

    if ($action === 'my_learning') {
        requireRoles($auth, learnerPlus());
        $stmt = $pdo->prepare('SELECT e.course_id, c.title AS course_title, c.description AS course_description, c.course_type, c.kodebits_cost,
            e.enrollment_status, e.enrolled_at,
            COALESCE(ROUND(AVG(mp.completion_percent), 0), 0) AS progress_percent,
            COALESCE((
                SELECT COUNT(*)
                FROM course_modules cm
                JOIN learning_modules lm ON lm.id = cm.module_id
                WHERE cm.course_id = e.course_id AND lm.status IN (\'Published\',\'Active\')
            ), 0) AS module_count,
            (
                SELECT cm.module_id
                FROM course_modules cm
                JOIN learning_modules lm ON lm.id = cm.module_id
                WHERE cm.course_id = e.course_id AND lm.status IN (\'Published\',\'Active\')
                ORDER BY cm.sequence_no ASC
                LIMIT 1
            ) AS first_module_id,
            COALESCE((
                SELECT COUNT(*)
                FROM submissions s
                WHERE s.user_id = e.user_id
            ), 0) AS challenge_submission_count
            FROM enrollments e
            JOIN courses c ON c.id = e.course_id
            LEFT JOIN module_progress mp ON mp.user_id = e.user_id AND mp.course_id = e.course_id
            WHERE e.user_id = :user_id
            GROUP BY e.course_id, c.title, c.description, c.course_type, c.kodebits_cost, e.enrollment_status, e.enrolled_at
            ORDER BY e.id DESC');
        $stmt->execute(['user_id' => $auth['id']]);
        jsonResponse(true, 'Learning progress loaded.', ['rows' => $stmt->fetchAll()]);
    }

    if ($action === 'react') {
        requireRoles($auth, learnerPlus());
        $missing = requiredFields($input, ['content_type', 'content_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $stmt = $pdo->prepare('INSERT INTO content_reactions (user_id, content_type, content_id, reaction_value, reacted_at)
            VALUES (:user_id, :content_type, :content_id, :reaction_value, :reacted_at)
            ON DUPLICATE KEY UPDATE reaction_value = VALUES(reaction_value), reacted_at = VALUES(reacted_at)');
        $stmt->execute([
            'user_id' => $auth['id'],
            'content_type' => trim((string) $input['content_type']),
            'content_id' => (int) $input['content_id'],
            'reaction_value' => trim((string) ($input['reaction_value'] ?? 'like')),
            'reacted_at' => nowUtc(),
        ]);

        jsonResponse(true, 'Reaction recorded.', []);
    }

    if ($action === 'faq_list') {
        requireRoles($auth, learnerPlus());
        $rows = $pdo->query("SELECT id, question, answer, status, created_at FROM faq_entries WHERE status = 'Published' ORDER BY id DESC")->fetchAll();
        jsonResponse(true, 'FAQ loaded.', ['rows' => $rows]);
    }

    jsonResponse(false, 'Unknown interaction action.', ['action' => $action], 404);
}

function handleFinance(PDO $pdo, string $action, array $input, array $auth): void
{
    if ($action === 'packages') {
        requireRoles($auth, learnerPlus());
        $rows = $pdo->query('SELECT id, package_name, php_amount, kodebits_amount, is_active FROM token_packages WHERE is_active = 1 ORDER BY php_amount ASC')->fetchAll();
        jsonResponse(true, 'Token packages loaded.', ['rows' => $rows]);
    }

    if ($action === 'purchase') {
        requireRoles($auth, learnerPlus());
        $missing = requiredFields($input, ['package_id', 'payment_channel', 'account_name', 'account_no']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $paymentChannel = trim((string) ($input['payment_channel'] ?? ''));
        $allowedChannels = ['Maya', 'GCash', 'Bank'];
        if (!in_array($paymentChannel, $allowedChannels, true)) {
            jsonResponse(false, 'Invalid payment channel.', ['allowed_channels' => $allowedChannels], 422);
        }

        $accountName = trim((string) ($input['account_name'] ?? ''));
        $accountNoRaw = preg_replace('/\s+/', '', (string) ($input['account_no'] ?? ''));
        if ($accountName === '' || $accountNoRaw === '') {
            jsonResponse(false, 'Payment account details are required.', [], 422);
        }

        $accountNo = preg_replace('/[^A-Za-z0-9]/', '', $accountNoRaw);
        if (strlen($accountNo) < 6) {
            jsonResponse(false, 'Payment account number is too short.', [], 422);
        }

        $maskedAccount = str_repeat('*', max(0, strlen($accountNo) - 4)) . substr($accountNo, -4);

        $packageStmt = $pdo->prepare('SELECT id, package_name, php_amount, kodebits_amount FROM token_packages WHERE id = :id AND is_active = 1 LIMIT 1');
        $packageStmt->execute(['id' => (int) $input['package_id']]);
        $package = $packageStmt->fetch();

        if (!$package) {
            jsonResponse(false, 'Token package not found.', [], 404);
        }

        $pdo->beginTransaction();

        ensureWallet($pdo, $auth['id']);

        $paymentRef = 'PAY-' . strtoupper(bin2hex(random_bytes(4)));
        $payment = $pdo->prepare('INSERT INTO payment_transactions (payment_ref, user_id, package_id, php_amount, payment_status, provider_name, provider_payload, created_at, updated_at)
            VALUES (:payment_ref, :user_id, :package_id, :php_amount, :payment_status, :provider_name, :provider_payload, :created_at, :updated_at)');
        $payment->execute([
            'payment_ref' => $paymentRef,
            'user_id' => $auth['id'],
            'package_id' => (int) $package['id'],
            'php_amount' => (float) $package['php_amount'],
            'payment_status' => 'success',
            'provider_name' => 'xendit-sandbox-sim',
            'provider_payload' => json_encode([
                'status' => 'simulated',
                'channel' => $paymentChannel,
                'account_name' => $accountName,
                'account_no_masked' => $maskedAccount,
                'xendit_reference' => trim((string) ($input['xendit_reference'] ?? '')),
            ], JSON_UNESCAPED_SLASHES),
            'created_at' => nowUtc(),
            'updated_at' => nowUtc(),
        ]);

        $wallet = $pdo->prepare('UPDATE wallets SET kodebits_balance = kodebits_balance + :kodebits, updated_at = :updated_at WHERE user_id = :user_id');
        $wallet->execute([
            'kodebits' => (int) $package['kodebits_amount'],
            'updated_at' => nowUtc(),
            'user_id' => $auth['id'],
        ]);

        $ledger = $pdo->prepare('INSERT INTO token_ledger (user_id, transaction_type, amount_kb, reference_type, reference_id, notes, created_at)
            VALUES (:user_id, :transaction_type, :amount_kb, :reference_type, :reference_id, :notes, :created_at)');
        $ledger->execute([
            'user_id' => $auth['id'],
            'transaction_type' => 'credit',
            'amount_kb' => (int) $package['kodebits_amount'],
            'reference_type' => 'payment',
            'reference_id' => $paymentRef,
            'notes' => 'Token purchase from package ' . $package['package_name'],
            'created_at' => nowUtc(),
        ]);

        createNotification($pdo, $auth['id'], 'email', 'Token purchase successful', 'Payment reference: ' . $paymentRef . ' via ' . $paymentChannel, 'sent');
        writeAudit($pdo, $auth['id'], 'purchase_tokens', 'payment_transactions', $paymentRef, null, [
            'kodebits' => (int) $package['kodebits_amount'],
            'payment_channel' => $paymentChannel,
            'account_no_masked' => $maskedAccount,
        ]);

        $pdo->commit();
        jsonResponse(true, 'Token purchase successful.', [
            'payment_ref' => $paymentRef,
            'credited_kodebits' => (int) $package['kodebits_amount'],
            'payment_channel' => $paymentChannel,
            'account_no_masked' => $maskedAccount,
        ]);
    }

    if ($action === 'use_tokens') {
        requireRoles($auth, learnerPlus());
        $missing = requiredFields($input, ['amount_kb', 'reference_type', 'reference_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $amount = (int) $input['amount_kb'];
        if ($amount <= 0) {
            jsonResponse(false, 'Amount must be positive.', [], 422);
        }

        $pdo->beginTransaction();
        spendKodebits($pdo, $auth['id'], $amount, (string) $input['reference_type'], (string) $input['reference_id']);
        writeAudit($pdo, $auth['id'], 'use_tokens', 'token_ledger', (string) $auth['id'], null, ['amount_kb' => $amount]);
        $pdo->commit();

        jsonResponse(true, 'Tokens deducted successfully.', []);
    }

    if ($action === 'earnings') {
        requireRoles($auth, [ROLE_CONTRIBUTOR, ROLE_INSTRUCTOR, ROLE_ADMIN]);

        if ($auth['role'] === ROLE_ADMIN) {
            $rows = $pdo->query('SELECT ce.id, ce.user_id, u.full_name, ce.period_label, ce.gross_php, ce.creator_share_php, ce.platform_share_php, ce.payout_status, ce.generated_at
                FROM creator_earnings ce
                JOIN users u ON u.id = ce.user_id
                ORDER BY ce.id DESC')->fetchAll();
        } else {
            $stmt = $pdo->prepare('SELECT ce.id, ce.user_id, u.full_name, ce.period_label, ce.gross_php, ce.creator_share_php, ce.platform_share_php, ce.payout_status, ce.generated_at
                FROM creator_earnings ce
                JOIN users u ON u.id = ce.user_id
                WHERE ce.user_id = :user_id
                ORDER BY ce.id DESC');
            $stmt->execute(['user_id' => $auth['id']]);
            $rows = $stmt->fetchAll();
        }

        jsonResponse(true, 'Earnings loaded.', ['rows' => $rows]);
    }

    if ($action === 'request_payout') {
        requireRoles($auth, [ROLE_CONTRIBUTOR, ROLE_INSTRUCTOR, ROLE_ADMIN]);
        $missing = requiredFields($input, ['creator_earning_id', 'request_amount_php', 'payout_channel']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $earningId = (int) $input['creator_earning_id'];
        $amount = (float) $input['request_amount_php'];

        $earningStmt = $pdo->prepare('SELECT id, user_id, creator_share_php, payout_status FROM creator_earnings WHERE id = :id LIMIT 1');
        $earningStmt->execute(['id' => $earningId]);
        $earning = $earningStmt->fetch();

        if (!$earning) {
            jsonResponse(false, 'Earnings record not found.', [], 404);
        }

        if ($auth['role'] !== ROLE_ADMIN && (int) $earning['user_id'] !== $auth['id']) {
            jsonResponse(false, 'Cannot request payout for another user.', [], 403);
        }

        if ($amount <= 0 || $amount > (float) $earning['creator_share_php']) {
            jsonResponse(false, 'Invalid payout amount.', [], 422);
        }

        $stmt = $pdo->prepare('INSERT INTO payout_requests (user_id, creator_earning_id, request_amount_php, payout_channel, payout_status, requested_at, processed_at)
            VALUES (:user_id, :creator_earning_id, :request_amount_php, :payout_channel, :payout_status, :requested_at, NULL)');
        $stmt->execute([
            'user_id' => (int) $earning['user_id'],
            'creator_earning_id' => $earningId,
            'request_amount_php' => $amount,
            'payout_channel' => trim((string) $input['payout_channel']),
            'payout_status' => 'processing',
            'requested_at' => nowUtc(),
        ]);

        createNotification($pdo, (int) $earning['user_id'], 'email', 'Payout request received', 'Your payout request is now processing.', 'sent');
        writeAudit($pdo, $auth['id'], 'request_payout', 'payout_requests', (string) $pdo->lastInsertId(), null, ['amount' => $amount]);

        jsonResponse(true, 'Payout request submitted.', ['payout_request_id' => (int) $pdo->lastInsertId()]);
    }

    if ($action === 'list_payout_requests') {
        requireRoles($auth, [ROLE_CONTRIBUTOR, ROLE_INSTRUCTOR, ROLE_ADMIN]);

        if ($auth['role'] === ROLE_ADMIN) {
            $rows = $pdo->query('SELECT pr.id, pr.user_id, u.full_name, pr.creator_earning_id, pr.request_amount_php, pr.payout_channel, pr.payout_status, pr.requested_at, pr.processed_at
                FROM payout_requests pr
                JOIN users u ON u.id = pr.user_id
                ORDER BY pr.id DESC')->fetchAll();
        } else {
            $stmt = $pdo->prepare('SELECT pr.id, pr.user_id, u.full_name, pr.creator_earning_id, pr.request_amount_php, pr.payout_channel, pr.payout_status, pr.requested_at, pr.processed_at
                FROM payout_requests pr
                JOIN users u ON u.id = pr.user_id
                WHERE pr.user_id = :user_id
                ORDER BY pr.id DESC');
            $stmt->execute(['user_id' => $auth['id']]);
            $rows = $stmt->fetchAll();
        }

        jsonResponse(true, 'Payout requests loaded.', ['rows' => $rows]);
    }

    if ($action === 'transaction_history') {
        requireRoles($auth, learnerPlus());

        $stmt = $pdo->prepare('SELECT tl.id, tl.transaction_type, tl.amount_kb, tl.reference_type, tl.reference_id, tl.notes, tl.created_at,
            pt.payment_ref, pt.php_amount, pt.payment_status
            FROM token_ledger tl
            LEFT JOIN payment_transactions pt ON pt.payment_ref = tl.reference_id AND tl.reference_type = :payment_ref_type
            WHERE tl.user_id = :user_id
            ORDER BY tl.id DESC
            LIMIT 200');
        $stmt->execute([
            'payment_ref_type' => 'payment',
            'user_id' => $auth['id'],
        ]);

        jsonResponse(true, 'Transaction history loaded.', ['rows' => $stmt->fetchAll()]);
    }

    if ($action === 'notifications') {
        requireRoles($auth, learnerPlus());

        if ($auth['role'] === ROLE_ADMIN && isset($input['target_user_id'])) {
            $stmt = $pdo->prepare('SELECT id, user_id, channel, subject, body, delivery_status, created_at, read_at
                FROM notifications
                WHERE user_id = :user_id
                ORDER BY id DESC
                LIMIT 100');
            $stmt->execute(['user_id' => (int) $input['target_user_id']]);
            $rows = $stmt->fetchAll();
        } else {
            $stmt = $pdo->prepare('SELECT id, user_id, channel, subject, body, delivery_status, created_at, read_at
                FROM notifications
                WHERE user_id = :user_id
                ORDER BY id DESC
                LIMIT 100');
            $stmt->execute(['user_id' => $auth['id']]);
            $rows = $stmt->fetchAll();
        }

        jsonResponse(true, 'Notifications loaded.', ['rows' => $rows]);
    }

    jsonResponse(false, 'Unknown finance action.', ['action' => $action], 404);
}

function handleAdmin(PDO $pdo, string $action, array $input, array $auth): void
{
    if ($action === 'list_users') {
        requireRoles($auth, moderatorPlus());
        $rows = $pdo->query('SELECT id, full_name, email, primary_role, status, email_verified, failed_login_count, lockout_until, created_at
            FROM users
            ORDER BY id DESC')->fetchAll();
        jsonResponse(true, 'User accounts loaded.', ['rows' => $rows]);
    }

    if ($action === 'contributor_requests') {
        requireRoles($auth, moderatorPlus());
        $rows = $pdo->query('SELECT cr.id, cr.user_id, u.full_name, cr.requested_role, cr.status, cr.notes, cr.reviewed_by, cr.reviewed_at, cr.created_at
            FROM contributor_requests cr
            JOIN users u ON u.id = cr.user_id
            ORDER BY cr.id DESC')->fetchAll();
        jsonResponse(true, 'Contributor requests loaded.', ['rows' => $rows]);
    }

    if ($action === 'list_credentials') {
        requireRoles($auth, moderatorPlus());
        $rows = $pdo->query('SELECT ic.id, ic.user_id, u.full_name, ic.credential_title, ic.file_url, ic.verification_status, ic.validated_by, ic.validated_at, ic.created_at
            FROM instructor_credentials ic
            JOIN users u ON u.id = ic.user_id
            ORDER BY ic.id DESC')->fetchAll();
        jsonResponse(true, 'Instructor credentials loaded.', ['rows' => $rows]);
    }

    if ($action === 'update_request') {
        requireRoles($auth, moderatorPlus());
        $missing = requiredFields($input, ['request_id', 'status']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $status = ucfirst(strtolower((string) $input['status']));
        if (!in_array($status, ['Approved', 'Rejected'], true)) {
            jsonResponse(false, 'Invalid request status.', [], 422);
        }

        $reqStmt = $pdo->prepare('SELECT id, user_id, requested_role, status FROM contributor_requests WHERE id = :id LIMIT 1');
        $reqStmt->execute(['id' => (int) $input['request_id']]);
        $request = $reqStmt->fetch();
        if (!$request) {
            jsonResponse(false, 'Contributor request not found.', [], 404);
        }

        $pdo->beginTransaction();

        $update = $pdo->prepare('UPDATE contributor_requests
            SET status = :status, reviewed_by = :reviewed_by, reviewed_at = :reviewed_at, updated_at = :updated_at
            WHERE id = :id');
        $update->execute([
            'status' => $status,
            'reviewed_by' => $auth['id'],
            'reviewed_at' => nowUtc(),
            'updated_at' => nowUtc(),
            'id' => (int) $request['id'],
        ]);

        if ($status === 'Approved') {
            $roleUpdate = $pdo->prepare('UPDATE users SET primary_role = :primary_role, updated_at = :updated_at WHERE id = :id');
            $roleUpdate->execute([
                'primary_role' => (string) $request['requested_role'],
                'updated_at' => nowUtc(),
                'id' => (int) $request['user_id'],
            ]);
        }

        createNotification(
            $pdo,
            (int) $request['user_id'],
            'email',
            'Contributor request update',
            'Your request is now ' . $status . '.',
            'sent'
        );

        writeAudit($pdo, $auth['id'], 'approve_contributor_request', 'contributor_requests', (string) $request['id'], null, ['status' => $status]);

        $pdo->commit();
        jsonResponse(true, 'Contributor request updated.', []);
    }

    if ($action === 'verify_instructor_credentials') {
        requireRoles($auth, moderatorPlus());
        $missing = requiredFields($input, ['credential_id', 'verification_status']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $status = ucfirst(strtolower((string) $input['verification_status']));
        if (!in_array($status, ['Accepted', 'Rejected'], true)) {
            jsonResponse(false, 'Invalid verification status.', [], 422);
        }

        $credStmt = $pdo->prepare('SELECT id, user_id FROM instructor_credentials WHERE id = :id LIMIT 1');
        $credStmt->execute(['id' => (int) $input['credential_id']]);
        $cred = $credStmt->fetch();
        if (!$cred) {
            jsonResponse(false, 'Credential record not found.', [], 404);
        }

        $pdo->beginTransaction();

        $update = $pdo->prepare('UPDATE instructor_credentials
            SET verification_status = :verification_status, validated_by = :validated_by, validated_at = :validated_at
            WHERE id = :id');
        $update->execute([
            'verification_status' => $status,
            'validated_by' => $auth['id'],
            'validated_at' => nowUtc(),
            'id' => (int) $cred['id'],
        ]);

        if ($status === 'Accepted') {
            $promote = $pdo->prepare('UPDATE users SET primary_role = :primary_role, updated_at = :updated_at WHERE id = :id');
            $promote->execute([
                'primary_role' => ROLE_INSTRUCTOR,
                'updated_at' => nowUtc(),
                'id' => (int) $cred['user_id'],
            ]);
        }

        createNotification(
            $pdo,
            (int) $cred['user_id'],
            'email',
            'Instructor credential result',
            'Your instructor credential review result: ' . $status,
            'sent'
        );

        writeAudit($pdo, $auth['id'], 'verify_instructor_credentials', 'instructor_credentials', (string) $cred['id'], null, ['status' => $status]);

        $pdo->commit();
        jsonResponse(true, 'Instructor credential review updated.', []);
    }

    if ($action === 'list_reports') {
        requireRoles($auth, moderatorPlus());
        $rows = $pdo->query('SELECT id, reporter_user_id, content_type, content_id, report_reason, report_status, created_at FROM content_reports ORDER BY id DESC')->fetchAll();
        jsonResponse(true, 'Content reports loaded.', ['rows' => $rows]);
    }

    if ($action === 'moderate_content') {
        requireRoles($auth, moderatorPlus());
        $missing = requiredFields($input, ['target_type', 'target_id', 'action_type']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $reportId = isset($input['report_id']) ? (int) $input['report_id'] : null;
        $targetType = trim((string) $input['target_type']);
        $targetId = (int) $input['target_id'];
        $actionType = trim((string) $input['action_type']);

        if ($reportId !== null && $reportId > 0) {
            $reportUpdate = $pdo->prepare('UPDATE content_reports SET report_status = :report_status WHERE id = :id');
            $reportUpdate->execute([
                'report_status' => 'Reviewed',
                'id' => $reportId,
            ]);
        }

        if (in_array($targetType, ['course', 'module', 'challenge'], true)) {
            $table = $targetType === 'course' ? 'courses' : ($targetType === 'module' ? 'learning_modules' : 'code_challenges');
            $status = in_array($actionType, ['archive', 'remove'], true) ? 'Archived' : 'Published';
            $update = $pdo->prepare('UPDATE ' . $table . ' SET status = :status, updated_at = :updated_at WHERE id = :id');
            $update->execute([
                'status' => $status,
                'updated_at' => nowUtc(),
                'id' => $targetId,
            ]);
        }

        if ($targetType === 'user' && in_array($actionType, ['suspend', 'reinstate'], true)) {
            $status = $actionType === 'suspend' ? 'Suspended' : 'Active';
            $updateUser = $pdo->prepare('UPDATE users SET status = :status, updated_at = :updated_at WHERE id = :id');
            $updateUser->execute([
                'status' => $status,
                'updated_at' => nowUtc(),
                'id' => $targetId,
            ]);
        }

        $log = $pdo->prepare('INSERT INTO moderation_actions (report_id, moderator_user_id, action_type, target_type, target_id, notes, created_at)
            VALUES (:report_id, :moderator_user_id, :action_type, :target_type, :target_id, :notes, :created_at)');
        $log->execute([
            'report_id' => $reportId,
            'moderator_user_id' => $auth['id'],
            'action_type' => $actionType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'notes' => trim((string) ($input['notes'] ?? 'Moderation action applied.')),
            'created_at' => nowUtc(),
        ]);

        writeAudit($pdo, $auth['id'], 'moderate_content', 'moderation_actions', (string) $pdo->lastInsertId(), null, ['target_type' => $targetType, 'target_id' => $targetId]);
        jsonResponse(true, 'Moderation action recorded.', []);
    }

    if ($action === 'system_reports') {
        requireRoles($auth, adminOnly(), 'Only administrators can access system reports.');

        $report = [
            'active_users' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Active'")->fetchColumn(),
            'suspended_users' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Suspended'")->fetchColumn(),
            'published_courses' => (int) $pdo->query("SELECT COUNT(*) FROM courses WHERE status IN ('Published','Active')")->fetchColumn(),
            'published_modules' => (int) $pdo->query("SELECT COUNT(*) FROM learning_modules WHERE status IN ('Published','Active')")->fetchColumn(),
            'approved_challenges' => (int) $pdo->query("SELECT COUNT(*) FROM code_challenges WHERE status IN ('Approved','Published')")->fetchColumn(),
            'total_payments_php' => (float) $pdo->query("SELECT COALESCE(SUM(php_amount),0) FROM payment_transactions WHERE payment_status = 'success'")->fetchColumn(),
            'total_rewards' => (int) $pdo->query('SELECT COUNT(*) FROM reward_events')->fetchColumn(),
            'open_content_reports' => (int) $pdo->query("SELECT COUNT(*) FROM content_reports WHERE report_status = 'Open'")->fetchColumn(),
        ];

        jsonResponse(true, 'System report generated.', ['report' => $report]);
    }

    if ($action === 'suspend_user') {
        requireRoles($auth, moderatorPlus());
        $missing = requiredFields($input, ['user_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $targetUserId = (int) $input['user_id'];
        if ($targetUserId === $auth['id']) {
            jsonResponse(false, 'You cannot suspend your own account.', [], 409);
        }

        $stmt = $pdo->prepare('UPDATE users SET status = :status, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'status' => 'Suspended',
            'updated_at' => nowUtc(),
            'id' => $targetUserId,
        ]);

        createNotification($pdo, $targetUserId, 'email', 'Account suspended', trim((string) ($input['notes'] ?? 'Your account has been suspended by moderation.')), 'sent');
        writeAudit($pdo, $auth['id'], 'suspend_user', 'users', (string) $targetUserId, null, ['status' => 'Suspended']);

        jsonResponse(true, 'User account suspended.', []);
    }

    if ($action === 'reinstate_user') {
        requireRoles($auth, moderatorPlus());
        $missing = requiredFields($input, ['user_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $targetUserId = (int) $input['user_id'];

        $stmt = $pdo->prepare('UPDATE users SET status = :status, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'status' => 'Active',
            'updated_at' => nowUtc(),
            'id' => $targetUserId,
        ]);

        createNotification($pdo, $targetUserId, 'email', 'Account reinstated', 'Your account has been reinstated.', 'sent');
        writeAudit($pdo, $auth['id'], 'reinstate_user', 'users', (string) $targetUserId, null, ['status' => 'Active']);

        jsonResponse(true, 'User account reinstated.', []);
    }

    if ($action === 'update_user_account') {
        requireRoles($auth, adminOnly());
        $missing = requiredFields($input, ['user_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $userId = (int) $input['user_id'];
        $fields = [];
        $params = ['id' => $userId];

        if (isset($input['full_name']) && trim((string) $input['full_name']) !== '') {
            $fields[] = 'full_name = :full_name';
            $params['full_name'] = trim((string) $input['full_name']);
        }

        if (isset($input['primary_role']) && trim((string) $input['primary_role']) !== '') {
            $fields[] = 'primary_role = :primary_role';
            $params['primary_role'] = trim((string) $input['primary_role']);
        }

        if (isset($input['status']) && trim((string) $input['status']) !== '') {
            $fields[] = 'status = :status';
            $params['status'] = trim((string) $input['status']);
        }

        if (count($fields) === 0) {
            jsonResponse(false, 'No fields to update.', [], 422);
        }

        $fields[] = 'updated_at = :updated_at';
        $params['updated_at'] = nowUtc();

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        writeAudit($pdo, $auth['id'], 'update_user_account', 'users', (string) $userId, null, $params);
        jsonResponse(true, 'User account updated.', []);
    }

    if ($action === 'create_game_preset') {
        requireRoles($auth, adminOnly());
        $missing = requiredFields($input, ['preset_name']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $rules = parseJsonObject((string) ($input['rules_json'] ?? '{}'));
        $rewards = parseJsonObject((string) ($input['rewards_json'] ?? '{}'));

        $stmt = $pdo->prepare('INSERT INTO game_presets (preset_name, rules_json, rewards_json, status, created_by, created_at, updated_at)
            VALUES (:preset_name, :rules_json, :rewards_json, :status, :created_by, :created_at, :updated_at)');
        $stmt->execute([
            'preset_name' => trim((string) $input['preset_name']),
            'rules_json' => json_encode($rules, JSON_UNESCAPED_SLASHES),
            'rewards_json' => json_encode($rewards, JSON_UNESCAPED_SLASHES),
            'status' => 'Active',
            'created_by' => $auth['id'],
            'created_at' => nowUtc(),
            'updated_at' => nowUtc(),
        ]);

        writeAudit($pdo, $auth['id'], 'create_game_preset', 'game_presets', (string) $pdo->lastInsertId(), null, ['preset_name' => $input['preset_name']]);
        jsonResponse(true, 'Game preset created.', ['preset_id' => (int) $pdo->lastInsertId()]);
    }

    if ($action === 'update_game_preset') {
        requireRoles($auth, adminOnly());
        $missing = requiredFields($input, ['preset_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $presetId = (int) $input['preset_id'];

        $fields = [];
        $params = ['id' => $presetId];

        if (isset($input['preset_name']) && trim((string) $input['preset_name']) !== '') {
            $fields[] = 'preset_name = :preset_name';
            $params['preset_name'] = trim((string) $input['preset_name']);
        }

        if (isset($input['rules_json']) && trim((string) $input['rules_json']) !== '') {
            $fields[] = 'rules_json = :rules_json';
            $params['rules_json'] = json_encode(parseJsonObject((string) $input['rules_json']), JSON_UNESCAPED_SLASHES);
        }

        if (isset($input['rewards_json']) && trim((string) $input['rewards_json']) !== '') {
            $fields[] = 'rewards_json = :rewards_json';
            $params['rewards_json'] = json_encode(parseJsonObject((string) $input['rewards_json']), JSON_UNESCAPED_SLASHES);
        }

        if (count($fields) === 0) {
            jsonResponse(false, 'No update fields provided.', [], 422);
        }

        $fields[] = 'updated_at = :updated_at';
        $params['updated_at'] = nowUtc();

        $sql = 'UPDATE game_presets SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        writeAudit($pdo, $auth['id'], 'update_game_preset', 'game_presets', (string) $presetId, null, $params);
        jsonResponse(true, 'Game preset updated.', []);
    }

    if ($action === 'delete_game_preset') {
        requireRoles($auth, adminOnly());
        $missing = requiredFields($input, ['preset_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $presetId = (int) $input['preset_id'];

        $inUse = $pdo->prepare("SELECT COUNT(*) FROM gamified_activities WHERE preset_id = :preset_id AND status IN ('Active','Draft')");
        $inUse->execute(['preset_id' => $presetId]);

        $status = 'Inactive';
        $stmt = $pdo->prepare('UPDATE game_presets SET status = :status, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'updated_at' => nowUtc(),
            'id' => $presetId,
        ]);

        writeAudit($pdo, $auth['id'], 'delete_game_preset', 'game_presets', (string) $presetId, null, ['status' => $status]);
        jsonResponse(true, 'Game preset deactivated.', []);
    }

    if ($action === 'create_faq') {
        requireRoles($auth, adminOnly());
        $missing = requiredFields($input, ['question', 'answer']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $stmt = $pdo->prepare('INSERT INTO faq_entries (question, answer, status, created_by, created_at, updated_at)
            VALUES (:question, :answer, :status, :created_by, :created_at, :updated_at)');
        $stmt->execute([
            'question' => trim((string) $input['question']),
            'answer' => trim((string) $input['answer']),
            'status' => 'Published',
            'created_by' => $auth['id'],
            'created_at' => nowUtc(),
            'updated_at' => nowUtc(),
        ]);

        writeAudit($pdo, $auth['id'], 'create_faq', 'faq_entries', (string) $pdo->lastInsertId(), null, ['question' => $input['question']]);
        jsonResponse(true, 'FAQ entry created.', ['faq_id' => (int) $pdo->lastInsertId()]);
    }

    if ($action === 'update_faq') {
        requireRoles($auth, adminOnly());
        $missing = requiredFields($input, ['faq_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $faqId = (int) $input['faq_id'];
        $fields = [];
        $params = ['id' => $faqId];

        if (isset($input['question']) && trim((string) $input['question']) !== '') {
            $fields[] = 'question = :question';
            $params['question'] = trim((string) $input['question']);
        }

        if (isset($input['answer']) && trim((string) $input['answer']) !== '') {
            $fields[] = 'answer = :answer';
            $params['answer'] = trim((string) $input['answer']);
        }

        if (count($fields) === 0) {
            jsonResponse(false, 'No update fields provided.', [], 422);
        }

        $fields[] = 'updated_at = :updated_at';
        $params['updated_at'] = nowUtc();

        $sql = 'UPDATE faq_entries SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        writeAudit($pdo, $auth['id'], 'update_faq', 'faq_entries', (string) $faqId, null, $params);
        jsonResponse(true, 'FAQ entry updated.', []);
    }

    if ($action === 'delete_faq') {
        requireRoles($auth, adminOnly());
        $missing = requiredFields($input, ['faq_id']);
        if ($missing !== null) {
            jsonResponse(false, 'Missing field.', ['field' => $missing], 422);
        }

        $faqId = (int) $input['faq_id'];
        $stmt = $pdo->prepare('UPDATE faq_entries SET status = :status, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'status' => 'Archived',
            'updated_at' => nowUtc(),
            'id' => $faqId,
        ]);

        writeAudit($pdo, $auth['id'], 'delete_faq', 'faq_entries', (string) $faqId, null, ['status' => 'Archived']);
        jsonResponse(true, 'FAQ entry archived.', []);
    }

    jsonResponse(false, 'Unknown admin action.', ['action' => $action], 404);
}

function registerFailedAttempt(PDO $pdo, string $email, ?int $userId): void
{
    $pdo->beginTransaction();

    $attempt = $pdo->prepare('INSERT INTO login_attempts (email, success, ip_address, attempt_at)
        VALUES (:email, 0, :ip_address, :attempt_at)');
    $attempt->execute([
        'email' => $email,
        'ip_address' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        'attempt_at' => nowUtc(),
    ]);

    if ($userId !== null) {
        $countStmt = $pdo->prepare('SELECT failed_login_count FROM users WHERE id = :id FOR UPDATE');
        $countStmt->execute(['id' => $userId]);
        $user = $countStmt->fetch();

        if ($user) {
            $newCount = (int) $user['failed_login_count'] + 1;
            $lockoutUntil = null;

            if ($newCount >= 9) {
                $lockoutUntil = gmdate('Y-m-d H:i:s', time() + 86400);
            } elseif ($newCount >= 6) {
                $lockoutUntil = gmdate('Y-m-d H:i:s', time() + 3600);
            } elseif ($newCount >= 3) {
                $lockoutUntil = gmdate('Y-m-d H:i:s', time() + 900);
            }

            $update = $pdo->prepare('UPDATE users SET failed_login_count = :failed_login_count, lockout_until = :lockout_until, updated_at = :updated_at WHERE id = :id');
            $update->execute([
                'failed_login_count' => $newCount,
                'lockout_until' => $lockoutUntil,
                'updated_at' => nowUtc(),
                'id' => $userId,
            ]);
        }
    }

    $pdo->commit();
}

function buildChallengeAttemptState(PDO $pdo, int $userId, int $challengeId): array
{
    $attemptsStmt = $pdo->prepare('SELECT COUNT(*) FROM submissions
        WHERE user_id = :user_id
          AND challenge_id = :challenge_id
          AND context_type = :context_type');
    $attemptsStmt->execute([
        'user_id' => $userId,
        'challenge_id' => $challengeId,
        'context_type' => 'STANDARD',
    ]);
    $attemptsUsed = (int) $attemptsStmt->fetchColumn();

    $latestSubmissionStmt = $pdo->prepare('SELECT id, submitted_at
        FROM submissions
        WHERE user_id = :user_id
          AND challenge_id = :challenge_id
          AND context_type = :context_type
        ORDER BY id DESC
        LIMIT 1');
    $latestSubmissionStmt->execute([
        'user_id' => $userId,
        'challenge_id' => $challengeId,
        'context_type' => 'STANDARD',
    ]);
    $latestSubmission = $latestSubmissionStmt->fetch();

    $evaluatedStmt = $pdo->prepare('SELECT s.id, sr.evaluated_at
        FROM submissions s
        JOIN submission_results sr ON sr.submission_id = s.id
        WHERE s.user_id = :user_id
          AND s.challenge_id = :challenge_id
          AND s.context_type = :context_type
        ORDER BY s.id DESC
        LIMIT 1');
    $evaluatedStmt->execute([
        'user_id' => $userId,
        'challenge_id' => $challengeId,
        'context_type' => 'STANDARD',
    ]);
    $evaluatedRow = $evaluatedStmt->fetch();

    $pendingStmt = $pdo->prepare('SELECT s.id
        FROM submissions s
        LEFT JOIN submission_results sr ON sr.submission_id = s.id
        WHERE s.user_id = :user_id
          AND s.challenge_id = :challenge_id
          AND s.context_type = :context_type
          AND sr.submission_id IS NULL
        ORDER BY s.id DESC
        LIMIT 1');
    $pendingStmt->execute([
        'user_id' => $userId,
        'challenge_id' => $challengeId,
        'context_type' => 'STANDARD',
    ]);
    $pendingRow = $pendingStmt->fetch();

    $isEvaluated = $evaluatedRow !== false && $evaluatedRow !== null;
    $attemptsRemaining = max(0, 3 - $attemptsUsed);
    $pendingSubmissionId = $isEvaluated ? null : ($pendingRow ? (int) $pendingRow['id'] : null);
    $canSubmit = !$isEvaluated && $attemptsUsed < 3;
    $canEvaluate = !$isEvaluated && $pendingSubmissionId !== null;

    $lockReason = null;
    if ($isEvaluated) {
        $lockReason = 'Evaluation is already completed for this challenge. New submissions are locked.';
    } elseif ($attemptsUsed >= 3) {
        $lockReason = 'Submission limit reached (3 attempts).';
    }

    return [
        'challenge_id' => $challengeId,
        'attempts_used' => $attemptsUsed,
        'attempts_remaining' => $attemptsRemaining,
        'max_attempts' => 3,
        'is_evaluated' => $isEvaluated,
        'can_submit' => $canSubmit,
        'can_evaluate' => $canEvaluate,
        'pending_submission_id' => $pendingSubmissionId,
        'latest_submission_id' => $latestSubmission ? (int) $latestSubmission['id'] : null,
        'latest_submitted_at' => $latestSubmission['submitted_at'] ?? null,
        'evaluated_submission_id' => $evaluatedRow ? (int) $evaluatedRow['id'] : null,
        'evaluated_at' => $evaluatedRow['evaluated_at'] ?? null,
        'lock_reason' => $lockReason,
    ];
}

function evaluateSubmission(PDO $pdo, int $submissionId, string $sourceCode): array
{
    $passed = (stripos($sourceCode, 'return') !== false || stripos($sourceCode, 'print') !== false);
    $score = $passed ? 100 : 45;
    $runStatus = $passed ? 'Passed' : 'Failed';

    $stmt = $pdo->prepare('INSERT INTO submission_results (submission_id, compile_status, run_status, score, passed_tests, total_tests, execution_time_ms, memory_used_kb, feedback_text, evaluated_at)
        VALUES (:submission_id, :compile_status, :run_status, :score, :passed_tests, :total_tests, :execution_time_ms, :memory_used_kb, :feedback_text, :evaluated_at)
        ON DUPLICATE KEY UPDATE compile_status = VALUES(compile_status), run_status = VALUES(run_status), score = VALUES(score), passed_tests = VALUES(passed_tests), total_tests = VALUES(total_tests), execution_time_ms = VALUES(execution_time_ms), memory_used_kb = VALUES(memory_used_kb), feedback_text = VALUES(feedback_text), evaluated_at = VALUES(evaluated_at)');
    $stmt->execute([
        'submission_id' => $submissionId,
        'compile_status' => 'ok',
        'run_status' => $runStatus,
        'score' => $score,
        'passed_tests' => $passed ? 3 : 1,
        'total_tests' => 3,
        'execution_time_ms' => $passed ? 820 : 630,
        'memory_used_kb' => $passed ? 5320 : 4024,
        'feedback_text' => $passed ? 'All checks passed in prototype mode.' : 'Failed one or more checks. Improve your logic and retry.',
        'evaluated_at' => nowUtc(),
    ]);

    $ownerStmt = $pdo->prepare('SELECT user_id FROM submissions WHERE id = :id LIMIT 1');
    $ownerStmt->execute(['id' => $submissionId]);
    $owner = $ownerStmt->fetch();

    if ($owner && $passed) {
        grantReward($pdo, (int) $owner['user_id'], 20, 3, 'challenge_pass', (string) $submissionId);
    }

    return [
        'run_status' => $runStatus,
        'score' => $score,
        'passed_tests' => $passed ? 3 : 1,
        'total_tests' => 3,
    ];
}

function assertOwnerOrAdmin(PDO $pdo, string $table, string $idColumn, int $idValue, array $auth, string $ownerColumn = 'created_by'): void
{
    if ($auth['role'] === ROLE_ADMIN) {
        return;
    }

    $sql = 'SELECT ' . $ownerColumn . ' AS owner_id FROM ' . $table . ' WHERE ' . $idColumn . ' = :id LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $idValue]);
    $row = $stmt->fetch();

    if (!$row) {
        jsonResponse(false, 'Record not found.', ['table' => $table, 'id' => $idValue], 404);
    }

    if ((int) $row['owner_id'] !== $auth['id']) {
        jsonResponse(false, 'You can only modify your own records.', [], 403);
    }
}

function ensureWallet(PDO $pdo, int $userId): void
{
    $check = $pdo->prepare('SELECT user_id FROM wallets WHERE user_id = :user_id LIMIT 1');
    $check->execute(['user_id' => $userId]);
    if ($check->fetch()) {
        return;
    }

    $insert = $pdo->prepare('INSERT INTO wallets (user_id, kodebits_balance, xp_points, updated_at) VALUES (:user_id, 0, 0, :updated_at)');
    $insert->execute([
        'user_id' => $userId,
        'updated_at' => nowUtc(),
    ]);
}

function hasDebitLedgerEntry(PDO $pdo, int $userId, string $referenceType, string $referenceId): bool
{
    $stmt = $pdo->prepare('SELECT id FROM token_ledger
        WHERE user_id = :user_id
          AND transaction_type = :transaction_type
          AND reference_type = :reference_type
          AND reference_id = :reference_id
        LIMIT 1');
    $stmt->execute([
        'user_id' => $userId,
        'transaction_type' => 'debit',
        'reference_type' => $referenceType,
        'reference_id' => $referenceId,
    ]);

    return (bool) $stmt->fetch();
}

function spendKodebits(PDO $pdo, int $userId, int $amount, string $referenceType, string $referenceId): void
{
    ensureWallet($pdo, $userId);

    $lock = $pdo->prepare('SELECT kodebits_balance FROM wallets WHERE user_id = :user_id FOR UPDATE');
    $lock->execute(['user_id' => $userId]);
    $wallet = $lock->fetch();

    if (!$wallet) {
        throw new RuntimeException('Wallet not found.');
    }

    $balance = (int) $wallet['kodebits_balance'];
    if ($balance < $amount) {
        throw new RuntimeException('Insufficient KodeBits.');
    }

    $update = $pdo->prepare('UPDATE wallets SET kodebits_balance = kodebits_balance - :amount, updated_at = :updated_at WHERE user_id = :user_id');
    $update->execute([
        'amount' => $amount,
        'updated_at' => nowUtc(),
        'user_id' => $userId,
    ]);

    $ledger = $pdo->prepare('INSERT INTO token_ledger (user_id, transaction_type, amount_kb, reference_type, reference_id, notes, created_at)
        VALUES (:user_id, :transaction_type, :amount_kb, :reference_type, :reference_id, :notes, :created_at)');
    $ledger->execute([
        'user_id' => $userId,
        'transaction_type' => 'debit',
        'amount_kb' => $amount,
        'reference_type' => $referenceType,
        'reference_id' => $referenceId,
        'notes' => 'Token debit',
        'created_at' => nowUtc(),
    ]);
}

function grantReward(PDO $pdo, int $userId, int $xp, int $kb, string $referenceType, string $referenceId): void
{
    ensureWallet($pdo, $userId);

    $reward = $pdo->prepare('INSERT IGNORE INTO reward_events (user_id, xp_awarded, kodebits_awarded, reference_type, reference_id, created_at)
        VALUES (:user_id, :xp_awarded, :kodebits_awarded, :reference_type, :reference_id, :created_at)');
    $reward->execute([
        'user_id' => $userId,
        'xp_awarded' => $xp,
        'kodebits_awarded' => $kb,
        'reference_type' => $referenceType,
        'reference_id' => $referenceId,
        'created_at' => nowUtc(),
    ]);

    if ($reward->rowCount() === 0) {
        return;
    }

    $wallet = $pdo->prepare('UPDATE wallets SET xp_points = xp_points + :xp, kodebits_balance = kodebits_balance + :kb, updated_at = :updated_at WHERE user_id = :user_id');
    $wallet->execute([
        'xp' => $xp,
        'kb' => $kb,
        'updated_at' => nowUtc(),
        'user_id' => $userId,
    ]);

    $ledger = $pdo->prepare('INSERT INTO token_ledger (user_id, transaction_type, amount_kb, reference_type, reference_id, notes, created_at)
        VALUES (:user_id, :transaction_type, :amount_kb, :reference_type, :reference_id, :notes, :created_at)');
    $ledger->execute([
        'user_id' => $userId,
        'transaction_type' => 'credit',
        'amount_kb' => $kb,
        'reference_type' => $referenceType,
        'reference_id' => $referenceId,
        'notes' => 'Reward credit',
        'created_at' => nowUtc(),
    ]);
}

function createNotification(PDO $pdo, int $userId, string $channel, string $subject, string $body, string $deliveryStatus): void
{
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, channel, subject, body, delivery_status, created_at)
        VALUES (:user_id, :channel, :subject, :body, :delivery_status, :created_at)');
    $stmt->execute([
        'user_id' => $userId,
        'channel' => $channel,
        'subject' => $subject,
        'body' => $body,
        'delivery_status' => $deliveryStatus,
        'created_at' => nowUtc(),
    ]);
}

function writeAudit(PDO $pdo, ?int $actorUserId, string $actionName, string $entityType, string $entityId, $oldData, $newData): void
{
    try {
        $stmt = $pdo->prepare('INSERT INTO audit_logs (actor_user_id, action_name, entity_type, entity_id, old_data, new_data, created_at)
            VALUES (:actor_user_id, :action_name, :entity_type, :entity_id, :old_data, :new_data, :created_at)');
        $stmt->execute([
            'actor_user_id' => $actorUserId,
            'action_name' => $actionName,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_data' => $oldData === null ? null : json_encode($oldData, JSON_UNESCAPED_SLASHES),
            'new_data' => $newData === null ? null : json_encode($newData, JSON_UNESCAPED_SLASHES),
            'created_at' => nowUtc(),
        ]);
    } catch (Throwable $e) {
        // Keep prototype flow resilient even if audit logging fails.
    }
}

function revokeAllSessions(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('UPDATE user_sessions SET revoked_at = :revoked_at WHERE user_id = :user_id AND revoked_at IS NULL');
    $stmt->execute([
        'revoked_at' => nowUtc(),
        'user_id' => $userId,
    ]);
}

function parseJsonObject(string $raw): array
{
    $trimmed = trim($raw);
    if ($trimmed === '') {
        return [];
    }

    $decoded = json_decode($trimmed, true);
    if (!is_array($decoded)) {
        return [];
    }

    return $decoded;
}
