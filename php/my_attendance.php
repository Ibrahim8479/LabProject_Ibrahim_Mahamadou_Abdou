<?php
require_once 'auth_check.php';

if (getCurrentUserRole() !== 'student') {
    header('Location: dashboard.php');
    exit();
}

$conn = getDBConnection();
$student_id = getCurrentUserId();

$course_filter = isset($_GET['course']) ? intval($_GET['course']) : 0;

$courses_query = "SELECT c.course_id, c.course_code, c.course_name 
                 FROM courses c 
                 JOIN course_student_list csl ON c.course_id = csl.course_id 
                 WHERE csl.student_id = $student_id 
                 ORDER BY c.course_code";
$courses = $conn->query($courses_query);

$attendance_query = "SELECT a.*, s.date, s.start_time, s.end_time, s.topic, s.location,
                     c.course_code, c.course_name
                     FROM attendance a
                     JOIN sessions s ON a.session_id = s.session_id
                     JOIN courses c ON s.course_id = c.course_id
                     WHERE a.student_id = $student_id";

if ($course_filter > 0) {
    $attendance_query .= " AND c.course_id = $course_filter";
}

$attendance_query .= " ORDER BY s.date DESC, s.start_time DESC";
$attendance_records = $conn->query($attendance_query);

$stats_query = "SELECT 
                COUNT(*) as total_sessions,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
                FROM attendance a
                JOIN sessions s ON a.session_id = s.session_id
                JOIN courses c ON s.course_id = c.course_id
                WHERE a.student_id = $student_id";

if ($course_filter > 0) {
    $stats_query .= " AND c.course_id = $course_filter";
}

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
$attendance_percentage = $stats['total_sessions'] > 0 ? 
    round((($stats['present_count'] + $stats['late_count']) / $stats['total_sessions']) * 100, 2) : 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Attendance Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>My Attendance</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Sessions</h3>
                <p class="stat-number"><?php echo $stats['total_sessions']; ?></p>
            </div>
            <div class="stat-card stat-success">
                <h3>Present</h3>
                <p class="stat-number"><?php echo $stats['present_count']; ?></p>
            </div>
            <div class="stat-card stat-warning">
                <h3>Late</h3>
                <p class="stat-number"><?php echo $stats['late_count']; ?></p>
            </div>
            <div class="stat-card stat-danger">
                <h3>Absent</h3>
                <p class="stat-number"><?php echo $stats['absent_count']; ?></p>
            </div>
            <div class="stat-card stat-info">
                <h3>Attendance Rate</h>

</body>
</html>
