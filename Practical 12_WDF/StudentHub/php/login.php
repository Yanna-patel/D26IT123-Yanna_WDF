
<?php
// Practical 10: Secure login handler

require_once __DIR__ . '/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.html');
    exit;
}

require_once __DIR__ . '/db.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (
    !is_string($email) ||
    !filter_var(trim($email), FILTER_VALIDATE_EMAIL) ||
    strlen(trim($email)) > 254 ||
    !is_string($password) ||
    $password === ''
) {
    header('Location: ../pages/login.html?error=invalid');
    exit;
}

$email = trim($email);

$stmt = $conn->prepare(
    'SELECT fullname, email, role, password_hash
     FROM students
     WHERE email = ?
     LIMIT 1'
);

if (!$stmt) {
    error_log('Login query preparation failed: ' . $conn->error);
    $conn->close();

    header('Location: ../pages/login.html?error=unavailable');
    exit;
}

$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

$fullname = '';
$dbEmail = '';
$role = '';
$passwordHash = '';

$stmt->bind_result(
    $fullname,
    $dbEmail,
    $role,
    $passwordHash
);

$found = ($stmt->num_rows === 1);

if ($found) {
    $stmt->fetch();
}

$stmt->close();
$conn->close();

if (
    !$found ||
    !password_verify($password, $passwordHash) ||
    !in_array($role, ['Student', 'Faculty', 'Admin'], true)
) {
    header('Location: ../pages/login.html?error=invalid');
    exit;
}

// Prevent session fixation.
session_regenerate_id(true);

// Store authenticated user information.
$_SESSION['user_id'] = $dbEmail;
$_SESSION['fullname'] = $fullname;
$_SESSION['role'] = $role;
$_SESSION['last_activity'] = time();

// Redirect to the correct PHP dashboard handler.
switch ($role) {
    case 'Student':
        header('Location: student-dashboard.php');
        break;

    case 'Faculty':
        header('Location: faculty-dashboard.php');
        break;

    case 'Admin':
        header('Location: dashboard.php');
        break;
}

exit;
?>
