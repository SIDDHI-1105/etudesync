<?php
// public/api/send_message.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/db.php';

if (empty($_SESSION['user_id'])) {
  echo json_encode(['success'=>false,'error'=>'Authentication required']);
  exit;
}

$user_id = (int)$_SESSION['user_id'];
$room_id = (int)($_POST['room_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($room_id <= 0 || $message === '') {
  echo json_encode(['success'=>false,'error'=>'Invalid parameters']);
  exit;
}

try {
  $stmt = $pdo->prepare("INSERT INTO messages (room_id, user_id, message) VALUES (:room, :user, :msg)");
  $stmt->execute([':room'=>$room_id, ':user'=>$user_id, ':msg'=>$message]);
  $id = (int)$pdo->lastInsertId();
  $row = $pdo->prepare("SELECT m.*, u.name AS user_name, u.avatar_path FROM messages m LEFT JOIN users u ON u.id = m.user_id WHERE m.message_id = :id LIMIT 1");
  $row->execute([':id'=>$id]);
  $msg = $row->fetch(PDO::FETCH_ASSOC);
  echo json_encode(['success'=>true,'message'=>$msg]);
  exit;
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>'DB error: '.$e->getMessage()]);
  exit;
}
