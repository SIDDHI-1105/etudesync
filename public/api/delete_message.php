<?php
// public/api/delete_message.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/db.php';

if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'error'=>'Auth']); exit; }
$actor = (int)$_SESSION['user_id'];
$message_id = (int)($_POST['message_id'] ?? 0);

if ($message_id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid']); exit; }

try {
  $m = $pdo->prepare("SELECT user_id, room_id FROM messages WHERE message_id = :id LIMIT 1");
  $m->execute([':id'=>$message_id]);
  $row = $m->fetch(PDO::FETCH_ASSOC);
  if (!$row) { echo json_encode(['success'=>false,'error'=>'Not found']); exit; }

  $isOwner = ((int)$row['user_id'] === $actor);
  $room_id = (int)$row['room_id'];
  $r = $pdo->prepare("SELECT role FROM room_participants WHERE room_id = :room AND user_id = :user LIMIT 1");
  $r->execute([':room'=>$room_id, ':user'=>$actor]);
  $role = $r->fetchColumn();
  $can = $isOwner || in_array($role, ['host','moderator']);

  if (!$can) { echo json_encode(['success'=>false,'error'=>'Not authorized']); exit; }

  $d = $pdo->prepare("DELETE FROM messages WHERE message_id = :id");
  $d->execute([':id'=>$message_id]);
  echo json_encode(['success'=>true]);
  exit;
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
  exit;
}
