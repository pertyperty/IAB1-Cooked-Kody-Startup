<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$leaderboard = getLeaderboard();

require_once __DIR__ . '/includes/header.php';
?>
<h2>Leaderboard</h2>
<p class="notice">Users ranked by total XP.</p>

<?php if (count($leaderboard) > 0): ?>
  <table>
    <thead>
      <tr>
        <th>Rank</th>
        <th>User ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Total XP</th>
        <th>Level</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php $rank = 1; ?>
      <?php foreach ($leaderboard as $row): ?>
        <tr>
          <td><?php echo $rank++; ?></td>
          <td><?php echo (int) $row['user_id']; ?></td>
          <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
          <td><?php echo htmlspecialchars($row['email']); ?></td>
          <td><?php echo (int) $row['total_xp']; ?></td>
          <td><?php echo (int) $row['level']; ?></td>
          <td><?php echo htmlspecialchars($row['account_status']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p>No leaderboard data yet.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

