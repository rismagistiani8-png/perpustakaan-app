<?php
require __DIR__ . '/lib/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && logged_in()) {
    check_csrf();
    log_activity('Logout');
}
$_SESSION = [];
session_destroy();
header('Location: index.php');
exit;
