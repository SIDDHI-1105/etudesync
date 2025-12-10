<?php
// public/api/save_whiteboard.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/db.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Authentication required']);
    exit;
}

$raw = file_get_contents('php://input');
if (!$raw) {
    echo json_encode(['success'=>false,'error'=>'No payload']);
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['room_id'])) {
    echo json_encode(['success'=>false,'error'=>'Invalid payload']);
    exit;
}

$room_id = (int)$data['room_id'];
$user_id = (int)$_SESSION['user_id'];
$strokes = $data['strokes'] ?? [];
$meta = $data['meta'] ?? ['saved_at' => date('c')];

// normalize meta.saved_at
if (!isset($meta['saved_at'])) $meta['saved_at'] = date('c');

try {
    $json = json_encode(['strokes'=>$strokes,'meta'=>$meta], JSON_UNESCAPED_SLASHES);
    $stmt = $pdo->prepare("INSERT INTO whiteboard_data (room_id, user_id, data, created_at) VALUES (:room, :user, :data, NOW())");
    $stmt->execute([':room'=>$room_id, ':user'=>$user_id, ':data'=>$json]);

    // compute saved_at_ms using meta.saved_at (fallback to NOW)
    $savedAt = strtotime($meta['saved_at']);
    if (!$savedAt) $savedAt = time();
    $savedAtMs = $savedAt * 1000;

    echo json_encode(['success'=>true,'wb_id'=>$pdo->lastInsertId(), 'meta'=>$meta, 'saved_at_ms'=>$savedAtMs]);
    exit;
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'error'=>'DB error: '.$e->getMessage()]);
    exit;
}
