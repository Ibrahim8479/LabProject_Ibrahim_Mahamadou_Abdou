<?php
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

if (getenv('SESSION_LIFETIME')) {
    $timeout = intval(getenv('SESSION_LIFETIME'));
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit();
    }
}

$_SESSION['last_activity'] = time();
?>
