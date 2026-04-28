<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

renderHead('Kody Creator Challenges', 'workspace-page', ['data-page' => 'creator-challenges']);
renderWorkspaceNav('creator', 'Creator Challenges', 'Create and edit coding challenges in a dedicated creator subpage.');
renderWorkspaceIntro('C01-C04. Challenge Management', 'Challenges Management', 'Challenge authoring and maintenance are implemented in this dedicated page.');
renderWorkspaceAreasRail('creator');
?>
<main class="shell workspace-stack">
    <section class="workspace-section">
        <div class="split-grid">
            <article class="surface inset-surface">
                <div class="surface-header compact-header">
                    <h3>My Challenges</h3>
                    <button type="button" class="secondary load-button" data-load-key="creator-challenges">Refresh</button>
                </div>
                <div id="creator-challenges-table" class="table-wrap"></div>
                <div id="creator-challenge-cards" class="catalog-list"></div>
            </article>

            <article class="surface inset-surface" data-roles="contributor,instructor,moderator,administrator">
                <div data-creator-mode="create">
                    <form class="stack-form api-form" data-module="challenge" data-action="create">
                        <h3>Create Code Challenge</h3>
                        <input name="title" placeholder="Challenge title" required>
                        <textarea name="prompt_text" placeholder="Prompt" required></textarea>
                        <select name="difficulty_level">
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                        </select>
                        <input name="language_scope" placeholder="Language scope" value="Python,Java,C++,JavaScript,PHP">
                        <input name="time_limit_ms" type="number" min="200" value="2000" placeholder="Time limit (ms)">
                        <input name="memory_limit_kb" type="number" min="1024" value="128000" placeholder="Memory limit (KB)">
                        <input name="kodebits_cost" type="number" min="0" value="0" placeholder="KodeBits cost">
                        <button type="submit">Create Challenge</button>
                    </form>
                </div>

                <div data-creator-mode="edit">
                    <details class="advanced-drawer" open>
                        <summary>Edit / Archive / Delete Challenge</summary>
                        <form class="stack-form api-form" data-module="challenge" data-action="edit">
                            <h3>Edit Challenge</h3>
                            <input name="challenge_id" type="number" min="1" placeholder="Challenge ID" required>
                            <input name="title" placeholder="New title">
                            <textarea name="prompt_text" placeholder="New prompt"></textarea>
                            <select name="difficulty_level">
                                <option value="">No difficulty change</option>
                                <option value="Beginner">Beginner</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Advanced">Advanced</option>
                            </select>
                            <input name="language_scope" placeholder="New language scope">
                            <input name="time_limit_ms" type="number" min="200" placeholder="New time limit (ms)">
                            <input name="memory_limit_kb" type="number" min="1024" placeholder="New memory limit (KB)">
                            <input name="kodebits_cost" type="number" min="0" placeholder="New KodeBits cost">
                            <input name="status" placeholder="Optional status">
                            <button type="submit">Apply Challenge Changes</button>
                        </form>

                        <form class="stack-form api-form" data-module="challenge" data-action="archive" data-critical-msg="Archive this challenge?">
                            <h3>Archive Challenge</h3>
                            <input name="challenge_id" type="number" min="1" placeholder="Challenge ID" required>
                            <button type="submit" class="danger">Archive Challenge</button>
                        </form>

                        <form class="stack-form api-form" data-module="challenge" data-action="delete" data-critical-msg="Delete this challenge? Existing submissions block deletion.">
                            <h3>Delete Challenge</h3>
                            <input name="challenge_id" type="number" min="1" placeholder="Challenge ID" required>
                            <button type="submit" class="danger">Delete Challenge</button>
                        </form>
                    </details>

                    <details class="advanced-drawer" open>
                        <summary>Create Game from Preset (Challenge)</summary>
                        <form class="stack-form api-form" data-module="gamification" data-action="create_activity" data-roles="contributor,instructor,moderator,administrator">
                            <h3>Challenge Activity from Preset</h3>
                            <input name="preset_id" type="number" min="1" placeholder="Preset ID" required>
                            <input name="target_id" type="number" min="1" placeholder="Challenge ID" required>
                            <input name="activity_name" placeholder="Activity name" required>
                            <input type="hidden" name="target_type" value="challenge">
                            <button type="submit">Create Challenge Activity</button>
                        </form>
                    </details>
                </div>
            </article>
        </div>
    </section>
</main>
<?php
renderWorkspaceFooter();
renderFooter(['assets/js/dashboard.js']);
