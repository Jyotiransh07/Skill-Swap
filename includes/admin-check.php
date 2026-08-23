<?php
/**
 * SkillSwap Campus - Admin Authentication Guard Middleware
 */

require_once __DIR__ . '/functions.php';
init_session();

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    set_flash('warning', 'Administrator authentication required.');
    header('Location: ' . get_base_url() . 'auth/login.php');
    exit;
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    set_flash('danger', 'Access denied. Administrator privileges required.');
    header('Location: ' . get_base_url() . 'student/dashboard.php');
    exit;
}
