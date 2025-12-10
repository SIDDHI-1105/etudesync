<?php
// public/room.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Dev helper while debugging — remove or set to 0 for production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';           // provides $pdo
require_once __DIR__ . '/../includes/header_dashboard.php'; // dashboard header

// require login
if (empty($_SESSION['user_id'])) {
    $_SESSION['after_login_redirect'] = 'public/room.php';
    header('Location: login.php');
    exit;
}

$uid = (int) $_SESSION['user_id'];

// validate inputs: need room_id and code (code optional)
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$room_code = isset($_GET['code']) ? trim($_GET['code']) : '';

if ($room_id <= 0) {
    echo '<div style="padding:24px">Invalid room. <a href="collabsphere.php">Back</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// load room
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = :id LIMIT 1");
$stmt->execute([':id' => $room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$room) {
    echo '<div style="padding:24px">Room not found. <a href="collabsphere.php">Back</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// optional: verify code if provided
if ($room_code !== '' && strcasecmp($room_code, $room['room_code']) !== 0) {
    echo '<div style="padding:24px">Invalid room code. <a href="collabsphere.php">Back</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// mark participant presence (update or insert)
try {
    $stmtP = $pdo->prepare("SELECT id FROM room_participants WHERE room_id = :room AND user_id = :user LIMIT 1");
    $stmtP->execute([':room' => $room_id, ':user' => $uid]);
    $exists = $stmtP->fetchColumn();
    if ($exists) {
        $stmtUp = $pdo->prepare("UPDATE room_participants SET joined_at = NOW() WHERE id = :id");
        $stmtUp->execute([':id' => $exists]);
    } else {
        $stmtIns = $pdo->prepare("INSERT INTO room_participants (room_id, user_id, role, joined_at) VALUES (:room, :user, 'participant', NOW())");
        $stmtIns->execute([':room' => $room_id, ':user' => $uid]);
    }
} catch (PDOException $e) {
    // ignore presence errors for now
}

// determine manage permission (simple check)
$me = $uid;
$canManage = false;
try {
    $stmt = $pdo->prepare("SELECT role FROM room_participants WHERE room_id = :r AND user_id = :u LIMIT 1");
    $stmt->execute([':r'=>$room_id, ':u'=>$me]);
    $myRole = $stmt->fetchColumn();
    $canManage = in_array($myRole, ['host','moderator']);
} catch (Exception $e) { /* ignore */ }

// thumbnail: prefer room-specific uploaded thumbnail, else fallback to uploaded file or project placeholder
$room_thumbnail = 'assets/images/collab-bg.jpg';
if (!empty($room['thumbnail'])) {
    $thumbPath = __DIR__ . '/../' . ltrim($room['thumbnail'], '/');
    if (file_exists($thumbPath)) {
        $room_thumbnail = $room['thumbnail'];
    }
} else {
    // dev fallback: prefer public asset
    if (file_exists(__DIR__ . '/../assets/images/collab-bg.jpg')) {
        $room_thumbnail = 'assets/images/collab-bg.jpg';
    } else {
        $room_thumbnail = 'assets/images/placeholder-room.png';
    }
}

// escape helper
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!-- ensure dashboard visuals load -->
<script>document.body.classList.add('dashboard-page');</script>

<link rel="stylesheet" href="assets/css/collab.css?v=2" />
<style>
/* small layout helpers (keep in external CSS if preferred) */
.room-layout { display:grid; grid-template-columns: 1fr 320px; gap:18px; align-items:start; }
@media (max-width: 980px) { .room-layout { grid-template-columns: 1fr; } }
.chat-panel { min-height: 520px; display:flex; flex-direction:column; gap:12px; }
.chat-messages { flex:1; overflow:auto; padding:12px; border-radius:12px; background: linear-gradient(180deg, rgba(255,255,255,0.01), rgba(255,255,255,0.02)); border:1px solid rgba(255,255,255,0.04); }
.glass-card { background: rgba(255,255,255,0.01); border:1px solid rgba(255,255,255,0.03); border-radius:12px; padding:12px; }
.small-muted { color:var(--muted); font-size:0.9rem; }
.room-panel { padding:12px; border-radius:10px; }
.chat-row { display:flex; gap:10px; align-items:flex-start; padding:8px; border-radius:8px; }
.chat-avatar { width:40px; height:40px; border-radius:8px; object-fit:cover; }
.chat-body { flex:1; }
.chat-meta { font-size:0.85rem; color:var(--muted); display:flex; gap:8px; align-items:center; }
.chat-text { margin-top:6px; white-space:pre-wrap; }
.chat-actions { display:flex; gap:8px; align-items:center; }
</style>

<!-- Background (single block) -->
<div class="dashboard-bg" aria-hidden="true">
  <video id="dashVideo" autoplay muted loop playsinline poster="assets/images/collab-bg.jpg">
    <source src="assets/videos/desk1.mp4" type="video/mp4">
  </video>
  <div class="dashboard-bg-overlay"></div>
</div>

<div class="collab-viewport" style="padding-top:18px;">
  <div class="collab-hero" style="align-items:flex-start; justify-content:center;">
    <div class="collab-card" style="width:100%; max-width:1200px; padding:20px 22px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <div style="display:flex;gap:12px;align-items:center">
          <img src="<?= e($room_thumbnail) ?>" alt="Room" style="width:64px;height:64px;border-radius:10px;object-fit:cover"/>
          <div>
            <div style="font-weight:800;font-size:1.1rem;"><?= e($room['title']) ?></div>
            <div class="small-muted"><?= e($room['topic'] ?: '—') ?> • Code: <strong><?= e($room['room_code']) ?></strong></div>
          </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
          <div class="small-muted">Room ID: <?= (int)$room['room_id'] ?></div>
          <button id="copyLinkBtn" class="btn small outline">Copy Link</button>
          <a class="btn small" href="collabsphere.php">Back</a>
        </div>
      </div>

      <div class="room-layout">
        <!-- LEFT (chat / whiteboard / polls) -->
        <div>
          <div class="room-panel glass-card" id="chatPanel" style="display:flex;flex-direction:column;gap:8px;padding:12px;">
            <div style="font-weight:800;display:flex;justify-content:space-between;align-items:center;">
              <div>Chat</div>
              <small style="color:var(--muted)">Realtime (polling)</small>
            </div>

            <div id="chatList" style="flex:1;overflow:auto;max-height:360px;padding:8px;display:flex;flex-direction:column;gap:8px;">
              <div style="color:var(--muted);text-align:center;padding-top:40px">No messages yet.</div>
            </div>

            <!-- CHAT FORM (fallback JS send to avoid page navigation) -->
            <form id="chatForm" onsubmit="return false;" style="display:flex;gap:8px;align-items:center;margin-top:8px;">
              <input id="chatInput" type="text" placeholder="Write a message..." style="flex:1;padding:10px;border-radius:10px;border:1px solid rgba(255,255,255,0.06);background:rgba(255,255,255,0.02);color:#fff" />
              <!-- button is type="button" so it never performs native submit -->
              <button id="chatSendBtn" type="button" class="btn primary small">Send</button>
            </form>
          </div>

          <div id="whiteboardArea" class="glass-card" style="margin-top:18px;padding:12px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:8px;">
              <div style="font-weight:800">Whiteboard</div>
              <div style="display:flex;gap:8px;align-items:center;">
                <label class="small-muted" style="font-size:0.9rem;margin-right:8px">Brush:</label>
                <input id="wbBrushSize" type="range" min="1" max="20" value="3" />
                <input id="wbColor" type="color" value="#ffffff" title="Brush color" style="width:42px;height:30px;border-radius:8px;border:none"/>
                <button id="wbUndo" class="btn small outline">Undo</button>
                <button id="wbClear" class="btn small outline">Clear</button>
                <button id="wbSave" class="btn small">Save</button>
                <button id="wbExport" class="btn small outline">Export PNG</button>
              </div>
            </div>

            <div style="position:relative;">
              <canvas id="wbCanvas" width="1200" height="600" style="width:100%;height:420px;border-radius:10px;background:transparent;touch-action:none;border:1px solid rgba(255,255,255,0.04)"></canvas>
            </div>

            <div id="wbStatus" style="margin-top:8px;color:var(--muted);font-size:0.9rem">Whiteboard ready. Changes autosaved every 10s when active.</div>
          </div>

          <div class="room-panel glass-card" id="pollPanel" style="margin-top:18px;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
              <h3 style="margin:0">Polls</h3>
              <div><?= $canManage ? '<small class="small-muted">You can manage polls</small>' : '' ?></div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;margin-top:10px;">
              <input id="pollQ" placeholder="Poll Question" style="flex:2" />
              <input id="pollOpt1" placeholder="Option 1" />
              <input id="pollOpt2" placeholder="Option 2" />
              <button id="createPollBtn" class="btn small">Create Poll</button>
            </div>
            <div id="pollArea" data-room="<?= (int)$room_id ?>" style="margin-top:12px;"></div>
          </div>
        </div>

        <!-- RIGHT (participants + files + tools) -->
        <aside>
          <div class="participants-box glass-card" style="padding:12px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
              <div style="font-weight:800">Participants</div>
              <div id="participantCount" class="small-muted">–</div>
            </div>
            <div id="participantsListBox" style="max-height:360px;overflow:auto;padding-right:6px;"></div>
          </div>

          <div style="height:14px"></div>

          <div class="room-panel glass-card" id="filesPanel">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
              <strong>Files</strong>
              <small style="color:var(--muted)">Upload & pin important files</small>
            </div>

            <form id="fileUploadForm" data-room="<?= (int)$room_id ?>" style="display:flex;gap:8px;align-items:center;">
              <input id="fileInput" type="file" />
              <button type="submit" class="btn primary small">Upload</button>
            </form>

            <div id="filesList" style="margin-top:12px;display:flex;flex-direction:column;gap:10px"></div>
          </div>

          <div style="height:14px"></div>

          <div class="glass-card" style="padding:12px;">
            <div style="font-weight:800;margin-bottom:8px">Room Tools</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
              <button id="togglePins" class="btn small outline">Show pinned</button>
              <a href="room_history.php?room_id=<?= (int)$room_id ?>" class="btn small">Room History</a>
              <a href="collabsphere.php" class="btn small outline">All Modules</a>
            </div>
          </div>
        </aside>

      </div> <!-- .room-layout -->
    </div> <!-- .collab-card -->
  </div>
</div>

<script>
const ROOM_ID = <?= (int)$room_id ?>;
const ROOM_CODE = <?= json_encode($room['room_code']) ?>;
const USER_ID = <?= (int)$uid ?>;
const CAN_MANAGE = <?= $canManage ? 'true' : 'false' ?>;
</script>

<!-- load core feature scripts (chat has fallback inline below) -->
<script src="assets/js/whiteboard.js?v=1" defer></script>
<script src="assets/js/chat.js?v=1" defer></script>
<script src="assets/js/files.js?v=1" defer></script>
<script src="assets/js/participants.js?v=1" defer></script>

<!-- Inline fallback send logic (works even if chat.js didn't bind or failed) -->
<script>
(function(){
  const chatInput = document.getElementById('chatInput');
  const sendBtn = document.getElementById('chatSendBtn');
  const chatList = document.getElementById('chatList');

  if (!sendBtn || !chatInput || !chatList) return;

  async function sendMessageFallback() {
    const text = chatInput.value.trim();
    if (!text) return;
    sendBtn.disabled = true;
    const prevText = sendBtn.textContent;
    sendBtn.textContent = 'Sending…';
    try {
      const fd = new FormData();
      fd.append('room_id', ROOM_ID);
      fd.append('message', text);
      const res = await fetch('api/send_message.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      if (!res.ok) {
        console.error('send_message responded', res.status);
        alert('Send failed (network).');
        return;
      }
      const j = await res.json();
      if (j.success && j.message) {
        const m = j.message;
        const row = document.createElement('div');
        row.className = 'chat-row' + (m.is_pinned ? ' pinned' : '');
        row.dataset.id = m.message_id;
        row.innerHTML = `
          <img class="chat-avatar" src="${m.avatar_path || 'assets/images/profile-placeholder.png'}" alt="${(m.user_name||'User')}">
          <div class="chat-body">
            <div class="chat-meta"><span class="chat-user">${(m.user_name||'User')}</span> <span class="chat-time">${new Date(m.created_at).toLocaleTimeString()}</span></div>
            <div class="chat-text">${(m.message || '')}</div>
          </div>
        `;
        chatList.appendChild(row);
        chatList.scrollTo({ top: chatList.scrollHeight, behavior: 'smooth' });
        chatInput.value = '';
      } else {
        alert(j.error || 'Send failed');
      }
    } catch (err) {
      console.error('Send failed', err);
      alert('Send failed — check console/network.');
    } finally {
      sendBtn.disabled = false;
      sendBtn.textContent = prevText;
    }
  }

  sendBtn.addEventListener('click', sendMessageFallback);
  chatInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessageFallback();
    }
  });
})();
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
