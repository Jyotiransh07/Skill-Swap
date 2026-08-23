<?php
/**
 * SkillSwap Campus - Admin Reviews Moderation
 */
require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Admin Reviews Moderation - SkillSwap Campus';
$pdo = Database::getConnection();

// Handle Delete Review
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $delId = (int)$_GET['delete'];
    $stmtDel = $pdo->prepare("DELETE FROM reviews WHERE review_id = :id");
    $stmtDel->execute(['id' => $delId]);
    set_flash('success', 'Review removed.');
    header('Location: ' . $baseUrl . 'admin/reviews.php');
    exit;
}

// Fetch all reviews
$stmt = $pdo->query("
    SELECT r.*, 
           rvr.name AS reviewer_name, rve.name AS reviewee_name, rve.user_id AS reviewee_id,
           sk.skill_name
    FROM reviews r
    JOIN users rvr ON r.reviewer_id = rvr.user_id
    JOIN users rve ON r.reviewee_id = rve.user_id
    JOIN sessions s ON r.session_id = s.session_id
    JOIN skills sk ON s.skill_id = sk.skill_id
    ORDER BY r.created_at DESC
");
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="container-fluid">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
          <h2 class="fw-bold mb-1">Global Peer Reviews</h2>
          <p class="text-muted mb-0">Monitor and moderate peer rating testimonials.</p>
        </div>
      </div>

      <div class="card-custom p-4">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Reviewer</th>
                <th>Reviewee</th>
                <th>Skill</th>
                <th>Rating</th>
                <th>Comment</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reviews as $rev): ?>
              <tr>
                <td>#<?= $rev['review_id'] ?></td>
                <td class="small fw-bold"><?= htmlspecialchars($rev['reviewer_name']) ?></td>
                <td class="small fw-bold"><?= htmlspecialchars($rev['reviewee_name']) ?></td>
                <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($rev['skill_name']) ?></span></td>
                <td><span class="text-warning fw-bold">⭐ <?= $rev['rating'] ?>/5</span></td>
                <td class="small text-muted" style="max-width:300px;">"<?= htmlspecialchars($rev['comment']) ?>"</td>
                <td class="small text-muted"><?= date('M d, Y', strtotime($rev['created_at'])) ?></td>
                <td>
                  <a href="reviews.php?delete=<?= $rev['review_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this review?');">Delete</a>
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
