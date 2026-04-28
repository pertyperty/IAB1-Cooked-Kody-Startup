<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Challenge Coding', 'workspace-page', ['data-page' => 'challenge-play']);
renderWorkspaceNav('challenges', 'Challenge Coding Page', 'Code, submit, and evaluate in a dedicated challenge coding interface.');
renderWorkspaceIntro('E08. Challenge Participation and Evaluation', 'Coding Workbench', 'This page is dedicated to code submission and manual evaluation flow for a selected challenge.');
renderLearningRail('challenges');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="split-grid">
            <article class="surface inset-surface">
                <h3>Selected Challenge</h3>
                <div id="challenge-selected" class="detail-card">Select a challenge from the catalog to review details and participate.</div>

                <form id="challenge-workbench-form" class="stack-form">
                    <h3>Coding Workbench</h3>
                    <p class="muted-note">Submit your code first, then click Evaluate Submission when ready. You have up to 3 submission attempts. Attempt 3 auto-evaluates.</p>

                    <input id="challenge-workbench-id" name="challenge_id" type="hidden" value="">

                    <div id="challenge-attempt-state" class="pill-row">
                        <span class="pill">Attempts: 0/3</span>
                        <span class="pill">No submission yet</span>
                    </div>

                    <select id="challenge-language" name="language_name">
                        <option>Python</option>
                        <option>Java</option>
                        <option>C++</option>
                        <option>JavaScript</option>
                        <option>PHP</option>
                    </select>

                    <div class="cli-panel">
                        <div class="cli-header">kody@challenge:~$ nano solution.code</div>
                        <textarea id="challenge-source" name="source_code" class="cli-source" placeholder="# Write your solution here&#10;def solve():&#10;    return 'ok'" required></textarea>
                    </div>

                    <input id="challenge-pending-submission-id" type="hidden" value="">
                    <div class="inline-actions">
                        <button id="challenge-submit-btn" type="submit">Submit Attempt</button>
                        <button id="challenge-evaluate-btn" type="button" class="secondary">Evaluate Submission</button>
                    </div>
                    <p id="challenge-workbench-hint" class="muted-note">Select a challenge to start coding.</p>
                </form>
            </article>

            <article class="surface inset-surface">
                <div class="surface-header compact-header">
                    <h3>Challenge Catalog</h3>
                    <button type="button" class="secondary load-button" data-load-key="challenge-play">Refresh</button>
                </div>
                <div id="challenge-catalog" class="catalog-list"></div>
            </article>
        </div>

        <div class="split-grid">
            <article class="surface inset-surface">
                <h3>Execution Feedback</h3>
                <div id="feedback-table" class="table-wrap"></div>

                <form id="feedback-form" class="stack-form api-form" data-module="interaction" data-action="view_feedback">
                    <h3>View Specific Feedback</h3>
                    <input name="submission_id" type="number" min="1" placeholder="Submission ID" required>
                    <button type="submit">View Feedback</button>
                </form>
            </article>

            <article class="surface inset-surface">
                <article class="catalog-card">
                    <h3>Challenge Rules</h3>
                    <p>Entry fee is charged in KodeBits on first paid access only. Manual evaluation locks further attempts. Maximum submissions: 3.</p>
                </article>
            </article>
        </div>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
