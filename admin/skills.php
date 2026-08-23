<?php
/**
 * SkillSwap Campus - Admin Skills & Categories Management
 */
require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Admin Skills Management - SkillSwap Campus';
$pdo = Database::getConnection();

$error = '';
$success = '';

// Handle Add Skill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_skill') {
    $name       = sanitize($_POST['skill_name'] ?? '');
    $category   = sanitize($_POST['category'] ?? '');
    $desc       = sanitize($_POST['description'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $error = 'Security check failed.';
    } elseif (empty($name) || empty($category)) {
        $error = 'Skill name and category are required.';
    } else {
        try {
            $stmtIns = $pdo->prepare("INSERT INTO skills (skill_name, category, description, created_at) VALUES (:name, :cat, :desc, NOW())");
            $stmtIns->execute(['name' => $name, 'cat' => $category, 'desc' => $desc]);
            set_flash('success', 'New master skill added successfully!');
            header('Location: ' . $baseUrl . 'admin/skills.php');
            exit;
        } catch (Exception $e) {
            $error = 'A skill with this name already exists.';
        }
    }
}

// Handle Delete Skill
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $delId = (int)$_GET['delete'];
    $stmtDel = $pdo->prepare("DELETE FROM skills WHERE skill_id = :id");
    $stmtDel->execute(['id' => $delId]);
    set_flash('success', 'Skill deleted from master catalog.');
    header('Location: ' . $baseUrl . 'admin/skills.php');
    exit;
}

// Fetch master skills with demand & supply metrics via View
$stmtSkills = $pdo->query("SELECT * FROM vw_skill_demand_supply ORDER BY category ASC, skill_name ASC");
$skillsList = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);

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
          <h2 class="fw-bold mb-1">Master Skills Catalog</h2>
          <p class="text-muted mb-0">Manage skill categories, names, descriptions, and view demand/supply statistics.</p>
        </div>
        <button class="btn btn-primary-custom mt-3 mt-md-0" data-bs-toggle="modal" data-bs-target="#addSkillModal">
          <i class="bi bi-plus-circle me-1"></i> Add Master Skill
        </button>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Skills Table Card -->
      <div class="card-custom p-4">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Skill Name</th>
                <th>Category</th>
                <th>Students Teaching</th>
                <th>Students Wanting</th>
                <th>Total Requests</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($skillsList as $sk): ?>
              <tr>
                <td>#<?= $sk['skill_id'] ?></td>
                <td>
                  <span class="fw-bold text-dark d-block"><?= htmlspecialchars($sk['skill_name']) ?></span>
                </td>
                <td><span class="badge bg-light text-secondary border"><?= htmlspecialchars($sk['category']) ?></span></td>
                <td><span class="badge bg-success-subtle text-success"><?= $sk['teachers_count'] ?? 0 ?> Teachers</span></td>
                <td><span class="badge bg-primary-subtle text-primary"><?= $sk['learners_count'] ?? 0 ?> Learners</span></td>
                <td class="fw-bold text-dark"><?= $sk['total_requests'] ?? 0 ?> Requests</td>
                <td>
                  <a href="skills.php?delete=<?= $sk['skill_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete <?= htmlspecialchars($sk['skill_name']) ?> from catalog?');">
                    <i class="bi bi-trash"></i> Delete
                  </a>
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

<!-- ADD MASTER SKILL MODAL -->
<div class="modal fade" id="addSkillModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-light">
        <h5 class="modal-title fw-bold text-dark mb-0"><i class="bi bi-plus-circle text-primary me-2"></i>Add Master Skill</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="skills.php">
        <div class="modal-body p-4">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <input type="hidden" name="action" value="add_skill">

          <div class="mb-3">
            <label for="skill_name" class="form-label fw-semibold">Skill Name *</label>
            <input type="text" class="form-control" id="skill_name" name="skill_name" required placeholder="e.g. Flutter Development">
          </div>

          <div class="mb-3">
            <label for="category" class="form-label fw-semibold">Category *</label>
            <select class="form-select" id="category" name="category" required>
              <option value="Programming">Programming</option>
              <option value="Design">Design</option>
              <option value="Hardware">Hardware</option>
              <option value="Creative">Creative</option>
              <option value="Business">Business</option>
              <option value="Languages">Languages</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label fw-semibold">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Brief summary of the skill..."></textarea>
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
