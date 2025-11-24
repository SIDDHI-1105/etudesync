<?php
// includes/header_dashboard.php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — ÉtudeSync' : 'ÉtudeSync' ?></title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Poppins:wght@500;700&display=swap" rel="stylesheet">

  <!-- GSAP (deferred) for dashboard micro-animations -->
  <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.0/gsap.min.js"></script>
  <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.0/ScrollTrigger.min.js"></script>

  <!-- Main CSS -->
  <link rel="stylesheet" href="assets/css/style.css?v=4" />
  <link rel="stylesheet" href="assets/css/animations.css?v=1" />
</head>

<body class="page-wrapper dashboard-page">

  <!-- Background (global slider left as-is for non-dashboard pages) -->
  <div id="bg-slider" class="bg-slider" aria-hidden="true"></div>
  <div class="bg-overlay" aria-hidden="true"></div>

  <header class="site-topbar container" role="banner">
    <div class="brand-left" role="presentation">
      <a href="index.php" class="brand-link" style="display:flex;align-items:center;gap:10px;text-decoration:none">
        <img src="assets/images/logo.jpg" alt="ÉtudeSync logo" class="brand-logo" />
        <span class="brand-name">ÉtudeSync</span>
      </a>
    </div>

    <!-- RIGHT CONTROLS: theme, notifications, profile -->
    <div class="header-controls" role="region" aria-label="Header controls">
      <!-- theme toggle -->
      <button id="headerThemeToggle" class="icon-btn header-icon" aria-label="Toggle theme" title="Toggle theme">🌓</button>

      <!-- notifications (can open notifications page/modal) -->
      <a href="notifications.php" class="icon-btn header-icon" aria-label="Notifications" title="Notifications">🔔</a>

      <!-- profile button (links to profile page where logout will be) -->
      <?php if (!empty($_SESSION['user_id'])): ?>
        <a href="profile.php" class="profile-pill header-icon" title="Profile" aria-label="Profile">
          <img src="assets/images/profile-placeholder.png" alt="Profile" class="profile-avatar" />
        </a>
      <?php else: ?>
        <a href="login.php" class="btn primary small">Login</a>
      <?php endif; ?>
    </div>
  </header>

  <main class="main-content page-content">
    <div class="container">

<script>
/* Small header JS: theme toggle persistence and keyboard accessible */
(function(){
  const THEME_KEY = 'etudestyle_pref';
  const toggle = document.getElementById('headerThemeToggle');

  function applyTheme(t) {
    if (t === 'light') {
      document.documentElement.classList.remove('dark-mode');
      document.documentElement.classList.add('light-mode');
      document.body.classList.remove('dark-mode');
      document.body.classList.add('light-mode');
    } else {
      document.documentElement.classList.remove('light-mode');
      document.documentElement.classList.add('dark-mode');
      document.body.classList.remove('light-mode');
      document.body.classList.add('dark-mode');
    }
  }

  const saved = localStorage.getItem(THEME_KEY) || 'dark';
  applyTheme(saved);

  if (toggle) {
    toggle.setAttribute('aria-pressed', saved === 'dark' ? 'true' : 'false');

    toggle.addEventListener('click', () => {
      const current = document.documentElement.classList.contains('dark-mode') ? 'dark' : 'light';
      const next = current === 'dark' ? 'light' : 'dark';
      applyTheme(next);
      localStorage.setItem(THEME_KEY, next);
      toggle.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');
    });

    toggle.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggle.click();
      }
    });
  }
})();
</script>
