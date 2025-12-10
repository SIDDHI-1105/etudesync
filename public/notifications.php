<?php

// filepath: d:\xampp\htdocs\etudesync\includes\notifications.php

// Simple helper functions for notifications

if (!function_exists('notify_user')) {
    /**
     * Create a notification for a user.
     *
     * @param PDO         $pdo
     * @param int         $userId
     * @param string      $title
     * @param string      $body
     * @param string|null $url  Optional URL to open when user clicks
     * @return bool
     */
    function notify_user(PDO $pdo, int $userId, string $title, string $body, ?string $url = null): bool
    {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, body, url, is_read, created_at)
                VALUES (:u, :t, :b, :url, 0, NOW())
            ");
            return $stmt->execute([
                ':u'   => $userId,
                ':t'   => $title,
                ':b'   => $body,
                ':url' => $url,
            ]);
        } catch (PDOException $e) {
            error_log('notify_user error: ' . $e->getMessage());
            return false;
        }
    }
}
