<?php
// public/api/announce_presence.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/db.php';

if (empty($_SESSION['user_id'])) {
  echo json_encode(['success'=>false,'error'=>'Authentication required']);
  exit;
}

$user_id = (int)$_SESSION['user_id'];
$room_id = (int)($_POST['room_id'] ?? $_GET['room_id'] ?? 0);
$role = $_POST['role'] ?? 'participant';
if ($room_id <= 0) {
  echo json_encode(['success'=>false,'error'=>'Invalid room_id']);
  exit;
}

try {
  // upsert participant
  $sql = "INSERT INTO room_participants (room_id, user_id, role) VALUES (:room, :user, :role)
          ON DUPLICATE KEY UPDATE last_seen = CURRENT_TIMESTAMP, role = VALUES(role)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':room'=>$room_id, ':user'=>$user_id, ':role'=>$role]);

  echo json_encode(['success'=>true]);
  exit;
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
  exit;
}
