
<?php
// Shared Events API: Practical 12 + Practical 13
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/session.php';

function apiRespond(bool $success, string $message, $data = null, int $status = 200): void
{
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// All API operations require an authenticated Admin.
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    apiRespond(false, 'Please log in to continue.', null, 401);
}

if ($_SESSION['role'] !== 'Admin') {
    apiRespond(false, 'Only Admin users can manage events.', null, 403);
}

require_once __DIR__ . '/db.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    apiRespond(false, 'Database connection failed. Check db.php.', null, 500);
}

$conn->set_charset('utf8mb4');

$uploadDirectory = __DIR__ . '/../uploads/event-posters/';
$uploadWebPath = '../uploads/event-posters/';
$maxFileSize = 2 * 1024 * 1024;

$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];

function readJsonBody(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        apiRespond(false, 'Invalid JSON request body.', null, 400);
    }

    return $data;
}

function validateEventData(array $data): array
{
    $title = trim((string)($data['title'] ?? ''));
    $description = trim((string)($data['description'] ?? ''));
    $category = trim((string)($data['category'] ?? ''));
    $eventDate = trim((string)($data['event_date'] ?? ''));
    $location = trim((string)($data['location'] ?? ''));

    if ($title === '' || $description === '' || $category === '' ||
        $eventDate === '' || $location === '') {
        apiRespond(false, 'Please fill in all required event fields.', null, 400);
    }

    if (mb_strlen($title) > 255 || mb_strlen($category) > 100 ||
        mb_strlen($location) > 255) {
        apiRespond(false, 'One or more fields exceed the permitted length.', null, 400);
    }

    $date = DateTime::createFromFormat('!Y-m-d\TH:i', $eventDate);
    $dateErrors = DateTime::getLastErrors();

    if (
        !$date ||
        ($dateErrors !== false &&
            ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) ||
        $date->format('Y-m-d\TH:i') !== $eventDate
    ) {
        apiRespond(false, 'Please enter a valid event date and time.', null, 400);
    }

    return [
        $title,
        $description,
        $category,
        $date->format('Y-m-d H:i:s'),
        $location
    ];
}

function validatePoster(
    array $file,
    string $directory,
    int $maxSize,
    array $allowedMimes
): ?string {
    if (!isset($file['error']) || is_array($file['error'])) {
        apiRespond(false, 'Invalid poster upload.', null, 400);
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        apiRespond(false, 'Poster upload failed. Check PHP upload limits.', null, 400);
    }

    if ($file['size'] <= 0 || $file['size'] > $maxSize) {
        apiRespond(false, 'Poster must be an image no larger than 2 MB.', null, 400);
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        apiRespond(false, 'Invalid uploaded file.', null, 400);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!isset($allowedMimes[$mime])) {
        apiRespond(false, 'Use a JPG, PNG, or WebP poster.', null, 400);
    }

    $imageInfo = @getimagesize($file['tmp_name']);

    if ($imageInfo === false || ($imageInfo['mime'] ?? '') !== $mime) {
        apiRespond(false, 'The uploaded file is not a valid image.', null, 400);
    }

    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        apiRespond(false, 'Could not create the poster directory.', null, 500);
    }

    if (!is_writable($directory)) {
        apiRespond(false, 'The poster directory is not writable.', null, 500);
    }

    return bin2hex(random_bytes(16)) . '.' . $allowedMimes[$mime];
}

function removePoster(?string $filename, string $directory): void
{
    if ($filename && basename($filename) === $filename) {
        $path = $directory . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function findEvent(int $id, mysqli $conn): ?array
{
    $stmt = $conn->prepare('SELECT id, poster FROM events WHERE id = ?');

    if (!$stmt) {
        apiRespond(false, 'Could not prepare the event lookup query.', null, 500);
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $event ?: null;
}

function insertEvent(
    mysqli $conn,
    array $fields,
    ?string $poster = null
): int {
    [$title, $description, $category, $eventDate, $location] = $fields;

    $stmt = $conn->prepare(
        'INSERT INTO events (title, description, category, event_date, location, poster)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    if (!$stmt) {
        apiRespond(false, 'Could not prepare the add-event query.', null, 500);
    }

    $stmt->bind_param(
        'ssssss',
        $title,
        $description,
        $category,
        $eventDate,
        $location,
        $poster
    );

    if (!$stmt->execute()) {
        $stmt->close();
        apiRespond(false, 'Failed to add event.', null, 500);
    }

    $id = $stmt->insert_id;
    $stmt->close();

    return $id;
}

function updateEvent(
    mysqli $conn,
    int $id,
    array $fields,
    ?string $poster,
    bool $replacePoster
): void {
    [$title, $description, $category, $eventDate, $location] = $fields;

    if ($replacePoster) {
        $stmt = $conn->prepare(
            'UPDATE events
             SET title = ?, description = ?, category = ?, event_date = ?,
                 location = ?, poster = ?
             WHERE id = ?'
        );

        if (!$stmt) {
            apiRespond(false, 'Could not prepare the update query.', null, 500);
        }

        $stmt->bind_param(
            'ssssssi',
            $title,
            $description,
            $category,
            $eventDate,
            $location,
            $poster,
            $id
        );
    } else {
        $stmt = $conn->prepare(
            'UPDATE events
             SET title = ?, description = ?, category = ?, event_date = ?, location = ?
             WHERE id = ?'
        );

        if (!$stmt) {
            apiRespond(false, 'Could not prepare the update query.', null, 500);
        }

        $stmt->bind_param(
            'sssssi',
            $title,
            $description,
            $category,
            $eventDate,
            $location,
            $id
        );
    }

    if (!$stmt->execute()) {
        $stmt->close();
        apiRespond(false, 'Failed to update event.', null, 500);
    }

    $stmt->close();
}

// GET: list events for both practicals.
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $conn->prepare(
        'SELECT id, title, description, category, event_date,
                location, created_at, poster
         FROM events
         ORDER BY event_date DESC, id DESC'
    );

    if (!$stmt || !$stmt->execute()) {
        apiRespond(false, 'Could not retrieve events.', null, 500);
    }

    $result = $stmt->get_result();
    $events = [];

    while ($row = $result->fetch_assoc()) {
        $row['poster_url'] = !empty($row['poster'])
            ? $uploadWebPath . rawurlencode($row['poster'])
            : null;

        $events[] = $row;
    }

    $stmt->close();
    apiRespond(true, 'Events retrieved successfully.', $events);
}

// Practical 12 legacy form requests use POST + action.
if ($method === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add' || $action === 'update') {
        $fields = validateEventData($_POST);

        $posterName = validatePoster(
            $_FILES['poster'] ?? ['error' => UPLOAD_ERR_NO_FILE],
            $uploadDirectory,
            $maxFileSize,
            $allowedMimeTypes
        );

        if ($posterName !== null &&
            !move_uploaded_file(
                $_FILES['poster']['tmp_name'],
                $uploadDirectory . $posterName
            )) {
            apiRespond(false, 'Could not save the uploaded poster.', null, 500);
        }

        if ($action === 'add') {
            try {
                $id = insertEvent($conn, $fields, $posterName);
            } catch (Throwable $e) {
                removePoster($posterName, $uploadDirectory);
                throw $e;
            }

            apiRespond(true, 'Event added successfully. Event ID: ' . $id, ['id' => $id], 201);
        }

        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            removePoster($posterName, $uploadDirectory);
            apiRespond(false, 'A valid event ID is required.', null, 400);
        }

        $existing = findEvent($id, $conn);

        if (!$existing) {
            removePoster($posterName, $uploadDirectory);
            apiRespond(false, 'Event not found.', null, 404);
        }

        $oldPoster = $existing['poster'] ?? null;

        try {
            updateEvent(
                $conn,
                $id,
                $fields,
                $posterName ?? $oldPoster,
                $posterName !== null
            );
        } catch (Throwable $e) {
            removePoster($posterName, $uploadDirectory);
            throw $e;
        }

        if ($posterName !== null) {
            removePoster($oldPoster, $uploadDirectory);
        }

        apiRespond(true, 'Event updated successfully.', ['id' => $id]);
    }

    if ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            apiRespond(false, 'A valid event ID is required.', null, 400);
        }

        $existing = findEvent($id, $conn);

        if (!$existing) {
            apiRespond(false, 'Event not found.', null, 404);
        }

        $stmt = $conn->prepare('DELETE FROM events WHERE id = ?');

        if (!$stmt) {
            apiRespond(false, 'Could not prepare the delete query.', null, 500);
        }

        $stmt->bind_param('i', $id);

        if (!$stmt->execute()) {
            $stmt->close();
            apiRespond(false, 'Failed to delete event.', null, 500);
        }

        $stmt->close();
        removePoster($existing['poster'] ?? null, $uploadDirectory);

        apiRespond(true, 'Event deleted successfully.', ['id' => $id]);
    }

    apiRespond(false, 'Invalid action.', null, 400);
}

// Practical 13 JSON requests use POST, PUT, and DELETE.
if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    header('Allow: GET, POST, PUT, DELETE');
    apiRespond(false, 'Unsupported request method.', null, 405);
}

$data = readJsonBody();

if ($method === 'POST') {
    $fields = validateEventData($data);
    $id = insertEvent($conn, $fields);
    apiRespond(true, 'Event added successfully.', ['id' => $id], 201);
}

$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    apiRespond(false, 'A valid event ID is required.', null, 400);
}

$existing = findEvent($id, $conn);

if (!$existing) {
    apiRespond(false, 'Event not found.', null, 404);
}

if ($method === 'PUT') {
    $fields = validateEventData($data);

    // JSON updates preserve the poster uploaded in Practical 12.
    updateEvent($conn, $id, $fields, $existing['poster'] ?? null, false);

    apiRespond(true, 'Event updated successfully.', ['id' => $id]);
}

if ($method === 'DELETE') {
    $stmt = $conn->prepare('DELETE FROM events WHERE id = ?');

    if (!$stmt) {
        apiRespond(false, 'Could not prepare the delete query.', null, 500);
    }

    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $stmt->close();
        apiRespond(false, 'Failed to delete event.', null, 500);
    }

    $stmt->close();
    removePoster($existing['poster'] ?? null, $uploadDirectory);

    apiRespond(true, 'Event deleted successfully.', ['id' => $id]);
}
