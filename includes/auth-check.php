<?php
/**
 * SkillSwap Campus - Student Authentication Guard Middleware
 */

require_once __DIR__ . '/functions.php';
init_session();

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    set_flash('warning', 'Please login to access your SkillSwap Campus account.');
    header('Location: ' . get_base_url() . 'auth/login.php');
    exit;
}

// Check user status in database to enforce suspension or inactivation
try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT status, role FROM users WHERE user_id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $userStatus = $stmt->fetch();

    if (!$userStatus || $userStatus['status'] !== 'active') {
        session_destroy();
        init_session();
        set_flash('danger', 'Your account has been deactivated or suspended by the administrator.');
        header('Location: ' . get_base_url() . 'auth/login.php');
        exit;
    }
} catch (Exception $e) {
    // Continue execution if DB check encounters soft error
}
