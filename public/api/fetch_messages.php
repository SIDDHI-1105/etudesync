<?php
// public/api/fetch_messages.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/db.php';

$room_id = (int)($_GET['room_id'] ?? 0);
$since_id = (int)($_GET['since_id'] ?? 0);
$limit = min(200, (int)($_GET['limit'] ?? 200));

if ($room_id <= 0) {
  echo json_encode(['success'=>false,'error'=>'Invalid room_id']);
  exit;
}

try {
  if ($since_id > 0) {
    $stmt = $pdo->prepare("SELECT m.*, u.name AS user_name, u.avatar_path FROM messages m LEFT JOIN users u ON u.id = m.user_id WHERE m.room_id = :room AND m.message_id > :since ORDER BY m.message_id ASC LIMIT :limit");
    $stmt->bindValue(':room', $room_id, PDO::PARAM_INT);
    $stmt->bindValue(':since', $since_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
  } else {
    $stmt = $pdo->prepare("SELECT m.*, u.name AS user_name, u.avatar_path FROM messages m LEFT JOIN users u ON u.id = m.user_id WHERE m.room_id = :room ORDER BY m.message_id DESC LIMIT :limit");
    $stmt->bindValue(':room', $room_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rows = array_reverse($rows); // return ascending
    echo json_encode(['success'=>true,'messages'=>$rows]); exit;
  }

  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['success'=>true,'messages'=>$rows]);
  exit;
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
  exit;
}
