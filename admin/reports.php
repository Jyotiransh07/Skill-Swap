<?php
/**
 * SkillSwap Campus - Admin Reports & Safety Management Page
 */
require_once __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Admin Reports Management - SkillSwap Campus';
$pdo = Database::getConnection();

// Handle Report Status Update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $reportId = (int)$_GET['id'];
    $act      = $_GET['action'];

    if (in_array($act, ['RESOLVED', 'DISMISSED', 'REVIEWED'])) {
        $stmtUp = $pdo->prepare("UPDATE reports SET status = :st, resolved_at = NOW() WHERE report_id = :id");
        $stmtUp->execute(['st' => $act, 'id' => $reportId]);
        set_flash('success', 'Report status updated to ' . $act);
    }
    header('Location: ' . $baseUrl . 'admin/reports.php');
    exit;
}

// Fetch all reports
$stmt = $pdo->query("
    SELECT r.*, 
           by_u.name AS reporter_name, by_u.email AS reporter_email,
           user_u.name AS reported_name, user_u.email AS reported_email, user_u.user_id AS reported_user_id
    FROM reports r
    JOIN users by_u ON r.reported_by = by_u.user_id
    JOIN users user_u ON r.reported_user = user_u.user_id
    ORDER BY r.created_at DESC
");
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="container-fluid">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
          <h2 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle text-danger me-2"></i>User Safety Reports</h2>
          <p class="text-muted mb-0">Review reports submitted by students regarding safety or misconduct.</p>
        </div>
      </div>

      <div class="card-custom p-4">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Reporter</th>
                <th>Reported Student</th>
                <th>Reason</th>
                <th>Description</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reports as $rep): ?>
              <tr>
                <td>#<?= $rep['report_id'] ?></td>
                <td>
                  <div class="fw-bold text-dark small"><?= htmlspecialchars($rep['reporter_name']) ?></div>
                  <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($rep['reporter_email']) ?></div>
                </td>
                <td>
                  <div class="fw-bold text-danger small"><?= htmlspecialchars($rep['reported_name']) ?></div>
                  <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($rep['reported_email']) ?></div>
                </td>
                <td><span class="badge bg-danger-subtle text-danger border border-danger"><?= htmlspecialchars($rep['reason']) ?></span></td>
                <td class="small text-muted" style="max-width:250px;">"<?= htmlspecialchars($rep['description']) ?>"</td>
                <td>
                  <?php if ($rep['status'] === 'PENDING'): ?>
                    <span class="badge bg-warning-subtle text-warning border border-warning">PENDING</span>
                  <?php elseif ($rep['status'] === 'RESOLVED'): ?>
                    <span class="badge bg-success-subtle text-success">RESOLVED</span>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($rep['status']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="small text-muted"><?= date('M d, Y', strtotime($rep['created_at'])) ?></td>
                <td>
                  <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                      Action
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                      <li><a class="dropdown-item text-success" href="reports.php?action=RESOLVED&id=<?= $rep['report_id'] ?>"><i class="bi bi-check-circle me-2"></i> Mark Resolved</a></li>
                      <li><a class="dropdown-item text-secondary" href="reports.php?action=DISMISSED&id=<?= $rep['report_id'] ?>"><i class="bi bi-x-circle me-2"></i> Dismiss Report</a></li>
                      <li><hr class="dropdown-divider"></li>
                      <li><a class="dropdown-item text-danger" href="users.php?action=suspend&id=<?= $rep['reported_user_id'] ?>"><i class="bi bi-slash-circle me-2"></i> Suspend Reported User</a></li>
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
