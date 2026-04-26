<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Learning', 'workspace-page', ['data-page' => 'learn']);
renderWorkspaceNav('learn', 'Learning Hub', 'Browse courses, modules, challenges, leaderboards, FAQ entries, and execution feedback through dedicated learning pages.');
renderWorkspaceIntro('E. User Interaction and Engagement', 'Learning Experience', 'Learners and inherited roles use this page for the core study and challenge flow.');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="catalog-grid">
            <article class="surface inset-surface">
                <div class="surface-header compact-header">
                    <h3>Course Catalog</h3>
                    <button type="button" class="secondary load-button" data-load-key="learning">Refresh</button>
                </div>
                <div id="course-catalog" class="catalog-list"></div>
            </article>

            <article class="surface inset-surface">
                <div class="surface-header compact-header">
                    <h3>Standalone Modules</h3>
                    <button type="button" class="secondary load-button" data-load-key="learning">Refresh</button>
                </div>
                <div id="module-catalog" class="catalog-list"></div>
            </article>
        </div>

        <div class="catalog-grid">
            <article class="surface inset-surface">
                <div class="surface-header compact-header">
                    <h3>Challenge Catalog</h3>
                    <button type="button" class="secondary load-button" data-load-key="learning">Refresh</button>
                </div>
                <div id="challenge-catalog" class="catalog-list"></div>
            </article>

            <article class="surface inset-surface">
                <h3>Leaderboard</h3>
                <div id="leaderboard-table" class="table-wrap"></div>
            </article>
        </div>

        <div class="split-grid">
            <article class="surface inset-surface">
                <h3>Course Modules</h3>
                <div id="course-modules-table" class="table-wrap"></div>

                <h3>Module Reader</h3>
                <div id="module-reader" class="detail-card">Select a course module or standalone module to view content.</div>
            </article>

            <article class="surface inset-surface">
                <h3>Execution Feedback</h3>
                <div id="feedback-table" class="table-wrap"></div>

                <h3>Judge Interface</h3>
                <article class="catalog-card">
                    <p>Execution and scoring are shown through a local evaluation pipeline that mirrors a connected judge interface.</p>
                </article>
            </article>
        </div>

        <div class="split-grid">
            <article class="surface inset-surface">
                <h3>FAQ</h3>
                <div id="faq-list" class="faq-list"></div>
            </article>

            <article class="surface inset-surface">
                <article class="catalog-card">
                    <h3>Actual Learning Flow</h3>
                    <p>Use the course, module, and challenge cards above to browse and interact with content. The direct UC forms remain below as testing tools.</p>
                </article>
            </article>
        </div>
    </section>
</main>
<section class="shell response-console developer-console">
    <h2>For Testing: Direct Learning Tools</h2>
    <div class="split-grid">
        <article class="surface inset-surface">
            <form id="enroll-form" class="stack-form api-form" data-module="interaction" data-action="enroll_course">
                <h3>UC E04 Enroll in Course</h3>
                <input name="course_id" type="number" min="1" placeholder="Course ID" required>
                <button type="submit">Enroll</button>
            </form>

            <form id="course-access-form" class="stack-form api-form" data-module="interaction" data-action="access_course_module">
                <h3>UC E05 Access Course Module</h3>
                <input name="course_id" type="number" min="1" placeholder="Course ID" required>
                <input name="module_id" type="number" min="1" placeholder="Module ID" required>
                <button type="submit">Access Module</button>
            </form>

            <form id="standalone-access-form" class="stack-form api-form" data-module="interaction" data-action="access_standalone_module">
                <h3>UC E06 Access Standalone Module</h3>
                <input name="module_id" type="number" min="1" placeholder="Standalone Module ID" required>
                <button type="submit">Access Standalone Module</button>
            </form>
        </article>
        <article class="surface inset-surface">
            <form id="participate-form" class="stack-form api-form" data-module="interaction" data-action="participate_challenge">
                <h3>UC E07 Participate in Challenge</h3>
                <input name="challenge_id" type="number" min="1" placeholder="Challenge ID" required>
                <select name="language_name">
                    <option>Python</option>
                    <option>Java</option>
                    <option>C++</option>
                    <option>JavaScript</option>
                    <option>PHP</option>
                </select>
                <textarea name="source_code" placeholder="Source code" required></textarea>
                <button type="submit">Submit Challenge</button>
            </form>

            <form id="feedback-form" class="stack-form api-form" data-module="interaction" data-action="view_feedback">
                <h3>UC E08 View Execution Feedback</h3>
                <input name="submission_id" type="number" min="1" placeholder="Submission ID" required>
                <button type="submit">View Feedback</button>
            </form>

            <form class="stack-form api-form" data-module="interaction" data-action="react">
                <h3>UC E10 React to Content</h3>
                <select name="content_type">
                    <option value="course">course</option>
                    <option value="module">module</option>
                    <option value="challenge">challenge</option>
                </select>
                <input name="content_id" type="number" min="1" placeholder="Content ID" required>
                <input name="reaction_value" placeholder="Reaction" value="like">
                <button type="submit">React</button>
            </form>
        </article>
    </div>
</section>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
