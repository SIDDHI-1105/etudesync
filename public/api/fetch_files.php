<?php
// public/api/fetch_files.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/db.php';

$room_id = (int)($_GET['room_id'] ?? 0);
if ($room_id <= 0) { echo json_encode(['success'=>false,'error'=>'Invalid room_id']); exit; }

try {
  $stmt = $pdo->prepare("SELECT f.*, u.name AS user_name FROM files f LEFT JOIN users u ON u.id = f.user_id WHERE f.room_id = :room ORDER BY f.is_pinned DESC, f.uploaded_at DESC");
  $stmt->execute([':room'=>$room_id]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // normalize
  foreach ($rows as &$r) {
    $r['size_readable'] = human_filesize((int)$r['size_bytes']);
  }

  echo json_encode(['success'=>true,'files'=>$rows]);
  exit;
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
  exit;
}

function human_filesize($bytes, $decimals = 2) {
  $sz = ['B','KB','MB','GB','TB'];
  $factor = (int)floor((strlen($bytes) - 1) / 3);
  if ($factor == 0) return $bytes . ' ' . $sz[$factor];
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $sz[$factor];
}
