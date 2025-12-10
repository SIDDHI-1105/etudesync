<?php
// public/api/upload_avatar.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/db.php';

if (empty($_SESSION['user_id'])) { echo json_encode(['success'=>false,'error'=>'Auth required']); exit; }
$uid = (int) $_SESSION['user_id'];

if (!isset($_FILES['avatar']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])) {
  echo json_encode(['success'=>false,'error'=>'No file uploaded']); exit;
}

$file = $_FILES['avatar'];
$maxBytes = 3 * 1024 * 1024; // 3MB
if ($file['size'] > $maxBytes) { echo json_encode(['success'=>false,'error'=>'File too large (max 3MB)']); exit; }

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
if (!isset($allowed[$mime])) { echo json_encode(['success'=>false,'error'=>'Only JPG/PNG/WEBP allowed']); exit; }

$ext = $allowed[$mime];
$dir = __DIR__ . '/../../public/assets/uploads/avatars/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$filename = 'avatar_u'.$uid.'_'.time().'.'.$ext;
$dest = $dir . $filename;
if (!move_uploaded_file($file['tmp_name'], $dest)) {
  echo json_encode(['success'=>false,'error'=>'Failed to move file']); exit;
}

// set web path
$webPath = 'assets/uploads/avatars/' . $filename;

// update DB
try {
  $stmt = $pdo->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
  $stmt->execute([':avatar'=>$webPath, ':id'=>$uid]);
  echo json_encode(['success'=>true, 'avatar'=>$webPath]);
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>'DB error: '.$e->getMessage()]);
}
