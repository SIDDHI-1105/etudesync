<?php
// logout.php
session_start();

// clear session data
$_SESSION = [];

// delete session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// destroy session
session_destroy();

// optional flash message (you can show on login)
session_start();
$_SESSION['success'] = 'You have been logged out.';
header('Location: login.php');
exit;
