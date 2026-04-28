<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Learning', 'workspace-page', ['data-page' => 'learn']);
renderWorkspaceNav('learn', 'Learning Hub', 'Browse courses and modules in a clean learning-first interface. Challenge coding is in a dedicated page.');
renderWorkspaceIntro('E. User Interaction and Engagement', 'Learning Experience', 'This page focuses only on studying: course discovery, module reading, and learning progression.');
renderLearningRail('learn');
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

        <article class="surface inset-surface">
            <h3>Related Areas</h3>
            <div class="inline-actions">
                <a class="button-link" href="challenges.php">Open Challenges</a>
                <a class="button-link" href="leaderboard.php">Open Leaderboard</a>
                <a class="button-link" href="faq.php">Open FAQ</a>
            </div>
        </article>

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

        <article class="surface inset-surface">
            <article class="catalog-card">
                <h3>Learning Flow</h3>
                <p>Use the course and standalone module cards above, then open a module in reader mode for focused study.</p>
            </article>
        </article>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
