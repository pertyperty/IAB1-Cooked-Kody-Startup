<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Challenges', 'workspace-page', ['data-page' => 'challenges']);
renderWorkspaceNav('challenges', 'Challenge Arena', 'Browse available coding challenges and jump into a dedicated coding page per challenge.');
renderWorkspaceIntro('E07. Challenge Discovery', 'Challenge Catalog', 'Choose a challenge here, then continue to the separate coding page for submission and evaluation.');
renderLearningRail('challenges');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="split-grid">
            <article class="surface inset-surface">
                <div class="surface-header compact-header">
                    <h3>Challenge Catalog</h3>
                    <button type="button" class="secondary load-button" data-load-key="challenges">Refresh</button>
                </div>
                <div id="challenge-catalog" class="catalog-list"></div>
            </article>

            <article class="surface inset-surface">
                <h3>Selected Challenge</h3>
                <div id="challenge-selected" class="detail-card">Select a challenge from the catalog to review details and participate.</div>
                <article class="catalog-card">
                    <h3>Continue to Coding Page</h3>
                    <p>Click Participate from any challenge card to open a separate coding interface page with submit and evaluate controls.</p>
                </article>
            </article>
        </div>

        <div class="split-grid">
            <article class="surface inset-surface">
                <h3>Flow Preview</h3>
                <article class="catalog-card">
                    <p>1) Browse and pick challenge.</p>
                    <p>2) Open dedicated coding page.</p>
                    <p>3) Submit, then evaluate manually. Third submit auto-evaluates.</p>
                </article>
            </article>

            <article class="surface inset-surface">
                <article class="catalog-card">
                    <h3>Challenge Rules</h3>
                    <p>Entry fee is charged in KodeBits when you first participate in a paid challenge. You can retry submissions up to the allowed attempt limit.</p>
                </article>

                <details class="advanced-drawer">
                    <summary>Advanced Challenge Tools</summary>
                    <form class="stack-form api-form" data-module="interaction" data-action="react">
                        <h3>React to Challenge Content</h3>
                        <input type="hidden" name="content_type" value="challenge">
                        <input name="content_id" type="number" min="1" placeholder="Challenge ID" required>
                        <input name="reaction_value" placeholder="Reaction" value="like">
                        <button type="submit">Save Reaction</button>
                    </form>
                </details>
            </article>
        </div>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
