
<?php
require_once __DIR__ . "/db.php";

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = filter_input(INPUT_POST, "student_id", FILTER_VALIDATE_INT);
    $event_id = filter_input(INPUT_POST, "event_id", FILTER_VALIDATE_INT);

    if (!$student_id || !$event_id) {
        $message = "Please enter valid student and event IDs.";
    } else {
        // Check whether the student exists
        $checkStudent = $conn->prepare(
            "SELECT id FROM students WHERE id = ?"
        );
        $checkStudent->bind_param("i", $student_id);
        $checkStudent->execute();
        $studentExists = $checkStudent->get_result()->num_rows > 0;
        $checkStudent->close();

        // Check whether the event exists
        $checkEvent = $conn->prepare(
            "SELECT id FROM events WHERE id = ?"
        );
        $checkEvent->bind_param("i", $event_id);
        $checkEvent->execute();
        $eventExists = $checkEvent->get_result()->num_rows > 0;
        $checkEvent->close();

        if (!$studentExists) {
            $message = "Student ID not found. Please check the students table.";
        } elseif (!$eventExists) {
            $message = "Event ID not found. Please check the events table.";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO registrations (student_id, event_id)
                 VALUES (?, ?)"
            );
            $stmt->bind_param("ii", $student_id, $event_id);

            try {
                if ($stmt->execute()) {
                    $message = "Student registered for the event successfully!";
                    $success = true;
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {
                    $message = "This student is already registered for this event.";
                } else {
                    error_log("Event registration failed: " . $e->getMessage());
                    $message = "Registration failed. Please try again.";
                }
            }

            $stmt->close();
        }
    }
} else {
    $message = "Please submit the registration form.";
}

$safeMessage = htmlspecialchars($message, ENT_QUOTES, "UTF-8");
$color = $success ? "green" : "red";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registration</title>
</head>
<body>
    <h2>Event Registration</h2>
    <p style="color: <?php echo $color; ?>;">
        <?php echo $safeMessage; ?>
    </p>

    <p><a href="../pages/events.html">Back to Events</a></p>
</body>
</html>
