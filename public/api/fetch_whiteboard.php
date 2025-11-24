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

try {
    // get last saved whiteboard for this room
    $stmt = $pdo->prepare("SELECT wb_id, user_id, data, created_at FROM whiteboard_data WHERE room_id = :room ORDER BY wb_id DESC LIMIT 1");
    $stmt->execute([':room'=>$room_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success'=>false,'error'=>'No whiteboard found']);
        exit;
    }
    $data = json_decode($row['data'], true);
    // attach metadata
    $result = [
      'wb_id' => (int)$row['wb_id'],
      'user_id' => (int)$row['user_id'],
      'data' => $data,
      'created_at' => $row['created_at']
    ];
    echo json_encode(['success'=>true,'data'=>$result]);
    exit;
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    exit;
}
