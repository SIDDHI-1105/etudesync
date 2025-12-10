<?php
// public/api/fetch_messages.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/db.php';

$room_id = (int)($_GET['room_id'] ?? 0);
$since_id = (int)($_GET['since_id'] ?? 0);
$limit = min(200, max(10, (int)($_GET['limit'] ?? 100)));

if ($room_id <= 0) {
  echo json_encode(['success'=>false,'error'=>'Invalid room_id']);
  exit;
}

try {
  // fetch messages only from messages table (no join to users to avoid column name issues)
  if ($since_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE room_id = :room AND message_id > :since ORDER BY message_id ASC LIMIT :limit");
    $stmt->bindValue(':room', $room_id, PDO::PARAM_INT);
    $stmt->bindValue(':since', $since_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } else {
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE room_id = :room ORDER BY message_id DESC LIMIT :limit");
    $stmt->bindValue(':room', $room_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rows = array_reverse($rows); // return ascending oldest->newest
  }

  // Helper: resolve user display name + avatar from users row safely
  $userCache = []; // user_id => ['user_name'=>..., 'avatar_path'=>...]
  $getUser = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");

  $result = [];
  foreach ($rows as $r) {
    $uid = isset($r['user_id']) ? (int)$r['user_id'] : 0;

    if ($uid && !isset($userCache[$uid])) {
      $getUser->execute([':id' => $uid]);
      $u = $getUser->fetch(PDO::FETCH_ASSOC);
      // choose name-like column
      $name = '';
      if ($u && is_array($u)) {
        foreach (['name','username','display_name','full_name','email'] as $c) {
          if (isset($u[$c]) && trim((string)$u[$c]) !== '') { $name = (string)$u[$c]; break; }
        }
        // choose avatar-like column
        $avatar = '';
        foreach (['avatar_path','avatar','profile_image','image'] as $c) {
          if (isset($u[$c]) && trim((string)$u[$c]) !== '') { $avatar = (string)$u[$c]; break; }
        }
      } else {
        $u = null;
        $name = '';
        $avatar = '';
      }
      $userCache[$uid] = ['user_name' => $name, 'avatar_path' => $avatar, 'raw' => $u];
    }

    $userInfo = $uid ? ($userCache[$uid] ?? ['user_name'=>'','avatar_path'=>'']) : ['user_name'=>'','avatar_path'=>''];

    // normalise fields expected by the frontend
    $result[] = [
      'message_id' => isset($r['message_id']) ? (int)$r['message_id'] : null,
      'room_id' => isset($r['room_id']) ? (int)$r['room_id'] : null,
      'user_id' => $uid,
      'message' => isset($r['message']) ? (string)$r['message'] : '',
      'is_edited' => isset($r['is_edited']) ? (int)$r['is_edited'] : 0,
      'is_pinned' => isset($r['is_pinned']) ? (int)$r['is_pinned'] : 0,
      'created_at' => isset($r['created_at']) ? $r['created_at'] : null,
      'user_name' => $userInfo['user_name'],
      'avatar_path' => $userInfo['avatar_path'],
    ];
  }

  echo json_encode(['success'=>true,'messages'=>$result]);
  exit;
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
  exit;
}
