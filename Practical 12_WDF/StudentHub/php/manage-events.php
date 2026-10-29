
<?php
// Practical 12: Admin-only event management page

$allowedRoles = ['Admin'];
require_once __DIR__ . '/auth.php';

require __DIR__ . '/../pages/manage-events.html';
