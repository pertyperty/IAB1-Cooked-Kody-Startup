<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody FAQ', 'workspace-page', ['data-page' => 'faq']);
renderWorkspaceNav('faq', 'FAQ and Help', 'Browse frequently asked questions in a dedicated help page.');
renderWorkspaceIntro('E11. View FAQ', 'Help Center', 'FAQ content is separated from study and challenge workflows for cleaner navigation.');
renderLearningRail('faq');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <article class="surface inset-surface">
            <div class="surface-header compact-header">
                <h3>Search FAQ</h3>
                <button type="button" class="secondary" id="btn-clear-faq-search">Clear</button>
            </div>
            <div class="search-bar-wrap">
                <input id="faq-search" type="search" placeholder="Search question or answer...">
            </div>
        </article>

        <div class="split-grid">
            <article class="surface inset-surface">
                <div class="surface-header compact-header">
                    <h3>FAQ Entries</h3>
                    <button type="button" class="secondary load-button" data-load-key="faq">Refresh</button>
                </div>
                <div id="faq-list" class="faq-list"></div>
            </article>

            <article class="surface inset-surface">
                <article class="catalog-card">
                    <h3>Need More Help?</h3>
                    <p>If your concern is not listed, contact platform moderators through governance channels or check your account notifications for updates.</p>
                </article>
            </article>
        </div>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
