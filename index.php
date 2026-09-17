<?php
/**
 * SkillSwap Campus - Premium Modern Landing Page
 */
require_once __DIR__ . '/includes/functions.php';
init_session();

$baseUrl = get_base_url();
$pageTitle = 'SkillSwap Campus - Learn. Teach. Connect.';

// Fetch live statistics from MySQL
$stats = [
    'total_students' => 20,
    'total_skills' => 25,
    'completed_sessions' => 42,
    'active_exchanges' => 15
];

try {
    $pdo = Database::getConnection();
    $stmtUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active'");
    $stats['total_students'] = (int)$stmtUsers->fetchColumn();

    $stmtSkills = $pdo->query("SELECT COUNT(*) FROM skills");
    $stats['total_skills'] = (int)$stmtSkills->fetchColumn();

    $stmtSessions = $pdo->query("SELECT COUNT(*) FROM sessions WHERE status = 'COMPLETED'");
    $stats['completed_sessions'] = (int)$stmtSessions->fetchColumn();

    $stmtRequests = $pdo->query("SELECT COUNT(*) FROM learning_requests WHERE status IN ('ACCEPTED', 'COMPLETED')");
    $stats['active_exchanges'] = (int)$stmtRequests->fetchColumn();

    // Featured skills
    $stmtPopSkills = $pdo->query("
        SELECT s.skill_id, s.skill_name, s.category, s.description,
               COUNT(us.user_skill_id) AS student_count
        FROM skills s
        LEFT JOIN user_skills us ON s.skill_id = us.skill_id
        GROUP BY s.skill_id
        ORDER BY student_count DESC
        LIMIT 6
    ");
    $featuredSkills = $stmtPopSkills->fetchAll(PDO::FETCH_ASSOC);

    // Featured students
    $stmtStudents = $pdo->query("
        SELECT u.user_id, u.name, u.department, u.year, u.reputation_score, u.profile_image
        FROM users u
        WHERE u.role = 'student' AND u.status = 'active'
        ORDER BY u.reputation_score DESC
        LIMIT 4
    ");
    $featuredStudents = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $featuredSkills = [];
    $featuredStudents = [];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Section -->
<section class="hero-section">
  <div class="container py-5">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="hero-tagline mb-2 text-info fw-bold"><i class="bi bi-mortarboard-fill me-2"></i> Campus Peer-to-Peer Learning</div>
        <h1 class="hero-title text-white mb-3">Learn. Teach.<br>Connect.</h1>
        <p class="fs-5 text-white-50 mb-4 me-lg-4">
          Turn the skills you know into opportunities to learn the skills you want. Peer-to-peer knowledge exchange designed specifically for college students — 100% free with zero cash required.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="<?= isset($_SESSION['user_id']) ? $baseUrl . 'student/matches.php' : $baseUrl . 'auth/register.php' ?>" class="btn btn-primary-custom btn-lg border-0 shadow" style="background:#6366f1;">
            <i class="bi bi-arrow-repeat me-2"></i> Start Swapping Now
          </a>
          <a href="<?= $baseUrl ?>student/find-skills.php" class="btn btn-outline-light btn-lg border border-secondary">
            <i class="bi bi-search me-2"></i> Explore Skills
          </a>
        </div>

        <div class="d-flex align-items-center gap-4 mt-5 pt-3 border-top border-light border-opacity-25">
          <div>
            <div class="h3 fw-bold text-white mb-0"><?= number_format($stats['total_students']) ?>+</div>
            <div class="small text-white-50">Active Students</div>
          </div>
          <div class="vr bg-light opacity-25"></div>
          <div>
            <div class="h3 fw-bold text-white mb-0"><?= number_format($stats['total_skills']) ?>+</div>
            <div class="small text-white-50">Skills Listed</div>
          </div>
          <div class="vr bg-light opacity-25"></div>
          <div>
            <div class="h3 fw-bold text-info mb-0">100%</div>
            <div class="small text-white-50">Free Peer Exchange</div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <!-- Hero Interactive Preview Card -->
        <div class="card-custom p-4 bg-white text-dark shadow-lg rounded-4 border-0">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill">
              <i class="bi bi-check-circle-fill me-1"></i> 96% Match Found
            </span>
            <span class="text-muted small fw-semibold"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Two-Way Skill Exchange</span>
          </div>

          <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 mb-3">
            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80" alt="Priya" class="avatar-lg">
            <div>
              <h5 class="fw-bold mb-1">Priya Mehta</h5>
              <p class="text-muted small mb-0"><i class="bi bi-building me-1"></i> Electronics • 3rd Year</p>
              <div class="badge bg-primary-subtle text-primary mt-1"><i class="bi bi-star-fill text-warning me-1"></i> 4.9 Rating (42 Sessions)</div>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <div class="p-2 border rounded-3 text-center bg-white">
                <span class="text-muted small d-block">Priya Teaches:</span>
                <span class="fw-bold text-primary">UI/UX & Figma</span>
              </div>
            </div>
            <div class="col-6">
              <div class="p-2 border rounded-3 text-center bg-white">
                <span class="text-muted small d-block">Priya Learns:</span>
                <span class="fw-bold text-success">Python Programming</span>
              </div>
            </div>
          </div>

          <div class="p-3 bg-primary-subtle rounded-3 small text-primary mb-3">
            <i class="bi bi-chat-quote-fill me-2"></i> "Priya teaches UI/UX which you want to learn. You teach Python which Priya wants to learn!"
          </div>

          <a href="<?= $baseUrl ?>auth/register.php" class="btn btn-primary-custom w-100 py-2">
            Send Exchange Request
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- How It Works Section -->
<section class="py-5 bg-white">
  <div class="container py-4">
    <div class="text-center max-w-700 mx-auto mb-5">
      <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill mb-2">SIMPLE 4-STEP PROCESS</span>
      <h2 class="display-6 fw-bold">How SkillSwap Campus Works</h2>
      <p class="text-muted">Exchange skills seamlessly with classmates in four simple steps.</p>
    </div>

    <div class="row g-4">
      <div class="col-md-3">
        <div class="card-custom h-100 p-4 text-center">
          <div class="stat-icon primary mx-auto mb-3" style="width:60px; height:60px; font-size:1.6rem;">1</div>
          <h5 class="fw-bold">Create Skill Profile</h5>
          <p class="text-muted small mb-0">List skills you can teach and skills you are eager to learn with your proficiency level.</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card-custom h-100 p-4 text-center">
          <div class="stat-icon success mx-auto mb-3" style="width:60px; height:60px; font-size:1.6rem;">2</div>
          <h5 class="fw-bold">Find Your Match</h5>
          <p class="text-muted small mb-0">Our smart algorithm pairs you with complementary peers who match your skill interests.</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card-custom h-100 p-4 text-center">
          <div class="stat-icon warning mx-auto mb-3" style="width:60px; height:60px; font-size:1.6rem;">3</div>
          <h5 class="fw-bold">Exchange Knowledge</h5>
          <p class="text-muted small mb-0">Schedule online Google Meet sessions or meet offline at the campus library.</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card-custom h-100 p-4 text-center">
          <div class="stat-icon accent mx-auto mb-3" style="width:60px; height:60px; font-size:1.6rem;">4</div>
          <h5 class="fw-bold">Grow Together</h5>
          <p class="text-muted small mb-0">Rate your partner, build your campus reputation score, and expand your network.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Popular Skills Grid -->
<section class="py-5 bg-light">
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <span class="badge bg-indigo text-white fw-bold px-3 py-2 rounded-pill mb-2" style="background:#4f46e5;">TOP CATEGORIES</span>
        <h2 class="fw-bold mb-0">Popular Campus Skills</h2>
      </div>
      <a href="<?= $baseUrl ?>student/find-skills.php" class="btn btn-outline-custom">View All Skills <i class="bi bi-arrow-right"></i></a>
    </div>

    <div class="row g-4">
      <?php if (!empty($featuredSkills)): ?>
        <?php foreach ($featuredSkills as $skill): ?>
        <div class="col-md-4">
          <div class="card-custom p-4 h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($skill['skill_name']) ?></h5>
                <span class="badge bg-light text-secondary border"><?= htmlspecialchars($skill['category']) ?></span>
              </div>
              <p class="text-muted small mb-3"><?= htmlspecialchars($skill['description']) ?></p>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
              <span class="small fw-semibold text-primary"><i class="bi bi-people-fill me-1"></i> <?= $skill['student_count'] ?> Students Teaching</span>
              <a href="<?= $baseUrl ?>student/find-skills.php?search=<?= urlencode($skill['skill_name']) ?>" class="btn btn-sm btn-light border text-dark fw-bold">Explore</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12 text-center text-muted">No skills found.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Featured Top Reputed Students -->
<section class="py-5 bg-white">
  <div class="container py-4">
    <div class="text-center mb-5">
      <h2 class="display-6 fw-bold">Top Campus Mentors</h2>
      <p class="text-muted">Meet highly-rated student teachers building their campus reputation.</p>
    </div>

    <div class="row g-4">
      <?php foreach ($featuredStudents as $st): ?>
      <div class="col-md-3">
        <div class="card-custom p-4 text-center h-100">
          <img src="<?= $baseUrl ?>uploads/profiles/<?= htmlspecialchars($st['profile_image']) ?>" alt="Avatar" class="avatar-lg mx-auto mb-3" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($st['name']) ?>&background=4f46e5&color=fff';">
          <h5 class="fw-bold mb-1"><?= htmlspecialchars($st['name']) ?></h5>
          <p class="text-muted small mb-2"><?= htmlspecialchars($st['department']) ?></p>
          <div class="mb-3">
            <span class="badge bg-warning-subtle text-dark fw-bold border border-warning">
              <i class="bi bi-shield-check text-warning me-1"></i> Reputation: <?= round($st['reputation_score']) ?>/100
            </span>
          </div>
          <a href="<?= $baseUrl ?>auth/login.php" class="btn btn-sm btn-outline-custom w-100">Connect & Swap</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Call to Action Banner -->
<section class="py-5 bg-light text-center border-top">
  <div class="container py-4">
    <h2 class="display-5 fw-bold mb-3 text-dark">Ready to Start Swapping Skills?</h2>
    <p class="fs-5 text-muted mb-4 max-w-600 mx-auto">Join hundreds of college students sharing Python, UI/UX, C++, Robotics, and Web Development today.</p>
    <a href="<?= $baseUrl ?>auth/register.php" class="btn btn-primary-custom btn-lg px-5 py-3 shadow-sm">
      Get Started For Free
    </a>
  </div>
</section>

<!-- Footer -->
<footer class="bg-white text-muted py-5 border-top">
  <div class="container">
    <div class="row g-4 border-bottom pb-4 mb-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center" style="width: 32px; height: 32px;"><i class="bi bi-arrow-repeat"></i></div>
          <span class="fs-5 fw-bold text-dark">SkillSwap Campus</span>
        </div>
        <p class="small">SkillSwap Campus is a peer-to-peer knowledge exchange application designed specifically for college students to learn and teach skills without monetary transactions.</p>
      </div>

      <div class="col-6 col-lg-2">
        <h6 class="text-dark fw-bold mb-3">Platform</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="<?= $baseUrl ?>index.php" class="text-muted text-decoration-none hover-primary">Home</a></li>
          <li class="mb-2"><a href="<?= $baseUrl ?>student/find-skills.php" class="text-muted text-decoration-none hover-primary">Explore Skills</a></li>
          <li class="mb-2"><a href="<?= $baseUrl ?>student/matches.php" class="text-muted text-decoration-none hover-primary">Smart Matches</a></li>
        </ul>
      </div>

      <div class="col-6 col-lg-2">
        <h6 class="text-dark fw-bold mb-3">Account</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="<?= $baseUrl ?>auth/login.php" class="text-muted text-decoration-none hover-primary">Student Login</a></li>
          <li class="mb-2"><a href="<?= $baseUrl ?>auth/register.php" class="text-muted text-decoration-none hover-primary">Register Account</a></li>
          <li class="mb-2"><a href="<?= $baseUrl ?>admin/dashboard.php" class="text-muted text-decoration-none hover-primary">Admin Panel</a></li>
        </ul>
      </div>

      <div class="col-lg-4">
        <h6 class="text-dark fw-bold mb-3">Environment Specs</h6>
        <p class="small text-muted mb-1"><strong>Stack:</strong> PHP 8+, MySQL, WAMP, Apache</p>
        <p class="small text-muted mb-1"><strong>Path:</strong> <code>C:\wamp64\www\skillswap\</code></p>
        <p class="small text-muted"><strong>URL:</strong> <code>http://localhost/skillswap/</code></p>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center small">
      <div>&copy; <?= date('Y') ?> SkillSwap Campus. All rights reserved.</div>
      <div>Designed with <i class="bi bi-heart-fill text-danger"></i> for College Project.</div>
    </div>
  </div>
</footer>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
