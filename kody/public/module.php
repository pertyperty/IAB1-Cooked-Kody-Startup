<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Module Content', 'workspace-page', ['data-page' => 'module']);
renderWorkspaceNav('learn', 'Module Content', 'This page displays the selected module after enroll or direct module access actions.');
renderWorkspaceIntro('E05 + E06. Module Access', 'Focused Module View', 'Use this page to read module content after access checks and enrollment rules are applied.');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="surface inset-surface">
            <div class="surface-header compact-header">
                <h3 id="module-page-heading">Loading module...</h3>
                <div class="inline-actions">
                    <a class="button-link secondary-link" href="learn.php">Back to Learning</a>
                </div>
            </div>
            <div id="module-reader" class="detail-card">Preparing module content...</div>
        </div>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);