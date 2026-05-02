<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/header.php';
?>

    <h2>Admin: Payments Crud</h2>
    <p class="notice">Starter CRUD page. Add form fields, table listing, and edit/delete actions for this module.</p>
    
    <section>
        <h3>Create</h3>
        <form method="post" action="../actions/create.php">
            <input type="hidden" name="module" value="payments_crud.php">
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
            <input type="hidden" name="module" value="payments_crud.php">
            <button type="submit">Update (Placeholder)</button>
        </form>
    </section>
    
    <section>
        <h3>Delete</h3>
        <form method="post" action="../actions/delete.php">
            <input type="hidden" name="module" value="payments_crud.php">
            <button type="submit">Delete (Placeholder)</button>
        </form>
    </section>
    
<?php require_once __DIR__ . '/../includes/footer.php'; ?>


