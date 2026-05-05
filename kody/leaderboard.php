<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$leaderboard = getLeaderboard();

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-hero">
  <h2>Leaderboard</h2>
  <p>See who is climbing fastest. The top ranks are highlighted as podium cards, with the full board below.</p>
</div>

<?php if (count($leaderboard) > 0): ?>
  <?php $topThree = array_slice($leaderboard, 0, 3); ?>
  <div class="podium">
      <?php foreach ($topThree as $index => $row): ?>
      <article class="podium-card">
        <div class="rank">Rank <?php echo $index + 1; ?></div>
        <h3 class="mt-035 mb-035"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></h3>
        <div class="card-meta"><?php echo htmlspecialchars($row['email']); ?></div>
        <div class="metric mt-075"><?php echo (int) $row['total_xp']; ?> XP</div>
        <div class="card-meta">Level <?php echo (int) $row['level']; ?> · <?php echo htmlspecialchars($row['account_status']); ?></div>
      </article>
    <?php endforeach; ?>
  </div>

  <section class="subtle-panel">
    <div class="section-heading">
      <h3>Full Leaderboard</h3>
      <span class="badge info"><?php echo (int) count($leaderboard); ?> users</span>
    </div>
    <div class="timeline mt-1">
      <?php $rank = 1; foreach ($leaderboard as $row): ?>
        <div class="timeline-item">
          <div class="section-heading align-start">
            <div>
              <div class="panel-eyebrow">Rank <?php echo $rank++; ?></div>
              <strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong>
            </div>
            <span class="badge success"><?php echo (int) $row['total_xp']; ?> XP</span>
          </div>
          <div class="meta-list mt-035">
            <?php echo htmlspecialchars($row['email']); ?> · Level <?php echo (int) $row['level']; ?> · <?php echo htmlspecialchars($row['account_status']); ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
<?php else: ?>
  <p>No leaderboard data yet.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

