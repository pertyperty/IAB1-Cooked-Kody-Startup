<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Top Up', 'workspace-page', ['data-page' => 'topup']);
renderWorkspaceNav('topup', 'KodeBits Top Up', 'Buy KodeBits packages through a makeshift paygate and review your wallet transaction history.');
renderWorkspaceIntro('F01. Purchase Tokens', 'Top Up Center', 'This page is dedicated to package top-up, paygate checkout, and transaction history only.');
renderWalletRail('topup');
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
                <h3>Checkout Paygate</h3>
                <form id="paygate-form" class="stack-form api-form" data-module="finance" data-action="purchase">
                    <h3>Xendit-Style Payment Interface</h3>
                    <p class="muted-note">Select a package first, then provide payment details for Maya, GCash, or Bank transfer.</p>
                    <input id="paygate-package-id" name="package_id" type="number" min="1" placeholder="Selected package ID" required>
                    <input id="paygate-package-label" type="text" placeholder="No package selected" readonly>
                    <select name="payment_channel" required>
                        <option value="GCash">GCash</option>
                        <option value="Maya">Maya</option>
                        <option value="Bank">Bank</option>
                    </select>
                    <input name="account_name" placeholder="Account holder name" required>
                    <input name="account_no" placeholder="Account / mobile number" required>
                    <input name="xendit_reference" placeholder="Optional external reference">
                    <button type="submit">Pay and Top Up</button>
                </form>

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
