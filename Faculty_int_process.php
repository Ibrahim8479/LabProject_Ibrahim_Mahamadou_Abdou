<?php
// Faculty_int_process.php
header('Content-Type: application/json');
require_once 'config.php';

checkUserType(['faculty_intern']);

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

$action = $_GET['action'] ?? '';

if ($action == 'submit_report') {
    $courseId = sanitizeInput($_POST['course_id']);
    $sessionId = sanitizeInput($_POST['session_id']);
    $reportDate = sanitizeInput($_POST['report_date']);
    $notes = sanitizeInput($_POST['notes']);
    
    try {
        $stmt = $conn->prepare("INSERT INTO reports (course_id, session_id, report_date, notes, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$courseId, $sessionId, $reportDate, $notes, $userId]);
        echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to submit report']);
    }
}
?>