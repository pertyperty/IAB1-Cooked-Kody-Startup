<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Finance', 'workspace-page', ['data-page' => 'finance']);
renderWorkspaceNav('finance', 'Earnings and Payouts', 'Creator earnings and payout requests are handled in this dedicated page. Token top-up is managed in the Top Up page.');
renderWorkspaceIntro('F03 + F04. Creator Earnings and Payouts', 'Earnings Center', 'This page is focused on earnings and payout workflows only.');
renderWalletRail('finance');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="split-grid">
            <article class="surface inset-surface">
                <h3>Creator Earnings</h3>
                <div id="earnings-table" class="table-wrap"></div>
                <div id="earnings-cards" class="catalog-list"></div>

                <h3>Payout Requests</h3>
                <div id="payout-requests-table" class="table-wrap"></div>
                <div id="payout-request-cards" class="catalog-list"></div>
            </article>

            <article class="surface inset-surface">
                <article class="catalog-card">
                    <h3>Top Up Moved</h3>
                    <p>KodeBits package purchase and wallet top-up operations now live in the dedicated Top Up page.</p>
                    <div class="inline-actions">
                        <a class="button-link" href="topup.php">Open Top Up Page</a>
                    </div>
                </article>
            </article>

            <article class="surface inset-surface">
                <h3>Payment Provider Interface</h3>
                <div class="integration-grid">
                    <article class="integration-card">
                        <strong>Payout Queue</strong>
                        <p>Payout requests behave like a connected disbursement interface while remaining local.</p>
                    </article>
                </div>
            </article>
        </div>
    </section>
</main>
<section class="shell response-console developer-console">
    <h2>For Testing: Direct Earnings Tools</h2>
    <div class="split-grid">
        <article class="surface inset-surface">
            <details class="advanced-drawer">
                <summary>Advanced Payout Request Form</summary>
                <form class="stack-form api-form" data-module="finance" data-action="request_payout" data-roles="contributor,instructor,administrator">
                    <h3>UC F04 Receive Earnings</h3>
                    <input name="creator_earning_id" type="number" min="1" placeholder="Creator earning ID" required>
                    <input name="request_amount_php" type="number" min="1" placeholder="Amount PHP" required>
                    <input name="payout_channel" placeholder="Payout channel" value="GCash" required>
                    <button type="submit">Request Payout</button>
                </form>
            </details>
        </article>
    </div>
</section>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
