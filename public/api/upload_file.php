<?php
// public/api/upload_file.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');

// DB
require_once __DIR__ . '/../../includes/db.php';

if (empty($_SESSION['user_id'])) {
  echo json_encode(['success'=>false,'error'=>'Authentication required']);
  exit;
}

$room_id = (int)($_POST['room_id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

if ($room_id <= 0) {
  echo json_encode(['success'=>false,'error'=>'Invalid room_id']);
  exit;
}

// Basic checks
if (empty($_FILES['file'])) {
  echo json_encode(['success'=>false,'error'=>'No file uploaded']);
  exit;
}

$file = $_FILES['file'];

// check upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
  echo json_encode(['success'=>false,'error'=>'Upload error code: '.$file['error']]);
  exit;
}

// size limit (example 50MB)
$maxBytes = 50 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
  echo json_encode(['success'=>false,'error'=>'File too large (max 50MB)']);
  exit;
}

// allowed mime types (you can adjust)
$allowed = [
  'image/png','image/jpeg','image/webp','application/pdf','text/plain',
  'application/zip','application/octet-stream'
];

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

if (!in_array($mime, $allowed)) {
  // allow unknown but warn (optional)
  // echo json_encode(['success'=>false,'error'=>'Unsupported file type: '.$mime]); exit;
}

// ensure upload directory exists (path relative to project public/)
$uploadDir = __DIR__ . '/../assets/uploads/room_files/';
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0755, true);
}

// create safe unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: '';
$safeName = bin2hex(random_bytes(10)) . ($ext ? '.' . $ext : '');
$targetPath = $uploadDir . $safeName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
  echo json_encode(['success'=>false,'error'=>'Failed to move uploaded file.']);
  exit;
}

// public relative path to store in DB
$publicPath = 'assets/uploads/room_files/' . $safeName;

try {
  $stmt = $pdo->prepare("INSERT INTO files (room_id, user_id, orig_name, file_path, mime_type, size_bytes) VALUES (:room, :user, :name, :path, :mime, :size)");
  $stmt->execute([
    ':room' => $room_id,
    ':user' => $user_id,
    ':name' => $file['name'],
    ':path' => $publicPath,
    ':mime' => $mime,
    ':size' => (int)$file['size']
  ]);
  $file_id = (int)$pdo->lastInsertId();
  echo json_encode(['success'=>true,'file_id'=>$file_id,'file_path'=>$publicPath,'orig_name'=>$file['name']]);
  exit;
} catch (PDOException $e) {
  // clean up file
  if (file_exists($targetPath)) unlink($targetPath);
  echo json_encode(['success'=>false,'error'=>'DB error: '.$e->getMessage()]);
  exit;
}
