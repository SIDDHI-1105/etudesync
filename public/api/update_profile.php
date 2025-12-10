<?php
// public/api/update_profile.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/db.php';

if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'error'=>'Authentication required']); exit; }
$uid = (int) $_SESSION['user_id'];

$payload = json_decode(file_get_contents('php://input'), true);
$name = trim($payload['name'] ?? '');
$bio  = trim($payload['bio'] ?? '');

if ($name === '') { echo json_encode(['success'=>false,'error'=>'Name required']); exit; }

try {
  $stmt = $pdo->prepare("UPDATE users SET name = :name, bio = :bio WHERE id = :id");
  $stmt->execute([':name'=>$name,':bio'=>$bio?:null,':id'=>$uid]);
  // also update session name so UI updates immediately
  $_SESSION['user_name'] = $name;
  echo json_encode(['success'=>true]);
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>'DB error: '.$e->getMessage()]);
}
