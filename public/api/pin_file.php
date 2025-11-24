<?php
// public/api/pin_file.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/db.php';

if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'error'=>'Auth required']); exit; }

$file_id = (int)($_POST['file_id'] ?? 0);
$pin = isset($_POST['pin']) ? (int)$_POST['pin'] : 1;
if ($file_id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid file_id']); exit; }

try {
  // simple permission: either file owner or host/moderator can pin
  $stmt = $pdo->prepare("SELECT user_id, room_id FROM files WHERE file_id = :id LIMIT 1");
  $stmt->execute([':id'=>$file_id]);
  $f = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$f) { echo json_encode(['success'=>false,'error'=>'Not found']); exit; }

  $actor = (int)$_SESSION['user_id'];
  $isOwner = $actor === (int)$f['user_id'];
  $r = $pdo->prepare("SELECT role FROM room_participants WHERE room_id = :room AND user_id = :user LIMIT 1");
  $r->execute([':room'=>$f['room_id'], ':user'=>$actor]);
  $role = $r->fetchColumn();
  $can = $isOwner || in_array($role, ['host','moderator']);

  if (!$can) { echo json_encode(['success'=>false,'error'=>'Not authorized']); exit; }

  $u = $pdo->prepare("UPDATE files SET is_pinned = :pin WHERE file_id = :id");
  $u->execute([':pin'=>$pin, ':id'=>$file_id]);
  echo json_encode(['success'=>true]);
  exit;
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
  exit;
}
