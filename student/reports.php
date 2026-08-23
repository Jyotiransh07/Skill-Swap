<?php
/**
 * SkillSwap Campus - User Safety & Reporting Page
 */
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Report Issue - SkillSwap Campus';
$currentUserId = $_SESSION['user_id'];
$pdo = Database::getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_report') {
    $reportedUser = (int)($_POST['reported_user'] ?? 0);
    $reason       = sanitize($_POST['reason'] ?? '');
    $description  = sanitize($_POST['description'] ?? '');
    $csrf_token   = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $error = 'Security check failed.';
    } elseif ($reportedUser <= 0 || empty($reason) || empty($description)) {
        $error = 'Please select a student, reason, and provide a clear description of the issue.';
    } else {
        $stmtIns = $pdo->prepare("
            INSERT INTO reports (reported_by, reported_user, reason, description, status, created_at)
            VALUES (:by, :user, :reason, :desc, 'PENDING', NOW())
        ");
        $stmtIns->execute([
            'by'     => $currentUserId,
            'user'   => $reportedUser,
            'reason' => $reason,
            'desc'   => $description
        ]);

        set_flash('success', 'Report submitted to campus administrators for review. Thank you for keeping our platform safe!');
        header('Location: ' . $baseUrl . 'student/reports.php');
        exit;
    }
}

// Fetch list of active students for reporting dropdown
$stmtStudents = $pdo->prepare("SELECT user_id, name, department FROM users WHERE user_id <> :id AND role = 'student' ORDER BY name ASC");
$stmtStudents->execute(['id' => $currentUserId]);
$studentList = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's submitted reports
$stmtMyReports = $pdo->prepare("
    SELECT r.*, u.name AS reported_name
    FROM reports r
    JOIN users u ON r.reported_user = u.user_id
    WHERE r.reported_by = :id
    ORDER BY r.created_at DESC
");
$stmtMyReports->execute(['id' => $currentUserId]);
$myReports = $stmtMyReports->fetchAll(PDO::FETCH_ASSOC);

$csrf_token = generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="container-fluid" style="max-width: 900px;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
          <h2 class="fw-bold mb-1"><i class="bi bi-flag text-danger me-2"></i>Report Inappropriate User</h2>
          <p class="text-muted mb-0">Help us maintain a safe, high-quality peer learning environment.</p>
        </div>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Submit Report Form Card -->
      <div class="card-custom p-4 p-md-5 mb-4">
        <h5 class="fw-bold mb-3">Submit a Safety Report</h5>

        <form method="POST" action="reports.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <input type="hidden" name="action" value="submit_report">

          <div class="row g-3">
            <div class="col-md-6">
              <label for="reported_user" class="form-label fw-semibold">Select Student to Report *</label>
              <select class="form-select" id="reported_user" name="reported_user" required>
                <option value="" disabled selected>Choose student...</option>
                <?php foreach ($studentList as $st): ?>
                  <option value="<?= $st['user_id'] ?>"><?= htmlspecialchars($st['name']) ?> (<?= htmlspecialchars($st['department']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label for="reason" class="form-label fw-semibold">Reason *</label>
              <select class="form-select" id="reason" name="reason" required>
                <option value="Spam">Spam or Unsolicited Promotion</option>
                <option value="Inappropriate behavior">Inappropriate or Unprofessional Behavior</option>
                <option value="Fake profile">Fake Profile or Misrepresentation</option>
                <option value="Harassment">Harassment or Offensive Language</option>
                <option value="Other">Other Policy Violation</option>
              </select>
            </div>

            <div class="col-12">
              <label for="description" class="form-label fw-semibold">Detailed Description of Incident *</label>
              <textarea class="form-control" id="description" name="description" rows="4" required placeholder="Please describe what happened, including dates or session details if relevant..."></textarea>
            </div>
          </div>

          <button type="submit" class="btn btn-danger mt-4 px-4 py-2 fw-semibold">
            <i class="bi bi-shield-exclamation me-1"></i> Submit Report to Admin
          </button>
        </form>
      </div>

      <!-- Previously Submitted Reports Status -->
      <h5 class="fw-bold mb-3"><i class="bi bi-history me-2"></i>My Report History</h5>
      <?php if (empty($myReports)): ?>
        <div class="card-custom p-4 text-center text-muted">
          <p class="mb-0">You have not submitted any user reports.</p>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($myReports as $rep): ?>
          <div class="col-12">
            <div class="card-custom p-3 bg-light d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-bold text-dark mb-1">Reported: <?= htmlspecialchars($rep['reported_name']) ?></div>
                <div class="small text-muted mb-1"><strong>Reason:</strong> <?= htmlspecialchars($rep['reason']) ?></div>
                <p class="small text-secondary mb-0">"<?= htmlspecialchars($rep['description']) ?>"</p>
              </div>
              <div class="text-end">
                <?php if ($rep['status'] === 'PENDING'): ?>
                  <span class="badge bg-warning-subtle text-warning border border-warning">PENDING REVIEW</span>
                <?php elseif ($rep['status'] === 'RESOLVED'): ?>
                  <span class="badge bg-success-subtle text-success border border-success">RESOLVED</span>
                <?php else: ?>
                  <span class="badge bg-secondary-subtle text-secondary border"><?= htmlspecialchars($rep['status']) ?></span>
                <?php endif; ?>
                <div class="small text-muted mt-1" style="font-size:0.7rem;"><?= date('M d, Y', strtotime($rep['created_at'])) ?></div>
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
