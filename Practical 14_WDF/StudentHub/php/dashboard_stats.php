<?php
// Practical 14: Admin Dashboard Statistics API

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$allowedRoles = ['Admin'];
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

function dashboardResponse($success, $message, $data = null, $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    dashboardResponse(false, 'Method not allowed.', null, 405);
}

try {
    $totalUsers = (int) $conn
        ->query("SELECT COUNT(*) AS total FROM students")
        ->fetch_assoc()['total'];

    $studentCount = (int) $conn
        ->query("SELECT COUNT(*) AS total FROM students WHERE role = 'Student'")
        ->fetch_assoc()['total'];

    $facultyCount = (int) $conn
        ->query("SELECT COUNT(*) AS total FROM students WHERE role = 'Faculty'")
        ->fetch_assoc()['total'];

    $adminCount = (int) $conn
        ->query("SELECT COUNT(*) AS total FROM students WHERE role = 'Admin'")
        ->fetch_assoc()['total'];

    $eventCount = (int) $conn
        ->query("SELECT COUNT(*) AS total FROM events")
        ->fetch_assoc()['total'];

    $registrationCount = (int) $conn
        ->query("SELECT COUNT(*) AS total FROM registrations")
        ->fetch_assoc()['total'];

    $sql = "
        SELECT
            r.id AS registration_id,
            s.fullname AS student_name,
            s.email AS student_email,
            e.title AS event_title,
            r.registered_at,
            r.status
        FROM registrations r
        INNER JOIN students s ON r.student_id = s.id
        INNER JOIN events e ON r.event_id = e.id
        ORDER BY r.registered_at DESC, r.id DESC
        LIMIT 10
    ";

    $result = $conn->query($sql);
    $latestRegistrations = [];

    while ($row = $result->fetch_assoc()) {
        $latestRegistrations[] = $row;
    }

    dashboardResponse(true, 'Dashboard statistics retrieved successfully.', [
        'total_users' => $totalUsers,
        'student_count' => $studentCount,
        'faculty_count' => $facultyCount,
        'admin_count' => $adminCount,
        'event_count' => $eventCount,
        'registration_count' => $registrationCount,
        'latest_registrations' => $latestRegistrations
    ]);

} catch (Throwable $e) {
    error_log('Dashboard statistics error: ' . $e->getMessage());

    dashboardResponse(
        false,
        'Unable to load dashboard statistics. Check the PHP error log.',
        null,
        500
    );
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>