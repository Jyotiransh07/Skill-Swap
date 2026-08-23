<?php
/**
 * SkillSwap Campus - My Skills Management Page
 */
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'My Skills - SkillSwap Campus';
$userId = $_SESSION['user_id'];
$pdo = Database::getConnection();

$error = '';
$success = '';

// Handle Add Skill Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_skill') {
    $skillId    = (int)($_POST['skill_id'] ?? 0);
    $skillType  = sanitize($_POST['skill_type'] ?? '');
    $skillLevel = sanitize($_POST['skill_level'] ?? 'INTERMEDIATE');
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $error = 'Security validation failed.';
    } elseif ($skillId <= 0 || !in_array($skillType, ['TEACH', 'LEARN']) || !in_array($skillLevel, ['BEGINNER', 'INTERMEDIATE', 'ADVANCED', 'EXPERT'])) {
        $error = 'Please select a valid skill, type, and level.';
    } else {
        try {
            // Check duplicate
            $stmtCheck = $pdo->prepare("SELECT user_skill_id FROM user_skills WHERE user_id = :uid AND skill_id = :sid AND skill_type = :stype LIMIT 1");
            $stmtCheck->execute(['uid' => $userId, 'sid' => $skillId, 'stype' => $skillType]);

            if ($stmtCheck->fetch()) {
                $error = 'You have already added this skill to your list.';
            } else {
                $stmtAdd = $pdo->prepare("INSERT INTO user_skills (user_id, skill_id, skill_type, skill_level, created_at) VALUES (:uid, :sid, :stype, :slevel, NOW())");
                $stmtAdd->execute([
                    'uid'    => $userId,
                    'sid'    => $skillId,
                    'stype'  => $skillType,
                    'slevel' => $skillLevel
                ]);
                $success = 'Skill successfully added to your profile!';
            }
        } catch (Exception $e) {
            $error = 'Error adding skill: ' . $e->getMessage();
        }
    }
}

// Handle Remove Skill
if (isset($_GET['remove']) && (int)$_GET['remove'] > 0) {
    $removeSkillId = (int)$_GET['remove'];
    $stmtDel = $pdo->prepare("DELETE FROM user_skills WHERE user_skill_id = :id AND user_id = :uid");
    $stmtDel->execute(['id' => $removeSkillId, 'uid' => $userId]);
    set_flash('success', 'Skill removed from your profile.');
    header('Location: ' . $baseUrl . 'student/skills.php');
    exit;
}

// Fetch all available skills for modal dropdown
$stmtAllMasterSkills = $pdo->query("SELECT * FROM skills ORDER BY category ASC, skill_name ASC");
$masterSkills = $stmtAllMasterSkills->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's current skills
$stmtUserSkills = $pdo->prepare("
    SELECT us.*, s.skill_name, s.category, s.description
    FROM user_skills us
    JOIN skills s ON us.skill_id = s.skill_id
    WHERE us.user_id = :uid
    ORDER BY s.skill_name ASC
");
$stmtUserSkills->execute(['uid' => $userId]);
$userSkills = $stmtUserSkills->fetchAll(PDO::FETCH_ASSOC);

$teachSkills = array_filter($userSkills, fn($s) => $s['skill_type'] === 'TEACH');
$learnSkills = array_filter($userSkills, fn($s) => $s['skill_type'] === 'LEARN');

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
          <h2 class="fw-bold mb-1">My Skills Management</h2>
          <p class="text-muted mb-0">Manage the skills you can teach and the skills you are eager to learn.</p>
        </div>
        <button class="btn btn-primary-custom mt-3 mt-md-0" data-bs-toggle="modal" data-bs-target="#addSkillModal">
          <i class="bi bi-plus-circle me-1"></i> Add New Skill
        </button>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Two-Column Layout -->
      <div class="row g-4">
        <!-- COLUMN 1: SKILLS I CAN TEACH -->
        <div class="col-lg-6">
          <div class="card-custom p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="fw-bold text-dark mb-0"><i class="bi bi-book-half text-success me-2"></i>Skills I Can Teach</h5>
              <span class="badge bg-success-subtle text-success fw-bold"><?= count($teachSkills) ?> Listed</span>
            </div>
            <p class="text-muted small mb-4">Peers will reach out to schedule learning sessions for these skills.</p>

            <?php if (empty($teachSkills)): ?>
              <div class="text-center py-5 border rounded-3 bg-light">
                <i class="bi bi-journal-plus fs-1 text-secondary opacity-50 d-block mb-2"></i>
                <p class="text-muted small mb-2">You haven't listed any teach skills yet.</p>
                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addSkillModal" onclick="document.getElementById('skill_type').value='TEACH';">
                  + Add Skill You Can Teach
                </button>
              </div>
            <?php else: ?>
              <div class="row g-3">
                <?php foreach ($teachSkills as $ts): ?>
                <div class="col-12">
                  <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                    <div>
                      <div class="d-flex align-items-center gap-2">
                        <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($ts['skill_name']) ?></h6>
                        <span class="badge bg-white text-secondary border"><?= htmlspecialchars($ts['category']) ?></span>
                      </div>
                      <span class="badge bg-success-subtle text-success mt-1">Level: <?= htmlspecialchars($ts['skill_level']) ?></span>
                    </div>
                    <a href="skills.php?remove=<?= $ts['user_skill_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to remove <?= htmlspecialchars($ts['skill_name']) ?>?');">
                      <i class="bi bi-trash"></i>
                    </a>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- COLUMN 2: SKILLS I WANT TO LEARN -->
        <div class="col-lg-6">
          <div class="card-custom p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="fw-bold text-dark mb-0"><i class="bi bi-mortarboard-fill text-primary me-2"></i>Skills I Want To Learn</h5>
              <span class="badge bg-primary-subtle text-primary fw-bold"><?= count($learnSkills) ?> Listed</span>
            </div>
            <p class="text-muted small mb-4">Our Smart Matching algorithm will find student mentors who teach these skills.</p>

            <?php if (empty($learnSkills)): ?>
              <div class="text-center py-5 border rounded-3 bg-light">
                <i class="bi bi-journal-bookmark fs-1 text-secondary opacity-50 d-block mb-2"></i>
                <p class="text-muted small mb-2">You haven't listed any learning goals yet.</p>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addSkillModal" onclick="document.getElementById('skill_type').value='LEARN';">
                  + Add Skill You Want To Learn
                </button>
              </div>
            <?php else: ?>
              <div class="row g-3">
                <?php foreach ($learnSkills as $ls): ?>
                <div class="col-12">
                  <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                    <div>
                      <div class="d-flex align-items-center gap-2">
                        <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($ls['skill_name']) ?></h6>
                        <span class="badge bg-white text-secondary border"><?= htmlspecialchars($ls['category']) ?></span>
                      </div>
                      <span class="badge bg-primary-subtle text-primary mt-1">Target Level: <?= htmlspecialchars($ls['skill_level']) ?></span>
                    </div>
                    <a href="skills.php?remove=<?= $ls['user_skill_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to remove <?= htmlspecialchars($ls['skill_name']) ?>?');">
                      <i class="bi bi-trash"></i>
                    </a>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- ADD SKILL MODAL -->
<div class="modal fade" id="addSkillModal" tabindex="-1" aria-labelledby="addSkillModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-light">
        <h5 class="modal-header-title fw-bold text-dark mb-0" id="addSkillModalLabel"><i class="bi bi-plus-circle text-primary me-2"></i>Add Skill to Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="skills.php">
        <div class="modal-body p-4">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <input type="hidden" name="action" value="add_skill">

          <div class="mb-3">
            <label for="skill_id" class="form-label fw-semibold">Select Skill *</label>
            <select class="form-select" id="skill_id" name="skill_id" required>
              <option value="" disabled selected>Choose a skill...</option>
              <?php 
              $currentCategory = '';
              foreach ($masterSkills as $ms): 
                if ($ms['category'] !== $currentCategory):
                  if ($currentCategory !== '') echo '</optgroup>';
                  $currentCategory = $ms['category'];
                  echo '<optgroup label="' . htmlspecialchars($currentCategory) . '">';
                endif;
              ?>
                <option value="<?= $ms['skill_id'] ?>"><?= htmlspecialchars($ms['skill_name']) ?></option>
              <?php endforeach; if ($currentCategory !== '') echo '</optgroup>'; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="skill_type" class="form-label fw-semibold">I Want To *</label>
            <select class="form-select" id="skill_type" name="skill_type" required>
              <option value="TEACH">TEACH this skill to peers</option>
              <option value="LEARN">LEARN this skill from peers</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="skill_level" class="form-label fw-semibold">Proficiency Level *</label>
            <select class="form-select" id="skill_level" name="skill_level" required>
              <option value="BEGINNER">BEGINNER</option>
              <option value="INTERMEDIATE" selected>INTERMEDIATE</option>
              <option value="ADVANCED">ADVANCED</option>
              <option value="EXPERT">EXPERT</option>
            </select>
          </div>
        </div>

        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-custom">Add Skill</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
