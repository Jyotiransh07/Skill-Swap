<?php
/**
 * SkillSwap Campus - Skills AJAX Search API Endpoint
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

init_session();
$pdo = Database::getConnection();

$query = sanitize($_GET['q'] ?? '');

$sql = "SELECT skill_id, skill_name, category FROM skills";
$params = [];

if (!empty($query)) {
    $sql .= " WHERE skill_name LIKE :q1 OR category LIKE :q2";
    $params['q1'] = '%' . $query . '%';
    $params['q2'] = '%' . $query . '%';
}

$sql .= " ORDER BY category ASC, skill_name ASC LIMIT 20";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $skills]);
