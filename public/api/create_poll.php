<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Not logged in']); exit;
}

$user_id = $_SESSION['user_id'];
$room_id = intval($_POST['room_id'] ?? 0);
$question = trim($_POST['question'] ?? '');
$options = $_POST['options'] ?? [];

if ($question === '' || count($options) < 2) {
    echo json_encode(['success'=>false,'error'=>'Invalid poll']); exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO polls (room_id, user_id, question, options)
        VALUES (:room_id, :user_id, :question, :options)
    ");
    $stmt->execute([
        ':room_id'=>$room_id,
        ':user_id'=>$user_id,
        ':question'=>$question,
        ':options'=>json_encode($options)
    ]);

    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
