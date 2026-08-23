<?php
/**
 * SkillSwap Campus - Explore & Search Skills Page
 */
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Explore Skills - SkillSwap Campus';
$currentUserId = $_SESSION['user_id'];
$pdo = Database::getConnection();

// Search & Filter parameters
$search     = sanitize($_GET['search'] ?? '');
$category   = sanitize($_GET['category'] ?? '');
$department = sanitize($_GET['department'] ?? '');
$year       = sanitize($_GET['year'] ?? '');

// Build Query
$sql = "
    SELECT DISTINCT u.user_id, u.name, u.email, u.department, u.year, u.profile_image, u.reputation_score, u.bio
    FROM users u
    LEFT JOIN user_skills us ON u.user_id = us.user_id
    LEFT JOIN skills s ON us.skill_id = s.skill_id
    WHERE u.user_id <> :current_id AND u.role = 'student' AND u.status = 'active'
";

$params = ['current_id' => $currentUserId];

if (!empty($search)) {
    $sql .= " AND (u.name LIKE :search OR s.skill_name LIKE :search OR u.department LIKE :search OR u.bio LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if (!empty($category)) {
    $sql .= " AND s.category = :category";
    $params['category'] = $category;
}

if (!empty($department)) {
    $sql .= " AND u.department = :department";
    $params['department'] = $department;
}

if (!empty($year)) {
    $sql .= " AND u.year = :year";
    $params['year'] = $year;
}

$sql .= " ORDER BY u.reputation_score DESC LIMIT 30";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for filter dropdown
$stmtCat = $pdo->query("SELECT DISTINCT category FROM skills ORDER BY category ASC");
$categories = $stmtCat->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="container-fluid">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
          <h2 class="fw-bold mb-1">Explore Campus Skills & Peers</h2>
          <p class="text-muted mb-0">Discover fellow students with skills you want to learn or teach.</p>
        </div>
      </div>

      <!-- Search & Filters Bar -->
      <div class="card-custom p-4 mb-4">
        <form method="GET" action="find-skills.php" class="row g-3">
          <div class="col-lg-4">
            <label for="search" class="form-label fw-semibold small">Search Keywords</label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Python, UI/UX, Rahul, Electronics...">
            </div>
          </div>

          <div class="col-md-3 col-lg-2">
            <label for="category" class="form-label fw-semibold small">Category</label>
            <select class="form-select" id="category" name="category">
              <option value="">All Categories</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3 col-lg-3">
            <label for="department" class="form-label fw-semibold small">Department</label>
            <select class="form-select" id="department" name="department">
              <option value="">All Departments</option>
              <option value="Computer Engineering" <?= $department === 'Computer Engineering' ? 'selected' : '' ?>>Computer Engineering</option>
              <option value="Computer Science" <?= $department === 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
              <option value="Electronics" <?= $department === 'Electronics' ? 'selected' : '' ?>>Electronics</option>
              <option value="Information Technology" <?= $department === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
              <option value="Mechanical Engineering" <?= $department === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
              <option value="Civil Engineering" <?= $department === 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
            </select>
          </div>

          <div class="col-md-3 col-lg-2">
            <label for="year" class="form-label fw-semibold small">Academic Year</label>
            <select class="form-select" id="year" name="year">
              <option value="">All Years</option>
              <option value="1st Year" <?= $year === '1st Year' ? 'selected' : '' ?>>1st Year</option>
              <option value="2nd Year" <?= $year === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
              <option value="3rd Year" <?= $year === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
              <option value="4th Year" <?= $year === '4th Year' ? 'selected' : '' ?>>4th Year</option>
            </select>
          </div>

          <div class="col-md-3 col-lg-1 d-flex align-items-end">
            <button type="submit" class="btn btn-primary-custom w-100 py-2">Filter</button>
          </div>
        </form>
      </div>

      <!-- Students Grid Results -->
      <?php if (empty($students)): ?>
        <div class="text-center py-5 card-custom">
          <i class="bi bi-person-x fs-1 text-secondary opacity-50 d-block mb-3"></i>
          <h5 class="fw-bold text-dark mb-1">No matching students found</h5>
          <p class="text-muted small">Try broadening your search keywords or clear category filters.</p>
          <a href="find-skills.php" class="btn btn-sm btn-outline-custom">Reset Search</a>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($students as $st): 
            $matchData = calculate_skill_match_score($pdo, $currentUserId, $st['user_id']);
            
            // Fetch student teach and learn skills
            $stmtStSkills = $pdo->prepare("
                SELECT us.skill_type, s.skill_name 
                FROM user_skills us
                JOIN skills s ON us.skill_id = s.skill_id
                WHERE us.user_id = :id
            ");
            $stmtStSkills->execute(['id' => $st['user_id']]);
            $stSkillList = $stmtStSkills->fetchAll(PDO::FETCH_ASSOC);

            $stTeach = array_column(array_filter($stSkillList, fn($k) => $k['skill_type'] === 'TEACH'), 'skill_name');
            $stLearn = array_column(array_filter($stSkillList, fn($k) => $k['skill_type'] === 'LEARN'), 'skill_name');
          ?>
          <div class="col-md-6 col-lg-4">
            <div class="card-custom h-100 p-4 d-flex flex-column justify-content-between position-relative">
              <?php if ($matchData['score'] > 0): ?>
                <span class="position-absolute top-0 end-0 m-3 badge bg-primary-subtle text-primary fw-bold">
                  <i class="bi bi-stars text-warning me-1"></i> <?= $matchData['score'] ?>% Match
                </span>
              <?php endif; ?>

              <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                  <img src="<?= $baseUrl ?>uploads/profiles/<?= htmlspecialchars($st['profile_image']) ?>" alt="Avatar" class="avatar-md" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($st['name']) ?>&background=4f46e5&color=fff';">
                  <div>
                    <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($st['name']) ?></h6>
                    <span class="text-muted small"><?= htmlspecialchars($st['department']) ?> • <?= htmlspecialchars($st['year']) ?></span>
                    <div class="small text-warning mt-1">
                      <i class="bi bi-shield-check"></i> Rep: <strong><?= round($st['reputation_score']) ?>/100</strong>
                    </div>
                  </div>
                </div>

                <div class="bg-light p-2 rounded-3 small mb-3">
                  <?php if (!empty($stTeach)): ?>
                    <div class="mb-1"><strong>Teaches:</strong> <span class="badge-teach"><?= implode(', ', array_slice($stTeach, 0, 3)) ?></span></div>
                  <?php endif; ?>
                  <?php if (!empty($stLearn)): ?>
                    <div><strong>Wants to Learn:</strong> <span class="badge-learn"><?= implode(', ', array_slice($stLearn, 0, 3)) ?></span></div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="d-flex gap-2 pt-2 border-top">
                <a href="profile.php?id=<?= $st['user_id'] ?>" class="btn btn-sm btn-outline-custom flex-grow-1">View Profile</a>
                <a href="matches.php" class="btn btn-sm btn-primary-custom flex-grow-1">Connect</a>
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
