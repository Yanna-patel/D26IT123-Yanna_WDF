
<?php
// =============================================
// StudentHub - Practical 7
// Registration Processing with CSV Storage
// =============================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/register.html");
    exit;
}

// Display success/error messages safely.
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
            .result-box h2 { color: <?php echo $color; ?>; }
            .result-box p { line-height: 1.6; overflow-wrap: anywhere; }
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

// Read and sanitize submitted values.
function cleanInput($value)
{
    return is_string($value) ? trim($value) : "";
}

$name = cleanInput($_POST["fullname"] ?? "");
$email = cleanInput($_POST["email"] ?? "");
$course = cleanInput($_POST["course"] ?? "");
$role = cleanInput($_POST["role"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

// Server-side validation.
$errors = [];

if (
    $name === "" ||
    !preg_match("/^[\p{L} .'-]{2,60}$/u", $name)
) {
    $errors[] = "Enter a valid full name containing 2–60 letters.";
}

if (
    strlen($email) > 254 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    $errors[] = "Enter a valid email address.";
}

$allowedCourses = ["B.Tech", "BCA", "BBA", "MCA"];
if (!in_array($course, $allowedCourses, true)) {
    $errors[] = "Please select a valid course.";
}

$allowedRoles = ["Student", "Faculty"];
if (!in_array($role, $allowedRoles, true)) {
    $errors[] = "Please select a valid registration role.";
}

if (!is_string($password) || strlen($password) < 8) {
    $errors[] = "Password must contain at least 8 characters.";
}

if (!is_string($confirmPassword) || $password !== $confirmPassword) {
    $errors[] = "Password and confirm password do not match.";
}

// Stop if validation fails.
if (!empty($errors)) {
    showMessage("Registration Failed", implode(" ", $errors));
}

// Store records in StudentHub/data/registrations.csv.
$dataDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . "data";
$file = $dataDirectory . DIRECTORY_SEPARATOR . "registrations.csv";

// Create data directory if it does not exist.
if (!is_dir($dataDirectory)) {
    if (!mkdir($dataDirectory, 0775, true) && !is_dir($dataDirectory)) {
        error_log("StudentHub: Cannot create data directory.");

        showMessage(
            "Storage Error",
            "Could not create the data folder. Check folder permissions."
        );
    }
}

// Open CSV in append mode.
$handle = fopen($file, "a+");

if ($handle === false) {
    error_log("StudentHub: Cannot open " . $file);

    showMessage(
        "Storage Error",
        "Could not open registrations.csv. Check folder permissions."
    );
}

// Lock file while writing.
if (!flock($handle, LOCK_EX)) {
    fclose($handle);
    showMessage("Storage Error", "Could not lock the registration file.");
}

// Determine whether the CSV is empty.
fseek($handle, 0, SEEK_END);
$isEmpty = ftell($handle) === 0;

$writeSuccessful = true;

// Write column headings for a new file.
if ($isEmpty) {
    $writeSuccessful = fputcsv($handle, [
        "Date",
        "Full Name",
        "Email",
        "Course",
        "Role"
    ]) !== false;
}

// Store registration data.
// Passwords are intentionally NOT stored in the CSV.
if ($writeSuccessful) {
    $writeSuccessful = fputcsv($handle, [
        date("Y-m-d H:i:s"),
        $name,
        $email,
        $course,
        $role
    ]) !== false;
}

if (!fflush($handle)) {
    $writeSuccessful = false;
}

flock($handle, LOCK_UN);
fclose($handle);

if (!$writeSuccessful) {
    error_log("StudentHub: Failed to write registration to CSV.");

    showMessage(
        "Storage Error",
        "Registration could not be saved. Please try again."
    );
}

// Show success only after data has been saved.
showMessage(
    "Registration Successful",
    "Your details have been validated and saved successfully.",
    true
);
?>
