<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    session_unset();
    session_destroy();
    sendJsonResponse(['logout' => true, 'message' => 'You have been logged out successfully']);
}

session_unset();
session_destroy();
header('Location: login.php');
exit();
?>
