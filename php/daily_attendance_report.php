<?php
require_once 'auth_check.php';

$conn = getDBConnection();
$user_id = getCurrentUserId();
$role = getCurrentUserRole();

// Get date filter
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get course filter
$course_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

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
} else {
    $courses_query = "SELECT course_id, course_code, course_name 
                     FROM courses 
                     ORDER BY course_code";
}
$courses = $conn->query($courses_query);

// Build query based on role
if ($role === 'student') {
    // Student view: their own attendance for the day
    $report_query = "SELECT s.session_id, s.topic, s.start_time, s.end_time, s.location,
                     c.course_code, c.course_name,
                     a.status, a.check_in_time, a.remarks
                     FROM sessions s
                     JOIN courses c ON s.course_id = c.course_id
                     LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = $user_id
                     WHERE s.date = '$selected_date'";
    
    if ($course_filter > 0) {
        $report_query .= " AND c.course_id = $course_filter";
    }
    
    $report_query .= " ORDER BY s.start_time";
    
} else {
    // Faculty/Admin view: all students' attendance for the day
    $report_query = "SELECT s.session_id, s.topic, s.start_time, s.end_time, s.location,
                     c.course_code, c.course_name,
                     u.user_id, u.first_name, u.last_name, u.email,
                     a.status, a.check_in_time, a.remarks
                     FROM sessions s
                     JOIN courses c ON s.course_id = c.course_id
                     LEFT JOIN course_student_list csl ON c.course_id = csl.course_id
                     LEFT JOIN users u ON csl.student_id = u.user_id
                     LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = u.user_id
                     WHERE s.date = '$selected_date'";
    
    if ($role === 'faculty') {
        $report_query .= " AND c.faculty_id = $user_id";
    }
    
    if ($course_filter > 0) {
        $report_query .= " AND c.course_id = $course_filter";
    }
    
    $report_query .= " ORDER BY s.start_time, c.course_code, u.last_name, u.first_name";
}

$report = $conn->query($report_query);

// Calculate statistics
$stats = [
    'total_sessions' => 0,
    'total_students' => 0,
    'present' => 0,
    'absent' => 0,
    'late' => 0
];

if ($role === 'student') {
    $report->data_seek(0);
    while ($row = $report->fetch_assoc()) {
        $stats['total_sessions']++;
        if ($row['status'] === 'present') $stats['present']++;
        elseif ($row['status'] === 'absent') $stats['absent']++;
        elseif ($row['status'] === 'late') $stats['late']++;
    }
} else {
    $report->data_seek(0);
    while ($row = $report->fetch_assoc()) {
        if ($row['user_id']) {
            $stats['total_students']++;
            if ($row['status'] === 'present') $stats['present']++;
            elseif ($row['status'] === 'absent') $stats['absent']++;
            elseif ($row['status'] === 'late') $stats['late']++;
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Attendance Report</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>Daily Attendance Report</h1>
        
        <div class="filter-section">
            <form method="GET" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="date">Select Date</label>
                        <input type="date" id="date" name="date" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">
                    </div>
                    
                    <div class="form-group">
                        <label for="course_id">Filter by Course</label>
                        <select id="course_id" name="course_id" onchange="this.form.submit()">
                            <option value="0">All Courses</option>
                            <?php 
                            $courses->data_seek(0);
                            while ($course = $courses->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $course['course_id']; ?>" <?php echo ($course_filter == $course['course_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="button" onclick="document.getElementById('date').value='<?php echo date('Y-m-d'); ?>'; this.form.submit();" class="btn btn-secondary">Today</button>
                    <?php if ($course_filter > 0): ?>
                    <a href="?date=<?php echo $selected_date; ?>" class="btn btn-secondary">Clear Course Filter</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="stats-grid">
            <?php if ($role === 'student'): ?>
            <div class="stat-card">
                <h3>Total Sessions</h3>
                <p class="stat-number"><?php echo $stats['total_sessions']; ?></p>
            </div>
            <?php else: ?>
            <div class="stat-card">
                <h3>Total Students</h3>
                <p class="stat-number"><?php echo $stats['total_students']; ?></p>
            </div>
            <?php endif; ?>
            
            <div class="stat-card stat-success">
                <h3>Present</h3>
                <p class="stat-number"><?php echo $stats['present']; ?></p>
            </div>
            <div class="stat-card stat-warning">
                <h3>Late</h3>
                <p class="stat-number"><?php echo $stats['late']; ?></p>
            </div>
            <div class="stat-card stat-danger">
                <h3>Absent</h3>
                <p class="stat-number"><?php echo $stats['absent']; ?></p>
            </div>
        </div>
        
        <div class="table-section">
            <h2>Attendance Records for <?php echo date('F d, Y', strtotime($selected_date)); ?></h2>
            
            <?php if ($report->num_rows > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Course</th>
                        <th>Topic</th>
                        <th>Location</th>
                        <?php if ($role !== 'student'): ?>
                        <th>Student</th>
                        <?php endif; ?>
                        <th>Status</th>
                        <th>Check-in Time</th>
                        <?php if ($role !== 'student'): ?>
                        <th>Remarks</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $report->data_seek(0);
                    while ($row = $report->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></td>
                        <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['topic']); ?></td>
                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                        <?php if ($role !== 'student'): ?>
                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                        <?php endif; ?>
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
                        <?php if ($role !== 'student'): ?>
                        <td><?php echo htmlspecialchars($row['remarks'] ?? '-'); ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="text-center" style="padding: 20px; color: #666;">No sessions or attendance records found for this date.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../js/logout.js"></script>
</body>
</html>
