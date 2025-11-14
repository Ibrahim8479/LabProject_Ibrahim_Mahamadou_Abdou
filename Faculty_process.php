<?php
// Faculty_process.php
header('Content-Type: application/json');
require_once 'config.php';

checkUserType(['faculty']);

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

$action = $_GET['action'] ?? '';

if ($action == 'add_course') {
    $courseId = sanitizeInput($_POST['course_id']);
    $courseName = sanitizeInput($_POST['course_name']);
    
    try {
        $stmt = $conn->prepare("INSERT INTO courses (course_id, course_name, faculty_id) VALUES (?, ?, ?)");
        $stmt->execute([$courseId, $courseName, $userId]);
        echo json_encode(['success' => true, 'message' => 'Course added successfully']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to add course: ' . $e->getMessage()]);
    }
}

elseif ($action == 'delete_course') {
    $courseId = sanitizeInput($_POST['course_id']);
    
    try {
        $stmt = $conn->prepare("DELETE FROM courses WHERE course_id = ? AND faculty_id = ?");
        $stmt->execute([$courseId, $userId]);
        echo json_encode(['success' => true, 'message' => 'Course deleted successfully']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete course']);
    }
}

elseif ($action == 'create_session') {
    $courseId = sanitizeInput($_POST['course_id']);
    $sessionDate = sanitizeInput($_POST['session_date']);
    $sessionTime = sanitizeInput($_POST['session_time']);
    $location = sanitizeInput($_POST['location']);
    
    try {
        $stmt = $conn->prepare("INSERT INTO sessions (course_id, session_date, session_time, location, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$courseId, $sessionDate, $sessionTime, $location, $userId]);
        echo json_encode(['success' => true, 'message' => 'Session created successfully']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to create session']);
    }
}
?>