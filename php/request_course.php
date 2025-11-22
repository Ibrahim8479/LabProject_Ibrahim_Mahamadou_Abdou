<?php
require_once 'config.php';

if (!isLoggedIn() || getCurrentUserRole() !== 'student') {
    sendJsonResponse(['success' => false, 'message' => 'Unauthorized access']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $student_id = getCurrentUserId();
    $course_id = intval($_POST['course_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    if ($course_id <= 0) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid course ID']);
    }
    
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        sendJsonResponse(['success' => false, 'message' => 'Course not found']);
    }
    
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT * FROM course_student_list WHERE course_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $course_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        sendJsonResponse(['success' => false, 'message' => 'You are already enrolled in this course']);
    }
    
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT * FROM course_requests WHERE course_id = ? AND student_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $course_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        sendJsonResponse(['success' => false, 'message' => 'You have already requested to join this course']);
    }
    
    $stmt->close();
    
    $stmt = $conn->prepare("INSERT INTO course_requests (course_id, student_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $course_id, $student_id, $message);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        sendJsonResponse(['success' => true, 'message' => 'Course request submitted successfully! The faculty will review your request.']);
    } else {
        $stmt->close();
        $conn->close();
        sendJsonResponse(['success' => false, 'message' => 'Failed to submit course request']);
    }
}
?>
