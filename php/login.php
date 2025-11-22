<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        sendJsonResponse(['success' => false, 'message' => 'Please enter both email and password']);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid email format']);
    }
    
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, password_hash, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                
                $stmt->close();
                $conn->close();
                
                sendJsonResponse([
                    'success' => true,
                    'username' => $_SESSION['username'],
                    'user_id' => $user['user_id'],
                    'role' => $user['role']
                ]);
            } else {
                $stmt->close();
                $conn->close();
                sendJsonResponse(['success' => false, 'message' => 'Invalid password']);
            }
        } else {
            $stmt->close();
            $conn->close();
            sendJsonResponse(['success' => false, 'message' => 'User not found with email: ' . $email]);
        }
    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Login error: ' . $e->getMessage()]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Attendance Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>Attendance Management System</h1>
            <h2>Login</h2>
            
            <?php if (isset($_GET['timeout'])): ?>
                <div class="error-message">Your session has expired. Please login again.</div>
            <?php endif; ?>
            
            <form id="loginForm">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <p class="register-link">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
    
    <script src="../js/login.js"></script>
</body>
</html>

