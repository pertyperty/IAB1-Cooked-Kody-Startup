<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Creator Courses', 'workspace-page', ['data-page' => 'creator-courses']);
renderWorkspaceNav('creator', 'Creator Courses', 'Create and edit courses in a dedicated creator subpage.');
renderWorkspaceIntro('B01-B04. Course Management', 'Courses Management', 'Course creation and maintenance are implemented here as a dedicated page.');
renderWorkspaceAreasRail('creator');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="split-grid">
            <article class="surface inset-surface">
                <div class="surface-header compact-header">
                    <h3>My Course Library</h3>
                    <button type="button" class="secondary load-button" data-load-key="creator-courses">Refresh</button>
                </div>
                <div id="content-courses-table" class="table-wrap"></div>
                <div id="creator-course-cards" class="catalog-list"></div>
            </article>

            <article class="surface inset-surface" data-roles="instructor,moderator,administrator">
                <div data-creator-mode="create">
                    <form class="stack-form api-form" data-module="content" data-action="create_course">
                        <h3>Create Course</h3>
                        <input name="title" placeholder="Course title" required>
                        <textarea name="description" placeholder="Description"></textarea>
                        <select name="course_type">
                            <option value="free">free</option>
                            <option value="premium">premium</option>
                        </select>
                        <input name="kodebits_cost" type="number" min="0" value="0" placeholder="KodeBits cost">
                        <button type="submit">Create Course</button>
                    </form>
                </div>

                <div data-creator-mode="edit">
                    <details class="advanced-drawer" open>
                        <summary>Edit / Archive / Delete Course</summary>
                        <form class="stack-form api-form" data-module="content" data-action="edit_course">
                            <h3>Edit Course</h3>
                            <input name="course_id" type="number" min="1" placeholder="Course ID" required>
                            <input name="title" placeholder="New title">
                            <textarea name="description" placeholder="New description"></textarea>
                            <input name="status" placeholder="Optional status">
                            <button type="submit">Apply Course Changes</button>
                        </form>

                        <form class="stack-form api-form" data-module="content" data-action="archive_course" data-critical-msg="Archive this course? It will be hidden from new enrollees.">
                            <h3>Archive Course</h3>
                            <input name="course_id" type="number" min="1" placeholder="Course ID" required>
                            <button type="submit" class="danger">Archive Course</button>
                        </form>

                        <form class="stack-form api-form" data-module="content" data-action="delete_course" data-critical-msg="Delete this course? Active enrollments block deletion.">
                            <h3>Delete Course</h3>
                            <input name="course_id" type="number" min="1" placeholder="Course ID" required>
                            <button type="submit" class="danger">Delete Course</button>
                        </form>
                    </details>
                </div>
            </article>
        </div>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
