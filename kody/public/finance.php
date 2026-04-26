<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Finance', 'workspace-page', ['data-page' => 'finance']);
renderWorkspaceNav('finance', 'Finance and Earnings', 'Token packages, wallet use, creator earnings, payout requests, and payment-style interfaces are separated into their own area.');
renderWorkspaceIntro('F. Transaction and Earnings Management', 'Finance Center', 'This page groups token purchase, spending, earnings, and payout operations in one website section.');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="split-grid">
            <article class="surface inset-surface">
                <h3>Top Up KodeBits</h3>
                <div id="package-showcase" class="catalog-list"></div>

                <h3>Creator Earnings</h3>
                <div id="earnings-table" class="table-wrap"></div>
            </article>

            <article class="surface inset-surface">
                <article class="catalog-card">
                    <h3>Checkout Experience</h3>
                    <p>Choose a package card to top up immediately. The manual finance forms remain below for testing and direct UC triggering.</p>
                </article>
            </article>

            <article class="surface inset-surface">
                <h3>Payment Provider Interface</h3>
                <div class="integration-grid">
                    <article class="integration-card">
                        <strong>Checkout Widget</strong>
                        <p>Token purchases are rendered as successful payment flows with provider-style references.</p>
                    </article>
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
    <h2>For Testing: Direct Finance Tools</h2>
    <div class="split-grid">
        <article class="surface inset-surface">
            <h3>Token Packages Table</h3>
            <div id="finance-packages-table" class="table-wrap"></div>

            <form class="stack-form api-form" data-module="finance" data-action="purchase">
                <h3>UC F01 Purchase Tokens</h3>
                <input name="package_id" type="number" min="1" placeholder="Package ID" required>
                <button type="submit">Purchase Tokens</button>
            </form>

            <form class="stack-form api-form" data-module="finance" data-action="use_tokens">
                <h3>UC F02 Use Tokens</h3>
                <input name="amount_kb" type="number" min="1" placeholder="Amount KB" required>
                <input name="reference_type" placeholder="Reference type" required>
                <input name="reference_id" placeholder="Reference ID" required>
                <button type="submit">Use Tokens</button>
            </form>
        </article>
        <article class="surface inset-surface">
            <form class="stack-form api-form" data-module="finance" data-action="request_payout" data-roles="contributor,instructor,administrator">
                <h3>UC F04 Receive Earnings</h3>
                <input name="creator_earning_id" type="number" min="1" placeholder="Creator earning ID" required>
                <input name="request_amount_php" type="number" min="1" placeholder="Amount PHP" required>
                <input name="payout_channel" placeholder="Payout channel" value="GCash" required>
                <button type="submit">Request Payout</button>
            </form>
        </article>
    </div>
</section>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
