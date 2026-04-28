<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Creator Modules', 'workspace-page', ['data-page' => 'creator-modules']);
renderWorkspaceNav('creator', 'Creator Modules', 'Create and edit modules in a dedicated creator subpage.');
renderWorkspaceIntro('B05-B09. Module Management', 'Modules Management', 'Module creation, editing, assignment, and module-game setup are handled in this dedicated page.');
renderWorkspaceAreasRail('creator');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="split-grid">
            <article class="surface inset-surface">
                <div class="surface-header compact-header">
                    <h3>My Modules</h3>
                    <button type="button" class="secondary load-button" data-load-key="creator-modules">Refresh</button>
                </div>
                <div id="content-modules-table" class="table-wrap"></div>
                <div id="creator-module-cards" class="catalog-list"></div>
            </article>

            <article class="surface inset-surface" data-roles="instructor,moderator,administrator">
                <div data-creator-mode="create">
                    <form class="stack-form api-form" data-module="content" data-action="create_module">
                        <h3>Create Module</h3>
                        <input name="title" placeholder="Module title" required>
                        <textarea name="body_content" placeholder="Body content"></textarea>
                        <select name="module_type">
                            <option value="course">course</option>
                            <option value="standalone">standalone</option>
                        </select>
                        <select name="difficulty_level">
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                        </select>
                        <input name="kodebits_cost" type="number" min="0" value="0" placeholder="KodeBits cost">
                        <button type="submit">Create Module</button>
                    </form>
                </div>

                <div data-creator-mode="edit">
                    <details class="advanced-drawer" open>
                        <summary>Edit / Assign / Archive / Delete Module</summary>
                        <form class="stack-form api-form" data-module="content" data-action="edit_module">
                            <h3>Edit Module</h3>
                            <input name="module_id" type="number" min="1" placeholder="Module ID" required>
                            <input name="title" placeholder="New title">
                            <textarea name="body_content" placeholder="New body content"></textarea>
                            <select name="module_type">
                                <option value="">No module type change</option>
                                <option value="course">course</option>
                                <option value="standalone">standalone</option>
                            </select>
                            <select name="difficulty_level">
                                <option value="">No difficulty change</option>
                                <option value="Beginner">Beginner</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Advanced">Advanced</option>
                            </select>
                            <input name="kodebits_cost" type="number" min="0" placeholder="New KodeBits cost">
                            <input name="status" placeholder="Optional status">
                            <button type="submit">Apply Module Changes</button>
                        </form>

                        <form class="stack-form api-form" data-module="content" data-action="assign_module">
                            <h3>Assign Standalone Module to Course</h3>
                            <input name="course_id" type="number" min="1" placeholder="Course ID" required>
                            <input name="module_id" type="number" min="1" placeholder="Module ID" required>
                            <input name="sequence_no" type="number" min="1" placeholder="Sequence" required>
                            <button type="submit">Assign Module</button>
                        </form>

                        <form class="stack-form api-form" data-module="content" data-action="archive_module" data-critical-msg="Archive this module?">
                            <h3>Archive Module</h3>
                            <input name="module_id" type="number" min="1" placeholder="Module ID" required>
                            <button type="submit" class="danger">Archive Module</button>
                        </form>

                        <form class="stack-form api-form" data-module="content" data-action="delete_module" data-critical-msg="Delete this module?">
                            <h3>Delete Module</h3>
                            <input name="module_id" type="number" min="1" placeholder="Module ID" required>
                            <button type="submit" class="danger">Delete Module</button>
                        </form>
                    </details>

                    <details class="advanced-drawer" open>
                        <summary>Create Game from Preset (Module)</summary>
                        <form class="stack-form api-form" data-module="gamification" data-action="create_activity" data-roles="contributor,instructor,moderator,administrator">
                            <h3>Module Activity from Preset</h3>
                            <input name="preset_id" type="number" min="1" placeholder="Preset ID" required>
                            <input name="target_id" type="number" min="1" placeholder="Module ID" required>
                            <input name="activity_name" placeholder="Activity name" required>
                            <input type="hidden" name="target_type" value="module">
                            <button type="submit">Create Module Activity</button>
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
