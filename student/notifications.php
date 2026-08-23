<?php
/**
 * SkillSwap Campus - Notifications Center
 */
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Notifications - SkillSwap Campus';
$currentUserId = $_SESSION['user_id'];
$pdo = Database::getConnection();

// Handle Mark All as Read
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    $stmtUp = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :id");
    $stmtUp->execute(['id' => $currentUserId]);
    set_flash('success', 'All notifications marked as read.');
    header('Location: ' . $baseUrl . 'student/notifications.php');
    exit;
}

// Fetch all notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :id ORDER BY created_at DESC");
$stmt->execute(['id' => $currentUserId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="container-fluid" style="max-width: 900px;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
          <h2 class="fw-bold mb-1"><i class="bi bi-bell text-primary me-2"></i>Notifications Center</h2>
          <p class="text-muted mb-0">Stay updated on exchange requests, session reminders, and reviews.</p>
        </div>
        <?php if (!empty($notifications)): ?>
          <a href="notifications.php?action=mark_all_read" class="btn btn-outline-custom mt-3 mt-md-0">
            <i class="bi bi-check-all me-1"></i> Mark All as Read
          </a>
        <?php endif; ?>
      </div>

      <?php if (empty($notifications)): ?>
        <div class="card-custom p-5 text-center text-muted">
          <i class="bi bi-bell-slash fs-1 d-block mb-2 opacity-50"></i>
          <p class="mb-0">You have no notifications yet.</p>
        </div>
      <?php else: ?>
        <div class="list-group shadow-sm rounded-4 overflow-hidden border">
          <?php foreach ($notifications as $n): ?>
          <div class="list-group-item p-3 border-bottom d-flex align-items-start justify-content-between gap-3 <?= $n['is_read'] ? 'bg-white' : 'bg-light border-start border-4 border-primary' ?>">
            <div class="d-flex align-items-start gap-3">
              <div class="stat-icon primary p-2 rounded-circle flex-shrink-0" style="width:38px; height:38px; font-size:1rem;">
                <i class="bi bi-bell-fill"></i>
              </div>
              <div>
                <p class="mb-1 text-dark fw-medium"><?= htmlspecialchars($n['message']) ?></p>
                <span class="text-muted small"><?= date('l, M d, Y \a\t h:i A', strtotime($n['created_at'])) ?></span>
              </div>
            </div>
            <?php if (!$n['is_read']): ?>
              <button class="btn btn-sm btn-light border btn-mark-notification-read text-nowrap" data-id="<?= $n['notification_id'] ?>">
                Mark Read
              </button>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
