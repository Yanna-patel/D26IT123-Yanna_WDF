
<?php
// StudentHub Practical 8
// Test Database Connection

require_once __DIR__ . "/db.php";

echo "<!DOCTYPE html>";
echo '<html lang="en"><head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo "<title>Database Test | StudentHub</title>";
echo "</head><body>";

echo "<h1>StudentHub Database Test</h1>";
echo "<p>Database connection successful!</p>";

$tables = ["students", "events", "registrations"];

foreach ($tables as $table) {
    // Table names are from this fixed list.
    $result = $conn->query("SHOW TABLES LIKE '$table'");

    if ($result && $result->num_rows > 0) {
        echo "<p>" . htmlspecialchars($table) . " table exists.</p>";
    } else {
        echo "<p>" . htmlspecialchars($table) . " table is missing.</p>";
    }
}

$conn->close();

echo "</body></html>";
?>
