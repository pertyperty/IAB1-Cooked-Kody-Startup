<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$challenges = getChallengeList();

require_once __DIR__ . '/includes/header.php';
?>
<h2>Submit Code</h2>
<p class="notice">Choose a challenge and simulate code evaluation.</p>

<form method="post" action="/kody/process_submission.php">
  <label for="challenge_id">Challenge</label><br>
  <select id="challenge_id" name="challenge_id" required>
    <option value="">Select challenge</option>
    <?php foreach ($challenges as $challenge): ?>
      <option value="<?php echo (int) $challenge['challenge_id']; ?>">
        #<?php echo (int) $challenge['challenge_id']; ?> -
        <?php echo htmlspecialchars($challenge['title']); ?>
        (<?php echo htmlspecialchars($challenge['programming_language'] ?? ''); ?>,
        XP <?php echo (int) ($challenge['xp_reward'] ?? 0); ?>)
      </option>
    <?php endforeach; ?>
  </select><br><br>

  <label for="language">Language</label><br>
  <input id="language" type="text" name="language" value="PHP" required><br><br>

  <label for="source_code">Source Code</label><br>
  <textarea id="source_code" name="source_code" rows="10" cols="70" required></textarea><br><br>

  <label for="execution_status">Execution Status (simulation)</label><br>
  <select id="execution_status" name="execution_status" required>
    <option value="pending">pending</option>
    <option value="passed">passed</option>
    <option value="failed">failed</option>
    <option value="error">error</option>
  </select><br><br>

  <label for="score">Score (0-100)</label><br>
  <input id="score" type="number" name="score" min="0" max="100" value="0" required><br><br>

  <button type="submit">Submit Code</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

