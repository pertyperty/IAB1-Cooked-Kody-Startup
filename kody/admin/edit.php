<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

$crud_module = $_GET['module'] ?? null;
$id = $_GET['id'] ?? null;

if (empty($crud_module) || empty($id)) {
    echo '<p class="notice">Missing module or id.</p>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$table = resolveCrudTableName(null, $crud_module);
$definition = getCrudTableDefinition($table);

if (!$table || !$definition) {
    echo '<p class="notice">Unknown module.</p>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$pdo = connectDB();
$pk = $definition['primary_key'];
$stmt = $pdo->prepare('SELECT * FROM ' . $table . ' WHERE `' . $pk . '` = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();

if (!$row) {
    echo '<p class="notice">Record not found.</p>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

?>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <h3>Edit</h3>
        <a href="index.php">Dashboard</a>
        <a href="<?php echo htmlspecialchars($crud_module); ?>">Back to list</a>
    </aside>

    <div class="admin-main">
        <div class="page-hero admin-hero">
            <h2>Edit <?php echo htmlspecialchars($table); ?> #<?php echo htmlspecialchars($row[$pk]); ?></h2>
            <p>Update the selected record while keeping system-managed fields read-only.</p>
        </div>

        <section class="subtle-panel">
            <div class="section-heading">
                <h3>Edit form</h3>
                <span class="badge info">ID <?php echo htmlspecialchars($row[$pk]); ?></span>
            </div>
            <form method="post" action="../actions/update.php" enctype="multipart/form-data">
                <input type="hidden" name="module" value="<?php echo htmlspecialchars($crud_module); ?>">
                <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($row[$pk]); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCsrfToken()); ?>">

                <div class="form-grid">
                    <?php
                    foreach ($definition['columns'] as $col) {
                        if ($col === $pk) continue;
                        if (isCrudAutoManagedColumn($table, $col)) continue;
                        if ($table === 'users' && $col === 'password_hash') {
                            echo '<label>Password<input type="password" name="password_hash" placeholder="Leave blank to keep current password"></label>';
                            continue;
                        }
                        echo renderCrudField($table, $col, $row[$col] ?? null);
                    }
                    ?>
                </div>

                <div class="page-actions mt-1">
                    <button type="submit" class="primary">Save changes</button>
                </div>
            </form>
        </section>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
