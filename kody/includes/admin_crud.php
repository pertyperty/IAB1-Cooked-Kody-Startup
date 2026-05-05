<?php
// Generic admin CRUD renderer. Expects `$crud_module` to be set to the module filename (e.g. 'users_crud.php').
require_once __DIR__ . '/functions.php';

if (empty($crud_module)) {
    echo '<p class="notice">No CRUD module defined.</p>';
    return;
}

$table = resolveCrudTableName(null, $crud_module);
$definition = getCrudTableDefinition($table);

if (!$table || !$definition) {
    echo '<p class="notice">Unable to resolve table for this CRUD module.</p>';
    return;
}

$flash = getCrudFlashMessage();
if ($flash) {
    $cls = $flash['type'] === 'success' ? 'notice success' : 'notice error';
    echo '<p class="' . $cls . '">' . htmlspecialchars($flash['message']) . '</p>';
}

// Begin admin layout
echo '<div class="admin-layout">';

// Sidebar
echo '<aside class="admin-sidebar">';
echo '<h3>Admin</h3>';
$modules = getCrudModuleTableMap();
foreach ($modules as $mod => $tbl) {
    $active = $mod === $crud_module ? ' style="font-weight:700;"' : '';
    echo '<a href="' . '../admin/' . htmlspecialchars($mod) . '"' . $active . '>' . htmlspecialchars(ucwords(str_replace(['_','-','crud.php'], [' ',' ',''], $mod))) . '</a>';
}
echo '</aside>';

echo '<div class="admin-main">';

// Build create form
echo '<section><h3>Create</h3>';
echo '<form method="post" action="../actions/create.php" enctype="multipart/form-data">';
echo '<input type="hidden" name="module" value="' . htmlspecialchars($crud_module) . '">';
echo '<input type="hidden" name="table" value="' . htmlspecialchars($table) . '">';
echo '<div class="form-grid">';

foreach ($definition['columns'] as $col) {
    // skip primary key if present in columns
    if ($col === $definition['primary_key']) continue;
    echo renderCrudField($table, $col, null);
}

echo '</div>';
echo '<button type="submit">Create</button>';
echo '</form></section>';

// Read table
echo '<section><h3>Records</h3>';
$pdo = connectDB();
$pk = $definition['primary_key'];

// Pagination & search
$q = $_GET['q'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if (!empty($q)) {
    $whereParts = [];
    foreach ($definition['columns'] as $col) {
        $whereParts[] = '`' . $col . '` LIKE :q';
    }
    $where = 'WHERE ' . implode(' OR ', $whereParts);
    $params['q'] = '%' . $q . '%';
}

$countSql = 'SELECT COUNT(*) FROM ' . $table . ' ' . $where;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = 'SELECT * FROM ' . $table . ' ' . $where . ' ORDER BY `' . $pk . '` DESC LIMIT :limit OFFSET :offset';
$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) $stmt->bindValue(':' . $k, $v);
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

if (empty($rows)) {
    echo '<p>No records found.</p>';
} else {
    // search form
    echo '<form method="get" action="' . htmlspecialchars(basename(__FILE__)) . '" style="margin-bottom:8px;" role="search" aria-label="Search records">';
    echo '<input type="hidden" name="module" value="' . htmlspecialchars($crud_module) . '">';
    echo '<input type="text" name="q" placeholder="Search..." value="' . htmlspecialchars($q) . '" aria-label="Search records" style="margin-right:6px;">';
    echo '<button type="submit" aria-label="Submit search">Search</button>';
    echo '</form>';

    echo '<table class="admin-table" role="table"><caption>' . htmlspecialchars(ucwords(str_replace(['_','-','crud.php'], [' ',' ',''], $crud_module))) . ' records</caption><thead><tr>';
    echo '<th scope="col">' . htmlspecialchars($pk) . '</th>';
    foreach ($definition['columns'] as $col) {
        echo '<th scope="col">' . htmlspecialchars($col) . '</th>';
    }
    echo '<th scope="col">Actions</th>';
    echo '</tr></thead><tbody>';

    foreach ($rows as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row[$pk]) . '</td>';
        foreach ($definition['columns'] as $col) {
            $val = isset($row[$col]) ? $row[$col] : '';

            // render FK labels if available
            if (preg_match('/(_id)$/', $col)) {
                $opts = getForeignKeyOptions($col);
                $display = isset($opts[$val]) ? $opts[$val] : (string)$val;
            } else {
                $display = (string)$val;
            }

            // truncate long text
            if (is_string($display) && strlen($display) > 140) {
                $display = substr($display, 0, 137) . '...';
            }

            echo '<td>' . htmlspecialchars($display) . '</td>';
        }

        // Actions: Edit page link and Delete form
        echo '<td class="actions">';
        echo '<a class="btn-secondary" href="../admin/edit.php?module=' . urlencode($crud_module) . '&id=' . urlencode($row[$pk]) . '" style="margin-right:6px;">Edit</a>';

        // Delete form
        echo '<form method="post" action="../actions/delete.php" style="display:inline-block;">';
        echo '<input type="hidden" name="module" value="' . htmlspecialchars($crud_module) . '">';
        echo '<input type="hidden" name="table" value="' . htmlspecialchars($table) . '">';
        echo '<input type="hidden" name="id" value="' . htmlspecialchars($row[$pk]) . '">';
        echo '<button type="submit" onclick="return confirm(\'Delete this record?\')">Delete</button>';
        echo '</form>';

        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    // pagination
    $totalPages = (int) ceil($total / $perPage);
    if ($totalPages > 1) {
        echo '<div class="pagination">';
        for ($p = 1; $p <= $totalPages; $p++) {
            if ($p === $page) {
                echo '<span class="current">' . $p . '</span>';
            } else {
                $link = '?module=' . urlencode($crud_module) . '&page=' . $p;
                if (!empty($q)) $link .= '&q=' . urlencode($q);
                echo '<a href="' . $link . '">' . $p . '</a>';
            }
        }
        echo '</div>';
    }
}

echo '</section>';

// Close main and layout
echo '</div>'; // .admin-main
echo '</div>'; // .admin-layout

?>
