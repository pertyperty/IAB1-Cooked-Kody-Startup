<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Top Up', 'workspace-page', ['data-page' => 'topup']);
renderWorkspaceNav('topup', 'KodeBits Top Up', 'Buy KodeBits packages and review your wallet transaction history.');
renderWorkspaceIntro('F01. Purchase Tokens', 'Top Up Center', 'This page is dedicated to package top-up and transaction history only.');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="split-grid">
            <article class="surface inset-surface">
                <div class="surface-header compact-header">
                    <h3>Top Up Packages</h3>
                    <button type="button" class="secondary load-button" data-load-key="topup">Refresh</button>
                </div>
                <div id="package-showcase" class="catalog-list"></div>

                <h3>Packages Table</h3>
                <div id="finance-packages-table" class="table-wrap"></div>
            </article>

            <article class="surface inset-surface">
                <h3>Transaction History</h3>
                <div id="topup-history-table" class="table-wrap"></div>
                <div id="topup-history-cards" class="catalog-list"></div>
            </article>
        </div>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
