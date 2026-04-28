<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Creator Workspace', 'workspace-page', ['data-page' => 'creator']);
renderWorkspaceNav('creator', 'Creator Workspace', 'Contributors, instructors, and administrators manage their own courses, modules, and challenges in a CMS-like interface.');
renderWorkspaceIntro('B + C. Content and Challenge Management', 'Content Management Workspace', 'This is now a creator hub. Creation and editing implementation are split into dedicated subpages.');
renderWorkspaceAreasRail('creator');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="split-grid">
            <article class="surface inset-surface">
                <h3>Creator Implementations</h3>
                <div class="inline-actions">
                    <button type="button" class="secondary" data-quick="open-create-course" data-roles="instructor,moderator,administrator">Create Course</button>
                    <button type="button" class="secondary" data-quick="open-create-module" data-roles="instructor,moderator,administrator">Create Module</button>
                    <button type="button" class="secondary" data-quick="open-create-challenge" data-roles="contributor,instructor,moderator,administrator">Create Challenge</button>
                </div>
                <p class="section-copy">Implementation pages are opened only by Create actions or Edit actions from the content lists.</p>
            </article>
        </div>

        <div class="split-grid">
            <article class="surface inset-surface" data-roles="instructor,moderator,administrator">
                <div class="surface-header compact-header">
                    <h3>My Course Library</h3>
                    <button type="button" class="secondary load-button" data-load-key="creator">Refresh</button>
                </div>
                <div id="content-courses-table" class="table-wrap"></div>
                <div id="creator-course-cards" class="catalog-list"></div>
            </article>

            <article class="surface inset-surface" data-roles="instructor,moderator,administrator">
                <h3>My Modules</h3>
                <div id="content-modules-table" class="table-wrap"></div>
                <div id="creator-module-cards" class="catalog-list"></div>
            </article>

            <article class="surface inset-surface" data-roles="contributor,instructor,moderator,administrator">
                <h3>My Challenges</h3>
                <div id="creator-challenges-table" class="table-wrap"></div>
                <div id="creator-challenge-cards" class="catalog-list"></div>
            </article>
        </div>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
