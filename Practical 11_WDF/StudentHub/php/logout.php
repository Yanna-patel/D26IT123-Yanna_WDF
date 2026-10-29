
<?php
// Practical 10: Secure logout

require_once __DIR__ . '/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.html');
    exit;
}

// Remove all session variables.
$_SESSION = [];

// Expire the browser's session cookie.
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

// Destroy the server-side session.
session_destroy();

header('Location: ../pages/login.html?error=logout');
exit;
?>
