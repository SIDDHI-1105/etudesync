<?php
// public/api/create_room.php
// returns JSON, uses PDO ($pdo) from includes/db.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');

// Correct path from public/api to project includes/
require_once __DIR__ . '/../../includes/db.php';

// validate user
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

$host_user_id = (int) $_SESSION['user_id'];

// helper to generate room code
function gen_room_code($len = 6) {
    $pool = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // avoid confusing chars
    $c = '';
    for ($i = 0; $i < $len; $i++) {
        $c .= $pool[random_int(0, strlen($pool) - 1)];
    }
    return $c;
}

// read POST
$title = trim($_POST['title'] ?? '');
$topic = trim($_POST['topic'] ?? '');
$scheduled = null;
if (!empty($_POST['scheduled_time'])) {
    $scheduled_raw = trim($_POST['scheduled_time']);
    // datetime-local is usually "YYYY-MM-DDTHH:MM", append seconds
    $scheduled = str_replace('T', ' ', $scheduled_raw);
    if (!preg_match('/:\d{2}$/', $scheduled)) {
        $scheduled .= ':00';
    }
}

// basic validation
if ($title === '') {
    echo json_encode(['success' => false, 'error' => 'Title is required.']);
    exit;
}

// ensure $pdo is present
if (!isset($pdo) || !$pdo) {
    echo json_encode(['success' => false, 'error' => 'Database connection missing.']);
    exit;
}

try {
    // generate unique room_code (few retries)
    $tries = 0;
    $exists = false;
    do {
        $room_code = gen_room_code(6);
        $stmt = $pdo->prepare("SELECT room_id FROM rooms WHERE room_code = :code LIMIT 1");
        $stmt->execute([':code' => $room_code]);
        $exists = (bool) $stmt->fetchColumn();
        $tries++;
    } while ($exists && $tries < 8);

    if ($exists) {
        echo json_encode(['success' => false, 'error' => 'Could not generate unique room code. Try again.']);
        exit;
    }

    // insert room
    $sql = "INSERT INTO rooms (title, topic, room_code, scheduled_time, host_user_id) VALUES (:title, :topic, :code, :scheduled, :host)";
    $stmt = $pdo->prepare($sql);

    // bind params (scheduled can be null)
    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':topic', $topic !== '' ? $topic : null, PDO::PARAM_STR);
    $stmt->bindValue(':code', $room_code, PDO::PARAM_STR);

    if ($scheduled === null) {
        $stmt->bindValue(':scheduled', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':scheduled', $scheduled, PDO::PARAM_STR);
    }
    $stmt->bindValue(':host', $host_user_id, PDO::PARAM_INT);

    $stmt->execute();
    $room_id = (int)$pdo->lastInsertId();

    // add host as participant
    $stmt2 = $pdo->prepare("INSERT INTO room_participants (room_id, user_id) VALUES (:room, :user)");
    $stmt2->execute([':room' => $room_id, ':user' => $host_user_id]);

    echo json_encode([
        'success' => true,
        'room_id' => $room_id,
        'room_code' => $room_code
    ]);
    exit;

} catch (PDOException $e) {
    // dev: helpful error; in production hide $e->getMessage()
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
