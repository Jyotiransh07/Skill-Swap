<?php
/**
 * SkillSwap Campus - Admin All Exchange Requests Monitoring
 */
require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Admin Exchange Requests - SkillSwap Campus';
$pdo = Database::getConnection();

// Fetch all requests
$stmt = $pdo->query("
    SELECT lr.*, 
           snd.name AS sender_name, snd.email AS sender_email,
           rec.name AS receiver_name, rec.email AS receiver_email,
           sk.skill_name
    FROM learning_requests lr
    JOIN users snd ON lr.sender_id = snd.user_id
    JOIN users rec ON lr.receiver_id = rec.user_id
    JOIN skills sk ON lr.skill_id = sk.skill_id
    ORDER BY lr.created_at DESC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="container-fluid">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
          <h2 class="fw-bold mb-1">Global Exchange Requests</h2>
          <p class="text-muted mb-0">Monitor all peer learning request proposals across campus.</p>
        </div>
      </div>

      <div class="card-custom p-4">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Sender</th>
                <th>Receiver</th>
                <th>Requested Skill</th>
                <th>Message</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($requests as $r): ?>
              <tr>
                <td>#<?= $r['request_id'] ?></td>
                <td>
                  <div class="fw-bold text-dark small"><?= htmlspecialchars($r['sender_name']) ?></div>
                  <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($r['sender_email']) ?></div>
                </td>
                <td>
                  <div class="fw-bold text-dark small"><?= htmlspecialchars($r['receiver_name']) ?></div>
                  <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($r['receiver_email']) ?></div>
                </td>
                <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($r['skill_name']) ?></span></td>
                <td class="small text-muted" style="max-width:250px;">"<?= htmlspecialchars($r['message']) ?>"</td>
                <td>
                  <?php if ($r['status'] === 'ACCEPTED'): ?>
                    <span class="badge bg-success-subtle text-success">ACCEPTED</span>
                  <?php elseif ($r['status'] === 'PENDING'): ?>
                    <span class="badge bg-warning-subtle text-warning">PENDING</span>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($r['status']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="small text-muted"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
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
