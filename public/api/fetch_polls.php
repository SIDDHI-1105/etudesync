<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';

$room_id = intval($_GET['room_id'] ?? 0);

// fetch polls
$stmt = $pdo->prepare("SELECT * FROM polls WHERE room_id = :room_id ORDER BY created_at DESC");
$stmt->execute([':room_id'=>$room_id]);
$polls = $stmt->fetchAll();

foreach ($polls as &$p) {
    $options = json_decode($p['options'], true);
    $poll_id = $p['poll_id'];

    $countStmt = $pdo->prepare("
        SELECT option_index, COUNT(*) as votes
        FROM poll_votes
        WHERE poll_id = :pid
        GROUP BY option_index
    ");
    $countStmt->execute([':pid'=>$poll_id]);
    $counts = $countStmt->fetchAll();

    $resultMap = [];
    foreach ($counts as $c) {
        $resultMap[$c['option_index']] = intval($c['votes']);
    }

    // match options with votes
    $final = [];
    foreach ($options as $i=>$opt) {
        $final[] = [
            'text' => $opt,
            'votes' => $resultMap[$i] ?? 0
        ];
    }

    $p['results'] = $final;
}

echo json_encode(['success'=>true, 'polls'=>$polls]);
