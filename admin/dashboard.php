<?php
/**
 * SkillSwap Campus - Professional Admin Dashboard Overview
 */
require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Admin Dashboard - SkillSwap Campus';
$pdo = Database::getConnection();

// Fetch Admin Statistics
$stats = [
    'total_students'     => 0,
    'active_students'    => 0,
    'total_skills'       => 0,
    'active_exchanges'   => 0,
    'completed_sessions' => 0,
    'pending_reports'    => 0
];

$stats['total_students']     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$stats['active_students']    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active'")->fetchColumn();
$stats['total_skills']       = (int)$pdo->query("SELECT COUNT(*) FROM skills")->fetchColumn();
$stats['active_exchanges']   = (int)$pdo->query("SELECT COUNT(*) FROM learning_requests WHERE status IN ('PENDING', 'ACCEPTED')")->fetchColumn();
$stats['completed_sessions'] = (int)$pdo->query("SELECT COUNT(*) FROM sessions WHERE status = 'COMPLETED'")->fetchColumn();
$stats['pending_reports']    = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'PENDING'")->fetchColumn();

// Fetch Recent Registrations
$stmtRecentUsers = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC LIMIT 5");
$recentUsers = $stmtRecentUsers->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="container-fluid">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
          <h2 class="fw-bold mb-1"><i class="bi bi-speedometer2 text-primary me-2"></i>Admin Overview Panel</h2>
          <p class="text-muted mb-0">System performance, student activity monitoring, and global moderation.</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex gap-2">
          <a href="users.php" class="btn btn-outline-custom">Manage Users</a>
          <a href="analytics.php" class="btn btn-primary-custom"><i class="bi bi-bar-chart-line me-1"></i> View Analytics</a>
        </div>
      </div>

      <!-- 6 Key Admin KPI Cards -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-2">
          <div class="stat-card">
            <div class="stat-icon primary"><i class="bi bi-people"></i></div>
            <div class="stat-details">
              <h3><?= $stats['total_students'] ?></h3>
              <p>Total Students</p>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
          <div class="stat-card">
            <div class="stat-icon success"><i class="bi bi-person-check"></i></div>
            <div class="stat-details">
              <h3><?= $stats['active_students'] ?></h3>
              <p>Active Students</p>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
          <div class="stat-card">
            <div class="stat-icon warning"><i class="bi bi-journal-code"></i></div>
            <div class="stat-details">
              <h3><?= $stats['total_skills'] ?></h3>
              <p>Master Skills</p>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
          <div class="stat-card">
            <div class="stat-icon accent"><i class="bi bi-arrow-repeat"></i></div>
            <div class="stat-details">
              <h3><?= $stats['active_exchanges'] ?></h3>
              <p>Active Exchanges</p>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
          <div class="stat-card">
            <div class="stat-icon primary"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-details">
              <h3><?= $stats['completed_sessions'] ?></h3>
              <p>Completed Sessions</p>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
          <div class="stat-card border-danger">
            <div class="stat-icon" style="background:#fee2e2; color:#ef4444;"><i class="bi bi-flag"></i></div>
            <div class="stat-details">
              <h3 class="text-danger"><?= $stats['pending_reports'] ?></h3>
              <p>Pending Reports</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Registrations & System Quick Actions Grid -->
      <div class="row g-4 mb-4">
        <!-- Recent Student Registrations Table -->
        <div class="col-lg-8">
          <div class="card-custom p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="fw-bold mb-0">Recent Student Registrations</h5>
              <a href="users.php" class="small text-primary font-semibold">View All Students</a>
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Student</th>
                    <th>College ID</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentUsers as $u): ?>
                  <tr>
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
                      <a href="users.php?search=<?= urlencode($u['email']) ?>" class="btn btn-sm btn-light border">Manage</a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- System Quick Actions Card -->
        <div class="col-lg-4">
          <div class="card-custom p-4 h-100 d-flex flex-column justify-content-between">
            <div>
              <h5 class="fw-bold mb-3">Quick Management Links</h5>
              <div class="d-grid gap-2">
                <a href="skills.php" class="btn btn-outline-custom text-start p-3">
                  <i class="bi bi-plus-circle text-primary me-2 fs-5"></i> Add New Master Skill
                </a>
                <a href="reports.php" class="btn btn-outline-custom text-start p-3">
                  <i class="bi bi-exclamation-triangle text-danger me-2 fs-5"></i> Review User Safety Reports (<?= $stats['pending_reports'] ?>)
                </a>
                <a href="analytics.php" class="btn btn-outline-custom text-start p-3">
                  <i class="bi bi-pie-chart text-success me-2 fs-5"></i> Generate Analytics Graphs
                </a>
              </div>
            </div>

            <div class="p-3 bg-light rounded-3 small text-muted mt-4 border">
              <i class="bi bi-info-circle-fill text-primary me-1"></i> WAMP Local Server Status: <strong class="text-success">Connected</strong>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
