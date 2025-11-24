<?php
// public/api/delete_file.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/db.php';

if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'error'=>'Auth required']); exit; }

$file_id = (int)($_POST['file_id'] ?? 0);
if ($file_id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid file_id']); exit; }

try {
  $stmt = $pdo->prepare("SELECT user_id, file_path FROM files WHERE file_id = :id LIMIT 1");
  $stmt->execute([':id'=>$file_id]);
  $f = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$f) { echo json_encode(['success'=>false,'error'=>'Not found']); exit; }

  $actor = (int)$_SESSION['user_id'];
  $isOwner = $actor === (int)$f['user_id'];
  // host/moderator can also delete
  $roomIdStmt = $pdo->prepare("SELECT room_id FROM files WHERE file_id = :id");
  $roomIdStmt->execute([':id'=>$file_id]);
  $room_id = (int)$roomIdStmt->fetchColumn();
  $r = $pdo->prepare("SELECT role FROM room_participants WHERE room_id = :room AND user_id = :user LIMIT 1");
  $r->execute([':room'=>$room_id, ':user'=>$actor]);
  $role = $r->fetchColumn();
  $can = $isOwner || in_array($role, ['host','moderator']);

  if (!$can) { echo json_encode(['success'=>false,'error'=>'Not authorized']); exit; }

  // delete file on disk (be defensive)
  $filepath = __DIR__ . '/../' . $f['file_path'];
  if (file_exists($filepath)) {
    @unlink($filepath);
  }

  $del = $pdo->prepare("DELETE FROM files WHERE file_id = :id");
  $del->execute([':id'=>$file_id]);

  echo json_encode(['success'=>true]);
  exit;
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
  exit;
}
