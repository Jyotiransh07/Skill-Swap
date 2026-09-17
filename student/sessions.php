<?php
/**
 * SkillSwap Campus - Sessions Management & Scheduling Page
 */
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = get_base_url();
$pageTitle = 'Learning Sessions - SkillSwap Campus';
$currentUserId = $_SESSION['user_id'];
$pdo = Database::getConnection();

$error   = '';
$success = '';

// Handle New Session Scheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'schedule_session') {
    $requestId   = (int)($_POST['request_id'] ?? 0);
    $sessionDate = sanitize($_POST['session_date'] ?? '');
    $startTime   = sanitize($_POST['start_time'] ?? '');
    $endTime     = sanitize($_POST['end_time'] ?? '');
    $mode        = sanitize($_POST['mode'] ?? 'ONLINE');
    $meetingLink = sanitize($_POST['meeting_link'] ?? '');
    $location    = sanitize($_POST['location'] ?? '');
    $csrf_token  = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $error = 'Security check failed.';
    } elseif ($requestId <= 0 || empty($sessionDate) || empty($startTime) || empty($endTime)) {
        $error = 'Please fill out date and start/end times.';
    } else {
        // Fetch request details
        $stmtReq = $pdo->prepare("SELECT * FROM learning_requests WHERE request_id = :id AND status = 'ACCEPTED'");
        $stmtReq->execute(['id' => $requestId]);
        $req = $stmtReq->fetch();

        if (!$req) {
            $error = 'Accepted exchange request not found.';
        } else {
            $teacherId = $req['receiver_id'];
            $learnerId = $req['sender_id'];
            $skillId   = $req['skill_id'];

            $stmtIns = $pdo->prepare("
                INSERT INTO sessions (request_id, teacher_id, learner_id, skill_id, session_date, start_time, end_time, mode, meeting_link, location, status, created_at)
                VALUES (:req_id, :t_id, :l_id, :sk_id, :sdate, :stime, :etime, :mode, :mlink, :loc, 'SCHEDULED', NOW())
            ");
            $stmtIns->execute([
                'req_id' => $requestId,
                't_id'   => $teacherId,
                'l_id'   => $learnerId,
                'sk_id'  => $skillId,
                'sdate'  => $sessionDate,
                'stime'  => $startTime,
                'etime'  => $endTime,
                'mode'   => $mode,
                'mlink'  => $meetingLink,
                'loc'    => $location
            ]);

            // Notify partner
            $partnerId = ($currentUserId === $teacherId) ? $learnerId : $teacherId;
            create_notification($pdo, $partnerId, "A new learning session has been scheduled for " . date('M d', strtotime($sessionDate)) . "!", "SESSION");

            set_flash('success', 'Session scheduled successfully!');
            header('Location: ' . $baseUrl . 'student/sessions.php');
            exit;
        }
    }
}

// Handle Mark Complete
if (isset($_GET['complete']) && (int)$_GET['complete'] > 0) {
    $sessId = (int)$_GET['complete'];
    $stmtUp = $pdo->prepare("UPDATE sessions SET status = 'COMPLETED' WHERE session_id = :id AND (teacher_id = :uid1 OR learner_id = :uid2)");
    $stmtUp->execute(['id' => $sessId, 'uid1' => $currentUserId, 'uid2' => $currentUserId]);

    // Recalculate reputation
    update_reputation_score($pdo, $currentUserId);

    set_flash('success', 'Session marked as Completed! Please leave a review for your exchange partner.');
    header('Location: ' . $baseUrl . 'student/reviews.php?session_id=' . $sessId);
    exit;
}

// Fetch Accepted Requests for modal dropdown
$stmtAcc = $pdo->prepare("
    SELECT lr.request_id, s.skill_name,
           CASE WHEN lr.sender_id = :id1 THEN u_rec.name ELSE u_snd.name END AS partner_name
    FROM learning_requests lr
    JOIN users u_snd ON lr.sender_id = u_snd.user_id
    JOIN users u_rec ON lr.receiver_id = u_rec.user_id
    JOIN skills s ON lr.skill_id = s.skill_id
    WHERE (lr.sender_id = :id2 OR lr.receiver_id = :id3) AND lr.status = 'ACCEPTED'
");
$stmtAcc->execute(['id1' => $currentUserId, 'id2' => $currentUserId, 'id3' => $currentUserId]);
$acceptedRequestsDropdown = $stmtAcc->fetchAll(PDO::FETCH_ASSOC);

// Fetch All User Sessions
$stmtSessions = $pdo->prepare("
    SELECT s.*, sk.skill_name,
           t.name AS teacher_name, l.name AS learner_name
    FROM sessions s
    JOIN skills sk ON s.skill_id = sk.skill_id
    JOIN users t ON s.teacher_id = t.user_id
    JOIN users l ON s.learner_id = l.user_id
    WHERE s.teacher_id = :id1 OR s.learner_id = :id2
    ORDER BY s.session_date DESC, s.start_time ASC
");
$stmtSessions->execute(['id1' => $currentUserId, 'id2' => $currentUserId]);
$sessions = $stmtSessions->fetchAll(PDO::FETCH_ASSOC);

$upcomingSessions  = array_filter($sessions, fn($s) => $s['status'] === 'SCHEDULED' || $s['status'] === 'ONGOING');
$completedSessions = array_filter($sessions, fn($s) => $s['status'] === 'COMPLETED');

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
          <h2 class="fw-bold mb-1">Learning Sessions</h2>
          <p class="text-muted mb-0">Schedule and track your online and in-person skill exchange sessions.</p>
        </div>
        <button class="btn btn-primary-custom mt-3 mt-md-0" data-bs-toggle="modal" data-bs-target="#scheduleModal">
          <i class="bi bi-calendar-plus me-1"></i> Schedule New Session
        </button>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" onclick="this.closest('.alert').remove();"></button>
        </div>
      <?php endif; ?>

      <!-- UPCOMING SESSIONS -->
      <h5 class="fw-bold mb-3"><i class="bi bi-clock-history text-primary me-2"></i>Upcoming Sessions</h5>
      <?php if (empty($upcomingSessions)): ?>
        <div class="card-custom p-4 text-center text-muted mb-4">
          <p class="mb-0">No upcoming sessions scheduled. Accept an exchange request to schedule a session!</p>
        </div>
      <?php else: ?>
        <div class="row g-4 mb-4">
          <?php foreach ($upcomingSessions as $sess): ?>
          <div class="col-lg-6">
            <div class="card-custom p-4 border-start border-4 border-primary">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($sess['skill_name']) ?></h5>
                <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($sess['mode']) ?></span>
              </div>

              <div class="small text-muted mb-2">
                <i class="bi bi-person-circle me-1"></i>
                Teacher: <strong><?= htmlspecialchars($sess['teacher_name']) ?></strong> | Learner: <strong><?= htmlspecialchars($sess['learner_name']) ?></strong>
              </div>

              <div class="p-3 bg-light rounded-3 small mb-3">
                <div class="mb-1"><i class="bi bi-calendar-event me-2 text-primary"></i> <strong>Date:</strong> <?= date('l, M d, Y', strtotime($sess['session_date'])) ?></div>
                <div class="mb-1"><i class="bi bi-clock me-2 text-primary"></i> <strong>Time:</strong> <?= date('h:i A', strtotime($sess['start_time'])) ?> - <?= date('h:i A', strtotime($sess['end_time'])) ?></div>
                <?php if ($sess['mode'] === 'ONLINE' && !empty($sess['meeting_link'])): ?>
                  <div><i class="bi bi-camera-video me-2 text-success"></i> <strong>Link:</strong> <a href="<?= htmlspecialchars($sess['meeting_link']) ?>" target="_blank" class="fw-bold text-success">Join Meeting</a></div>
                <?php elseif (!empty($sess['location'])): ?>
                  <div><i class="bi bi-geo-alt me-2 text-danger"></i> <strong>Location:</strong> <?= htmlspecialchars($sess['location']) ?></div>
                <?php endif; ?>
              </div>

              <div class="d-flex justify-content-between align-items-center">
                <span class="badge bg-warning-subtle text-warning border border-warning">SCHEDULED</span>
                <a href="sessions.php?complete=<?= $sess['session_id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Mark this session as completed?');">
                  <i class="bi bi-check2-circle me-1"></i> Mark Completed
                </a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- COMPLETED SESSIONS -->
      <h5 class="fw-bold mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Completed Sessions</h5>
      <?php if (empty($completedSessions)): ?>
        <div class="card-custom p-4 text-center text-muted">
          <p class="mb-0">No past completed sessions recorded yet.</p>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($completedSessions as $cs): ?>
          <div class="col-md-6">
            <div class="card-custom p-3 bg-light">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($cs['skill_name']) ?></h6>
                <span class="badge bg-success-subtle text-success">COMPLETED</span>
              </div>
              <div class="small text-muted mb-2">
                Partner: <?= htmlspecialchars($cs['teacher_id'] == $currentUserId ? $cs['learner_name'] : $cs['teacher_name']) ?>
                • Date: <?= date('M d, Y', strtotime($cs['session_date'])) ?>
              </div>
              <a href="reviews.php?session_id=<?= $cs['session_id'] ?>" class="btn btn-sm btn-outline-custom">Leave / View Review</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<!-- SCHEDULE SESSION MODAL -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-light">
        <h5 class="modal-title fw-bold text-dark mb-0"><i class="bi bi-calendar-plus text-primary me-2"></i>Schedule Learning Session</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="sessions.php">
        <div class="modal-body p-4">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <input type="hidden" name="action" value="schedule_session">

          <div class="mb-3">
            <label for="request_id" class="form-label fw-semibold">Select Accepted Exchange *</label>
            <select class="form-select" id="request_id" name="request_id" required>
              <option value="" disabled selected>Choose exchange partner...</option>
              <?php foreach ($acceptedRequestsDropdown as $ar): ?>
                <option value="<?= $ar['request_id'] ?>"><?= htmlspecialchars($ar['skill_name']) ?> with <?= htmlspecialchars($ar['partner_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="session_date" class="form-label fw-semibold">Session Date *</label>
            <input type="date" class="form-control" id="session_date" name="session_date" min="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label for="start_time" class="form-label fw-semibold">Start Time *</label>
              <input type="time" class="form-control" id="start_time" name="start_time" required>
            </div>
            <div class="col-6">
              <label for="end_time" class="form-label fw-semibold">End Time *</label>
              <input type="time" class="form-control" id="end_time" name="end_time" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="mode" class="form-label fw-semibold">Mode *</label>
            <select class="form-select" id="mode" name="mode" required>
              <option value="ONLINE">ONLINE (Google Meet / Zoom)</option>
              <option value="OFFLINE">OFFLINE (In-Person Campus Location)</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="meeting_link" class="form-label fw-semibold">Meeting Link (For Online)</label>
            <input type="url" class="form-control" id="meeting_link" name="meeting_link" placeholder="https://meet.google.com/abc-xyz">
          </div>

          <div class="mb-3">
            <label for="location" class="form-label fw-semibold">Location (For Offline)</label>
            <input type="text" class="form-control" id="location" name="location" placeholder="e.g. Central Library Study Room 3">
          </div>
        </div>

        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-custom">Confirm & Schedule</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
