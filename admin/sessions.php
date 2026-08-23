<?php
/**
 * SkillSwap Campus - Admin All Sessions Monitoring
 */
require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Admin Sessions - SkillSwap Campus';
$pdo = Database::getConnection();

// Fetch all sessions
$stmt = $pdo->query("
    SELECT s.*, 
           t.name AS teacher_name, l.name AS learner_name,
           sk.skill_name
    FROM sessions s
    JOIN users t ON s.teacher_id = t.user_id
    JOIN users l ON s.learner_id = l.user_id
    JOIN skills sk ON s.skill_id = sk.skill_id
    ORDER BY s.session_date DESC
");
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="container-fluid">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
          <h2 class="fw-bold mb-1">Global Learning Sessions</h2>
          <p class="text-muted mb-0">Track all online and offline sessions scheduled across the platform.</p>
        </div>
      </div>

      <div class="card-custom p-4">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Skill</th>
                <th>Teacher</th>
                <th>Learner</th>
                <th>Date & Time</th>
                <th>Mode</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sessions as $s): ?>
              <tr>
                <td>#<?= $s['session_id'] ?></td>
                <td><span class="fw-bold text-dark"><?= htmlspecialchars($s['skill_name']) ?></span></td>
                <td class="small"><?= htmlspecialchars($s['teacher_name']) ?></td>
                <td class="small"><?= htmlspecialchars($s['learner_name']) ?></td>
                <td class="small">
                  <?= date('M d, Y', strtotime($s['session_date'])) ?><br>
                  <span class="text-muted"><?= date('h:i A', strtotime($s['start_time'])) ?></span>
                </td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['mode']) ?></span></td>
                <td>
                  <?php if ($s['status'] === 'COMPLETED'): ?>
                    <span class="badge bg-success-subtle text-success">COMPLETED</span>
                  <?php elseif ($s['status'] === 'SCHEDULED'): ?>
                    <span class="badge bg-warning-subtle text-warning">SCHEDULED</span>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($s['status']) ?></span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
