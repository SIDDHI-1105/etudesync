<?php
// public/api/fetch_participants.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/db.php';

$room_id = (int)($_GET['room_id'] ?? 0);
if ($room_id <= 0) {
  echo json_encode(['success'=>false,'error'=>'Invalid room_id']);
  exit;
}

// define "active" threshold (in seconds)
$active_seconds = 45;

try {
  $stmt = $pdo->prepare("
    SELECT rp.user_id, rp.role, rp.joined_at, rp.last_seen, u.name AS user_name, u.email AS user_email, u.avatar_path
    FROM room_participants rp
    LEFT JOIN users u ON u.id = rp.user_id
    WHERE rp.room_id = :room
    ORDER BY rp.role DESC, rp.joined_at ASC
  ");
  $stmt->execute([':room'=>$room_id]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $now = time();
  $participants = array_map(function($r) use ($now, $active_seconds) {
    $last = strtotime($r['last_seen'] ?? '1970-01-01');
    $is_active = ($now - $last) <= $active_seconds;
    return [
      'user_id' => (int)$r['user_id'],
      'name' => $r['user_name'] ?? 'Unknown',
      'email' => $r['user_email'] ?? null,
      'avatar' => $r['avatar_path'] ?? null,
      'role' => $r['role'],
      'joined_at' => $r['joined_at'],
      'last_seen' => $r['last_seen'],
      'active' => $is_active
    ];
  }, $rows);

  echo json_encode(['success'=>true,'participants'=>$participants]);
  exit;
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
  exit;
}
