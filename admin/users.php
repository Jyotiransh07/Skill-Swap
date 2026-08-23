<?php
/**
 * SkillSwap Campus - Admin User Management Page
 */
require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Admin User Management - SkillSwap Campus';
$pdo = Database::getConnection();

// Handle User Status Actions (Activate, Suspend, Deactivate, Delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $targetId = (int)$_GET['id'];
    $act      = $_GET['action'];

    if ($targetId > 1) { // Protect Admin user_id=1
        if ($act === 'activate') {
            $stmtUp = $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = :id");
            $stmtUp->execute(['id' => $targetId]);
            set_flash('success', 'User activated successfully.');
        } elseif ($act === 'suspend') {
            $stmtUp = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE user_id = :id");
            $stmtUp->execute(['id' => $targetId]);
            set_flash('warning', 'User account suspended.');
        } elseif ($act === 'deactivate') {
            $stmtUp = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE user_id = :id");
            $stmtUp->execute(['id' => $targetId]);
            set_flash('info', 'User status set to inactive.');
        } elseif ($act === 'delete') {
            $stmtDel = $pdo->prepare("DELETE FROM users WHERE user_id = :id AND role <> 'admin'");
            $stmtDel->execute(['id' => $targetId]);
            set_flash('success', 'User account deleted from system.');
        }
    }
    header('Location: ' . $baseUrl . 'admin/users.php');
    exit;
}

// Search & Filter
$search     = sanitize($_GET['search'] ?? '');
$status     = sanitize($_GET['status'] ?? '');
$department = sanitize($_GET['department'] ?? '');

$sql = "SELECT * FROM users WHERE role = 'student'";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE :search OR email LIKE :search OR college_id LIKE :search OR department LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if (!empty($status)) {
    $sql .= " AND status = :status";
    $params['status'] = $status;
}

if (!empty($department)) {
    $sql .= " AND department = :department";
    $params['department'] = $department;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="container-fluid">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
          <h2 class="fw-bold mb-1">Manage Campus Students</h2>
          <p class="text-muted mb-0">Review student accounts, update status, and enforce safety policies.</p>
        </div>
      </div>

      <!-- Search & Filter Bar -->
      <div class="card-custom p-4 mb-4">
        <form method="GET" action="users.php" class="row g-3">
          <div class="col-md-5">
            <label for="search" class="form-label fw-semibold small">Search Query</label>
            <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, email, college ID...">
          </div>
          <div class="col-md-3">
            <label for="status" class="form-label fw-semibold small">Account Status</label>
            <select class="form-select" id="status" name="status">
              <option value="">All Statuses</option>
              <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
              <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
            </select>
          </div>
          <div class="col-md-3">
            <label for="department" class="form-label fw-semibold small">Department</label>
            <select class="form-select" id="department" name="department">
              <option value="">All Departments</option>
              <option value="Computer Engineering" <?= $department === 'Computer Engineering' ? 'selected' : '' ?>>Computer Engineering</option>
              <option value="Computer Science" <?= $department === 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
              <option value="Electronics" <?= $department === 'Electronics' ? 'selected' : '' ?>>Electronics</option>
              <option value="Information Technology" <?= $department === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
            </select>
          </div>
          <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-primary-custom w-100 py-2">Filter</button>
          </div>
        </form>
      </div>

      <!-- Users Table Card -->
      <div class="card-custom p-4">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Student Profile</th>
                <th>College ID</th>
                <th>Department</th>
                <th>Year</th>
                <th>Reputation</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
              <tr>
                <td>#<?= $u['user_id'] ?></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <img src="<?= $baseUrl ?>uploads/profiles/<?= htmlspecialchars($u['profile_image']) ?>" class="avatar-sm" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($u['name']) ?>';">
                    <div>
                      <div class="fw-bold text-dark small"><?= htmlspecialchars($u['name']) ?></div>
                      <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($u['email']) ?></div>
                    </div>
                  </div>
                </td>
                <td><code><?= htmlspecialchars($u['college_id']) ?></code></td>
                <td class="small"><?= htmlspecialchars($u['department']) ?></td>
                <td class="small"><?= htmlspecialchars($u['year']) ?></td>
                <td><span class="fw-bold text-warning">⭐ <?= round($u['reputation_score']) ?></span></td>
                <td>
                  <?php if ($u['status'] === 'active'): ?>
                    <span class="badge bg-success-subtle text-success">Active</span>
                  <?php elseif ($u['status'] === 'suspended'): ?>
                    <span class="badge bg-danger-subtle text-danger">Suspended</span>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($u['status']) ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                      Manage
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                      <li><a class="dropdown-item" href="<?= $baseUrl ?>student/profile.php?id=<?= $u['user_id'] ?>" target="_blank"><i class="bi bi-eye me-2"></i> View Profile</a></li>
                      <?php if ($u['status'] !== 'active'): ?>
                        <li><a class="dropdown-item text-success" href="users.php?action=activate&id=<?= $u['user_id'] ?>"><i class="bi bi-check-circle me-2"></i> Activate Account</a></li>
                      <?php endif; ?>
                      <?php if ($u['status'] !== 'suspended'): ?>
                        <li><a class="dropdown-item text-warning" href="users.php?action=suspend&id=<?= $u['user_id'] ?>"><i class="bi bi-slash-circle me-2"></i> Suspend Account</a></li>
                      <?php endif; ?>
                      <li><hr class="dropdown-divider"></li>
                      <li><a class="dropdown-item text-danger" href="users.php?action=delete&id=<?= $u['user_id'] ?>" onclick="return confirm('Permanently delete <?= htmlspecialchars($u['name']) ?>?');"><i class="bi bi-trash me-2"></i> Delete Account</a></li>
                    </ul>
                  </div>
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
