<?php
/**
 * SkillSwap Campus - Notifications AJAX API Endpoint
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

init_session();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = Database::getConnection();
$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'mark_read') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = :id AND user_id = :uid");
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        echo json_encode(['success' => true]);
        exit;
    }
}

if ($action === 'fetch_unread') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
    $stmt->execute(['uid' => $userId]);
    $unread = (int)$stmt->fetchColumn();
    echo json_encode(['success' => true, 'unread_count' => $unread]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
