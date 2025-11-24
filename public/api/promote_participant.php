<?php
// public/api/promote_participant.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/db.php';

if (empty($_SESSION['user_id'])) {
  echo json_encode(['success'=>false,'error'=>'Authentication required']);
  exit;
}

$actor = (int)$_SESSION['user_id'];
$room_id = (int)($_POST['room_id'] ?? 0);
$target_user = (int)($_POST['user_id'] ?? 0);
$new_role = $_POST['role'] ?? '';

if (!$room_id || !$target_user || !in_array($new_role, ['participant','moderator','host'])) {
  echo json_encode(['success'=>false,'error'=>'Invalid params']);
  exit;
}

try {
  // only host can promote to host or moderator
  $r = $pdo->prepare("SELECT role FROM room_participants WHERE room_id = :room AND user_id = :user LIMIT 1");
  $r->execute([':room'=>$room_id, ':user'=>$actor]);
  $actor_role = $r->fetchColumn();
  if ($actor_role !== 'host') {
    echo json_encode(['success'=>false,'error'=>'Only host can change roles']);
    exit;
  }

  // prevent accidental self-demotion if desired; allow host -> host transfer
  $u = $pdo->prepare("UPDATE room_participants SET role = :role WHERE room_id = :room AND user_id = :user");
  $u->execute([':role'=>$new_role, ':room'=>$room_id, ':user'=>$target_user]);

  echo json_encode(['success'=>true]);
  exit;
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
  exit;
}
