<?php
// Practical 14: Return the current login status and role.

require_once __DIR__ . '/session.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$loggedIn = isset($_SESSION['user_id'], $_SESSION['role']);

echo json_encode([
    'logged_in' => $loggedIn,
    'role' => $loggedIn ? $_SESSION['role'] : 'Guest'
]);