<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Crud</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <h2>Admin: Progress Crud</h2>
    <p class="notice">Starter CRUD page. Add form fields, table listing, and edit/delete actions for this module.</p>
    
    <section>
        <h3>Create</h3>
        <form method="post" action="../actions/create.php">
            <input type="hidden" name="module" value="progress_crud.php">
            <button type="submit">Save (Placeholder)</button>
        </form>
    </section>
    
    <section>
        <h3>Read</h3>
        <p>Show database records here.</p>
    </section>
    
    <section>
        <h3>Update</h3>
        <form method="post" action="../actions/update.php">
            <input type="hidden" name="module" value="progress_crud.php">
            <button type="submit">Update (Placeholder)</button>
        </form>
    </section>
    
    <section>
        <h3>Delete</h3>
        <form method="post" action="../actions/delete.php">
            <input type="hidden" name="module" value="progress_crud.php">
            <button type="submit">Delete (Placeholder)</button>
        </form>
    </section>
    
</body>
</html>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
