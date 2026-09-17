<?php
/**
 * SkillSwap Campus - Global Header Navbar Component
 */
require_once __DIR__ . '/functions.php';
init_session();
$baseUrl = get_base_url();

$isLoggedIn = isset($_SESSION['user_id']);
$userName   = $_SESSION['user_name'] ?? 'User';
$userRole   = $_SESSION['user_role'] ?? 'student';
$userAvatar = $_SESSION['profile_image'] ?? 'default-avatar.png';

$unreadCount = 0;
$unreadNotifications = [];

if ($isLoggedIn) {
    try {
        $pdo = Database::getConnection();
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :id AND is_read = 0");
        $stmtCount->execute(['id' => $_SESSION['user_id']]);
        $unreadCount = (int)$stmtCount->fetchColumn();

        $stmtList = $pdo->prepare("SELECT notification_id, message, created_at, is_read FROM notifications WHERE user_id = :id ORDER BY created_at DESC LIMIT 5");
        $stmtList->execute(['id' => $_SESSION['user_id']]);
        $unreadNotifications = $stmtList->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Soft fallback
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
  <div class="container-fluid px-lg-4">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $baseUrl ?>index.php">
      <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width:38px; height:38px; font-weight:800;">
        <i class="bi bi-arrow-repeat fs-5"></i>
      </div>
      <div>
        <span class="fw-bold text-dark fs-5">SkillSwap</span>
        <span class="text-primary fw-extrabold fs-5">Campus</span>
        <span class="d-none d-sm-inline-block badge bg-light text-primary border border-primary ms-1" style="font-size:0.65rem;">v1.0</span>
      </div>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
        <li class="nav-item">
          <a class="nav-link fw-semibold" href="<?= $baseUrl ?>index.php">Home</a>
        </li>
        <?php if ($isLoggedIn && $userRole === 'student'): ?>
        <li class="nav-item">
          <a class="nav-link fw-semibold" href="<?= $baseUrl ?>student/matches.php">
            <i class="bi bi-stars text-warning me-1"></i> Smart Matches
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link fw-semibold" href="<?= $baseUrl ?>student/find-skills.php">Explore Skills</a>
        </li>
        <?php endif; ?>
      </ul>

      <div class="d-flex align-items-center gap-3">
        <?php if ($isLoggedIn): ?>
          <!-- Notification Dropdown -->
          <div class="dropdown">
            <button class="btn btn-light rounded-circle position-relative p-2" type="button" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-bell fs-5 text-secondary"></i>
              <?php if ($unreadCount > 0): ?>
              <span id="notificationBadgeCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?= $unreadCount ?>
              </span>
              <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2" style="width: 320px; max-height: 400px; overflow-y: auto;" aria-labelledby="notifDropdown">
              <li class="d-flex justify-content-between align-items-center px-2 py-1 border-bottom">
                <span class="fw-bold fs-6">Notifications</span>
                <a href="<?= $baseUrl ?>student/notifications.php" class="small text-primary text-decoration-none">View All</a>
              </li>
              <?php if (empty($unreadNotifications)): ?>
                <li class="text-center py-4 text-muted small">No recent notifications</li>
              <?php else: ?>
                <?php foreach ($unreadNotifications as $n): ?>
                <li class="p-2 border-bottom notification-item <?= $n['is_read'] ? 'opacity-75' : 'bg-light rounded' ?>">
                  <div class="d-flex justify-content-between align-items-start">
                    <p class="mb-1 small text-dark fw-medium"><?= htmlspecialchars($n['message']) ?></p>
                  </div>
                  <span class="text-muted" style="font-size:0.7rem;"><?= date('M d, H:i', strtotime($n['created_at'])) ?></span>
                </li>
                <?php endforeach; ?>
              <?php endif; ?>
            </ul>
          </div>

          <!-- User Menu Dropdown -->
          <div class="dropdown d-flex align-items-center bg-white border rounded-pill p-1 pe-2 shadow-sm">
            <?php
              $profileLink = $userRole === 'admin' ? $baseUrl . 'admin/dashboard.php' : $baseUrl . 'student/profile.php';
            ?>
            <a href="<?= $profileLink ?>" class="d-flex align-items-center text-dark text-decoration-none gap-2">
              <img src="<?= $baseUrl ?>uploads/profiles/<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" class="avatar-sm rounded-circle" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($userName) ?>&background=4f46e5&color=fff';" style="width:32px; height:32px; object-fit:cover;">
              <span class="fw-bold d-none d-md-inline-block"><?= htmlspecialchars($userName) ?></span>
            </a>
            
            <a href="#" class="text-secondary ms-2 text-decoration-none" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false" style="padding-left: 5px; border-left: 1px solid #dee2e6;">
              <i class="bi bi-caret-down-fill" style="font-size: 0.8rem;"></i>
            </a>

            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3" aria-labelledby="userMenu">
              <?php if ($userRole === 'admin'): ?>
                <li><a class="dropdown-item fw-semibold py-2" href="<?= $baseUrl ?>admin/dashboard.php"><i class="bi bi-speedometer2 me-2 text-primary"></i> Admin Panel</a></li>
              <?php else: ?>
                <li><a class="dropdown-item fw-semibold py-2" href="<?= $baseUrl ?>student/dashboard.php"><i class="bi bi-grid me-2 text-primary"></i> Dashboard</a></li>
                <li><a class="dropdown-item fw-semibold py-2" href="<?= $baseUrl ?>student/profile.php"><i class="bi bi-person me-2 text-primary"></i> My Profile</a></li>
                <li><a class="dropdown-item fw-semibold py-2" href="<?= $baseUrl ?>student/edit-profile.php"><i class="bi bi-gear me-2 text-primary"></i> Settings</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger fw-semibold py-2" href="<?= $baseUrl ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
            </ul>
          </div>

        <?php else: ?>
          <a href="<?= $baseUrl ?>auth/login.php" class="btn btn-outline-custom">Login</a>
          <a href="<?= $baseUrl ?>auth/register.php" class="btn btn-primary-custom">Register Free</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
