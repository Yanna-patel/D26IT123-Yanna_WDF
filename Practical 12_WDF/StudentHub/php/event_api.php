
<?php
// Practical 12: Admin-only event management API

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/session.php';

function respond(bool $success, string $message, $data = null, int $status = 200): void
{
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Return JSON rather than redirecting API requests to an HTML page.
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    respond(false, 'Please log in to continue.', null, 401);
}

if ($_SESSION['role'] !== 'Admin') {
    respond(false, 'Access denied. Only admins can manage events.', null, 403);
}

require_once __DIR__ . '/db.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    respond(false, 'Database connection failed. Check php/db.php.', null, 500);
}

$conn->set_charset('utf8mb4');

$uploadDirectory = __DIR__ . '/../uploads/event-posters/';
$uploadWebPath = '../uploads/event-posters/';
$maxFileSize = 2 * 1024 * 1024; // 2 MB
$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
];

function cleanText(string $value): string
{
    return trim($value);
}

function validateEventFields(): array
{
    $title = cleanText($_POST['title'] ?? '');
    $description = cleanText($_POST['description'] ?? '');
    $category = cleanText($_POST['category'] ?? '');
    $eventDate = cleanText($_POST['event_date'] ?? '');
    $location = cleanText($_POST['location'] ?? '');

    if ($title === '' || $description === '' || $category === '' ||
        $eventDate === '' || $location === '') {
        respond(false, 'Please fill in all required event fields.', null, 400);
    }

    if (mb_strlen($title) > 255 || mb_strlen($category) > 100 ||
        mb_strlen($location) > 255) {
        respond(false, 'One or more fields exceed the permitted length.', null, 400);
    }

    $date = DateTime::createFromFormat('Y-m-d\TH:i', $eventDate);
    $dateErrors = DateTime::getLastErrors();

    if (
        !$date ||
        ($dateErrors !== false &&
            ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) ||
        $date->format('Y-m-d\TH:i') !== $eventDate
    ) {
        respond(false, 'Please enter a valid event date and time.', null, 400);
    }

    return [$title, $description, $category, $date->format('Y-m-d H:i:s'), $location];
}

function validatePoster(array $file, string $directory, int $maxSize, array $allowedMimes): ?string
{
    if (!isset($file['error']) || is_array($file['error'])) {
        respond(false, 'Invalid poster upload.', null, 400);
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        respond(false, 'Poster upload failed. Check the file and PHP upload limits.', null, 400);
    }

    if ($file['size'] <= 0 || $file['size'] > $maxSize) {
        respond(false, 'Poster must be an image no larger than 2 MB.', null, 400);
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        respond(false, 'Invalid uploaded file.', null, 400);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!isset($allowedMimes[$mime])) {
        respond(false, 'Invalid poster format. Use JPG, PNG, or WebP.', null, 400);
    }

    $imageInfo = @getimagesize($file['tmp_name']);

    if ($imageInfo === false || ($imageInfo['mime'] ?? '') !== $mime) {
        respond(false, 'The uploaded file is not a valid supported image.', null, 400);
    }

    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        respond(false, 'Could not create the poster upload directory.', null, 500);
    }

    if (!is_writable($directory)) {
        respond(false, 'The poster upload directory is not writable.', null, 500);
    }

    // Do not use the original filename.
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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $conn->prepare(
        'SELECT id, title, description, category, event_date, location, created_at, poster
         FROM events
         ORDER BY event_date DESC, id DESC'
    );

    if (!$stmt || !$stmt->execute()) {
        respond(false, 'Could not retrieve events.', null, 500);
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
    respond(true, 'Events retrieved successfully.', $events);
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    respond(false, 'Unsupported request method.', null, 405);
}

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    [$title, $description, $category, $eventDate, $location] = validateEventFields();

    $posterName = validatePoster(
        $_FILES['poster'] ?? ['error' => UPLOAD_ERR_NO_FILE],
        $uploadDirectory,
        $maxFileSize,
        $allowedMimeTypes
    );

    if ($posterName !== null &&
        !move_uploaded_file($_FILES['poster']['tmp_name'], $uploadDirectory . $posterName)) {
        respond(false, 'Could not save the uploaded poster.', null, 500);
    }

    $stmt = $conn->prepare(
        'INSERT INTO events (title, description, category, event_date, location, poster)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    if (!$stmt) {
        removePoster($posterName, $uploadDirectory);
        respond(false, 'Could not prepare the add-event query.', null, 500);
    }

    $stmt->bind_param('ssssss', $title, $description, $category, $eventDate, $location, $posterName);

    if (!$stmt->execute()) {
        $stmt->close();
        removePoster($posterName, $uploadDirectory);
        respond(false, 'Failed to add event.', null, 500);
    }

    $newId = $stmt->insert_id;
    $stmt->close();

    respond(true, 'Event added successfully. Event ID: ' . $newId);
}

if ($action === 'update') {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

    if (!$id) {
        respond(false, 'A valid event ID is required.', null, 400);
    }

    [$title, $description, $category, $eventDate, $location] = validateEventFields();

    $find = $conn->prepare('SELECT poster FROM events WHERE id = ?');

    if (!$find) {
        respond(false, 'Could not prepare the event lookup query.', null, 500);
    }

    $find->bind_param('i', $id);
    $find->execute();
    $existing = $find->get_result()->fetch_assoc();
    $find->close();

    if (!$existing) {
        respond(false, 'Event not found.', null, 404);
    }

    $oldPoster = $existing['poster'] ?? null;
    $newPoster = validatePoster(
        $_FILES['poster'] ?? ['error' => UPLOAD_ERR_NO_FILE],
        $uploadDirectory,
        $maxFileSize,
        $allowedMimeTypes
    );

    if ($newPoster !== null &&
        !move_uploaded_file($_FILES['poster']['tmp_name'], $uploadDirectory . $newPoster)) {
        respond(false, 'Could not save the new poster.', null, 500);
    }

    $posterToSave = $newPoster ?? $oldPoster;

    $stmt = $conn->prepare(
        'UPDATE events
         SET title = ?, description = ?, category = ?, event_date = ?, location = ?, poster = ?
         WHERE id = ?'
    );

    if (!$stmt) {
        removePoster($newPoster, $uploadDirectory);
        respond(false, 'Could not prepare the update-event query.', null, 500);
    }

    $stmt->bind_param(
        'ssssssi',
        $title,
        $description,
        $category,
        $eventDate,
        $location,
        $posterToSave,
        $id
    );

    if (!$stmt->execute()) {
        $stmt->close();
        removePoster($newPoster, $uploadDirectory);
        respond(false, 'Failed to update event.', null, 500);
    }

    $stmt->close();

    if ($newPoster !== null && $oldPoster !== $newPoster) {
        removePoster($oldPoster, $uploadDirectory);
    }

    respond(true, 'Event updated successfully.');
}

if ($action === 'delete') {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

    if (!$id) {
        respond(false, 'A valid event ID is required.', null, 400);
    }

    $find = $conn->prepare('SELECT poster FROM events WHERE id = ?');

    if (!$find) {
        respond(false, 'Could not prepare the event lookup query.', null, 500);
    }

    $find->bind_param('i', $id);
    $find->execute();
    $existing = $find->get_result()->fetch_assoc();
    $find->close();

    if (!$existing) {
        respond(false, 'Event not found.', null, 404);
    }

    $stmt = $conn->prepare('DELETE FROM events WHERE id = ?');

    if (!$stmt) {
        respond(false, 'Could not prepare the delete-event query.', null, 500);
    }

    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $stmt->close();
        respond(false, 'Failed to delete event.', null, 500);
    }

    $stmt->close();
    removePoster($existing['poster'] ?? null, $uploadDirectory);

    respond(true, 'Event deleted successfully.');
}

respond(false, 'Invalid event action.', null, 400);
