<?php
// public/api/kick_participant.php
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

if (!$room_id || !$target_user) {
  echo json_encode(['success'=>false,'error'=>'Missing params']);
  exit;
}

// check actor role
try {
  $r = $pdo->prepare("SELECT role FROM room_participants WHERE room_id = :room AND user_id = :user LIMIT 1");
  $r->execute([':room'=>$room_id, ':user'=>$actor]);
  $actor_role = $r->fetchColumn();
  if (!$actor_role || !in_array($actor_role, ['host','moderator'])) {
    echo json_encode(['success'=>false,'error'=>'Not authorized']);
    exit;
  }

  // do not allow kicking host
  $t = $pdo->prepare("SELECT role FROM room_participants WHERE room_id = :room AND user_id = :user LIMIT 1");
  $t->execute([':room'=>$room_id, ':user'=>$target_user]);
  $target_role = $t->fetchColumn();
  if ($target_role === 'host') {
    echo json_encode(['success'=>false,'error'=>'Cannot kick host']);
    exit;
  }

  // delete participant row (kick)
  $d = $pdo->prepare("DELETE FROM room_participants WHERE room_id = :room AND user_id = :user");
  $d->execute([':room'=>$room_id, ':user'=>$target_user]);

  echo json_encode(['success'=>true]);
  exit;
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
  exit;
}
