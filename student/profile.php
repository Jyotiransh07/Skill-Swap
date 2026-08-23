<?php
/**
 * SkillSwap Campus - Student Profile View Page
 */
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'My Profile - SkillSwap Campus';

// View target user ID if provided via GET, else view logged-in user profile
$targetUserId = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];
$isOwnProfile = ($targetUserId === $_SESSION['user_id']);

$pdo = Database::getConnection();

// Fetch target user info
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE user_id = :id AND status = 'active'");
$stmtUser->execute(['id' => $targetUserId]);
$profileUser = $stmtUser->fetch();

if (!$profileUser) {
    set_flash('danger', 'Student profile not found.');
    header('Location: ' . $baseUrl . 'student/dashboard.php');
    exit;
}

// Fetch teach & learn skills
$stmtSkills = $pdo->prepare("
    SELECT us.*, s.skill_name, s.category, s.description
    FROM user_skills us
    JOIN skills s ON us.skill_id = s.skill_id
    WHERE us.user_id = :id
    ORDER BY us.skill_type ASC, s.skill_name ASC
");
$stmtSkills->execute(['id' => $targetUserId]);
$allSkills = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);

$teachSkills = array_filter($allSkills, fn($s) => $s['skill_type'] === 'TEACH');
$learnSkills = array_filter($allSkills, fn($s) => $s['skill_type'] === 'LEARN');

// Fetch reviews received
$stmtReviews = $pdo->prepare("
    SELECT r.*, reviewer.name AS reviewer_name, reviewer.profile_image AS reviewer_avatar, s.skill_id, sk.skill_name
    FROM reviews r
    JOIN users reviewer ON r.reviewer_id = reviewer.user_id
    JOIN sessions s ON r.session_id = s.session_id
    JOIN skills sk ON s.skill_id = sk.skill_id
    WHERE r.reviewee_id = :id
    ORDER BY r.created_at DESC
");
$stmtReviews->execute(['id' => $targetUserId]);
$reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats
$avgRating = 0.0;
if (!empty($reviews)) {
    $sum = array_sum(array_column($reviews, 'rating'));
    $avgRating = round($sum / count($reviews), 1);
}

$stmtSessCount = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE (teacher_id = :id1 OR learner_id = :id2) AND status = 'COMPLETED'");
$stmtSessCount->execute(['id1' => $targetUserId, 'id2' => $targetUserId]);
$completedSessionsCount = (int)$stmtSessCount->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="container-fluid">
      <!-- Profile Header Card -->
      <div class="card-custom p-4 p-md-5 mb-4 position-relative">
        <div class="row align-items-center g-4">
          <div class="col-auto">
            <img src="<?= $baseUrl ?>uploads/profiles/<?= htmlspecialchars($profileUser['profile_image']) ?>" alt="Profile Avatar" class="avatar-lg" style="width: 100px; height: 100px;" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($profileUser['name']) ?>&background=4f46e5&color=fff';">
          </div>
          <div class="col">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
              <h2 class="fw-bold text-dark mb-0"><?= htmlspecialchars($profileUser['name']) ?></h2>
              <span class="badge bg-primary-subtle text-primary fw-semibold"><?= htmlspecialchars($profileUser['year']) ?></span>
            </div>
            <p class="text-muted mb-2"><i class="bi bi-building me-1"></i> <?= htmlspecialchars($profileUser['department']) ?> • College ID: <code><?= htmlspecialchars($profileUser['college_id']) ?></code></p>
            <p class="text-secondary small mb-0"><?= htmlspecialchars($profileUser['bio'] ?? 'No bio written yet.') ?></p>
          </div>

          <div class="col-md-auto text-md-end">
            <div class="d-inline-block text-center p-3 bg-light rounded-3 border me-md-2 mb-2 mb-md-0">
              <div class="h3 fw-bold text-primary mb-0"><?= round($profileUser['reputation_score']) ?>/100</div>
              <span class="small text-muted font-semibold"><i class="bi bi-shield-check text-warning"></i> Reputation</span>
            </div>

            <?php if ($isOwnProfile): ?>
              <a href="<?= $baseUrl ?>student/edit-profile.php" class="btn btn-outline-custom">
                <i class="bi bi-pencil-square me-1"></i> Edit Profile
              </a>
            <?php else: ?>
              <a href="<?= $baseUrl ?>student/matches.php" class="btn btn-primary-custom">
                <i class="bi bi-send me-1"></i> Send Request
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Overview Stats Strip -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="card-custom p-3 text-center">
            <h4 class="fw-bold text-warning mb-0">⭐ <?= $avgRating > 0 ? $avgRating : '5.0' ?></h4>
            <span class="small text-muted">Average Rating (<?= count($reviews) ?> Reviews)</span>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card-custom p-3 text-center">
            <h4 class="fw-bold text-primary mb-0"><?= $completedSessionsCount ?></h4>
            <span class="small text-muted">Completed Sessions</span>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card-custom p-3 text-center">
            <h4 class="fw-bold text-success mb-0"><?= count($teachSkills) ?> Teach / <?= count($learnSkills) ?> Learn</h4>
            <span class="small text-muted">Active Skills Pool</span>
          </div>
        </div>
      </div>

      <!-- Skills Grid (Teach & Learn) -->
      <div class="row g-4 mb-4">
        <!-- Skills I Can Teach -->
        <div class="col-md-6">
          <div class="card-custom p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="fw-bold text-dark mb-0"><i class="bi bi-book text-success me-2"></i>Skills I Can Teach</h5>
              <?php if ($isOwnProfile): ?>
                <a href="<?= $baseUrl ?>student/skills.php" class="small text-primary font-semibold">+ Add Skill</a>
              <?php endif; ?>
            </div>

            <?php if (empty($teachSkills)): ?>
              <p class="text-muted small mb-0">No teach skills added yet.</p>
            <?php else: ?>
              <div class="d-flex flex-wrap gap-2">
                <?php foreach ($teachSkills as $ts): ?>
                  <div class="p-2 bg-success-subtle rounded-3 border border-success border-opacity-25 me-2 mb-2">
                    <span class="fw-bold text-success me-1"><?= htmlspecialchars($ts['skill_name']) ?></span>
                    <span class="badge bg-white text-secondary"><?= htmlspecialchars($ts['skill_level']) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Skills I Want To Learn -->
        <div class="col-md-6">
          <div class="card-custom p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="fw-bold text-dark mb-0"><i class="bi bi-mortarboard text-primary me-2"></i>Skills I Want to Learn</h5>
              <?php if ($isOwnProfile): ?>
                <a href="<?= $baseUrl ?>student/skills.php" class="small text-primary font-semibold">+ Add Skill</a>
              <?php endif; ?>
            </div>

            <?php if (empty($learnSkills)): ?>
              <p class="text-muted small mb-0">No learn skills added yet.</p>
            <?php else: ?>
              <div class="d-flex flex-wrap gap-2">
                <?php foreach ($learnSkills as $ls): ?>
                  <div class="p-2 bg-primary-subtle rounded-3 border border-primary border-opacity-25 me-2 mb-2">
                    <span class="fw-bold text-primary me-1"><?= htmlspecialchars($ls['skill_name']) ?></span>
                    <span class="badge bg-white text-secondary"><?= htmlspecialchars($ls['skill_level']) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Peer Reviews Section -->
      <div class="card-custom p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-chat-left-text text-warning me-2"></i>Peer Exchange Reviews</h5>
        <?php if (empty($reviews)): ?>
          <p class="text-muted small mb-0">No reviews received yet. Complete exchange sessions to collect reviews!</p>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($reviews as $rev): ?>
            <div class="col-md-6">
              <div class="p-3 bg-light rounded-3 border">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <img src="<?= $baseUrl ?>uploads/profiles/<?= htmlspecialchars($rev['reviewer_avatar']) ?>" class="avatar-sm" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($rev['reviewer_name']) ?>';">
                    <span class="fw-bold text-dark small"><?= htmlspecialchars($rev['reviewer_name']) ?></span>
                  </div>
                  <div class="text-warning small">
                    <?= str_repeat('★', (int)$rev['rating']) ?>
                  </div>
                </div>
                <div class="small text-muted mb-1">Skill: <strong><?= htmlspecialchars($rev['skill_name']) ?></strong></div>
                <p class="small text-dark mb-0 italic">"<?= htmlspecialchars($rev['comment']) ?>"</p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
