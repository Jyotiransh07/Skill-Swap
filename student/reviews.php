<?php
/**
 * SkillSwap Campus - Peer Reviews Page
 */
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Peer Reviews - SkillSwap Campus';
$currentUserId = $_SESSION['user_id'];
$pdo = Database::getConnection();

$error = '';

// Pre-selected session ID from GET if coming from completed sessions page
$targetSessionId = (int)($_GET['session_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $sessionId  = (int)($_POST['session_id'] ?? 0);
    $rating     = (int)($_POST['rating'] ?? 5);
    $comment    = sanitize($_POST['comment'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $error = 'Security check failed.';
    } elseif ($sessionId <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
        $error = 'Please select a session, rating (1-5 stars), and write a review comment.';
    } else {
        // Fetch session details
        $stmtSess = $pdo->prepare("SELECT * FROM sessions WHERE session_id = :id AND status = 'COMPLETED'");
        $stmtSess->execute(['id' => $sessionId]);
        $sess = $stmtSess->fetch();

        if (!$sess) {
            $error = 'Completed session not found.';
        } else {
            $revieweeId = ($sess['teacher_id'] === $currentUserId) ? $sess['learner_id'] : $sess['teacher_id'];

            try {
                $stmtRev = $pdo->prepare("
                    INSERT INTO reviews (session_id, reviewer_id, reviewee_id, rating, comment, created_at)
                    VALUES (:sess_id, :rvr_id, :rve_id, :rating, :comment, NOW())
                ");
                $stmtRev->execute([
                    'sess_id' => $sessionId,
                    'rvr_id'  => $currentUserId,
                    'rve_id'  => $revieweeId,
                    'rating'  => $rating,
                    'comment' => $comment
                ]);

                // Recalculate reputation for reviewee
                update_reputation_score($pdo, $revieweeId);

                create_notification($pdo, $revieweeId, $_SESSION['user_name'] . " gave you a $rating-star review!", "REVIEW");

                set_flash('success', 'Review submitted successfully! Thank you for rating your peer.');
                header('Location: ' . $baseUrl . 'student/reviews.php');
                exit;
            } catch (Exception $e) {
                $error = 'You have already submitted a review for this session.';
            }
        }
    }
}

// Fetch Completed Sessions eligible for review
$stmtEligible = $pdo->prepare("
    SELECT s.session_id, sk.skill_name,
           CASE WHEN s.teacher_id = :id1 THEN l.name ELSE t.name END AS partner_name
    FROM sessions s
    JOIN skills sk ON s.skill_id = sk.skill_id
    JOIN users t ON s.teacher_id = t.user_id
    JOIN users l ON s.learner_id = l.user_id
    LEFT JOIN reviews r ON s.session_id = r.session_id AND r.reviewer_id = :id2
    WHERE (s.teacher_id = :id3 OR s.learner_id = :id4) AND s.status = 'COMPLETED' AND r.review_id IS NULL
");
$stmtEligible->execute(['id1' => $currentUserId, 'id2' => $currentUserId, 'id3' => $currentUserId, 'id4' => $currentUserId]);
$eligibleSessions = $stmtEligible->fetchAll(PDO::FETCH_ASSOC);

// Fetch Review History Given and Received
$stmtHistory = $pdo->prepare("
    SELECT r.*, 
           reviewer.name AS reviewer_name, reviewer.profile_image AS reviewer_avatar,
           reviewee.name AS reviewee_name, reviewee.profile_image AS reviewee_avatar,
           sk.skill_name
    FROM reviews r
    JOIN users reviewer ON r.reviewer_id = reviewer.user_id
    JOIN users reviewee ON r.reviewee_id = reviewee.user_id
    JOIN sessions s ON r.session_id = s.session_id
    JOIN skills sk ON s.skill_id = sk.skill_id
    WHERE r.reviewer_id = :id1 OR r.reviewee_id = :id2
    ORDER BY r.created_at DESC
");
$stmtHistory->execute(['id1' => $currentUserId, 'id2' => $currentUserId]);
$reviewsHistory = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

$csrf_token = generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="container-fluid">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
          <h2 class="fw-bold mb-1">Peer Ratings & Reviews</h2>
          <p class="text-muted mb-0">Give feedback for completed sessions and build your campus reputation.</p>
        </div>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Submit Review Card -->
      <div class="card-custom p-4 mb-4 border-start border-4 border-warning">
        <h5 class="fw-bold mb-3"><i class="bi bi-star-fill text-warning me-2"></i>How was your skill exchange session?</h5>

        <?php if (empty($eligibleSessions)): ?>
          <p class="text-muted small mb-0">No pending sessions awaiting your review. Complete a session to submit a review!</p>
        <?php else: ?>
          <form method="POST" action="reviews.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="action" value="submit_review">

            <div class="row g-3">
              <div class="col-md-6">
                <label for="session_id" class="form-label fw-semibold">Select Completed Session *</label>
                <select class="form-select" id="session_id" name="session_id" required>
                  <option value="" disabled selected>Choose session...</option>
                  <?php foreach ($eligibleSessions as $es): ?>
                    <option value="<?= $es['session_id'] ?>" <?= $targetSessionId === $es['session_id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($es['skill_name']) ?> with <?= htmlspecialchars($es['partner_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-6">
                <label for="rating" class="form-label fw-semibold">Star Rating (1 to 5) *</label>
                <select class="form-select" id="rating" name="rating" required>
                  <option value="5" selected>⭐⭐⭐⭐⭐ (5/5) - Outstanding!</option>
                  <option value="4">⭐⭐⭐⭐ (4/5) - Very Good</option>
                  <option value="3">⭐⭐⭐ (3/5) - Good</option>
                  <option value="2">⭐⭐ (2/5) - Fair</option>
                  <option value="1">⭐ (1/5) - Poor</option>
                </select>
              </div>

              <div class="col-12">
                <label for="comment" class="form-label fw-semibold">Review Feedback *</label>
                <textarea class="form-control" id="comment" name="comment" rows="3" required placeholder="Write a short testimonial about your peer's teaching style, punctuality, and skill clarity..."></textarea>
              </div>
            </div>

            <button type="submit" class="btn btn-primary-custom mt-3">Submit Review</button>
          </form>
        <?php endif; ?>
      </div>

      <!-- Review History -->
      <h5 class="fw-bold mb-3"><i class="bi bi-journal-text me-2"></i>Review History</h5>
      <?php if (empty($reviewsHistory)): ?>
        <div class="card-custom p-4 text-center text-muted">
          <p class="mb-0">No review history available.</p>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($reviewsHistory as $rh): ?>
          <div class="col-md-6">
            <div class="card-custom p-3">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <span class="badge bg-primary-subtle text-primary mb-1"><?= htmlspecialchars($rh['skill_name']) ?></span>
                  <div class="fw-bold text-dark small">
                    <?= $rh['reviewer_id'] === $currentUserId ? 'Given to: ' . htmlspecialchars($rh['reviewee_name']) : 'Received from: ' . htmlspecialchars($rh['reviewer_name']) ?>
                  </div>
                </div>
                <div class="text-warning small">
                  <?= str_repeat('★', (int)$rh['rating']) ?>
                </div>
              </div>
              <p class="small text-muted mb-1">"<?= htmlspecialchars($rh['comment']) ?>"</p>
              <span class="text-secondary" style="font-size: 0.7rem;"><?= date('M d, Y', strtotime($rh['created_at'])) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
