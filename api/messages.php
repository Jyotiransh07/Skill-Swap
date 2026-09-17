<?php
/**
 * SkillSwap Campus - Messages API
 */
require_once __DIR__ . '/../includes/functions.php';
init_session();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo = Database::getConnection();

try {
    if ($action === 'fetch_contacts') {
        // Find users that this user has interacted with (sessions or accepted requests)
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.user_id, u.name, u.profile_image 
            FROM users u
            WHERE u.user_id != :uid 
            AND u.status = 'active'
            AND (
                u.user_id IN (SELECT teacher_id FROM sessions WHERE learner_id = :uid1)
                OR 
                u.user_id IN (SELECT learner_id FROM sessions WHERE teacher_id = :uid2)
                OR 
                u.user_id IN (SELECT sender_id FROM learning_requests WHERE receiver_id = :uid3 AND status = 'ACCEPTED')
                OR 
                u.user_id IN (SELECT receiver_id FROM learning_requests WHERE sender_id = :uid4 AND status = 'ACCEPTED')
            )
        ");
        $stmt->execute([
            'uid' => $user_id,
            'uid1' => $user_id,
            'uid2' => $user_id,
            'uid3' => $user_id,
            'uid4' => $user_id
        ]);
        
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch last message and unread count for each contact
        foreach ($contacts as &$contact) {
            $partner_id = $contact['user_id'];
            
            // Unread count
            $stmtUnread = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = :partner AND receiver_id = :me AND is_read = 0");
            $stmtUnread->execute(['partner' => $partner_id, 'me' => $user_id]);
            $contact['unread_count'] = $stmtUnread->fetchColumn();
            
            // Last message
            $stmtLast = $pdo->prepare("SELECT message, created_at, sender_id FROM messages WHERE (sender_id = :p1 AND receiver_id = :m1) OR (sender_id = :m2 AND receiver_id = :p2) ORDER BY created_at DESC LIMIT 1");
            $stmtLast->execute(['p1' => $partner_id, 'm1' => $user_id, 'm2' => $user_id, 'p2' => $partner_id]);
            $lastMsg = $stmtLast->fetch(PDO::FETCH_ASSOC);
            
            if ($lastMsg) {
                $contact['last_message'] = $lastMsg['message'];
                $contact['last_message_time'] = date('M d, H:i', strtotime($lastMsg['created_at']));
                $contact['is_last_mine'] = ($lastMsg['sender_id'] == $user_id);
                $contact['sort_time'] = strtotime($lastMsg['created_at']);
            } else {
                $contact['last_message'] = 'Start a conversation...';
                $contact['last_message_time'] = '';
                $contact['is_last_mine'] = false;
                $contact['sort_time'] = 0;
            }
        }
        
        // Sort contacts by most recent message
        usort($contacts, function($a, $b) {
            return $b['sort_time'] <=> $a['sort_time'];
        });
        
        echo json_encode(['success' => true, 'contacts' => $contacts]);
        
    } elseif ($action === 'fetch_messages') {
        $partner_id = $_GET['partner_id'] ?? 0;
        if (!$partner_id) throw new Exception("Partner ID required");
        
        // Mark as read
        $stmtUpdate = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = :partner AND receiver_id = :me AND is_read = 0");
        $stmtUpdate->execute(['partner' => $partner_id, 'me' => $user_id]);
        
        // Fetch conversation
        $stmtMsg = $pdo->prepare("
            SELECT m.*, u.name as sender_name, u.profile_image as sender_image 
            FROM messages m
            JOIN users u ON m.sender_id = u.user_id
            WHERE (m.sender_id = :p1 AND m.receiver_id = :m1) 
               OR (m.sender_id = :m2 AND m.receiver_id = :p2)
            ORDER BY m.created_at ASC
        ");
        $stmtMsg->execute(['p1' => $partner_id, 'm1' => $user_id, 'm2' => $user_id, 'p2' => $partner_id]);
        $messages = $stmtMsg->fetchAll(PDO::FETCH_ASSOC);
        
        // Format time
        foreach($messages as &$msg) {
            $msg['time_formatted'] = date('H:i', strtotime($msg['created_at']));
            $msg['is_mine'] = ($msg['sender_id'] == $user_id);
        }
        
        echo json_encode(['success' => true, 'messages' => $messages]);
        
    } elseif ($action === 'send_message') {
        $receiver_id = $_POST['receiver_id'] ?? 0;
        $message = trim($_POST['message'] ?? '');
        
        if (!$receiver_id || empty($message)) {
            throw new Exception("Invalid parameters");
        }
        
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, is_read, created_at) VALUES (:sender, :receiver, :msg, 0, NOW())");
        $stmt->execute([
            'sender' => $user_id,
            'receiver' => $receiver_id,
            'msg' => sanitize($message)
        ]);
        
        echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
        
    } elseif ($action === 'poll_unread_total') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = :me AND is_read = 0");
        $stmt->execute(['me' => $user_id]);
        echo json_encode(['success' => true, 'unread_total' => $stmt->fetchColumn()]);
    } else {
        throw new Exception("Invalid Action");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
