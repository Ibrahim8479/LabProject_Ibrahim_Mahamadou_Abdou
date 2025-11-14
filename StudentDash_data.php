<?php
// StudentDash_data.php
header('Content-Type: application/json');
require_once 'config.php';

checkUserType(['student']);

$conn = getDBConnection();
$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'];

$response = ['full_name' => $fullName];

// Get courses
$stmt = $conn->prepare("
    SELECT c.course_id, c.course_name,
           COUNT(DISTINCT s.session_id) as total_sessions,
           COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.session_id END) as attended_sessions,
           ROUND((COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.session_id END) * 100.0 / 
                  NULLIF(COUNT(DISTINCT s.session_id), 0)), 0) as attendance_rate
    FROM student_courses sc
    JOIN courses c ON sc.course_id = c.course_id
    LEFT JOIN sessions s ON c.course_id = s.course_id
    LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = ?
    WHERE sc.student_id = ?
    GROUP BY c.course_id, c.course_name
");
$stmt->execute([$userId, $userId]);
$response['courses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get schedule
$stmt = $conn->prepare("
    SELECT c.course_id, c.course_name, 
           DATE_FORMAT(s.session_date, '%b %d, %Y') as session_date, 
           DATE_FORMAT(s.session_time, '%h:%i %p') as session_time, 
           s.location
    FROM sessions s
    JOIN courses c ON s.course_id = c.course_id
    JOIN student_courses sc ON c.course_id = sc.course_id
    WHERE sc.student_id = ? AND s.session_date >= CURDATE()
    ORDER BY s.session_date ASC, s.session_time ASC
    LIMIT 10
");
$stmt->execute([$userId]);
$response['schedule'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get grades
$stmt = $conn->prepare("
    SELECT c.course_id, c.course_name,
           ROUND((COUNT(CASE WHEN a.status = 'present' THEN 1 END) * 100.0 / 
                  NULLIF(COUNT(*), 0)), 0) as attendance_rate,
           CASE 
               WHEN COUNT(CASE WHEN a.status = 'present' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0) >= 85 THEN 'Good'
               WHEN COUNT(CASE WHEN a.status = 'present' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0) >= 75 THEN 'Average'
               ELSE 'Poor'
           END as participation,
           CASE 
               WHEN COUNT(CASE WHEN a.status = 'present' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0) >= 75 THEN 'On Track'
               ELSE 'Needs Attention'
           END as status
    FROM student_courses sc
    JOIN courses c ON sc.course_id = c.course_id
    LEFT JOIN sessions s ON c.course_id = s.course_id
    LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = ?
    WHERE sc.student_id = ?
    GROUP BY c.course_id, c.course_name
");
$stmt->execute([$userId, $userId]);
$response['grades'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($response);
?>