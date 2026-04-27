<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Leaderboard', 'workspace-page', ['data-page' => 'leaderboard']);
renderWorkspaceNav('leaderboard', 'Leaderboard', 'View ranking standings and role-based points progression in a dedicated leaderboard page.');
renderWorkspaceIntro('D03. View Leaderboard', 'Ranking Board', 'Leaderboard is separated from learning and challenge pages for a cleaner website structure.');
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
            </article>

            <article class="surface inset-surface">
                <article class="catalog-card">
                    <h3>Ranking Notes</h3>
                    <p>Ranks are based on XP and KodeBits balances. Reward events and challenge results can affect position over time.</p>
                </article>
            </article>
        </div>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
