
<?php
// Practical 7: Process Contact Form

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/contact.html");
    exit;
}

function cleanInput($value) {
    return trim((string) $value);
}

function showMessage($title, $message, $success = false) {
    $color = $success ? "#15803d" : "#dc2626";
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?></title>
        <link rel="stylesheet" href="../css/style.css">
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
                padding: 12px 20px;
                background: #003366;
                color: white;
                text-decoration: none;
                border-radius: 6px;
            }
            .result-box p { overflow-wrap: anywhere; }
        </style>
    </head>
    <body>
        <main class="result-box">
            <h2><?php echo htmlspecialchars($title); ?></h2>
            <p><?php echo htmlspecialchars($message); ?></p>
            <a href="../pages/contact.html">Back to Contact</a>
        </main>
    </body>
    </html>
    <?php
    exit;
}

$name = cleanInput($_POST["name"] ?? "");
$email = cleanInput($_POST["email"] ?? "");
$subject = cleanInput($_POST["subject"] ?? "");
$message = cleanInput($_POST["message"] ?? "");

$errors = [];

if ($name === "" || !preg_match("/^[\p{L} .'-]{2,60}$/u", $name)) {
    $errors[] = "Enter a valid name (2–60 characters).";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Enter a valid email address.";
}

if ($subject === "" || strlen($subject) > 150) {
    $errors[] = "Subject is required and must be at most 150 characters.";
}

if ($message === "" || strlen($message) < 5 || strlen($message) > 3000) {
    $errors[] = "Message must contain between 5 and 3000 characters.";
}

if (!empty($errors)) {
    showMessage("Submission Failed", implode(" ", $errors));
}

$file = __DIR__ . "/../data/contacts.csv";
$directory = dirname($file);

if (!is_dir($directory) || !is_writable($directory)) {
    showMessage("Storage Error", "The data folder is missing or not writable.");
}

$isNewFile = !file_exists($file) || filesize($file) === 0;
$handle = fopen($file, "a");

if ($handle === false) {
    showMessage("Storage Error", "Could not open the contact file.");
}

if (!flock($handle, LOCK_EX)) {
    fclose($handle);
    showMessage("Storage Error", "Could not lock the contact file.");
}

$success = true;

if ($isNewFile) {
    $success = fputcsv($handle, [
        "Date", "Name", "Email", "Subject", "Message"
    ]) !== false;
}

if ($success) {
    $success = fputcsv($handle, [
        date("Y-m-d H:i:s"),
        $name,
        $email,
        $subject,
        $message
    ]) !== false;
}

flock($handle, LOCK_UN);
fclose($handle);

if (!$success) {
    showMessage("Storage Error", "Your message could not be saved.");
}

showMessage(
    "Message Submitted Successfully",
    "Thank you! Your contact form has been validated and saved.",
    true
);
?>
