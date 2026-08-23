<?php
/**
 * SkillSwap Campus - Smart Skill Matches Core Page
 */
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Smart Skill Matches - SkillSwap Campus';
$currentUserId = $_SESSION['user_id'];
$pdo = Database::getConnection();

$message = '';
$error   = '';

// Handle Exchange Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_request') {
    $receiverId = (int)($_POST['receiver_id'] ?? 0);
    $skillId    = (int)($_POST['skill_id'] ?? 0);
    $msgText    = sanitize($_POST['message'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $error = 'Security validation failed.';
    } elseif ($receiverId <= 0 || $skillId <= 0 || empty($msgText)) {
        $error = 'Please select a skill and write a short proposal message.';
    } else {
        try {
            // Check existing request
            $stmtCheck = $pdo->prepare("
                SELECT request_id FROM learning_requests 
                WHERE sender_id = :sid AND receiver_id = :rid AND skill_id = :skid AND status = 'PENDING'
            ");
            $stmtCheck->execute(['sid' => $currentUserId, 'rid' => $receiverId, 'skid' => $skillId]);

            if ($stmtCheck->fetch()) {
                $error = 'You already have a pending exchange request with this student for this skill.';
            } else {
                $stmtReq = $pdo->prepare("
                    INSERT INTO learning_requests (sender_id, receiver_id, skill_id, message, status, created_at)
                    VALUES (:sid, :rid, :skid, :msg, 'PENDING', NOW())
                ");
                $stmtReq->execute([
                    'sid'  => $currentUserId,
                    'rid'  => $receiverId,
                    'skid' => $skillId,
                    'msg'  => $msgText
                ]);

                // Create notification for receiver
                $senderName = $_SESSION['user_name'];
                create_notification($pdo, $receiverId, "$senderName sent you a skill exchange request!", "REQUEST");

                set_flash('success', 'Skill exchange request sent successfully!');
                header('Location: ' . $baseUrl . 'student/requests.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Error sending request: ' . $e->getMessage();
        }
    }
}

// Fetch all active students except current user
$stmtOtherUsers = $pdo->prepare("SELECT user_id FROM users WHERE user_id <> :id AND role = 'student' AND status = 'active'");
$stmtOtherUsers->execute(['id' => $currentUserId]);
$candidateIds = $stmtOtherUsers->fetchAll(PDO::FETCH_COLUMN);

$allMatches = [];
foreach ($candidateIds as $cid) {
    $matchData = calculate_skill_match_score($pdo, $currentUserId, (int)$cid);
    if ($matchData['score'] > 0) {
        $stmtCand = $pdo->prepare("SELECT user_id, name, department, year, profile_image, reputation_score, bio FROM users WHERE user_id = :id");
        $stmtCand->execute(['id' => $cid]);
        $candInfo = $stmtCand->fetch();

        // Fetch candidates teaching skills list for dropdown
        $stmtTeachSkills = $pdo->prepare("
            SELECT s.skill_id, s.skill_name 
            FROM user_skills us
            JOIN skills s ON us.skill_id = s.skill_id
            WHERE us.user_id = :id AND us.skill_type = 'TEACH'
        ");
        $stmtTeachSkills->execute(['id' => $cid]);
        $candInfo['teach_skills_list'] = $stmtTeachSkills->fetchAll(PDO::FETCH_ASSOC);

        $allMatches[] = array_merge($candInfo, $matchData);
    }
}

// Sort by score DESC
usort($allMatches, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

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
          <h2 class="fw-bold mb-1"><i class="bi bi-stars text-warning me-2"></i>Your Smart Skill Matches</h2>
          <p class="text-muted mb-0">Students whose skill set and learning goals perfectly complement yours.</p>
        </div>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (empty($allMatches)): ?>
        <div class="card-custom p-5 text-center">
          <i class="bi bi-stars fs-1 text-warning d-block mb-3 opacity-75"></i>
          <h4 class="fw-bold text-dark mb-1">No Skill Matches Found Yet</h4>
          <p class="text-muted small mb-3">Add more skills to your profile to let our compatibility algorithm discover perfect peers!</p>
          <a href="skills.php" class="btn btn-primary-custom">Go to My Skills</a>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($allMatches as $m): ?>
          <div class="col-lg-6">
            <div class="match-card <?= $m['is_perfect'] ? 'perfect-match' : '' ?> h-100 d-flex flex-column justify-content-between">
              <div>
                <?php if ($m['is_perfect']): ?>
                  <span class="match-badge-tag"><i class="bi bi-patch-check-fill me-1"></i> Perfect Exchange</span>
                <?php endif; ?>

                <div class="d-flex align-items-center gap-3 mb-3">
                  <div class="match-ring" style="--score: <?= $m['score'] ?>;">
                    <span class="match-ring-text"><?= $m['score'] ?>%</span>
                  </div>
                  <div>
                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($m['name']) ?></h5>
                    <span class="text-muted small"><i class="bi bi-building me-1"></i> <?= htmlspecialchars($m['department']) ?> • <?= htmlspecialchars($m['year']) ?></span>
                    <div class="small text-warning mt-1">
                      <i class="bi bi-shield-check"></i> Reputation Score: <strong><?= round($m['reputation_score']) ?>/100</strong>
                    </div>
                  </div>
                </div>

                <!-- Skill Exchange Breakdown -->
                <div class="bg-light p-3 rounded-3 mb-3">
                  <?php if (!empty($m['learn_skills'])): ?>
                    <div class="mb-2">
                      <strong class="text-dark small">You Learn:</strong>
                      <span class="badge-learn"><?= implode(', ', $m['learn_skills']) ?></span>
                    </div>
                  <?php endif; ?>

                  <?php if (!empty($m['teach_skills'])): ?>
                    <div>
                      <strong class="text-dark small">You Teach:</strong>
                      <span class="badge-teach"><?= implode(', ', $m['teach_skills']) ?></span>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- Match Score Explanation -->
                <div class="p-3 bg-white border rounded-3 small mb-3">
                  <div class="fw-bold text-primary mb-1"><i class="bi bi-info-circle-fill me-1"></i> Why this match?</div>
                  <ul class="mb-0 ps-3 text-muted">
                    <?php foreach ($m['reasons'] as $reason): ?>
                      <li><?= htmlspecialchars($reason) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              </div>

              <div class="d-flex gap-2 pt-2 border-top">
                <a href="profile.php?id=<?= $m['user_id'] ?>" class="btn btn-outline-custom flex-grow-1">View Profile</a>
                <button type="button" class="btn btn-primary-custom flex-grow-1" data-bs-toggle="modal" data-bs-target="#requestModal<?= $m['user_id'] ?>">
                  <i class="bi bi-send me-1"></i> Send Exchange Request
                </button>
              </div>
            </div>
          </div>

          <!-- SEND REQUEST MODAL FOR THIS MATCH -->
          <div class="modal fade" id="requestModal<?= $m['user_id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light">
                  <h5 class="modal-title fw-bold text-dark mb-0"><i class="bi bi-arrow-repeat text-primary me-2"></i>Propose Skill Exchange with <?= htmlspecialchars($m['name']) ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="matches.php">
                  <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="send_request">
                    <input type="hidden" name="receiver_id" value="<?= $m['user_id'] ?>">

                    <div class="mb-3">
                      <label for="skill_id_<?= $m['user_id'] ?>" class="form-label fw-semibold">Skill You Want To Learn From <?= htmlspecialchars($m['name']) ?> *</label>
                      <select class="form-select" id="skill_id_<?= $m['user_id'] ?>" name="skill_id" required>
                        <?php foreach ($m['teach_skills_list'] as $ts): ?>
                          <option value="<?= $ts['skill_id'] ?>"><?= htmlspecialchars($ts['skill_name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="mb-3">
                      <label for="message_<?= $m['user_id'] ?>" class="form-label fw-semibold">Personal Proposal Message *</label>
                      <textarea class="form-control" id="message_<?= $m['user_id'] ?>" name="message" rows="3" required placeholder="Hi <?= htmlspecialchars($m['name']) ?>! I saw you teach this skill. I would love to schedule a session and exchange skills with you!"></textarea>
                    </div>
                  </div>
                  <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Send Proposal Request</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
