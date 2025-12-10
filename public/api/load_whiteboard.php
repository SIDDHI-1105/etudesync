<?php
// public/api/load_whiteboard.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/db.php';

$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
if ($room_id <= 0) {
    echo json_encode(['success'=>false,'error'=>'Invalid room_id']);
    exit;
}

$since = isset($_GET['since']) ? (int)$_GET['since'] : 0;

try {
    $stmt = $pdo->prepare("SELECT wb_id, user_id, data, created_at FROM whiteboard_data WHERE room_id = :room ORDER BY wb_id DESC LIMIT 1");
    $stmt->execute([':room'=>$room_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success'=>true,'data'=>null,'updated'=>false]);
        exit;
    }

    $payload = json_decode($row['data'], true);
    if (!is_array($payload)) $payload = [];

    $strokes = $payload['strokes'] ?? [];
    $meta = $payload['meta'] ?? ['saved_at' => $row['created_at']];

    // timestamp in ms
    $savedAt = strtotime($meta['saved_at']) ?: strtotime($row['created_at']);
    $savedAtMs = $savedAt ? $savedAt * 1000 : (int)(microtime(true) * 1000);

    if ($since && $savedAtMs <= $since) {
        echo json_encode(['success'=>true,'data'=>null,'updated'=>false]);
        exit;
    }

    $result = [
        'wb_id' => (int)$row['wb_id'],
        'user_id' => (int)$row['user_id'],
        'strokes' => $strokes,
        'meta' => $meta,
        'created_at' => $row['created_at'],
        'saved_at_ms' => $savedAtMs
    ];

    echo json_encode(['success'=>true,'data'=>$result,'updated'=>true]);
    exit;
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    exit;
}
