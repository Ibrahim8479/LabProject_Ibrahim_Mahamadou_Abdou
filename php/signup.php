<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $dob = $_POST['dob'] ?? null;
    
    $errors = [];
    if (empty($first_name)) $errors[] = 'First name required';
    if (empty($last_name)) $errors[] = 'Last name required';
    if (empty($email)) $errors[] = 'Email required';
    if (empty($password)) $errors[] = 'Password required';
    if (strlen($password) < 6) $errors[] = 'Password must be 6+ characters';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match';
    if (empty($role)) $errors[] = 'Role required';
    if (!in_array($role, ['student', 'faculty'])) $errors[] = 'Invalid role selected';
    
    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'message' => implode(', ', $errors)]);
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        sendJsonResponse(['success' => false, 'message' => 'Email already registered']);
    }
    $stmt->close();
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role, dob) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $first_name, $last_name, $email, $password_hash, $role, $dob);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create user');
        }
        
        $user_id = $conn->insert_id;
        $stmt->close();
        
        if ($role === 'student') {
            $stmt = $conn->prepare("INSERT INTO students (student_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        } elseif ($role === 'faculty') {
            $stmt = $conn->prepare("INSERT INTO faculty (faculty_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        sendJsonResponse(['success' => true, 'message' => 'Registration successful!']);
        
    } catch (Exception $e) {
        $conn->rollback();
        sendJsonResponse(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
    }
    
    $conn->close();
}
?>
