<?php
require_once 'auth_check.php';

$conn = getDBConnection();
$user_id = getCurrentUserId();
$role = getCurrentUserRole();

$stats = [];

if ($role === 'student') {
    $result = $conn->query("SELECT COUNT(*) as count FROM course_student_list WHERE student_id = $user_id");
    $stats['enrolled_courses'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM course_requests WHERE student_id = $user_id AND status = 'pending'");
    $stats['pending_requests'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE student_id = $user_id AND status = 'present'");
    $stats['sessions_attended'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE student_id = $user_id");
    $total_sessions = $result->fetch_assoc()['total'];
    $stats['attendance_percentage'] = $total_sessions > 0 ? round(($stats['sessions_attended'] / $total_sessions) * 100, 2) : 0;
    
} elseif ($role === 'faculty') {
    $result = $conn->query("SELECT COUNT(*) as count FROM courses WHERE faculty_id = $user_id");
    $stats['courses_teaching'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM course_requests cr JOIN courses c ON cr.course_id = c.course_id WHERE c.faculty_id = $user_id AND cr.status = 'pending'");
    $stats['pending_requests'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM sessions s JOIN courses c ON s.course_id = c.course_id WHERE c.faculty_id = $user_id");
    $stats['total_sessions'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(DISTINCT csl.student_id) as count FROM course_student_list csl JOIN courses c ON csl.course_id = c.course_id WHERE c.faculty_id = $user_id");
    $stats['total_students'] = $result->fetch_assoc()['count'];
    
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Attendance Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
        <p style="color: #7f8c8d;">Role: <?php echo ucfirst($role); ?></p>
        
        <div class="stats-grid">
            <?php if ($role === 'student'): ?>
                <div class="stat-card">
                    <h3>Enrolled Courses</h3>
                    <p class="stat-number"><?php echo $stats['enrolled_courses']; ?></p>
                </div>
                <div class="stat-card stat-warning">
                    <h3>Pending Requests</h3>
                    <p class="stat-number"><?php echo $stats['pending_requests']; ?></p>
                </div>
                <div class="stat-card stat-success">
                    <h3>Sessions Attended</h3>
                    <p class="stat-number"><?php echo $stats['sessions_attended']; ?></p>
                </div>
                <div class="stat-card stat-info">
                    <h3>Attendance Rate</h3>
                    <p class="stat-number"><?php echo $stats['attendance_percentage']; ?>%</p>
                </div>
            <?php elseif ($role === 'faculty'): ?>
                <div class="stat-card">
                    <h3>Courses Teaching</h3>
                    <p class="stat-number"><?php echo $stats['courses_teaching']; ?></p>
                </div>
                <div class="stat-card stat-warning">
                    <h3>Pending Requests</h3>
                    <p class="stat-number"><?php echo $stats['pending_requests']; ?></p>
                </div>
                <div class="stat-card stat-info">
                    <h3>Total Sessions</h3>
                    <p class="stat-number"><?php echo $stats['total_sessions']; ?></p>
                </div>
                <div class="stat-card stat-success">
                    <h3>Total Students</h3>
                    <p class="stat-number"><?php echo $stats['total_students']; ?></p>
                </div>
    
            <?php endif; ?>
        </div>
        
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="action-buttons">
                <?php if ($role === 'student'): ?>
                    <a href="student_courses.php" class="btn btn-primary">My Courses</a>
                    <a href="search_courses.php" class="btn btn-success">Join Courses</a>
                <?php elseif ($role === 'faculty'): ?>
                    <a href="faculty_courses.php" class="btn btn-primary">My Courses</a>
                    <a href="course_requests.php" class="btn btn-warning">Course Requests (<?php echo $stats['pending_requests']; ?>)</a>

                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../js/logout.js"></script>
</body>
</html>
