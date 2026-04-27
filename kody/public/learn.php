<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Learning', 'workspace-page', ['data-page' => 'learn']);
renderWorkspaceNav('learn', 'Learning Hub', 'Browse courses, modules, leaderboards, and FAQ entries. Challenge participation is in its own dedicated page.');
renderWorkspaceIntro('E. User Interaction and Engagement', 'Learning Experience', 'Learners and inherited roles use this page for the core study and challenge flow.');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <article class="surface inset-surface">
            <div class="surface-header compact-header">
                <h3>Search Learning Content</h3>
                <button type="button" class="secondary" id="btn-clear-learning-search">Clear</button>
            </div>
            <div class="search-bar-wrap">
                <input id="learning-search" type="search" placeholder="Search courses and modules...">
            </div>
        </article>

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
                <article class="catalog-card">
                    <h3>Leaderboard Moved</h3>
                    <p>Ranking is now available in a dedicated Leaderboard page.</p>
                    <div class="inline-actions">
                        <a class="button-link" href="leaderboard.php">Open Leaderboard</a>
                    </div>
                </article>
            </article>

            <article class="surface inset-surface">
                <article class="catalog-card">
                    <h3>Challenges Moved</h3>
                    <p>Challenge browsing and participation now live in the dedicated Challenges page to keep learning and challenge workflows cleanly separated.</p>
                    <div class="inline-actions">
                        <a class="button-link" href="challenges.php">Open Challenges Page</a>
                    </div>
                </article>
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
                <h3>Study Flow</h3>
                <article class="catalog-card">
                    <p>Course and module discovery remains here. Challenge submission and execution feedback are now in the dedicated Challenges page.</p>
                </article>
            </article>
        </div>

        <div class="split-grid">
            <article class="surface inset-surface">
                <article class="catalog-card">
                    <h3>FAQ Moved</h3>
                    <p>Frequently asked questions now live in a dedicated FAQ page.</p>
                    <div class="inline-actions">
                        <a class="button-link" href="faq.php">Open FAQ</a>
                    </div>
                </article>
            </article>

            <article class="surface inset-surface">
                <article class="catalog-card">
                    <h3>Actual Learning Flow</h3>
                    <p>Use the course and module cards above to study content. Challenge participation is available in the dedicated Challenges page.</p>
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
            <article class="catalog-card">
                <h3>Challenge Tools</h3>
                <p>Challenge participation and execution feedback tools were moved to <strong>challenges.php</strong>.</p>
            </article>
        </article>
    </div>
</section>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
