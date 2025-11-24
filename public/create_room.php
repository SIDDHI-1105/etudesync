<?php
// public/create_room.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// require login
if (empty($_SESSION['user_id'])) {
    $_SESSION['after_login_redirect'] = 'public/create_room.php';
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/header_dashboard.php';
// use your logged-in header
?>

<!-- ensure dashboard styles and topbar behavior apply -->
<script>document.body.classList.add('dashboard-page');</script>

<!-- Dashboard-style background (video + fallback image).
     Fallback image currently points to the uploaded dev file path.
     If you prefer a project-local copy, copy the file to public/assets/images/collab-bg.png
     and update the CSS to use that relative path instead. -->
<div class="dashboard-bg" aria-hidden="true" style="background-image: url('/mnt/data/affbdf41-2655-47ad-8304-5e3c61138048.png');">
  <video id="dashVideo" autoplay muted loop playsinline>
    <source src="assets/videos/desk1.mp4" type="video/mp4">
    <!-- If video is missing the CSS background-image above will show -->
  </video>
  <div class="dashboard-bg-overlay"></div>
</div>

<link rel="stylesheet" href="assets/css/collab.css?v=2" />

<div class="collab-viewport">
  <div class="collab-hero">
    <div class="collab-card" style="max-width:760px; margin:0 auto;">
      <div class="collab-card-head">
        <img src="assets/images/whiteboard-icon.png" alt="Create Room" class="collab-logo"/>
        <h1>Create a Room</h1>
        <p class="lead">Fill details and create a private study room. Share the room code to invite others.</p>
      </div>

      <form id="createRoomForm" class="create-room-form" autocomplete="off" method="post" action="api/create_room.php">
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
          <div style="flex:1;min-width:200px">
            <label for="title" style="display:block;font-weight:700;margin-bottom:6px">Room Title</label>
            <input id="title" name="title" required maxlength="200" placeholder="e.g. DBMS Quick Revision" style="width:100%;padding:12px;border-radius:10px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.02);color:#fff" />
          </div>

          <div style="flex:1;min-width:200px">
            <label for="topic" style="display:block;font-weight:700;margin-bottom:6px">Topic (optional)</label>
            <input id="topic" name="topic" maxlength="200" placeholder="e.g. ER Diagrams" style="width:100%;padding:12px;border-radius:10px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.02);color:#fff" />
          </div>
        </div>

        <div style="margin-top:12px;display:flex;gap:12px;flex-wrap:wrap;">
          <div style="flex:1;min-width:220px">
            <label for="scheduled_time" style="display:block;font-weight:700;margin-bottom:6px">Scheduled time (optional)</label>
            <input id="scheduled_time" name="scheduled_time" type="datetime-local" style="width:100%;padding:12px;border-radius:10px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.02);color:#fff" />
          </div>

          <div style="flex:0 0 160px;display:flex;flex-direction:column;justify-content:flex-end;">
            <button id="submitCreate" type="button" class="btn primary" style="padding:12px;border-radius:10px;margin-top:6px;">Create Room</button>
          </div>
        </div>

        <div id="crMsg" style="margin-top:14px;color:rgba(255,255,255,0.9);display:none;"></div>
      </form>

      <div style="margin-top:18px;color:rgba(255,255,255,0.7);font-size:0.95rem;">
        <strong>Tip:</strong> After creation you will get a room code. Share it with participants or send them the direct link.
      </div>
    </div>
  </div>
</div>

<!-- keep your JS but ensure create_room.js posts to api/create_room.php -->
<script src="assets/js/create_room.js?v=1"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
