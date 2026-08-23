<?php
/**
 * SkillSwap Campus - Database Connection Configuration
 * Configured for WAMP / Apache / MySQL local server.
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'skillswap');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=%s", DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // In production, log error privately. For localhost, show helpful message.
                die('<div style="font-family:sans-serif; padding:30px; background:#fef2f2; color:#991b1b; border:1px solid #fca5a5; margin:50px auto; max-width:600px; border-radius:8px;">'
                  . '<h2>Database Connection Error</h2>'
                  . '<p>Could not connect to MySQL server. Please ensure <strong>WAMP / MySQL</strong> is running and <code>skillswap</code> database is imported.</p>'
                  . '<p><strong>Details:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>'
                  . '</div>');
            }
        }
        return self::$instance;
    }
}
