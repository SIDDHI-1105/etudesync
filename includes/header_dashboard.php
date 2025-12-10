<?php
// includes/header_dashboard.php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// compute web base so links work when app is under subfolder
$webBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); // e.g. "/etudesync/public"
// allow pages to set $body_class before including this header.
$body_class = $body_class ?? 'page-wrapper';
// allow pages to set $page_title (already supported)
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — ÉtudeSync' : 'ÉtudeSync' ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($webBase) ?>/assets/css/style.css?v=4" />
</head>
<body class="<?= htmlspecialchars($body_class) ?>">

  <!-- Dashboard video background (keeps same layering as your CSS) -->
  <div class="dashboard-bg" aria-hidden="true">
    <video autoplay muted loop playsinline>
      <source src="<?= htmlspecialchars($webBase) ?>/assets/videos/desk1.mp4" type="video/mp4">
      <!-- fallback image -->
    </video>
    <div class="dashboard-bg-overlay"></div>
  </div>

  <div id="bg-slider" class="bg-slider" aria-hidden="true"></div>
  <div class="bg-overlay" aria-hidden="true"></div>

  <header class="site-topbar" role="banner">
    <div class="container" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
      <div class="brand-left">
        <!-- Logo now links to dashboard -->
        <a href="<?= htmlspecialchars($webBase) ?>/dashboard.php" class="brand-link" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
          <img src="<?= htmlspecialchars($webBase) ?>/assets/images/logo.jpg" alt="ÉtudeSync logo" class="brand-logo" />
          <span class="brand-name">ÉtudeSync</span>
        </a>
      </div>

      <div class="header-controls" style="display:flex;align-items:center;gap:10px;">
        <!-- Music toggle button (play/pause/next behavior handled in footer JS) -->
        <button id="musicToggle" class="header-icon music-btn" title="Play soothing music" aria-pressed="false" aria-label="Play soothing music" type="button" style="display:flex;align-items:center;justify-content:center;padding:8px;border-radius:10px;border:none;cursor:pointer;">
          <svg id="musicIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M7 6v12l10-6L7 6z" fill="currentColor"></path>
          </svg>
        </button>

        <!-- Notifications -->
        <a href="<?= htmlspecialchars($webBase) ?>/notifications.php" class="header-icon" aria-label="Notifications" title="Notifications">🔔</a>

        <?php if (!empty($_SESSION['user_id'])):
            $sessAvatar = $_SESSION['user_avatar'] ?? 'assets/images/avatar-default.png';
            $imgUrl = rtrim($webBase, '/') . '/' . ltrim($sessAvatar, '/');
        ?>
          <a href="<?= htmlspecialchars($webBase) ?>/profile.php" class="header-icon header-profile" aria-label="Profile" style="padding:0;margin-left:8px;">
            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Profile" class="profile-avatar">
          </a>
        <?php else: ?>
          <a href="<?= htmlspecialchars($webBase) ?>/login.php" class="btn primary small">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="main-content page-content">
    <div class="container">
