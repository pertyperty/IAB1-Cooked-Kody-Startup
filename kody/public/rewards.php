<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Rewards and Rankings', 'workspace-page', ['data-page' => 'rewards']);
renderWorkspaceNav('rewards', 'Rewards and Rankings', 'Gamification, ranking, preset activity creation, and weekly challenge operations are separated into their own page.');
renderWorkspaceIntro('D. Gamification and Rewards System', 'Gamification Center', 'This page shows the reward and ranking side of the platform as its own product area.');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="split-grid">
            <article class="surface inset-surface">
                <h3>Preset Library</h3>
                <div id="preset-table" class="table-wrap"></div>

                <h3>Leaderboard Snapshot</h3>
                <div id="gamification-leaderboard-table" class="table-wrap"></div>
            </article>

            <article class="surface inset-surface">
                <form class="stack-form api-form" data-module="gamification" data-action="create_activity" data-roles="contributor,instructor,administrator">
                    <h3>UC D01 Create Gamified Activity From Preset</h3>
                    <input name="preset_id" type="number" min="1" placeholder="Preset ID" required>
                    <input name="activity_name" placeholder="Activity name" required>
                    <select name="target_type">
                        <option value="course">course</option>
                        <option value="module">module</option>
                        <option value="challenge">challenge</option>
                    </select>
                    <input name="target_id" type="number" min="1" placeholder="Target ID" required>
                    <button type="submit">Create Activity</button>
                </form>

                <form class="stack-form api-form" data-module="gamification" data-action="grant_reward" data-roles="moderator,administrator">
                    <h3>UC D02 Grant Rewards</h3>
                    <input name="user_id" type="number" min="1" placeholder="User ID" required>
                    <input name="xp_awarded" type="number" min="0" value="10" placeholder="XP awarded">
                    <input name="kodebits_awarded" type="number" min="0" value="2" placeholder="KodeBits awarded">
                    <input name="reference_type" placeholder="Reference type" value="manual_reward">
                    <input name="reference_id" placeholder="Reference ID" value="manual">
                    <button type="submit">Grant Reward</button>
                </form>
            </article>

            <article class="surface inset-surface">
                <form class="stack-form api-form" data-module="gamification" data-action="create_weekly" data-roles="moderator,administrator">
                    <h3>UC D04 Configure Weekly Challenge</h3>
                    <input name="challenge_id" type="number" min="1" placeholder="Challenge ID" required>
                    <button type="submit">Configure Weekly Challenge</button>
                </form>

                <form class="stack-form api-form" data-module="gamification" data-action="evaluate_weekly" data-roles="moderator,administrator">
                    <h3>UC D05 Evaluate Weekly Submissions</h3>
                    <input name="weekly_challenge_id" type="number" min="1" placeholder="Weekly Challenge ID" required>
                    <button type="submit">Evaluate Weekly Results</button>
                </form>

                <form class="stack-form api-form" data-module="gamification" data-action="publish_weekly" data-roles="moderator,administrator">
                    <h3>UC D06 Publish Weekly Results</h3>
                    <input name="weekly_challenge_id" type="number" min="1" placeholder="Weekly Challenge ID" required>
                    <button type="submit">Publish Weekly Results</button>
                </form>

                <article class="catalog-card">
                    <h3>Ranking Engine Interface</h3>
                    <p>The page presents ranking and weekly-result operations as if the live gamification service is already integrated.</p>
                </article>
            </article>
        </div>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
