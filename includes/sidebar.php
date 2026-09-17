<?php
/**
 * SkillSwap Campus - Modular Dashboard Sidebar Menu
 */
require_once __DIR__ . '/functions.php';
init_session();
$baseUrl = get_base_url();

$userRole = $_SESSION['user_role'] ?? 'student';
$currentScript = basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="sidebar">
  <div class="sidebar-brand d-none d-lg-flex align-items-center gap-2">
    <i class="bi bi-layers-fill text-primary"></i>
    <span>Navigation</span>
  </div>
  <ul class="sidebar-menu">
    <?php if ($userRole === 'admin'): ?>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>admin/dashboard.php" class="sidebar-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
          <i class="bi bi-speedometer2"></i> <span>Overview</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>admin/users.php" class="sidebar-link <?= $currentScript === 'users.php' ? 'active' : '' ?>">
          <i class="bi bi-people"></i> <span>Manage Users</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>admin/skills.php" class="sidebar-link <?= $currentScript === 'skills.php' ? 'active' : '' ?>">
          <i class="bi bi-journal-code"></i> <span>Skills & Categories</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>admin/requests.php" class="sidebar-link <?= $currentScript === 'requests.php' ? 'active' : '' ?>">
          <i class="bi bi-arrow-left-right"></i> <span>All Requests</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>admin/sessions.php" class="sidebar-link <?= $currentScript === 'sessions.php' ? 'active' : '' ?>">
          <i class="bi bi-calendar-event"></i> <span>All Sessions</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>admin/reviews.php" class="sidebar-link <?= $currentScript === 'reviews.php' ? 'active' : '' ?>">
          <i class="bi bi-star"></i> <span>Reviews</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>admin/reports.php" class="sidebar-link <?= $currentScript === 'reports.php' ? 'active' : '' ?>">
          <i class="bi bi-exclamation-triangle"></i> <span>Reports</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>admin/analytics.php" class="sidebar-link <?= $currentScript === 'analytics.php' ? 'active' : '' ?>">
          <i class="bi bi-bar-chart-line"></i> <span>Analytics</span>
        </a>
      </li>

    <?php else: ?>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>student/dashboard.php" class="sidebar-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
          <i class="bi bi-grid-1x2"></i> <span>Dashboard</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>student/matches.php" class="sidebar-link <?= $currentScript === 'matches.php' ? 'active' : '' ?>">
          <i class="bi bi-stars text-warning"></i> <span>Smart Matches</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>student/skills.php" class="sidebar-link <?= $currentScript === 'skills.php' ? 'active' : '' ?>">
          <i class="bi bi-book"></i> <span>My Skills</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>student/find-skills.php" class="sidebar-link <?= $currentScript === 'find-skills.php' ? 'active' : '' ?>">
          <i class="bi bi-search"></i> <span>Explore Skills</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>student/requests.php" class="sidebar-link <?= $currentScript === 'requests.php' ? 'active' : '' ?>">
          <i class="bi bi-send"></i> <span>Exchange Requests</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>student/sessions.php" class="sidebar-link <?= $currentScript === 'sessions.php' ? 'active' : '' ?>">
          <i class="bi bi-calendar-week"></i> <span>Learning Sessions</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>student/calendar.php" class="sidebar-link <?= $currentScript === 'calendar.php' ? 'active' : '' ?>">
          <i class="bi bi-calendar3"></i> <span>Interactive Calendar</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>student/messages.php" class="sidebar-link <?= $currentScript === 'messages.php' ? 'active' : '' ?>">
          <i class="bi bi-chat-dots"></i> <span>Messages</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>student/reviews.php" class="sidebar-link <?= $currentScript === 'reviews.php' ? 'active' : '' ?>">
          <i class="bi bi-chat-square-quote"></i> <span>Reviews</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>student/notifications.php" class="sidebar-link <?= $currentScript === 'notifications.php' ? 'active' : '' ?>">
          <i class="bi bi-bell"></i> <span>Notifications</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a href="<?= $baseUrl ?>student/reports.php" class="sidebar-link <?= $currentScript === 'reports.php' ? 'active' : '' ?>">
          <i class="bi bi-flag"></i> <span>Report Issue</span>
        </a>
      </li>
    <?php endif; ?>
  </ul>
</aside>
