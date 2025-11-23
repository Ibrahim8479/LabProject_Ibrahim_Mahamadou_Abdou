<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

// Check authentication
if (!isLoggedIn()) {
    sendJsonResponse(['success' => false, 'message' => 'Not logged in']);
}

if (getCurrentUserRole() !== 'faculty') {
    sendJsonResponse(['success' => false, 'message' => 'Only faculty can process requests']);
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $faculty_id = getCurrentUserId();
    $request_id = intval($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    // Log for debugging
    error_log("Processing request - ID: $request_id, Action: $action, Faculty: $faculty_id");
    
    if ($request_id <= 0) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid request ID: ' . $request_id]);
    }
    
    if (!in_array($action, ['approve', 'reject'])) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid action: ' . $action]);
    }
    
    try {
        $conn = getDBConnection();
        
        // Get request details
        $stmt = $conn->prepare("SELECT cr.request_id, cr.course_id, cr.student_id, cr.status, c.faculty_id, c.course_name
                               FROM course_requests cr 
                               JOIN courses c ON cr.course_id = c.course_id 
                               WHERE cr.request_id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            $conn->close();
            sendJsonResponse(['success' => false, 'message' => 'Request not found']);
        }
        
        $request = $result->fetch_assoc();
        $stmt->close();
        
        // Check if already processed
        if ($request['status'] !== 'pending') {
            $conn->close();
            sendJsonResponse(['success' => false, 'message' => 'Request already ' . $request['status']]);
        }
        
        // Check if this faculty owns the course
        if ($request['faculty_id'] != $faculty_id) {
            $conn->close();
            sendJsonResponse(['success' => false, 'message' => 'This is not your course']);
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        
        // Update request status
        $stmt = $conn->prepare("UPDATE course_requests SET status = ?, processed_at = NOW(), processed_by = ? WHERE request_id = ?");
        $stmt->bind_param("sii", $status, $faculty_id, $request_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update request status');
        }
        $stmt->close();
        
        // If approved, enroll student
        if ($action === 'approve') {
            // Check if already enrolled
            $stmt = $conn->prepare("SELECT * FROM course_student_list WHERE course_id = ? AND student_id = ?");
            $stmt->bind_param("ii", $request['course_id'], $request['student_id']);
            $stmt->execute();
            $check = $stmt->get_result();
            
            if ($check->num_rows === 0) {
                $stmt->close();
                
                // Enroll student
                $stmt = $conn->prepare("INSERT INTO course_student_list (course_id, student_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $request['course_id'], $request['student_id']);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to enroll student');
                }
            }
            $stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        $conn->close();
        
        $message = ($action === 'approve') ? 
            'Request approved! Student enrolled successfully.' : 
            'Request rejected.';
        
        sendJsonResponse([
            'success' => true, 
            'message' => $message,
            'action' => $action
        ]);
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
            $conn->close();
        }
        error_log("Error processing request: " . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    sendJsonResponse(['success' => false, 'message' => 'Invalid request method']);
}
?>
