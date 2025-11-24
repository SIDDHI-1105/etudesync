<?php
// public/dashboard.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// protect page
if (empty($_SESSION['user_id'])) {
    // store current URI so user returns here after login
    $_SESSION['after_login_redirect'] = $_SERVER['REQUEST_URI'] ?? 'dashboard.php';
    $_SESSION['error'] = 'Please sign in to access the dashboard.';
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/header_dashboard.php';

$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Guest');
?>

<!-- mark body so header/global slider can be hidden by CSS -->
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.body.classList.add('dashboard-page');
});
</script>

<!-- Local single video background (only one video will play) -->
<div class="dashboard-bg">
  <video id="dashVideo" autoplay muted loop playsinline>
    <source src="assets/videos/desk1.mp4" type="video/mp4">
    <!-- fallback static image -->
  </video>
  <div class="dashboard-bg-overlay"></div>
</div>

<div class="dashboard-content container">
  <div class="dashboard-glass">

    <h2 class="dash-title">Good to see you, <span class="dash-user"><?= $userName ?></span></h2>
    <p id="dash-quote" class="dash-tagline">A neat study desk in your browser — focus without distractions.</p>

    <!-- modules grid: 3 columns on top, 3 columns on bottom (2 rows) -->
    <div class="dash-modules-grid">
      <!-- UPDATED: link now points to collabsphere.php -->
      <a href="collabsphere.php" class="module-card">
        <img src="assets/images/icon-collabsphere.png" alt="CollabSphere" class="module-icon" />
        <div class="module-name">CollabSphere</div>
      </a>

      <a href="infovault.php" class="module-card">
        <img src="assets/images/icon-infovault.png" alt="InfoVault" class="module-icon" />
        <div class="module-name">InfoVault</div>
      </a>

      <a href="focusflow.php" class="module-card">
        <img src="assets/images/icon-focusflow.png" alt="FocusFlow" class="module-icon" />
        <div class="module-name">FocusFlow</div>
      </a>

      <a href="assessarena.php" class="module-card">
        <img src="assets/images/icon-assessarena.png" alt="AssessArena" class="module-icon" />
        <div class="module-name">AssessArena</div>
      </a>

      <a href="#" class="module-card locked">
        <img src="assets/images/icon-mindspace.png" alt="MindSpace" class="module-icon" />
        <div class="module-name">MindSpace</div>
        <span class="lock-badge">🔒 Premium</span>
      </a>

      <a href="#" class="module-card locked">
        <img src="assets/images/icon-socialhub.png" alt="SocialHub" class="module-icon" />
        <div class="module-name">SocialHub</div>
        <span class="lock-badge">🔒 Premium</span>
      </a>
    </div>

  </div>
</div>

<script>
(function(){
  // rotate quotes every 6 seconds
  const quotes = [
    "A neat study desk in your browser — focus without distractions.",
    "Create short study rooms and stay focused with friends.",
    "Upload notes, create flashcards and revise smarter.",
    "Pomodoro + planner = better study flow.",
    "Quizzes, leaderboards and progress — see how you improve."
  ];
  let qIdx = 0;
  const qEl = document.getElementById('dash-quote');
  if (qEl) {
    setInterval(() => {
      qIdx = (qIdx + 1) % quotes.length;
      qEl.style.opacity = 0;
      setTimeout(() => {
        qEl.textContent = quotes[qIdx];
        qEl.style.opacity = 1;
      }, 300);
    }, 6000); // 6000 ms = 6 seconds
  }

  // locked modules: show upgrade toast / modal (simple)
  // NOTE: only intercept clicks for locked modules now (previous code prevented ALL links)
  document.querySelectorAll('.module-card.locked').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      // small non-blocking toast
      const t = document.createElement('div');
      t.className = 'upgrade-toast';
      t.textContent = 'This feature is premium. Upgrade to access.';
      document.body.appendChild(t);
      setTimeout(()=> t.classList.add('visible'), 20);
      setTimeout(()=> { t.classList.remove('visible'); setTimeout(()=> t.remove(),350); }, 2200);
    });
  });

})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!--
  Notes:
  - If collabsphere.php is inside 'public/' and dashboard.php is also in 'public/', href="collabsphere.php" is correct.
  - If your project uses a different webroot or subfolder (e.g. /etudesync/public/), consider using an absolute path like:
      href="/etudesync/public/collabsphere.php"
  - Uploaded image you used earlier is available (for reference) at:
      /mnt/data/1f5368f1-3156-47a6-8117-295468d61947.png
    You can move that file into public/assets/images/ and reference it as:
      assets/images/your-file.png
-->
