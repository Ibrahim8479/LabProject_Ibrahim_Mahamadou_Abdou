<?php
require_once 'config.php';

if (!isLoggedIn() || getCurrentUserRole() !== 'faculty') {
    sendJsonResponse(['success' => false, 'message' => 'Unauthorized access']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $faculty_id = getCurrentUserId();
    $course_code = trim($_POST['course_code'] ?? '');
    $course_name = trim($_POST['course_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $credit_hours = intval($_POST['credit_hours'] ?? 0);
    
    $errors = [];
    
    if (empty($course_code)) $errors[] = 'Course code is required';
    if (empty($course_name)) $errors[] = 'Course name is required';
    if ($credit_hours < 1 || $credit_hours > 6) $errors[] = 'Credit hours must be between 1 and 6';
    
    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'message' => implode(', ', $errors)]);
    }
    
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_code = ?");
    $stmt->bind_param("s", $course_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        sendJsonResponse(['success' => false, 'message' => 'Course code already exists']);
    }
    
    $stmt->close();
    
    $stmt = $conn->prepare("INSERT INTO courses (course_code, course_name, description, credit_hours, faculty_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $course_code, $course_name, $description, $credit_hours, $faculty_id);
    
    if ($stmt->execute()) {
        $course_id = $conn->insert_id;
        $stmt->close();
        $conn->close();
        sendJsonResponse(['success' => true, 'message' => 'Course created successfully', 'course_id' => $course_id]);
    } else {
        $stmt->close();
        $conn->close();
        sendJsonResponse(['success' => false, 'message' => 'Failed to create course']);
    }
}
?>
