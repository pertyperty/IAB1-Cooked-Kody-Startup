<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$challenges = getChallengeList();
$selectedChallengeId = isset($_GET['challenge_id']) ? (int) $_GET['challenge_id'] : 0;
$selectedChallenge = $selectedChallengeId > 0 ? getChallengeById($selectedChallengeId) : null;

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-hero">
  <h2>Challenge Runner</h2>
  <p>Select a challenge card, then submit code in the terminal-style editor below.</p>
</div>

<section>
  <div class="section-heading">
    <h3>Challenges</h3>
    <span class="badge info"><?php echo (int) count($challenges); ?> available</span>
  </div>
  <?php if (count($challenges) > 0): ?>
    <div class="card-grid mt-1">
      <?php foreach ($challenges as $challenge): ?>
        <article class="content-card">
          <div class="section-heading align-start">
            <div>
              <div class="panel-eyebrow">Challenge #<?php echo (int) $challenge['challenge_id']; ?></div>
              <h4 class="mb-025"><?php echo htmlspecialchars($challenge['title']); ?></h4>
            </div>
            <span class="badge dark"><?php echo (int) ($challenge['xp_reward'] ?? 0); ?> XP</span>
          </div>
          <p class="meta"><?php echo htmlspecialchars($challenge['programming_language'] ?? ''); ?> | Difficulty: <?php echo htmlspecialchars($challenge['difficulty'] ?? ''); ?></p>
          <a class="card-button primary" href="/kody/submit_code.php?challenge_id=<?php echo (int) $challenge['challenge_id']; ?>">Open challenge</a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No challenges available.</p>
  <?php endif; ?>
</section>

<section class="cli-panel mt-1">
  <div class="section-heading">
    <h3>Submission Console</h3>
    <span class="badge success">CLI style</span>
  </div>

  <?php if ($selectedChallenge): ?>
    <div class="selected-challenge">
      <div class="panel-eyebrow">Selected challenge</div>
      <strong>#<?php echo (int) $selectedChallenge['challenge_id']; ?> - <?php echo htmlspecialchars($selectedChallenge['title']); ?></strong>
      <div class="meta-list mt-035">
        <?php echo htmlspecialchars($selectedChallenge['programming_language'] ?? ''); ?> | XP <?php echo (int) ($selectedChallenge['xp_reward'] ?? 0); ?>
      </div>
      <div class="meta-list mt-035 prewrap"><?php echo htmlspecialchars($selectedChallenge['description'] ?? ''); ?></div>
    </div>
  <?php else: ?>
    <p class="mt-075 muted">Open a challenge above to load the editor.</p>
  <?php endif; ?>

  <form method="post" action="/kody/process_submission.php" class="mt-1">
    <label for="challenge_id">Challenge ID</label>
    <input id="challenge_id" type="number" name="challenge_id" value="<?php echo (int) $selectedChallengeId; ?>" min="1" required>

    <div class="split-layout split-2 mt-1">
      <div>
        <label for="language">Language</label>
        <input id="language" type="text" name="language" value="PHP" required>
      </div>
      <div>
        <label for="score">Score (0-100)</label>
        <input id="score" type="number" name="score" min="0" max="100" value="0" required>
      </div>
    </div>

    <label for="execution_status" class="mt-1">Execution Status</label>
    <select id="execution_status" name="execution_status" required>
      <option value="pending">pending</option>
      <option value="passed">passed</option>
      <option value="failed">failed</option>
      <option value="error">error</option>
    </select>

    <label for="source_code" class="mt-1">Source Code</label>
    <textarea id="source_code" name="source_code" rows="14" cols="70" placeholder="// paste your solution here" required></textarea>

    <div class="page-actions mt-1">
      <button type="submit" class="primary">Run submission</button>
      <a class="button-link" href="/kody/progress.php">Open progress</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

