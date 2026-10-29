
<?php
require_once __DIR__ . "/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Please submit the registration form.");
}

$event_id = filter_input(INPUT_POST, "event_id", FILTER_VALIDATE_INT);
$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$roll = trim($_POST["roll"] ?? "");

if (!$event_id || $name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || $roll === "") {
    die("Please enter valid registration details.");
}

// Check whether the student exists
$stmt = $conn->prepare(
    "SELECT id, fullname FROM students WHERE email = ? AND roll = ?"
);
$stmt->bind_param("ss", $email, $roll);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student || strcasecmp($student["fullname"], $name) !== 0) {
    die("Student details not found. Please check your name, email, and enrollment number.");
}

$student_id = (int) $student["id"];

// Check whether the event exists
$stmt = $conn->prepare("SELECT id FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$eventExists = $stmt->get_result()->num_rows > 0;
$stmt->close();

if (!$eventExists) {
    die("Event not found in the database.");
}

// Register the student
$stmt = $conn->prepare(
    "INSERT INTO registrations (student_id, event_id) VALUES (?, ?)"
);
$stmt->bind_param("ii", $student_id, $event_id);

try {
    $stmt->execute();
    $message = "Registration successful!";
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        $message = "You are already registered for this event.";
    } else {
        error_log("Event registration failed: " . $e->getMessage());
        $message = "Registration failed. Please try again.";
    }
}

$stmt->close();
$conn->close();

echo htmlspecialchars($message, ENT_QUOTES, "UTF-8");
?>
