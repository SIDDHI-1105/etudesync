<?php
// includes/auth.php
// Session helpers and common functions (drop-in replacement)

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Is the current request an XHR / AJAX request?
 *
 * @return bool
 */
function is_ajax(): bool {
    return (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    );
}

/**
 * Check whether user is logged in.
 *
 * @return bool
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Require that user is logged in. If not:
 *  - For AJAX/XHR: return 401 JSON and exit.
 *  - For normal requests: save redirect target and redirect to login page.
 *
 * @param string|null $loginPath Optional path to login (default '/login.php')
 * @return void
 */
function require_login(string $loginPath = '/login.php'): void {
    if (!is_logged_in()) {
        if (is_ajax()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'unauthenticated']);
            exit;
        }
        // Save where to return after login and redirect to login page
        $_SESSION['after_login_redirect'] = $_SERVER['REQUEST_URI'] ?? '/';
        // Use absolute path to avoid relative include issues; adjust if your app is in a subfolder
        header('Location: ' . $loginPath);
        exit;
    }
}

/**
 * Return current user info from session, or null if not logged in.
 *
 * @return array|null ['id'=>int, 'username'=>string|null, 'is_premium'=>int]
 */
function current_user(): ?array {
    if (!is_logged_in()) return null;
    return [
        'id' => isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
        'username' => $_SESSION['user_name'] ?? null,
        'is_premium' => isset($_SESSION['is_premium']) ? (int)$_SESSION['is_premium'] : 0,
    ];
}

/**
 * Log out current user (destroy session data).
 *
 * @return void
 */
function logout(): void {
    // Unset all session values
    $_SESSION = [];

    // Delete session cookie if present
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'] ?? '/', $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true
        );
    }

    // Destroy PHP session
    session_destroy();
}
