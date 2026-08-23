<?php
/**
 * SkillSwap Campus - Student Main Dashboard
 */
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Student Dashboard - SkillSwap Campus';

$userId = $_SESSION['user_id'];
$pdo = Database::getConnection();

// Fetch current user details
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE user_id = :id");
$stmtUser->execute(['id' => $userId]);
$user = $stmtUser->fetch();

// 1. Statistics
$stmtTeach = $pdo->prepare("SELECT COUNT(*) FROM user_skills WHERE user_id = :id AND skill_type = 'TEACH'");
$stmtTeach->execute(['id' => $userId]);
$countTeach = (int)$stmtTeach->fetchColumn();

$stmtLearn = $pdo->prepare("SELECT COUNT(*) FROM user_skills WHERE user_id = :id AND skill_type = 'LEARN'");
$stmtLearn->execute(['id' => $userId]);
$countLearn = (int)$stmtLearn->fetchColumn();

$stmtExchanges = $pdo->prepare("SELECT COUNT(*) FROM learning_requests WHERE (sender_id = :id1 OR receiver_id = :id2) AND status IN ('ACCEPTED', 'COMPLETED')");
$stmtExchanges->execute(['id1' => $userId, 'id2' => $userId]);
$countExchanges = (int)$stmtExchanges->fetchColumn();

$stmtSessions = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE (teacher_id = :id1 OR learner_id = :id2) AND status = 'COMPLETED'");
$stmtSessions->execute(['id1' => $userId, 'id2' => $userId]);
$countSessions = (int)$stmtSessions->fetchColumn();

// Calculate profile completion %
$completionScore = 20; // base for registration
if ($countTeach > 0) $completionScore += 25;
if ($countLearn > 0) $completionScore += 25;
if (!empty($user['bio'])) $completionScore += 15;
if (!empty($user['profile_image']) && $user['profile_image'] !== 'default-avatar.png') $completionScore += 15;

// 2. Fetch Top Smart Matches (Candidates)
$stmtOtherUsers = $pdo->prepare("SELECT user_id FROM users WHERE user_id <> :id AND role = 'student' AND status = 'active' LIMIT 15");
$stmtOtherUsers->execute(['id' => $userId]);
$candidateIds = $stmtOtherUsers->fetchAll(PDO::FETCH_COLUMN);

$recommendedMatches = [];
foreach ($candidateIds as $cid) {
    $matchData = calculate_skill_match_score($pdo, $userId, (int)$cid);
    if ($matchData['score'] > 0) {
        $stmtCand = $pdo->prepare("SELECT user_id, name, department, year, profile_image, reputation_score FROM users WHERE user_id = :id");
        $stmtCand->execute(['id' => $cid]);
        $candInfo = $stmtCand->fetch();
        $recommendedMatches[] = array_merge($candInfo, $matchData);
    }
}

// Sort matches by score DESC
usort($recommendedMatches, function($a, $b) {
    return $b['score'] <=> $a['score'];
});
$topMatches = array_slice($recommendedMatches, 0, 3);

// 3. Upcoming Scheduled Sessions
$stmtUpcoming = $pdo->prepare("
    SELECT s.*, sk.skill_name,
           t.name AS teacher_name, l.name AS learner_name
    FROM sessions s
    JOIN skills sk ON s.skill_id = sk.skill_id
    JOIN users t ON s.teacher_id = t.user_id
    JOIN users l ON s.learner_id = l.user_id
    WHERE (s.teacher_id = :id1 OR s.learner_id = :id2) AND s.status = 'SCHEDULED'
    ORDER BY s.session_date ASC, s.start_time ASC
    LIMIT 3
");
$stmtUpcoming->execute(['id1' => $userId, 'id2' => $userId]);
$upcomingSessions = $stmtUpcoming->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="container-fluid">
      <!-- Welcome Header -->
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
          <h2 class="fw-bold mb-1">Welcome back, <?= htmlspecialchars($user['name']) ?> 👋</h2>
          <p class="text-muted mb-0">Here is your campus skill exchange summary for today.</p>
        </div>
        <div class="mt-3 mt-md-0">
          <a href="<?= $baseUrl ?>student/matches.php" class="btn btn-primary-custom">
            <i class="bi bi-stars text-warning me-1"></i> View All Smart Matches
          </a>
        </div>
      </div>

      <!-- Profile Completion Banner if < 100% -->
      <?php if ($completionScore < 100): ?>
      <div class="card-custom p-3 bg-white mb-4 border-start border-4 border-primary">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
          <div>
            <div class="fw-bold text-dark mb-1">Complete your Skill Profile (<?= $completionScore ?>% Completed)</div>
            <p class="text-muted small mb-0">Add skills you teach and want to learn to get 100% precision smart matches.</p>
          </div>
          <div class="d-flex align-items-center gap-3" style="min-width: 250px;">
            <div class="progress flex-grow-1" style="height: 10px;">
              <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $completionScore ?>%;"></div>
            </div>
            <a href="<?= $baseUrl ?>student/skills.php" class="btn btn-sm btn-outline-custom text-nowrap">Add Skills</a>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- 5 Key KPI Statistics Cards -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-lg">
          <div class="stat-card">
            <div class="stat-icon primary"><i class="bi bi-book"></i></div>
            <div class="stat-details">
              <h3><?= $countTeach ?></h3>
              <p>Skills I Teach</p>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg">
          <div class="stat-card">
            <div class="stat-icon success"><i class="bi bi-mortarboard"></i></div>
            <div class="stat-details">
              <h3><?= $countLearn ?></h3>
              <p>Skills I Want</p>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg">
          <div class="stat-card">
            <div class="stat-icon warning"><i class="bi bi-arrow-repeat"></i></div>
            <div class="stat-details">
              <h3><?= $countExchanges ?></h3>
              <p>Active Exchanges</p>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg">
          <div class="stat-card">
            <div class="stat-icon accent"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-details">
              <h3><?= $countSessions ?></h3>
              <p>Sessions Completed</p>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg">
          <div class="stat-card border-primary">
            <div class="stat-icon" style="background:#e0e7ff; color:#4f46e5;"><i class="bi bi-shield-check"></i></div>
            <div class="stat-details">
              <h3 class="text-primary"><?= round($user['reputation_score']) ?><span class="fs-6 text-muted">/100</span></h3>
              <p>Reputation Score</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Dashboard Grid: Recommended Matches + Upcoming Sessions -->
      <div class="row g-4 mb-4">
        <!-- Top Recommended Matches -->
        <div class="col-lg-8">
          <div class="card-custom p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div>
                <h5 class="fw-bold mb-0"><i class="bi bi-stars text-warning me-2"></i>Top Recommended Skill Matches</h5>
                <span class="text-muted small">Matched automatically based on your learning interests</span>
              </div>
              <a href="<?= $baseUrl ?>student/matches.php" class="btn btn-sm btn-light border fw-semibold">View All</a>
            </div>

            <?php if (empty($topMatches)): ?>
              <div class="text-center py-5 text-muted">
                <i class="bi bi-search fs-1 d-block mb-2 text-secondary opacity-50"></i>
                <p class="mb-1 fw-semibold">No skill matches calculated yet.</p>
                <p class="small">Add skills in <a href="<?= $baseUrl ?>student/skills.php">My Skills</a> to unlock high-compatibility matches!</p>
              </div>
            <?php else: ?>
              <div class="row g-3">
                <?php foreach ($topMatches as $match): ?>
                <div class="col-md-6 col-lg-12">
                  <div class="match-card <?= $match['is_perfect'] ? 'perfect-match' : '' ?>">
                    <?php if ($match['is_perfect']): ?>
                      <span class="match-badge-tag"><i class="bi bi-patch-check-fill me-1"></i> Perfect Exchange</span>
                    <?php endif; ?>

                    <div class="d-flex align-items-center gap-3 mb-3">
                      <div class="match-ring" style="--score: <?= $match['score'] ?>;">
                        <span class="match-ring-text"><?= $match['score'] ?>%</span>
                      </div>
                      <div>
                        <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($match['name']) ?></h6>
                        <span class="text-muted small"><?= htmlspecialchars($match['department']) ?> • <?= htmlspecialchars($match['year']) ?></span>
                      </div>
                    </div>

                    <div class="bg-light p-2 rounded-3 small mb-3">
                      <?php if (!empty($match['learn_skills'])): ?>
                        <div class="mb-1"><strong>You Learn:</strong> <span class="badge-learn"><?= implode(', ', $match['learn_skills']) ?></span></div>
                      <?php endif; ?>
                      <?php if (!empty($match['teach_skills'])): ?>
                        <div><strong>You Teach:</strong> <span class="badge-teach"><?= implode(', ', $match['teach_skills']) ?></span></div>
                      <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                      <span class="small text-muted"><i class="bi bi-shield-star me-1 text-warning"></i> Rep: <?= round($match['reputation_score']) ?>/100</span>
                      <a href="<?= $baseUrl ?>student/matches.php" class="btn btn-sm btn-primary-custom">Connect & Swap</a>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Upcoming Sessions Widget -->
        <div class="col-lg-4">
          <div class="card-custom p-4 h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-calendar-event me-2 text-primary"></i>Upcoming Sessions</h5>
                <a href="<?= $baseUrl ?>student/sessions.php" class="small text-primary fw-semibold">View All</a>
              </div>

              <?php if (empty($upcomingSessions)): ?>
                <div class="text-center py-4 text-muted">
                  <i class="bi bi-calendar-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                  <p class="small mb-0">No upcoming sessions scheduled.</p>
                </div>
              <?php else: ?>
                <div class="list-group list-group-flush">
                  <?php foreach ($upcomingSessions as $sess): ?>
                  <div class="list-group-item px-0 py-3 bg-transparent border-bottom">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                      <span class="fw-bold text-dark small"><?= htmlspecialchars($sess['skill_name']) ?></span>
                      <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($sess['mode']) ?></span>
                    </div>
                    <div class="small text-muted mb-2">
                      <i class="bi bi-person me-1"></i> Partner: <?= htmlspecialchars($sess['teacher_id'] == $userId ? $sess['learner_name'] : $sess['teacher_name']) ?>
                    </div>
                    <div class="small text-secondary fw-semibold">
                      <i class="bi bi-clock me-1"></i> <?= date('M d, Y', strtotime($sess['session_date'])) ?> at <?= date('h:i A', strtotime($sess['start_time'])) ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="pt-3 border-top mt-3">
              <a href="<?= $baseUrl ?>student/sessions.php" class="btn btn-outline-custom w-100 text-center">
                Schedule New Session
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
