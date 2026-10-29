
<?php
// Practical 11: Student Management API
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

function sendResponse($success, $message, $data = null, $status = 200)
{
    http_response_code($status);

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);

    exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    sendResponse(false, 'Database connection is not available. Check php/db.php.', null, 500);
}

$conn->set_charset('utf8mb4');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $search = trim($_GET['search'] ?? '');
    $course = trim($_GET['course'] ?? '');
    $role = trim($_GET['role'] ?? '');

    $sql = 'SELECT id, fullname, email, course, role FROM students WHERE 1=1';
    $types = '';
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (fullname LIKE ? OR email LIKE ? OR course LIKE ?)';
        $term = '%' . $search . '%';
        $types .= 'sss';
        array_push($params, $term, $term, $term);
    }

    if ($course !== '') {
        $sql .= ' AND course = ?';
        $types .= 's';
        $params[] = $course;
    }

    if ($role !== '') {
        $sql .= ' AND role = ?';
        $types .= 's';
        $params[] = $role;
    }

    $sql .= ' ORDER BY id DESC';

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        sendResponse(false, 'Could not prepare the student search query.', null, 500);
    }

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        sendResponse(false, 'Failed to retrieve student records.', null, 500);
    }

    $result = $stmt->get_result();
    $students = [];

    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    $stmt->close();
    sendResponse(true, 'Student records retrieved successfully.', $students);
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    sendResponse(false, 'Unsupported request method.', null, 405);
}

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $role = trim($_POST['role'] ?? 'Student');
    $password = $_POST['password'] ?? '';

    if ($fullname === '' || $email === '' || $course === '' || $password === '') {
        sendResponse(false, 'Please fill in all required fields.', null, 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, 'Please enter a valid email address.', null, 400);
    }

    if (strlen($password) < 8) {
        sendResponse(false, 'Password must contain at least 8 characters.', null, 400);
    }

    if (!in_array($role, ['Student', 'Faculty', 'Admin'], true)) {
        sendResponse(false, 'Please select a valid role.', null, 400);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        'INSERT INTO students (fullname, email, course, role, password_hash)
         VALUES (?, ?, ?, ?, ?)'
    );

    if (!$stmt) {
        sendResponse(false, 'Could not prepare the add-student query.', null, 500);
    }

    $stmt->bind_param('sssss', $fullname, $email, $course, $role, $passwordHash);

    if (!$stmt->execute()) {
        $duplicate = $stmt->errno === 1062;
        $stmt->close();

        if ($duplicate) {
            sendResponse(false, 'This email address is already registered.', null, 409);
        }

        sendResponse(false, 'Failed to add student. Please check the database.', null, 500);
    }

    $newId = $stmt->insert_id;
    $stmt->close();

    sendResponse(true, 'Student added successfully. ID: ' . $newId);
}

if ($action === 'update') {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $role = trim($_POST['role'] ?? 'Student');
    $password = $_POST['password'] ?? '';

    if (!$id || $fullname === '' || $email === '' || $course === '') {
        sendResponse(false, 'Please provide a valid ID and fill in all required fields.', null, 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, 'Please enter a valid email address.', null, 400);
    }

    if (!in_array($role, ['Student', 'Faculty', 'Admin'], true)) {
        sendResponse(false, 'Please select a valid role.', null, 400);
    }

    if ($password !== '' && strlen($password) < 8) {
        sendResponse(false, 'A new password must contain at least 8 characters.', null, 400);
    }

    // Preserve the current password hash unless a new password is supplied.
    if ($password !== '') {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            'UPDATE students
             SET fullname = ?, email = ?, course = ?, role = ?, password_hash = ?
             WHERE id = ?'
        );

        if (!$stmt) {
            sendResponse(false, 'Could not prepare the update query.', null, 500);
        }

        $stmt->bind_param(
            'sssssi',
            $fullname,
            $email,
            $course,
            $role,
            $passwordHash,
            $id
        );
    } else {
        $stmt = $conn->prepare(
            'UPDATE students
             SET fullname = ?, email = ?, course = ?, role = ?
             WHERE id = ?'
        );

        if (!$stmt) {
            sendResponse(false, 'Could not prepare the update query.', null, 500);
        }

        $stmt->bind_param('ssssi', $fullname, $email, $course, $role, $id);
    }

    if (!$stmt->execute()) {
        $duplicate = $stmt->errno === 1062;
        $stmt->close();

        if ($duplicate) {
            sendResponse(false, 'That email address is already used by another record.', null, 409);
        }

        sendResponse(false, 'Failed to update student.', null, 500);
    }

    $stmt->close();

    sendResponse(true, 'Student record updated successfully.');
}

if ($action === 'delete') {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

    if (!$id) {
        sendResponse(false, 'Please provide a valid student ID.', null, 400);
    }

    $stmt = $conn->prepare('DELETE FROM students WHERE id = ?');

    if (!$stmt) {
        sendResponse(false, 'Could not prepare the delete query.', null, 500);
    }

    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $stmt->close();
        sendResponse(false, 'Failed to delete student.', null, 500);
    }

    $deleted = $stmt->affected_rows;
    $stmt->close();

    if ($deleted === 0) {
        sendResponse(false, 'Student not found. No record was deleted.', null, 404);
    }

    sendResponse(true, 'Student record deleted successfully.');
}

sendResponse(false, 'Invalid action requested.', null, 400);
