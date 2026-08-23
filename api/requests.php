<?php
/**
 * SkillSwap Campus - Exchange Requests AJAX API Endpoint
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

init_session();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = Database::getConnection();
$currentUserId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'accept') {
    $reqId = (int)($_POST['request_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE learning_requests SET status = 'ACCEPTED', updated_at = NOW() WHERE request_id = :id AND receiver_id = :uid");
    $stmt->execute(['id' => $reqId, 'uid' => $currentUserId]);
    echo json_encode(['success' => true, 'message' => 'Request accepted']);
    exit;
}

if ($action === 'reject') {
    $reqId = (int)($_POST['request_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE learning_requests SET status = 'REJECTED', updated_at = NOW() WHERE request_id = :id AND receiver_id = :uid");
    $stmt->execute(['id' => $reqId, 'uid' => $currentUserId]);
    echo json_encode(['success' => true, 'message' => 'Request rejected']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
