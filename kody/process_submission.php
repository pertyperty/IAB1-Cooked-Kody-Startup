<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$currentUser = getCurrentUser();
$userId = (int) $currentUser['user_id'];

$message = '';
$messageType = 'notice';
$resultDetails = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $challengeId = (int) ($_POST['challenge_id'] ?? 0);
  $language = trim($_POST['language'] ?? '');
  $sourceCode = trim($_POST['source_code'] ?? '');
  $executionStatus = $_POST['execution_status'] ?? 'pending';
  $score = (int) ($_POST['score'] ?? 0);

  $allowedStatuses = ['pending', 'passed', 'failed', 'error'];
  if ($challengeId <= 0 || $language === '' || $sourceCode === '' || !in_array($executionStatus, $allowedStatuses, true)) {
    $message = 'Invalid submission input. Please complete all required fields.';
  } else {
    $challenge = getChallengeById($challengeId);

    if (!$challenge) {
      $message = 'Selected challenge was not found.';
    } else {
      $submissionResult = submitCode([
        'challenge_id' => $challengeId,
        'user_id' => $userId,
        'source_code' => $sourceCode,
        'language' => $language,
        'execution_status' => $executionStatus,
        'score' => $score,
      ]);

      if (!$submissionResult['success']) {
        $message = 'Submission failed to save.';
      } else {
        $awardedXp = 0;
        $progressUpdated = false;

        if ($executionStatus === 'passed') {
          $awardedXp = (int) ($challenge['xp_reward'] ?? 0);
          if ($awardedXp > 0) {
            awardXP($userId, $awardedXp);
          }
          $progressUpdated = markChallengeComplete($userId, $challengeId);
        }

        $message = 'Submission processed successfully.';
        $messageType = 'success';
        $resultDetails = [
          'submission_id' => $submissionResult['submission_id'],
          'challenge_id' => $challengeId,
          'execution_status' => $executionStatus,
          'score' => $score,
          'awarded_xp' => $awardedXp,
          'progress_updated' => $progressUpdated,
        ];
      }
    }
  }
}

require_once __DIR__ . '/includes/header.php';
?>
<h2>Process Submission</h2>

<?php if ($message !== ''): ?>
  <p class="<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<?php if (!empty($resultDetails)): ?>
  <h3>Result Summary</h3>
  <ul>
    <li>Submission ID: <?php echo (int) $resultDetails['submission_id']; ?></li>
    <li>Challenge ID: <?php echo (int) $resultDetails['challenge_id']; ?></li>
    <li>Execution Status: <?php echo htmlspecialchars($resultDetails['execution_status']); ?></li>
    <li>Score: <?php echo (int) $resultDetails['score']; ?></li>
    <li>Awarded XP: <?php echo (int) $resultDetails['awarded_xp']; ?></li>
    <li>Progress Updated: <?php echo $resultDetails['progress_updated'] ? 'Yes' : 'No'; ?></li>
  </ul>
<?php endif; ?>

<p><a href="/kody/submit_code.php">Back to Submit Code</a></p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

