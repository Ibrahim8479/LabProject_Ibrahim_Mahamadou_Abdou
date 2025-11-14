<?php
// Faculty_data.php
header('Content-Type: application/json');
require_once 'config.php';

checkUserType(['faculty']);

$conn = getDBConnection();
$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'];

$response = ['full_name' => $fullName];

// Get courses
$stmt = $conn->prepare("
    SELECT c.course_id, c.course_name, COUNT(sc.student_id) as student_count
    FROM courses c
    LEFT JOIN student_courses sc ON c.course_id = sc.course_id
    WHERE c.faculty_id = ?
    GROUP BY c.course_id, c.course_name
");
$stmt->execute([$userId]);
$response['courses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get sessions
$stmt = $conn->prepare("
    SELECT s.session_id, c.course_name, 
           DATE_FORMAT(s.session_date, '%b %d, %Y') as session_date,
           DATE_FORMAT(s.session_time, '%h:%i %p') as session_time,
           ROUND((COUNT(CASE WHEN a.status = 'present' THEN 1 END) * 100.0 / NULLIF(COUNT(a.attendance_id), 0)), 0) as attendance_rate
    FROM sessions s
    JOIN courses c ON s.course_id = c.course_id
    LEFT JOIN attendance a ON s.session_id = a.session_id
    WHERE c.faculty_id = ?
    GROUP BY s.session_id
    ORDER BY s.session_date DESC, s.session_time DESC
    LIMIT 10
");
$stmt->execute([$userId]);
$response['sessions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$stmt = $conn->prepare("
    SELECT 
        ROUND(AVG(CASE WHEN a.status = 'present' THEN 100 ELSE 0 END), 0) as avg_attendance,
        COUNT(DISTINCT CASE WHEN att_rate.rate < 75 THEN att_rate.student_id END) as at_risk,
        COUNT(DISTINCT CASE WHEN att_rate.rate = 100 THEN att_rate.student_id END) as perfect
    FROM (
        SELECT a.student_id, 
               (COUNT(CASE WHEN a.status = 'present' THEN 1 END) * 100.0 / COUNT(*)) as rate
        FROM attendance a
        JOIN sessions s ON a.session_id = s.session_id
        JOIN courses c ON s.course_id = c.course_id
        WHERE c.faculty_id = ?
        GROUP BY a.student_id
    ) att_rate
");
$stmt->execute([$userId]);
$response['stats'] = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($response);
?>