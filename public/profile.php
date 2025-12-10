<?php
// public/profile.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login('login.php');

$userId = (int) ($_SESSION['user_id'] ?? 0);

// compute web base (so links work when app lives in a subfolder)
$webBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); // e.g. "/etudesync/public"

// fetch user
$stmt = $pdo->prepare("SELECT id, username, email, profile_photo, avatar, bio, phone, age FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    $_SESSION['error'] = 'User not found.';
    header('Location: dashboard.php');
    exit;
}

// helpers
function normalize_path_rel($p) {
    if (!$p) return null;
    $p = trim($p);
    $p = preg_replace('#/+#','/',$p);
    return ltrim($p, '/');
}

$displayRel = normalize_path_rel($user['avatar'] ?? $user['profile_photo'] ?? null);
$uploadDirFs  = __DIR__ . '/assets/uploads/profile/';
$uploadDirRel = 'assets/uploads/profile/';
$uploadError = $uploadSuccess = null;
$updateError = $updateSuccess = null;

// handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo']) && empty($_POST['action'])) {
    $f = $_FILES['profile_photo'];
    if ($f['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed, true)) {
            $uploadError = 'Allowed types: jpg, png, webp.';
        } else {
            if (!is_dir($uploadDirFs) && !mkdir($uploadDirFs, 0775, true)) {
                $uploadError = 'Failed to create upload directory. Check permissions.';
            } else {
                $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
                $target   = $uploadDirFs . $filename;
                if (move_uploaded_file($f['tmp_name'], $target)) {
                    $relative = $uploadDirRel . $filename;
                    $upd = $pdo->prepare("UPDATE users SET avatar = :a WHERE id = :id");
                    $upd->execute([':a' => $relative, ':id' => $userId]);
                    $_SESSION['user_avatar'] = $relative;
                    header('Location: profile.php');
                    exit;
                } else {
                    $uploadError = 'Failed to move file. Check folder permissions.';
                }
            }
        }
    } else {
        $uploadError = 'Upload error code: ' . $f['error'];
    }
}

// handle meta update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_meta') {
    $bio   = trim((string)($_POST['bio'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $age   = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;

    if (strlen($bio) > 512) {
        $updateError = 'Bio too long (max 512 chars).';
    } else {
        $upd = $pdo->prepare("UPDATE users SET bio = :bio, phone = :phone, age = :age WHERE id = :id");
        $upd->execute([':bio' => $bio, ':phone' => $phone, ':age' => $age, ':id' => $userId]);
        $updateSuccess = 'Profile updated.';
        header('Location: profile.php');
        exit;
    }
}

// compute image url
$finalImgPath = null;
if ($displayRel) {
    $fsCheck = __DIR__ . '/' . ltrim($displayRel, '/');
    if (file_exists($fsCheck)) {
        $finalImgPath = $webBase . '/' . ltrim($displayRel, '/');
    } else {
        $finalImgPath = $webBase . '/' . ltrim($displayRel, '/');
    }
}
$imgSrc = $finalImgPath ?: ($webBase . '/assets/images/avatar-default.png');

$page_title = 'Profile';
$body_class = 'page-wrapper dashboard-page';
require_once __DIR__ . '/../includes/header_dashboard.php';
?>

<!-- content goes inside header_dashboard main/container -->
<div class="profile-page">
  <section class="profile-card glass-card">
    <h1 style="margin-top:6px;margin-bottom:16px;">Your Profile</h1>

    <div class="profile-info">
      <div class="profile-photo">
        <img id="currentAvatar" src="<?= htmlspecialchars($imgSrc) ?>" alt="avatar">
      </div>

      <div style="flex:1;min-width:260px;">
        <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? '') ?></p>
        <p><strong>Age:</strong> <?= htmlspecialchars($user['age'] ?? '') ?></p>
        <p style="margin-top:10px;">
          <strong>Bio:</strong><br>
          <?= nl2br(htmlspecialchars($user['bio'] ?? '')) ?>
        </p>
      </div>
    </div>

    <?php if ($uploadError): ?>
      <div class="form-error"><?= htmlspecialchars($uploadError) ?></div>
    <?php endif; ?>
    <?php if ($uploadSuccess): ?>
      <div class="form-ok"><?= htmlspecialchars($uploadSuccess) ?></div>
    <?php endif; ?>
    <?php if ($updateError): ?>
      <div class="form-error"><?= htmlspecialchars($updateError) ?></div>
    <?php endif; ?>
    <?php if ($updateSuccess): ?>
      <div class="form-ok"><?= htmlspecialchars($updateSuccess) ?></div>
    <?php endif; ?>

    <hr style="margin:18px 0;border-color:rgba(255,255,255,0.08);">

    <!-- Upload form -->
    <form method="post" enctype="multipart/form-data" class="profile-form">
      <div class="profile-field">
        <label for="profile_photo">Choose profile photo</label>
        <input type="file" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png,.webp">
      </div>
      <div class="profile-actions">
        <button type="submit" class="btn primary">Upload</button>
      </div>
    </form>

    <!-- Meta form -->
    <form method="post" class="profile-form" style="margin-top:12px;">
      <input type="hidden" name="action" value="update_meta">

      <div class="profile-field">
        <label for="phone">Phone</label>
        <input type="text" name="phone" id="phone"
               value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
      </div>

      <div class="profile-field">
        <label for="age">Age</label>
        <input type="number" name="age" id="age"
               value="<?= htmlspecialchars($user['age'] ?? '') ?>">
      </div>

      <div class="profile-field">
        <label for="bio">Bio</label>
        <textarea name="bio" id="bio" rows="4"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
      </div>

      <div class="profile-actions">
        <button type="submit" class="btn primary">Save changes</button>
      </div>
    </form>

    <div class="profile-actions profile-logout">
      <a href="logout.php" class="btn logout">Logout</a>
    </div>
  </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
// local preview in the card
document.getElementById('profile_photo')?.addEventListener('change', function(e){
  const f = this.files && this.files[0];
  if (!f) return;
  const reader = new FileReader();
  reader.onload = function(ev){
    const img = document.getElementById('currentAvatar');
    if (img) img.src = ev.target.result;
  };
  reader.readAsDataURL(f);
});
</script>

<script>
// sync chosen avatar to header icon as well
document.addEventListener('DOMContentLoaded', function () {
  const fileInput = document.querySelector('input[type="file"]');
  if (!fileInput) return;

  const headerAvatar  = document.querySelector('header.site-topbar .header-profile .profile-avatar');
  const profilePreview = document.querySelector('.profile-photo img');

  let currentObjectUrl = null;
  function setPreviewSrc(imgEl, src) {
    if (!imgEl) return;
    if (currentObjectUrl) {
      try { URL.revokeObjectURL(currentObjectUrl); } catch(e) {}
      currentObjectUrl = null;
    }
    imgEl.src = src;
  }

  fileInput.addEventListener('change', function () {
    const f = fileInput.files && fileInput.files[0];
    if (!f || !f.type.startsWith('image/')) return;

    const blobUrl = URL.createObjectURL(f);
    currentObjectUrl = blobUrl;

    if (headerAvatar)  setPreviewSrc(headerAvatar, blobUrl);
    if (profilePreview) setPreviewSrc(profilePreview, blobUrl);
  }, false);
});
</script>
