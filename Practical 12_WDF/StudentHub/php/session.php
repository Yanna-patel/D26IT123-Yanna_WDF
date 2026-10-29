
<?php
// Practical 10: Session configuration and timeout

ini_set('session.use_strict_mode', '1');

$isHttps = !empty($_SERVER['HTTPS'])
    && $_SERVER['HTTPS'] !== 'off';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Session expires after 15 minutes of inactivity.
$timeoutSeconds = 15 * 60;

if (
    isset($_SESSION['user_id'], $_SESSION['last_activity'])
    && time() - $_SESSION['last_activity'] > $timeoutSeconds
) {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax'
            ]
        );
    }

    session_destroy();
    session_start();

    $_SESSION['session_expired'] = true;
}

// Refresh the activity timestamp for logged-in users.
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
}
?>
