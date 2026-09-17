<?php
/**
 * SkillSwap Campus - Calendar API
 * Returns sessions for the current user in FullCalendar JSON format.
 */
require_once __DIR__ . '/../includes/functions.php';
init_session();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$start = $_GET['start'] ?? ''; // FullCalendar passes ISO start/end
$end = $_GET['end'] ?? '';

$pdo = Database::getConnection();

try {
    // Fetch sessions where user is teacher or learner
    $sql = "
        SELECT 
            s.session_id, s.session_date, s.start_time, s.end_time, s.mode, s.meeting_link, s.location, s.status,
            sk.skill_name,
            t.name as teacher_name, t.user_id as teacher_id,
            l.name as learner_name, l.user_id as learner_id
        FROM sessions s
        JOIN skills sk ON s.skill_id = sk.skill_id
        JOIN users t ON s.teacher_id = t.user_id
        JOIN users l ON s.learner_id = l.user_id
        WHERE (s.teacher_id = :u1 OR s.learner_id = :u2)
    ";
    
    // If start/end provided, filter (FullCalendar passes ISO8601)
    $params = ['u1' => $user_id, 'u2' => $user_id];
    if ($start && $end) {
        $sql .= " AND s.session_date BETWEEN :start AND :end";
        $params['start'] = substr($start, 0, 10);
        $params['end'] = substr($end, 0, 10);
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $events = [];
    foreach ($sessions as $s) {
        $is_teacher = ($s['teacher_id'] == $user_id);
        $partner_name = $is_teacher ? $s['learner_name'] : $s['teacher_name'];
        $role_text = $is_teacher ? 'Teaching' : 'Learning';
        
        $title = "{$s['skill_name']} ($role_text with $partner_name)";
        
        // Colors based on status
        $color = '#4f46e5'; // Primary (Scheduled)
        if ($s['status'] === 'COMPLETED') $color = '#10b981'; // Success
        if ($s['status'] === 'CANCELLED') $color = '#ef4444'; // Danger
        
        $events[] = [
            'id' => $s['session_id'],
            'title' => $title,
            'start' => $s['session_date'] . 'T' . $s['start_time'],
            'end' => $s['session_date'] . 'T' . $s['end_time'],
            'color' => $color,
            'extendedProps' => [
                'skill' => $s['skill_name'],
                'partner' => $partner_name,
                'role' => $role_text,
                'mode' => $s['mode'],
                'meeting_link' => $s['meeting_link'],
                'location' => $s['location'],
                'status' => $s['status']
            ]
        ];
    }
    
    echo json_encode($events);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
