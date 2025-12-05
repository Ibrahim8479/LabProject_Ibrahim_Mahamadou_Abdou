<?php
require_once 'auth_check.php';

$conn = getDBConnection();
$user_id = getCurrentUserId();
$role = getCurrentUserRole();

// Get course selection
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Get courses based on role
if ($role === 'student') {
    $courses_query = "SELECT c.course_id, c.course_code, c.course_name 
                     FROM courses c 
                     JOIN course_student_list csl ON c.course_id = csl.course_id 
                     WHERE csl.student_id = $user_id 
                     ORDER BY c.course_code";
} elseif ($role === 'faculty') {
    $courses_query = "SELECT course_id, course_code, course_name 
                     FROM courses 
                     WHERE faculty_id = $user_id 
                     ORDER BY course_code";
}
$courses = $conn->query($courses_query);

$course_details = null;
$attendance_data = [];
$summary_stats = [];

if ($course_id > 0) {
    // Get course details
    $stmt = $conn->prepare("SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as faculty_name
                           FROM courses c
                           JOIN users u ON c.faculty_id = u.user_id
                           WHERE c.course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $course_details = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($role === 'student') {
        // Student view: their attendance for this course
        $query = "SELECT s.session_id, s.date, s.topic, s.start_time, s.end_time,
                  a.status, a.check_in_time
                  FROM sessions s
                  LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = $user_id
                  WHERE s.course_id = $course_id
                  ORDER BY s.date DESC, s.start_time DESC";
        
        $attendance_data = $conn->query($query);
        
        // Calculate student summary
        $summary_query = "SELECT 
                         COUNT(DISTINCT s.session_id) as total_sessions,
                         SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                         SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
                         SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
                         SUM(CASE WHEN a.status IS NULL THEN 1 ELSE 0 END) as not_marked
                         FROM sessions s
                         LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = $user_id
                         WHERE s.course_id = $course_id";
        
        $summary_stats = $conn->query($summary_query)->fetch_assoc();
        
    } else {
        // Faculty view: all students' attendance
        $query = "SELECT u.user_id, u.first_name, u.last_name, u.email,
                  COUNT(DISTINCT s.session_id) as total_sessions,
                  SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                  SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
                  SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
                  ROUND((SUM(CASE WHEN a.status IN ('present', 'late') THEN 1 ELSE 0 END) / COUNT(DISTINCT s.session_id)) * 100, 2) as attendance_rate
                  FROM course_student_list csl
                  JOIN users u ON csl.student_id = u.user_id
                  LEFT JOIN sessions s ON csl.course_id = s.course_id
                  LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = u.user_id
                  WHERE csl.course_id = $course_id
                  GROUP BY u.user_id
                  ORDER BY u.last_name, u.first_name";
        
        $attendance_data = $conn->query($query);
        
        // Get overall course statistics
        $summary_query = "SELECT 
                         COUNT(DISTINCT s.session_id) as total_sessions,
                         COUNT(DISTINCT csl.student_id) as total_students,
                         SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                         SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
                         SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent
                         FROM sessions s
                         CROSS JOIN course_student_list csl ON s.course_id = csl.course_id
                         LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = csl.student_id
                         WHERE s.course_id = $course_id";
        
        $summary_stats = $conn->query($summary_query)->fetch_assoc();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Attendance Report</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>Overall Course Attendance Report</h1>
        
        <div class="filter-section">
            <form method="GET" action="">
                <div class="form-group">
                    <label for="course_id">Select Course</label>
                    <div style="display: flex; gap: 1rem;">
                        <select id="course_id" name="course_id" onchange="this.form.submit()" required>
                            <option value="0">-- Select a Course --</option>
                            <?php 
                            $courses->data_seek(0);
                            while ($course = $courses->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $course['course_id']; ?>" <?php echo ($course_id == $course['course_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        
                        <?php if ($course_id > 0): ?>
                        <a href="course_attendance_report.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if ($course_id > 0 && $course_details): ?>
        
        <div class="session-info">
            <h2>Course Information</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div>
                    <p><strong>Course Code:</strong> <?php echo htmlspecialchars($course_details['course_code']); ?></p>
                    <p><strong>Course Name:</strong> <?php echo htmlspecialchars($course_details['course_name']); ?></p>
                </div>
                <div>
                    <p><strong>Faculty:</strong> <?php echo htmlspecialchars($course_details['faculty_name']); ?></p>
                    <p><strong>Credit Hours:</strong> <?php echo $course_details['credit_hours']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="stats-grid">
            <?php if ($role === 'student'): ?>
            <div class="stat-card">
                <h3>Total Sessions</h3>
                <p class="stat-number"><?php echo $summary_stats['total_sessions']; ?></p>
            </div>
            <div class="stat-card stat-success">
                <h3>Present</h3>
                <p class="stat-number"><?php echo $summary_stats['present']; ?></p>
            </div>
            <div class="stat-card stat-warning">
                <h3>Late</h3>
                <p class="stat-number"><?php echo $summary_stats['late']; ?></p>
            </div>
            <div class="stat-card stat-danger">
                <h3>Absent</h3>
                <p class="stat-number"><?php echo $summary_stats['absent']; ?></p>
            </div>
            <div class="stat-card stat-info">
                <h3>Attendance Rate</h3>
                <p class="stat-number">
                    <?php 
                    $rate = $summary_stats['total_sessions'] > 0 ? 
                        round((($summary_stats['present'] + $summary_stats['late']) / $summary_stats['total_sessions']) * 100, 2) : 0;
                    echo $rate;
                    ?>%
                </p>
            </div>
            <?php else: ?>
            <div class="stat-card">
                <h3>Total Sessions</h3>
                <p class="stat-number"><?php echo $summary_stats['total_sessions']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Students</h3>
                <p class="stat-number"><?php echo $summary_stats['total_students']; ?></p>
            </div>
            <div class="stat-card stat-success">
                <h3>Total Present</h3>
                <p class="stat-number"><?php echo $summary_stats['present']; ?></p>
            </div>
            <div class="stat-card stat-warning">
                <h3>Total Late</h3>
                <p class="stat-number"><?php echo $summary_stats['late']; ?></p>
            </div>
            <div class="stat-card stat-danger">
                <h3>Total Absent</h3>
                <p class="stat-number"><?php echo $summary_stats['absent']; ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="table-section">
            <?php if ($role === 'student'): ?>
            <h2>My Session-by-Session Attendance</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Topic</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Check-in Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($attendance_data->num_rows > 0): ?>
                        <?php while ($row = $attendance_data->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['topic']); ?></td>
                            <td><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></td>
                            <td>
                                <?php if ($row['status']): ?>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge" style="background-color: #e0e0e0; color: #666;">Not Marked</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['check_in_time'] ? date('h:i A', strtotime($row['check_in_time'])) : '-'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No sessions found for this course.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php else: ?>
            <h2>Student Attendance Summary</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Total Sessions</th>
                        <th>Present</th>
                        <th>Late</th>
                        <th>Absent</th>
                        <th>Attendance Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($attendance_data->num_rows > 0): ?>
                        <?php while ($row = $attendance_data->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo $row['total_sessions']; ?></td>
                            <td><?php echo $row['present']; ?></td>
                            <td><?php echo $row['late']; ?></td>
                            <td><?php echo $row['absent']; ?></td>
                            <td>
                                <span style="font-weight: bold; color: <?php echo $row['attendance_rate'] >= 75 ? '#27ae60' : ($row['attendance_rate'] >= 50 ? '#f39c12' : '#e74c3c'); ?>">
                                    <?php echo $row['attendance_rate'] ?? 0; ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No students enrolled in this course.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
        <div style="background: white; padding: 40px; border-radius: 12px; text-align: center; color: #666;">
            <p>Please select a course to view the attendance report.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="../js/logout.js"></script>
</body>
</html>
