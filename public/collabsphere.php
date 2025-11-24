<?php
// public/collabsphere.php
// Public landing for CollabSphere (public header)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../includes/header_dashboard.php';
?>
<!-- make page use dashboard background/video styling -->
<script>document.body.classList.add('dashboard-page');</script>

<link rel="stylesheet" href="assets/css/collab.css?v=2" />
<div class="collab-viewport">
  <div class="collab-hero">
    <div class="collab-card">
      <div class="collab-card-head">
        <!-- fallback uses uploaded local image; recommended: copy this file into assets/images/ for production -->
        <img src="/mnt/data/affbdf41-2655-47ad-8304-5e3c61138048.png" alt="ÉtudeSync CollabSphere" class="collab-logo" loading="lazy" />
        <h1>CollabSphere</h1>
        <p class="lead">Create rooms, study together, share files, draw on a collaborative whiteboard and run quick polls — all with ÉtudeSync style.</p>
      </div>

      <div class="collab-actions-grid">
        <a class="action-card" href="create_room.php" title="Create a Room">
          <div class="action-icon"><img src="assets/images/whiteboard-icon.png" alt="Create" loading="lazy" /></div>
          <div class="action-title">Create a Room</div>
          <div class="action-sub">Start a new private or scheduled room</div>
        </a>

        <a class="action-card" href="join_room.php" title="Join a Room">
          <div class="action-icon"><img src="assets/images/chat-icon.png" alt="Join" loading="lazy" /></div>
          <div class="action-title">Join a Room</div>
          <div class="action-sub">Enter room code to join instantly</div>
        </a>

        <a class="action-card" href="room_history.php" title="History">
          <div class="action-icon"><img src="assets/images/participants-icon.png" alt="History" loading="lazy" /></div>
          <div class="action-title">Room History</div>
          <div class="action-sub">View rooms you created or joined earlier</div>
        </a>
      </div>

      <div class="collab-footer">
        <small>CollabSphere • Built to match ÉtudeSync style</small>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/collab_main.js?v=1" defer></script>

<?php
require_once __DIR__ . '/../includes/footer.php';
