<?php
session_start();

// Simulate faculty login
$_SESSION['user_id'] = 1;  // Your faculty user_id
$_SESSION['role'] = 'faculty';
$_SESSION['username'] = 'Test Faculty';

// Simulate AJAX request
$_POST['ajax'] = '1';
$_POST['request_id'] = '1';  // The pending request ID
$_POST['action'] = 'approve';

echo "Testing approval...<br>";

require_once 'process_request.php';
?>
