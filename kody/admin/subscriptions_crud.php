<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/header.php';

$crud_module = basename(__FILE__);
require_once __DIR__ . '/../includes/admin_crud.php';

require_once __DIR__ . '/../includes/footer.php';


