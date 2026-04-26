<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Creator Workspace', 'workspace-page', ['data-page' => 'creator']);
renderWorkspaceNav('creator', 'Creator Workspace', 'Contributors, instructors, and administrators manage courses, modules, and challenges here through dedicated pages.');
renderWorkspaceIntro('B + C. Content and Challenge Management', 'Authoring and Submission Operations', 'This page keeps creator work separate from learning and governance.');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="split-grid">
            <article class="surface inset-surface">
                <div class="surface-header compact-header">
                    <h3>Courses</h3>
                    <button type="button" class="secondary load-button" data-load-key="creator">Refresh</button>
                </div>
                <div id="content-courses-table" class="table-wrap"></div>

                <h3>Modules</h3>
                <div id="content-modules-table" class="table-wrap"></div>

                <h3>Challenges</h3>
                <div id="creator-challenges-table" class="table-wrap"></div>

                <h3>Creator Content Viewer</h3>
                <div id="creator-content-viewer" class="detail-card">Select a module or challenge row action to view details here.</div>
            </article>

            <article class="surface inset-surface">
                <form class="stack-form api-form" data-module="content" data-action="create_course">
                    <h3>UC B01 Create Course</h3>
                    <input name="title" placeholder="Course title" required>
                    <textarea name="description" placeholder="Description"></textarea>
                    <select name="course_type">
                        <option value="free">free</option>
                        <option value="premium">premium</option>
                    </select>
                    <input name="kodebits_cost" type="number" min="0" value="0" placeholder="KodeBits cost">
                    <button type="submit">Create Course</button>
                </form>

                <form class="stack-form api-form" data-module="content" data-action="edit_course">
                    <h3>UC B02 Edit Course</h3>
                    <input name="course_id" type="number" min="1" placeholder="Course ID" required>
                    <input name="title" placeholder="New title">
                    <textarea name="description" placeholder="New description"></textarea>
                    <input name="status" placeholder="Optional status">
                    <button type="submit">Edit Course</button>
                </form>

                <form class="stack-form api-form" data-module="content" data-action="archive_course" data-critical-msg="Archive this course? It will be hidden from new enrollees.">
                    <h3>UC B03 Archive Course</h3>
                    <input name="course_id" type="number" min="1" placeholder="Course ID" required>
                    <button type="submit" class="danger">Archive Course</button>
                </form>

                <form class="stack-form api-form" data-module="content" data-action="delete_course" data-critical-msg="Delete this course? Active enrollments block deletion.">
                    <h3>UC B04 Delete Course</h3>
                    <input name="course_id" type="number" min="1" placeholder="Course ID" required>
                    <button type="submit" class="danger">Delete Course</button>
                </form>
            </article>

            <article class="surface inset-surface">
                <form class="stack-form api-form" data-module="content" data-action="create_module">
                    <h3>UC B05 Create Module</h3>
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

                <form class="stack-form api-form" data-module="content" data-action="edit_module">
                    <h3>UC B06 Edit Module</h3>
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
                    <button type="submit">Edit Module</button>
                </form>

                <form class="stack-form api-form" data-module="content" data-action="archive_module" data-critical-msg="Archive this module?">
                    <h3>UC B07 Archive Module</h3>
                    <input name="module_id" type="number" min="1" placeholder="Module ID" required>
                    <button type="submit" class="danger">Archive Module</button>
                </form>

                <form class="stack-form api-form" data-module="content" data-action="delete_module" data-critical-msg="Delete this module?">
                    <h3>UC B08 Delete Module</h3>
                    <input name="module_id" type="number" min="1" placeholder="Module ID" required>
                    <button type="submit" class="danger">Delete Module</button>
                </form>

                <form class="stack-form api-form" data-module="content" data-action="assign_module">
                    <h3>UC B09 Assign Module to Course</h3>
                    <input name="course_id" type="number" min="1" placeholder="Course ID" required>
                    <input name="module_id" type="number" min="1" placeholder="Module ID" required>
                    <input name="sequence_no" type="number" min="1" placeholder="Sequence" required>
                    <button type="submit">Assign Module</button>
                </form>
            </article>
        </div>

        <div class="split-grid">
            <article class="surface inset-surface">
                <form class="stack-form api-form" data-module="challenge" data-action="create">
                    <h3>UC C01 Create Code Challenge</h3>
                    <input name="title" placeholder="Challenge title" required>
                    <textarea name="prompt_text" placeholder="Prompt" required></textarea>
                    <select name="difficulty_level">
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                    </select>
                    <input name="language_scope" placeholder="Language scope" value="Python,Java,C++,JavaScript,PHP">
                    <input name="time_limit_ms" type="number" min="200" value="2000" placeholder="Time limit (ms)">
                    <input name="memory_limit_kb" type="number" min="1024" value="128000" placeholder="Memory limit (KB)">
                    <input name="kodebits_cost" type="number" min="0" value="0" placeholder="KodeBits cost">
                    <button type="submit">Create Challenge</button>
                </form>

                <form class="stack-form api-form" data-module="challenge" data-action="edit">
                    <h3>UC C02 Edit Code Challenge</h3>
                    <input name="challenge_id" type="number" min="1" placeholder="Challenge ID" required>
                    <input name="title" placeholder="New title">
                    <textarea name="prompt_text" placeholder="New prompt"></textarea>
                    <select name="difficulty_level">
                        <option value="">No difficulty change</option>
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                    </select>
                    <input name="language_scope" placeholder="New language scope">
                    <input name="time_limit_ms" type="number" min="200" placeholder="New time limit (ms)">
                    <input name="memory_limit_kb" type="number" min="1024" placeholder="New memory limit (KB)">
                    <input name="kodebits_cost" type="number" min="0" placeholder="New KodeBits cost">
                    <input name="status" placeholder="Optional status">
                    <button type="submit">Edit Challenge</button>
                </form>
            </article>

            <article class="surface inset-surface">
                <form class="stack-form api-form" data-module="challenge" data-action="archive" data-critical-msg="Archive this challenge?">
                    <h3>UC C03 Archive Code Challenge</h3>
                    <input name="challenge_id" type="number" min="1" placeholder="Challenge ID" required>
                    <button type="submit" class="danger">Archive Challenge</button>
                </form>

                <form class="stack-form api-form" data-module="challenge" data-action="delete" data-critical-msg="Delete this challenge? Existing submissions block deletion.">
                    <h3>UC C04 Delete Code Challenge</h3>
                    <input name="challenge_id" type="number" min="1" placeholder="Challenge ID" required>
                    <button type="submit" class="danger">Delete Challenge</button>
                </form>

                <form class="stack-form api-form" data-module="challenge" data-action="submit">
                    <h3>UC C06 Submit Code Solution</h3>
                    <input name="challenge_id" type="number" min="1" placeholder="Challenge ID" required>
                    <select name="language_name">
                        <option>Python</option>
                        <option>Java</option>
                        <option>C++</option>
                        <option>JavaScript</option>
                        <option>PHP</option>
                    </select>
                    <textarea name="source_code" placeholder="Source code" required></textarea>
                    <button type="submit">Submit Solution</button>
                </form>

                <form class="stack-form api-form" data-module="challenge" data-action="evaluate">
                    <h3>UC C07 Evaluate Existing Submission</h3>
                    <input name="submission_id" type="number" min="1" placeholder="Submission ID" required>
                    <button type="submit">Evaluate Submission</button>
                </form>
            </article>

            <article class="surface inset-surface">
                <article class="catalog-card">
                    <h3>Execution Service Interface</h3>
                    <p>The challenge authoring and evaluation experience is presented like a connected judge workflow, while still using local evaluation logic.</p>
                </article>
            </article>
        </div>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
