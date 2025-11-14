<?php
// login_process.php
require_once 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT user_id, full_name, email, password, user_type FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];
                
                switch ($user['user_type']) {
                    case 'student':
                        header('Location: StudentDash.html');
                        break;
                    case 'faculty':
                        header('Location: Faculty.html');
                        break;
                    case 'faculty_intern':
                        header('Location: Faculty_int.html');
                        break;
                }
                exit();
            } else {
                $_SESSION['login_error'] = "Invalid email or password";
                header('Location: login.html?error=1');
                exit();
            }
        } catch(PDOException $e) {
            $_SESSION['login_error'] = "Login failed: " . $e->getMessage();
            header('Location: login.html?error=1');
            exit();
        }
    }
}

if (!empty($errors)) {
    $_SESSION['login_errors'] = $errors;
    header('Location: login.html?error=1');
    exit();
}
?>