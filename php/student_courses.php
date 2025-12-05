<?php
require_once 'auth_check.php';

if (getCurrentUserRole() !== 'student') {
    header('Location: dashboard.php');
    exit();
}

$conn = getDBConnection();
$student_id = getCurrentUserId();

$query = "SELECT c.*, 
          CONCAT(u.first_name, ' ', u.last_name) as faculty_name,
          (SELECT COUNT(*) FROM sessions WHERE course_id = c.course_id) as total_sessions,
          (SELECT COUNT(*) FROM attendance a 
           JOIN sessions s ON a.session_id = s.session_id 
           WHERE s.course_id = c.course_id AND a.student_id = $student_id) as attended_sessions,
          (SELECT COUNT(*) FROM attendance a 
           JOIN sessions s ON a.session_id = s.session_id 
           WHERE s.course_id = c.course_id AND a.student_id = $student_id AND a.status = 'present') as present_sessions
          FROM courses c
          JOIN course_student_list csl ON c.course_id = csl.course_id
          JOIN users u ON c.faculty_id = u.user_id
          WHERE csl.student_id = $student_id
          ORDER BY c.course_code";

$courses = $conn->query($query);

$pending_query = "SELECT cr.*, c.course_code, c.course_name, CONCAT(u.first_name, ' ', u.last_name) as faculty_name
                  FROM course_requests cr
                  JOIN courses c ON cr.course_id = c.course_id
                  JOIN users u ON c.faculty_id = u.user_id
                  WHERE cr.student_id = $student_id AND cr.status = 'pending'
                  ORDER BY cr.requested_at DESC";
$pending_requests = $conn->query($pending_query);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Student Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1>My Courses</h1>
            <a href="search_courses.php" class="btn btn-primary">+ Join New Course</a>
        </div>
        
        <?php if ($pending_requests->num_rows > 0): ?>
        <div class="table-section" style="background-color: #fff3cd; border-left: 4px solid #f39c12;">
            <h2>Pending Course Requests</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Faculty</th>
                        <th>Requested Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($request = $pending_requests->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($request['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($request['faculty_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <?php if ($courses->num_rows > 0): ?>
            <div class="stats-grid">
                <?php while ($course = $courses->fetch_assoc()): 
                    $attendance_rate = $course['attended_sessions'] > 0 ? 
                        round(($course['present_sessions'] / $course['attended_sessions']) * 100, 2) : 0;
                ?>
                <div class="stat-card" style="text-align: left;">
                    <h3><?php echo htmlspecialchars($course['course_code']); ?></h3>
                    <h4 style="color: #2c3e50; margin: 0.5rem 0;"><?php echo htmlspecialchars($course['course_name']); ?></h4>
                    <p style="color: #7f8c8d; font-size: 0.9rem; margin: 0.5rem 0;">
                        <strong>Faculty:</strong> <?php echo htmlspecialchars($course['faculty_name']); ?>
                    </p>
                    <p style="color: #7f8c8d; font-size: 0.9rem; margin: 0.5rem 0;">
                        <strong>Credit Hours:</strong> <?php echo $course['credit_hours']; ?>
                    </p>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ecf0f1;">
                        <p style="font-size: 0.9rem; margin: 0.25rem 0;">
                            <strong>Sessions:</strong> <?php echo $course['attended_sessions']; ?> / <?php echo $course['total_sessions']; ?>
                        </p>
                        <p style="font-size: 0.9rem; margin: 0.25rem 0;">
                            <strong>Attendance:</strong> 
                            <span style="color: <?php echo $attendance_rate >= 75 ? '#27ae60' : ($attendance_rate >= 50 ? '#f39c12' : '#e74c3c'); ?>; font-weight: bold;">
                                <?php echo $attendance_rate; ?>%
                            </span>
                        </p>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="error-message">
                You are not enrolled in any courses yet. Click "Join New Course" to browse available courses.
            </div>
        <?php endif; ?>
    </div>
    
    <script src="../js/logout.js"></script>
</body>
</html>
