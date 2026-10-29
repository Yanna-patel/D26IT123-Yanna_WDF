
<?php
// Practical 10: Authentication and role-based access control

require_once __DIR__ . '/session.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    $error = !empty($_SESSION['session_expired'])
        ? 'expired'
        : 'auth';

    header('Location: ../pages/login.html?error=' . $error);
    exit;
}

$allowedRoles = $allowedRoles ?? [];

if (!in_array($_SESSION['role'], $allowedRoles, true)) {
    // A logged-in user should be sent to their own dashboard.
    switch ($_SESSION['role']) {
        case 'Student':
            header('Location: student-dashboard.php');
            break;

        case 'Faculty':
            header('Location: faculty-dashboard.php');
            break;

        case 'Admin':
            header('Location: dashboard.php');
            break;

        default:
            header('Location: logout.php');
            break;
    }

    exit;
}
?>
