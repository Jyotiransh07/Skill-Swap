<?php
/**
 * SkillSwap Campus - Core Business Logic & Helper Functions
 */

require_once __DIR__ . '/../config/database.php';

function init_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generate_csrf_token(): string {
    init_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    init_session();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function set_flash(string $type, string $message): void {
    init_session();
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash(): ?array {
    init_session();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function get_base_url(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    
    // Normalize to WAMP standard root URL if needed
    if (strpos($scriptDir, '/skillswap') !== false) {
        return $protocol . '://' . $host . '/skillswap/';
    }
    return $protocol . '://' . $host . '/skillswap/';
}

function create_notification(PDO $pdo, int $user_id, string $message, string $type = 'SYSTEM'): bool {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type, is_read, created_at) VALUES (:user_id, :message, :type, 0, NOW())");
    return $stmt->execute([
        'user_id' => $user_id,
        'message' => $message,
        'type'    => $type
    ]);
}

/**
 * Smart Matching Compatibility Algorithm
 * Calculates compatibility score (0-100%) and provides explainable match rationale.
 */
function calculate_skill_match_score(PDO $pdo, int $current_user_id, int $target_user_id): array {
    // 1. Fetch current user details
    $stmtUser1 = $pdo->prepare("SELECT user_id, department, year, reputation_score FROM users WHERE user_id = :id");
    $stmtUser1->execute(['id' => $current_user_id]);
    $u1 = $stmtUser1->fetch();

    // 2. Fetch target user details
    $stmtUser2 = $pdo->prepare("SELECT user_id, department, year, reputation_score FROM users WHERE user_id = :id");
    $stmtUser2->execute(['id' => $target_user_id]);
    $u2 = $stmtUser2->fetch();

    if (!$u1 || !$u2) {
        return [
            'score' => 0,
            'is_perfect' => false,
            'learn_skills' => [],
            'teach_skills' => [],
            'reasons' => ['User profile incomplete.']
        ];
    }

    // Fetch U1 Learn skills and U2 Teach skills (Skills U1 can learn from U2)
    $stmtDirect = $pdo->prepare("
        SELECT s.skill_id, s.skill_name 
        FROM user_skills us2
        JOIN skills s ON us2.skill_id = s.skill_id
        JOIN user_skills us1 ON us1.skill_id = s.skill_id
        WHERE us2.user_id = :target_id AND us2.skill_type = 'TEACH'
          AND us1.user_id = :current_id AND us1.skill_type = 'LEARN'
    ");
    $stmtDirect->execute(['target_id' => $target_user_id, 'current_id' => $current_user_id]);
    $directMatchSkills = $stmtDirect->fetchAll(PDO::FETCH_ASSOC);

    // Fetch U1 Teach skills and U2 Learn skills (Skills U1 can teach U2)
    $stmtReverse = $pdo->prepare("
        SELECT s.skill_id, s.skill_name 
        FROM user_skills us1
        JOIN skills s ON us1.skill_id = s.skill_id
        JOIN user_skills us2 ON us2.skill_id = s.skill_id
        WHERE us1.user_id = :current_id AND us1.skill_type = 'TEACH'
          AND us2.user_id = :target_id AND us2.skill_type = 'LEARN'
    ");
    $stmtReverse->execute(['current_id' => $current_user_id, 'target_id' => $target_user_id]);
    $reverseMatchSkills = $stmtReverse->fetchAll(PDO::FETCH_ASSOC);

    $score = 0;
    $reasons = [];
    $learnSkillsNames = array_column($directMatchSkills, 'skill_name');
    $teachSkillsNames = array_column($reverseMatchSkills, 'skill_name');

    // 1. Direct match (50 points)
    if (!empty($directMatchSkills)) {
        $score += 50;
        $reasons[] = "Teaches " . implode(', ', $learnSkillsNames) . " which you want to learn.";
    }

    // 2. Reverse match (30 points)
    if (!empty($reverseMatchSkills)) {
        $score += 30;
        $reasons[] = "Wants to learn " . implode(', ', $teachSkillsNames) . " which you can teach.";
    }

    // 3. Same department bonus (10 points)
    if (!empty($u1['department']) && $u1['department'] === $u2['department']) {
        $score += 10;
        $reasons[] = "Both are in " . $u1['department'] . " department.";
    }

    // 4. Same year bonus (5 points)
    if (!empty($u1['year']) && $u1['year'] === $u2['year']) {
        $score += 5;
        $reasons[] = "Both are in " . $u1['year'] . ".";
    }

    // 5. Reputation bonus (5 points)
    if ((float)$u2['reputation_score'] >= 80.0) {
        $score += 5;
        $reasons[] = "High teacher reputation (" . round($u2['reputation_score']) . "/100).";
    }

    // Cap at 100
    $finalScore = min(100, $score);
    $isPerfect = ($finalScore >= 80 && !empty($directMatchSkills) && !empty($reverseMatchSkills));

    return [
        'score' => $finalScore,
        'is_perfect' => $isPerfect,
        'learn_skills' => $learnSkillsNames,
        'teach_skills' => $teachSkillsNames,
        'reasons' => $reasons
    ];
}

/**
 * Re-evaluates User Reputation Score
 */
function update_reputation_score(PDO $pdo, int $user_id): void {
    try {
        $stmt = $pdo->prepare("CALL sp_calculate_reputation(:id)");
        $stmt->execute(['id' => $user_id]);
    } catch (Exception $e) {
        // Fallback PHP manual calculation if stored procedures are restricted
        $stmtRating = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE reviewee_id = :id");
        $stmtRating->execute(['id' => $user_id]);
        $avgRating = (float)($stmtRating->fetchColumn() ?? 0);

        $stmtSessions = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE (teacher_id = :id1 OR learner_id = :id2) AND status = 'COMPLETED'");
        $stmtSessions->execute(['id1' => $user_id, 'id2' => $user_id]);
        $completedSessions = (int)$stmtSessions->fetchColumn();

        $stmtExchanges = $pdo->prepare("SELECT COUNT(*) FROM learning_requests WHERE (sender_id = :id1 OR receiver_id = :id2) AND status = 'COMPLETED'");
        $stmtExchanges->execute(['id1' => $user_id, 'id2' => $user_id]);
        $completedExchanges = (int)$stmtExchanges->fetchColumn();

        $score = ($avgRating / 5.0 * 40.0) 
               + (min($completedSessions, 10) / 10.0 * 40.0) 
               + (min($completedExchanges, 5) / 5.0 * 20.0);
        $score = min(100.0, max(0.0, $score));

        $stmtUpdate = $pdo->prepare("UPDATE users SET reputation_score = :score WHERE user_id = :id");
        $stmtUpdate->execute(['score' => $score, 'id' => $user_id]);
    }
}

// Temporary Auto-Migration for New Features
try {
    $migrationPdo = Database::getConnection();
    $migrationPdo->exec("
    CREATE TABLE IF NOT EXISTS `messages` (
      `message_id` INT AUTO_INCREMENT PRIMARY KEY,
      `sender_id` INT NOT NULL,
      `receiver_id` INT NOT NULL,
      `message` TEXT NOT NULL,
      `is_read` TINYINT(1) DEFAULT 0,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
      FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Exception $e) {
    // Ignore if already created or errors out
}

