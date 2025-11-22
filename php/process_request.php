<?php
require_once 'config.php';

if (!isLoggedIn() || getCurrentUserRole() !== 'faculty') {
    sendJsonResponse(['success' => false, 'message' => 'Unauthorized access']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $faculty_id = getCurrentUserId();
    $request_id = intval($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($request_id <= 0) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid request ID']);
    }
    
    if (!in_array($action, ['approve', 'reject'])) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid action']);
    }
    
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT cr.*, c.faculty_id 
                           FROM course_requests cr 
                           JOIN courses c ON cr.course_id = c.course_id 
                           WHERE cr.request_id = ? AND cr.status = 'pending'");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        sendJsonResponse(['success' => false, 'message' => 'Request not found or already processed']);
    }
    
    $request = $result->fetch_assoc();
    $stmt->close();
    
    if ($request['faculty_id'] != $faculty_id) {
        $conn->close();
        sendJsonResponse(['success' => false, 'message' => 'Unauthorized: This is not your course']);
    }
    
    $conn->begin_transaction();
    
    try {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        
        $stmt = $conn->prepare("UPDATE course_requests SET status = ?, processed_at = NOW(), processed_by = ? WHERE request_id = ?");
        $stmt->bind_param("sii", $status, $faculty_id, $request_id);
        $stmt->execute();
        $stmt->close();
        
        if ($action === 'approve') {
            $stmt = $conn->prepare("SELECT * FROM course_student_list WHERE course_id = ? AND student_id = ?");
            $stmt->bind_param("ii", $request['course_id'], $request['student_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                $stmt = $conn->prepare("INSERT INTO course_student_list (course_id, student_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $request['course_id'], $request['student_id']);
                $stmt->execute();
            }
            
            $stmt->close();
        }
        
        $conn->commit();
        
        $message = ($action === 'approve') ? 
            'Request approved! Student has been enrolled in the course.' : 
            'Request rejected.';
        
        sendJsonResponse(['success' => true, 'message' => $message, 'action' => $action]);
        
    } catch (Exception $e) {
        $conn->rollback();
        sendJsonResponse(['success' => false, 'message' => 'Failed to process request: ' . $e->getMessage()]);
    }
    
    $conn->close();
}
?>
