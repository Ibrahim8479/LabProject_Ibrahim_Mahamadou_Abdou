<?php
// signup_process.php
//collect the data
//connect to database
//write query
//check if excution work
//fetch the data
//compare password
//make the decision
require_once 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullName = sanitizeInput($_POST['fullName']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $userType = sanitizeInput($_POST['userType']);
    
    if (empty($fullName)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    if (!in_array($userType, ['student', 'faculty', 'faculty_intern'])) {
        $errors[] = "Invalid user type";
    }
    
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");//.$email
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $errors[] = "Email already exists";
            }
        } catch(PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, user_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$fullName, $email, $hashedPassword, $userType]);
            
            header('Location: login.html?registered=1');
            exit();
        } catch(PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}

if (!empty($errors)) {
    $_SESSION['signup_errors'] = $errors;
    header('Location: signup.html?error=1');
    exit();
}
?>