<?php
/**
 * SkillSwap Campus - Sessions AJAX API Endpoint
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

if ($action === 'complete') {
    $sessId = (int)($_POST['session_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE sessions SET status = 'COMPLETED' WHERE session_id = :id AND (teacher_id = :uid1 OR learner_id = :uid2)");
    $stmt->execute(['id' => $sessId, 'uid1' => $currentUserId, 'uid2' => $currentUserId]);

    // Recalculate reputation
    update_reputation_score($pdo, $currentUserId);

    echo json_encode(['success' => true, 'message' => 'Session marked as completed']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
