<?php
// public/api/edit_message.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/db.php';

if (empty($_SESSION['user_id'])) {
  echo json_encode(['success'=>false,'error'=>'Authentication required']);
  exit;
}
$user = (int)$_SESSION['user_id'];
$message_id = (int)($_POST['message_id'] ?? 0);
$new_text = trim($_POST['message'] ?? '');

if ($message_id <= 0 || $new_text === '') {
  echo json_encode(['success'=>false,'error'=>'Invalid params']);
  exit;
}

try {
  // verify owner
  $v = $pdo->prepare("SELECT user_id FROM messages WHERE message_id = :id LIMIT 1");
  $v->execute([':id'=>$message_id]);
  $owner = $v->fetchColumn();
  if (!$owner || (int)$owner !== $user) {
    echo json_encode(['success'=>false,'error'=>'Not authorized to edit']);
    exit;
  }

  $u = $pdo->prepare("UPDATE messages SET message = :m, is_edited = 1, updated_at = CURRENT_TIMESTAMP WHERE message_id = :id");
  $u->execute([':m'=>$new_text, ':id'=>$message_id]);
  echo json_encode(['success'=>true]);
  exit;
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
  exit;
}
