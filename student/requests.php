<?php
/**
 * SkillSwap Campus - Exchange Requests Page
 */
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Exchange Requests - SkillSwap Campus';
$currentUserId = $_SESSION['user_id'];
$pdo = Database::getConnection();

// Handle Status Changes (Accept, Reject, Cancel)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $reqId  = (int)$_GET['id'];
    $act    = $_GET['action'];

    $stmtGet = $pdo->prepare("SELECT * FROM learning_requests WHERE request_id = :id");
    $stmtGet->execute(['id' => $reqId]);
    $reqObj = $stmtGet->fetch();

    if ($reqObj) {
        if ($act === 'accept' && $reqObj['receiver_id'] === $currentUserId) {
            $stmtUp = $pdo->prepare("UPDATE learning_requests SET status = 'ACCEPTED', updated_at = NOW() WHERE request_id = :id");
            $stmtUp->execute(['id' => $reqId]);
            create_notification($pdo, $reqObj['sender_id'], $_SESSION['user_name'] . " accepted your skill exchange request!", "REQUEST_ACCEPTED");
            set_flash('success', 'Exchange request accepted! You can now schedule a session.');
        } elseif ($act === 'reject' && $reqObj['receiver_id'] === $currentUserId) {
            $stmtUp = $pdo->prepare("UPDATE learning_requests SET status = 'REJECTED', updated_at = NOW() WHERE request_id = :id");
            $stmtUp->execute(['id' => $reqId]);
            set_flash('info', 'Exchange request rejected.');
        } elseif ($act === 'cancel' && $reqObj['sender_id'] === $currentUserId) {
            $stmtUp = $pdo->prepare("UPDATE learning_requests SET status = 'CANCELLED', updated_at = NOW() WHERE request_id = :id");
            $stmtUp->execute(['id' => $reqId]);
            set_flash('info', 'Exchange request cancelled.');
        }
    }
    header('Location: ' . $baseUrl . 'student/requests.php');
    exit;
}

// Fetch Received Requests
$stmtReceived = $pdo->prepare("
    SELECT lr.*, u.name AS sender_name, u.department, u.profile_image, u.reputation_score, s.skill_name
    FROM learning_requests lr
    JOIN users u ON lr.sender_id = u.user_id
    JOIN skills s ON lr.skill_id = s.skill_id
    WHERE lr.receiver_id = :id AND lr.status = 'PENDING'
    ORDER BY lr.created_at DESC
");
$stmtReceived->execute(['id' => $currentUserId]);
$receivedRequests = $stmtReceived->fetchAll(PDO::FETCH_ASSOC);

// Fetch Sent Requests
$stmtSent = $pdo->prepare("
    SELECT lr.*, u.name AS receiver_name, u.department, u.profile_image, s.skill_name
    FROM learning_requests lr
    JOIN users u ON lr.receiver_id = u.user_id
    JOIN skills s ON lr.skill_id = s.skill_id
    WHERE lr.sender_id = :id AND lr.status = 'PENDING'
    ORDER BY lr.created_at DESC
");
$stmtSent->execute(['id' => $currentUserId]);
$sentRequests = $stmtSent->fetchAll(PDO::FETCH_ASSOC);

// Fetch Accepted Requests
$stmtAccepted = $pdo->prepare("
    SELECT lr.*, 
           CASE WHEN lr.sender_id = :id1 THEN u_rec.name ELSE u_snd.name END AS partner_name,
           CASE WHEN lr.sender_id = :id2 THEN u_rec.profile_image ELSE u_snd.profile_image END AS partner_avatar,
           s.skill_name
    FROM learning_requests lr
    JOIN users u_snd ON lr.sender_id = u_snd.user_id
    JOIN users u_rec ON lr.receiver_id = u_rec.user_id
    JOIN skills s ON lr.skill_id = s.skill_id
    WHERE (lr.sender_id = :id3 OR lr.receiver_id = :id4) AND lr.status = 'ACCEPTED'
    ORDER BY lr.updated_at DESC
");
$stmtAccepted->execute(['id1' => $currentUserId, 'id2' => $currentUserId, 'id3' => $currentUserId, 'id4' => $currentUserId]);
$acceptedRequests = $stmtAccepted->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-wrapper">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main-content">
    <div class="container-fluid">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
          <h2 class="fw-bold mb-1">Skill Exchange Requests</h2>
          <p class="text-muted mb-0">Manage incoming and outgoing skill exchange proposals.</p>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <ul class="nav nav-pills mb-4 border-bottom pb-2" id="requestTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active fw-bold position-relative me-2" id="received-tab" data-bs-toggle="tab" data-bs-target="#received" type="button" role="tab">
            Received
            <?php if (count($receivedRequests) > 0): ?>
              <span class="badge bg-danger ms-1"><?= count($receivedRequests) ?></span>
            <?php endif; ?>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link fw-bold me-2" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button" role="tab">
            Sent (<?= count($sentRequests) ?>)
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link fw-bold" id="accepted-tab" data-bs-toggle="tab" data-bs-target="#accepted" type="button" role="tab">
            Accepted Exchanges (<?= count($acceptedRequests) ?>)
          </button>
        </li>
      </ul>

      <!-- Tab Content -->
      <div class="tab-content" id="requestTabsContent">
        <!-- 1. RECEIVED REQUESTS -->
        <div class="tab-pane fade show active" id="received" role="tabpanel">
          <?php if (empty($receivedRequests)): ?>
            <div class="card-custom p-5 text-center">
              <i class="bi bi-inbox fs-1 text-secondary opacity-50 d-block mb-2"></i>
              <p class="text-muted mb-0">No pending received exchange requests.</p>
            </div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($receivedRequests as $r): ?>
              <div class="col-lg-6">
                <div class="card-custom p-4">
                  <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= $baseUrl ?>uploads/profiles/<?= htmlspecialchars($r['profile_image']) ?>" class="avatar-md" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($r['sender_name']) ?>';">
                    <div>
                      <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($r['sender_name']) ?></h6>
                      <span class="text-muted small"><?= htmlspecialchars($r['department']) ?></span>
                    </div>
                  </div>
                  <div class="p-3 bg-light rounded-3 small mb-3">
                    <div><strong>Requested Skill:</strong> <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($r['skill_name']) ?></span></div>
                    <div class="mt-2 text-dark">"<?= htmlspecialchars($r['message']) ?>"</div>
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted" style="font-size:0.75rem;"><?= date('M d, Y H:i', strtotime($r['created_at'])) ?></span>
                    <div class="d-flex gap-2">
                      <a href="requests.php?action=reject&id=<?= $r['request_id'] ?>" class="btn btn-sm btn-outline-danger">Reject</a>
                      <a href="requests.php?action=accept&id=<?= $r['request_id'] ?>" class="btn btn-sm btn-primary-custom"><i class="bi bi-check-lg"></i> Accept Request</a>
                    </div>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- 2. SENT REQUESTS -->
        <div class="tab-pane fade" id="sent" role="tabpanel">
          <?php if (empty($sentRequests)): ?>
            <div class="card-custom p-5 text-center">
              <i class="bi bi-send-x fs-1 text-secondary opacity-50 d-block mb-2"></i>
              <p class="text-muted mb-0">No pending sent requests.</p>
            </div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($sentRequests as $s): ?>
              <div class="col-lg-6">
                <div class="card-custom p-4">
                  <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= $baseUrl ?>uploads/profiles/<?= htmlspecialchars($s['profile_image']) ?>" class="avatar-md" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($s['receiver_name']) ?>';">
                    <div>
                      <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($s['receiver_name']) ?></h6>
                      <span class="text-muted small"><?= htmlspecialchars($s['department']) ?></span>
                    </div>
                  </div>
                  <div class="p-3 bg-light rounded-3 small mb-3">
                    <div><strong>Requested Skill:</strong> <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($s['skill_name']) ?></span></div>
                    <div class="mt-2 text-dark">"<?= htmlspecialchars($s['message']) ?>"</div>
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-warning-subtle text-warning border border-warning">PENDING APPROVAL</span>
                    <a href="requests.php?action=cancel&id=<?= $s['request_id'] ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Cancel this request?');">Cancel</a>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- 3. ACCEPTED EXCHANGES -->
        <div class="tab-pane fade" id="accepted" role="tabpanel">
          <?php if (empty($acceptedRequests)): ?>
            <div class="card-custom p-5 text-center">
              <i class="bi bi-calendar-check fs-1 text-secondary opacity-50 d-block mb-2"></i>
              <p class="text-muted mb-0">No accepted exchanges yet.</p>
            </div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($acceptedRequests as $a): ?>
              <div class="col-lg-6">
                <div class="card-custom p-4 border-success">
                  <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= $baseUrl ?>uploads/profiles/<?= htmlspecialchars($a['partner_avatar']) ?>" class="avatar-md" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($a['partner_name']) ?>';">
                    <div>
                      <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($a['partner_name']) ?></h6>
                      <span class="badge bg-success-subtle text-success mt-1"><i class="bi bi-check-circle-fill"></i> Exchange Active</span>
                    </div>
                  </div>
                  <div class="p-3 bg-light rounded-3 small mb-3">
                    <div><strong>Skill:</strong> <span class="fw-bold text-primary"><?= htmlspecialchars($a['skill_name']) ?></span></div>
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-muted">Accepted: <?= date('M d, Y', strtotime($a['updated_at'])) ?></span>
                    <a href="sessions.php?req_id=<?= $a['request_id'] ?>" class="btn btn-sm btn-primary-custom">
                      <i class="bi bi-calendar-plus me-1"></i> Schedule Session
                    </a>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
