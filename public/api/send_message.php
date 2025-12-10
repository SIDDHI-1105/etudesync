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
  // optional: ensure room exists
  $r = $pdo->prepare("SELECT room_id FROM rooms WHERE room_id = :id LIMIT 1");
  $r->execute([':id' => $room_id]);
  if (!$r->fetchColumn()) {
    echo json_encode(['success'=>false,'error'=>'Room not found']);
    exit;
  }

  $stmt = $pdo->prepare("INSERT INTO messages (room_id, user_id, message, created_at) VALUES (:room, :user, :msg, CURRENT_TIMESTAMP)");
  $stmt->execute([':room'=>$room_id, ':user'=>$user_id, ':msg'=>$message]);
  $id = (int)$pdo->lastInsertId();

  // fetch the inserted message row (no join)
  $mstmt = $pdo->prepare("SELECT * FROM messages WHERE message_id = :id LIMIT 1");
  $mstmt->execute([':id' => $id]);
  $rmsg = $mstmt->fetch(PDO::FETCH_ASSOC);

  // fetch user row safely (if exists) and build friendly fields
  $userName = '';
  $avatar = '';
  if ($rmsg && isset($rmsg['user_id']) && (int)$rmsg['user_id'] > 0) {
    $g = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $g->execute([':id' => (int)$rmsg['user_id']]);
    $u = $g->fetch(PDO::FETCH_ASSOC);
    if ($u) {
      foreach (['name','username','display_name','full_name','email'] as $c) {
        if (isset($u[$c]) && trim((string)$u[$c]) !== '') { $userName = (string)$u[$c]; break; }
      }
      foreach (['avatar_path','avatar','profile_image','image'] as $c) {
        if (isset($u[$c]) && trim((string)$u[$c]) !== '') { $avatar = (string)$u[$c]; break; }
      }
    }
  }

  $out = [
    'message_id' => isset($rmsg['message_id']) ? (int)$rmsg['message_id'] : null,
    'room_id' => isset($rmsg['room_id']) ? (int)$rmsg['room_id'] : null,
    'user_id' => isset($rmsg['user_id']) ? (int)$rmsg['user_id'] : null,
    'message' => isset($rmsg['message']) ? (string)$rmsg['message'] : '',
    'is_edited' => isset($rmsg['is_edited']) ? (int)$rmsg['is_edited'] : 0,
    'is_pinned' => isset($rmsg['is_pinned']) ? (int)$rmsg['is_pinned'] : 0,
    'created_at' => isset($rmsg['created_at']) ? $rmsg['created_at'] : null,
    'user_name' => $userName,
    'avatar_path' => $avatar
  ];

  echo json_encode(['success'=>true,'message'=>$out]);
  exit;
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>'DB error: '.$e->getMessage()]);
  exit;
}
