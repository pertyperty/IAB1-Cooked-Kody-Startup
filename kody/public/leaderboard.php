<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Leaderboard', 'workspace-page', ['data-page' => 'leaderboard']);
renderWorkspaceNav('leaderboard', 'Leaderboard', 'View both global and weekly ranking standings in a dedicated leaderboard page.');
renderWorkspaceIntro('D03. View Leaderboard', 'Ranking Board', 'Global and weekly leaderboards are shown side by side for cleaner ranking visibility.');
renderLearningRail('leaderboard');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="split-grid">
            <article class="surface inset-surface">
                <div class="surface-header compact-header">
                    <h3>Global Leaderboard</h3>
                    <button type="button" class="secondary load-button" data-load-key="leaderboard">Refresh</button>
                </div>
                <div id="leaderboard-table" class="table-wrap"></div>
                <div id="leaderboard-cards" class="catalog-list"></div>
            </article>

            <article class="surface inset-surface">
                <div class="surface-header compact-header">
                    <h3>Weekly Leaderboard</h3>
                </div>
                <div id="weekly-leaderboard-table" class="table-wrap"></div>
                <div id="weekly-leaderboard-cards" class="catalog-list"></div>
            </article>

            <article class="surface inset-surface">
                <article class="catalog-card">
                    <h3>Ranking Notes</h3>
                    <p>Global rank is based on XP and KodeBits. Weekly rank is based on weekly challenge final score and rank position.</p>
                </article>
            </article>
        </div>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
