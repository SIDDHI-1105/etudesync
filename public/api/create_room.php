<?php
// public/api/create_room.php
// supports both JSON (AJAX) and normal form submit (redirect)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Correct path from public/api to project includes/
require_once __DIR__ . '/../../includes/db.php';

// helper to detect JSON/AJAX request
function wants_json() {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    if (stripos($accept, 'application/json') !== false) return true;
    if (strtolower($xhr) === 'xmlhttprequest') return true;
    return false;
}

function respond_json($payload, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function respond_error_or_redirect($message, $status = 400) {
    if (wants_json()) {
        respond_json(['success' => false, 'error' => $message], $status);
    } else {
        // store error for the create page and redirect back
        $_SESSION['error'] = $message;
        header('Location: ../create_room.php');
        exit;
    }
}

function respond_success_or_redirect($data) {
    if (wants_json()) {
        respond_json(array_merge(['success' => true], $data));
    } else {
        // redirect to room page (relative to public/api/)
        $code = $data['room_code'] ?? null;
        if ($code) {
            header('Location: ../room.php?code=' . urlencode($code));
            exit;
        } else {
            // fallback: redirect to dashboard
            header('Location: ../dashboard.php');
            exit;
        }
    }
}

// validate user
if (empty($_SESSION['user_id'])) {
    respond_error_or_redirect('Authentication required.', 401);
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
    // datetime-local is usually "YYYY-MM-DDTHH:MM", append seconds if missing
    $scheduled = str_replace('T', ' ', $scheduled_raw);
    if (!preg_match('/:\d{2}$/', $scheduled)) {
        $scheduled .= ':00';
    }
}

// basic validation
if ($title === '') {
    respond_error_or_redirect('Title is required.', 400);
}

// ensure $pdo is present
if (!isset($pdo) || !$pdo) {
    respond_error_or_redirect('Database connection missing.', 500);
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
        respond_error_or_redirect('Could not generate unique room code. Try again.', 500);
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
    $room_id = (int) $pdo->lastInsertId();

    // add host as participant
    $stmt2 = $pdo->prepare("INSERT INTO room_participants (room_id, user_id) VALUES (:room, :user)");
    $stmt2->execute([':room' => $room_id, ':user' => $host_user_id]);

    // success: either return JSON or redirect to room page
    respond_success_or_redirect([
        'room_id' => $room_id,
        'room_code' => $room_code
    ]);

} catch (PDOException $e) {
    // dev: helpful error; in production hide $e->getMessage()
    respond_error_or_redirect('Database error: ' . $e->getMessage(), 500);
}
