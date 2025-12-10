<?php
// filepath: d:\xampp\htdocs\etudesync\public\api\mark_notification.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/db.php'; // must create $pdo

// Basic auth check
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Auth']);
    exit;
}
$uid = (int) $_SESSION['user_id'];

// Read JSON payload
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Bad payload']);
    exit;
}

$id = isset($payload['id']) ? (int) $payload['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid id']);
    exit;
}

try {
    // Use a descriptive placeholder name :user_id to avoid confusion
    $stmt = $pdo->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE id = :id AND user_id = :user_id
    ");
    $stmt->execute([
        ':id' => $id,
        ':user_id' => $uid
    ]);

    // Optionally check whether a row was updated
    if ($stmt->rowCount() === 0) {
        // nothing changed — either id doesn't exist or doesn't belong to user
        echo json_encode(['success' => false, 'message' => 'No notification updated']);
        exit;
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Log detailed DB error server-side
    error_log('mark_notification error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB error']);
    exit;
}
