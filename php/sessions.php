<?php
require_once 'auth_check.php';

if (getCurrentUserRole() !== 'faculty') {
    header('Location: dashboard.php');
    exit();
}

$conn = getDBConnection();
$user_id = getCurrentUserId();
$role = getCurrentUserRole();

$message = '';

// Handle session creation with attendance code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $course_id = intval($_POST['course_id']);
    $topic = trim($_POST['topic']);
    $location = trim($_POST['location']);
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    // Generate unique 6-digit attendance code
    $attendance_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    $stmt = $conn->prepare("INSERT INTO sessions (course_id, topic, location, date, start_time, end_time, attendance_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $course_id, $topic, $location, $date, $start_time, $end_time, $attendance_code);
    
    if ($stmt->execute()) {
        $message = '<div class="success-message">Session created successfully! Attendance Code: <strong>' . $attendance_code . '</strong></div>';
    } else {
        $message = '<div class="error-message">Failed to create session.</div>';
    }
    $stmt->close();
}

// Handle session deletion
if (isset($_GET['delete'])) {
    $session_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM sessions WHERE session_id = ?");
    $stmt->bind_param("i", $session_id);
    
    if ($stmt->execute()) {
        $message = '<div class="success-message">Session deleted successfully!</div>';
    } else {
        $message = '<div class="error-message">Failed to delete session.</div>';
    }
    $stmt->close();
}

// Get user's courses
if ($role === 'faculty') {
    $courses_query = "SELECT course_id, course_code, course_name FROM courses WHERE faculty_id = $user_id ORDER BY course_code";
} else {
    $courses_query = "SELECT course_id, course_code, course_name FROM courses ORDER BY course_code";
}
$user_courses = $conn->query($courses_query);

// Get sessions with attendance codes
if ($role === 'faculty') {
    $sessions_query = "SELECT s.*, c.course_code, c.course_name, 
                       (SELECT COUNT(*) FROM attendance WHERE session_id = s.session_id) as attendance_count,
                       (SELECT COUNT(*) FROM course_student_list WHERE course_id = s.course_id) as total_students
                       FROM sessions s 
                       JOIN courses c ON s.course_id = c.course_id 
                       WHERE c.faculty_id = $user_id 
                       ORDER BY s.date DESC, s.start_time DESC";
} else {
    $sessions_query = "SELECT s.*, c.course_code, c.course_name,
                       (SELECT COUNT(*) FROM attendance WHERE session_id = s.session_id) as attendance_count,
                       (SELECT COUNT(*) FROM course_student_list WHERE course_id = s.course_id) as total_students
                       FROM sessions s 
                       JOIN courses c ON s.course_id = c.course_id 
                       ORDER BY s.date DESC, s.start_time DESC";
}
$sessions = $conn->query($sessions_query);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessions - Attendance Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>Session Management</h1>
        
        <?php echo $message; ?>
        
        <?php if ($role === 'faculty'): ?>
        <div class="form-section">
            <h2>Create New Session</h2>
            <form method="POST" action="" class="form-horizontal">
                <input type="hidden" name="action" value="create">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="course_id">Course *</label>
                        <select id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php 
                            $user_courses->data_seek(0);
                            while ($course = $user_courses->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $course['course_id']; ?>">
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="topic">Topic *</label>
                        <input type="text" id="topic" name="topic" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date">Date *</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location *</label>
                        <input type="text" id="location" name="location" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_time">Start Time *</label>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_time">End Time *</label>
                        <input type="time" id="end_time" name="end_time" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Session (Auto-generates Attendance Code)</button>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="table-section">
            <h2>All Sessions</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Course</th>
                        <th>Topic</th>
                        <th>Location</th>
                        <th>Code</th>
                        <th>Attendance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sessions->num_rows > 0): ?>
                        <?php while ($session = $sessions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($session['date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($session['start_time'])) . ' - ' . date('h:i A', strtotime($session['end_time'])); ?></td>
                            <td><?php echo htmlspecialchars($session['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($session['topic']); ?></td>
                            <td><?php echo htmlspecialchars($session['location']); ?></td>
                            <td><strong style="font-size: 16px; color: #28a745;"><?php echo $session['attendance_code'] ?? 'N/A'; ?></strong></td>
                            <td><?php echo $session['attendance_count']; ?> / <?php echo $session['total_students']; ?></td>
                            <td>
                                <a href="session_attendance.php?id=<?php echo $session['session_id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                <?php if ($role === 'faculty'): ?>
                                <a href="take_attendance.php?session_id=<?php echo $session['session_id']; ?>" class="btn btn-sm btn-success">Take</a>
                                <a href="?delete=<?php echo $session['session_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No sessions found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="../js/logout.js"></script>
</body>
</html>
