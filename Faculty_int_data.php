<?php
// Faculty_int_data.php
header('Content-Type: application/json');
require_once 'config.php';

checkUserType(['faculty_intern']);

$conn = getDBConnection();
$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'];

$response = ['full_name' => $fullName];

// Get courses
$stmt = $conn->prepare("
    SELECT c.course_id, c.course_name, u.full_name as faculty_name
    FROM intern_assignments ia
    JOIN courses c ON ia.course_id = c.course_id
    LEFT JOIN users u ON c.faculty_id = u.user_id
    WHERE ia.intern_id = ?
");
$stmt->execute([$userId]);
$response['courses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get sessions
$stmt = $conn->prepare("
    SELECT s.session_id, c.course_id, c.course_name, 
           DATE_FORMAT(s.session_date, '%b %d, %Y') as session_date,
           DATE_FORMAT(s.session_time, '%h:%i %p') as session_time,
           s.location
    FROM sessions s
    JOIN courses c ON s.course_id = c.course_id
    JOIN intern_assignments ia ON c.course_id = ia.course_id
    WHERE ia.intern_id = ? AND s.session_date >= CURDATE()
    ORDER BY s.session_date ASC, s.session_time ASC
    LIMIT 10
");
$stmt->execute([$userId]);
$response['sessions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get reports
$stmt = $conn->prepare("
    SELECT r.report_id, DATE_FORMAT(r.report_date, '%b %d, %Y') as report_date,
           c.course_id, c.course_name, r.notes
    FROM reports r
    JOIN courses c ON r.course_id = c.course_id
    WHERE r.created_by = ?
    ORDER BY r.report_date DESC
    LIMIT 10
");
$stmt->execute([$userId]);
$response['reports'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($response);
?>