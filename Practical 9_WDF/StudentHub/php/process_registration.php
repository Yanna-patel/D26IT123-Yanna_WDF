<?php
// StudentHub Practical 9
// Secure Registration using MySQLi

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/register.html");
    exit;
}

require_once __DIR__ . "/db.php";

// Display success or error messages
function showMessage($title, $message, $success = false)
{
    $color = $success ? "#15803d" : "#dc2626";
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> | StudentHub</title>
        <link rel="stylesheet" href="../style.css">
        <style>
            .result-box {
                max-width: 600px;
                margin: 60px auto;
                padding: 30px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0,0,0,.12);
                text-align: center;
            }

            .result-box h2 {
                color: <?php echo $color; ?>;
            }

            .result-box a {
                display: inline-block;
                margin-top: 15px;
                padding: 12px 22px;
                background: #003366;
                color: white;
                text-decoration: none;
                border-radius: 6px;
            }
        </style>
    </head>
    <body>
        <main class="result-box">
            <h2><?php echo htmlspecialchars($title); ?></h2>
            <p><?php echo htmlspecialchars($message); ?></p>
            <a href="../pages/register.html">Back to Registration</a>
        </main>
    </body>
    </html>
    <?php
    exit;
}

// Clean text input
function cleanInput($value)
{
    return is_string($value) ? trim($value) : "";
}

// Get form data
$name = cleanInput($_POST["fullname"] ?? "");
$email = cleanInput($_POST["email"] ?? "");
$course = cleanInput($_POST["course"] ?? "");
$role = cleanInput($_POST["role"] ?? "");

$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

// Backend validation
$errors = [];

if (!preg_match("/^[\p{L} .'-]{2,60}$/u", $name)) {
    $errors[] = "Enter a valid name containing 2-60 letters.";
}

if (
    strlen($email) > 254 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    $errors[] = "Enter a valid email address.";
}

if (!in_array($course, ["B.Tech", "BCA", "BBA", "MCA"], true)) {
    $errors[] = "Select a valid course.";
}

if (!in_array($role, ["Student", "Faculty"], true)) {
    $errors[] = "Select a valid role.";
}

if (!is_string($password) || strlen($password) < 8) {
    $errors[] = "Password must contain at least 8 characters.";
}

if (
    !is_string($confirmPassword) ||
    !is_string($password) ||
    $password !== $confirmPassword
) {
    $errors[] = "Passwords do not match.";
}

if (!empty($errors)) {
    showMessage("Registration Failed", implode(" ", $errors));
}

// Use the full name as the username identifier.
// Check duplicate name or email using a prepared statement.
$stmt = $conn->prepare(
    "SELECT fullname FROM students
     WHERE fullname = ? OR email = ?"
);

if (!$stmt) {
    error_log("Duplicate check prepare failed: " . $conn->error);
    showMessage(
        "Database Error",
        "Could not process your registration right now."
    );
}

$stmt->bind_param("ss", $name, $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    $conn->close();

    showMessage(
        "Registration Failed",
        "This full name or email is already registered."
    );
}

$stmt->close();

// Hash the password before storing it
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

if ($passwordHash === false) {
    $conn->close();

    showMessage(
        "Registration Failed",
        "Could not securely process your password."
    );
}

// Insert registration details using a prepared statement
$stmt = $conn->prepare(
    "INSERT INTO students
     (fullname, email, course, role, password_hash)
     VALUES (?, ?, ?, ?, ?)"
);

if (!$stmt) {
    error_log("Insert prepare failed: " . $conn->error);
    $conn->close();

    showMessage(
        "Database Error",
        "Could not prepare the registration."
    );
}

$stmt->bind_param(
    "sssss",
    $name,
    $email,
    $course,
    $role,
    $passwordHash
);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    showMessage(
        "Registration Successful",
        "Your details have been saved securely to StudentHub.",
        true
    );
}

// Handle database duplicate-key errors
if ($stmt->errno === 1062) {
    $stmt->close();
    $conn->close();

    showMessage(
        "Registration Failed",
        "This name or email is already registered."
    );
}

error_log("Registration insert failed: " . $stmt->error);

$stmt->close();
$conn->close();

showMessage(
    "Database Error",
    "Your registration could not be saved. Please try again."
);
?>