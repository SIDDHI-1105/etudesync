<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'error'=>'Not logged in']); exit;
}

$user_id = $_SESSION['user_id'];
$poll_id = intval($_POST['poll_id'] ?? 0);
$option_index = intval($_POST['option_index'] ?? -1);

try {
    $stmt = $pdo->prepare("
        INSERT INTO poll_votes (poll_id, user_id, option_index)
        VALUES (:poll_id, :user_id, :option_index)
        ON DUPLICATE KEY UPDATE option_index = :option_index
    ");
    $stmt->execute([
        ':poll_id'=>$poll_id,
        ':user_id'=>$user_id,
        ':option_index'=>$option_index
    ]);

    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
