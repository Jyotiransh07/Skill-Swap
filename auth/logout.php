<?php
/**
 * SkillSwap Campus - Session Logout Handler
 */
require_once __DIR__ . '/../includes/functions.php';
init_session();

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

init_session();
set_flash('info', 'You have been successfully logged out.');
header('Location: ' . get_base_url() . 'auth/login.php');
exit;
