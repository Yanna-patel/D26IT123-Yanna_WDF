
<?php
$allowedRoles = ['Student'];

require_once __DIR__ . '/auth.php';

// Display the HTML dashboard only after authorization.
require __DIR__ . '/../pages/student-dashboard.html';
?>
