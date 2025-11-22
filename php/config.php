<?php
require_once __DIR__ . '/load_env.php';

define('DB_HOST', getenv('DB_HOST'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
define('DB_NAME', getenv('DB_NAME'));

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        if (isset($_POST['ajax']) || isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit();
        }
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: dashboard.php');
        exit();
    }
}

function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
?>
