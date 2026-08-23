<?php
/**
 * SkillSwap Campus - Smart Matches AJAX API Endpoint
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
$targetUserId  = (int)($_GET['target_id'] ?? 0);

if ($targetUserId > 0) {
    $matchData = calculate_skill_match_score($pdo, $currentUserId, $targetUserId);
    echo json_encode(['success' => true, 'match' => $matchData]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Target user ID required']);
