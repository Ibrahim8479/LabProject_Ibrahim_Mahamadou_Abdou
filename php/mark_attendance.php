<?php
require_once 'auth_check.php';

if (getCurrentUserRole() !== 'student') {
    header('Location: dashboard.php');
    exit();
}

$conn = getDBConnection();
$student_id = getCurrentUserId();
$message = '';

// Handle attendance marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $attendance_code = trim($_POST['attendance_code']);
    
    if (empty($attendance_code)) {
        $message = '<div class="error-message">Please enter an attendance code.</div>';
    } else {
        // Find session with this code
        $stmt = $conn->prepare("SELECT s.session_id, s.course_id, s.topic, s.date, s.start_time, s.end_time, c.course_name, c.course_code
                               FROM sessions s
                               JOIN courses c ON s.course_id = c.course_id
                               WHERE s.attendance_code = ?");
        $stmt->bind_param("s", $attendance_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $message = '<div class="error-message">Invalid attendance code. Please check and try again.</div>';
        } else {
            $session = $result->fetch_assoc();
            $session_id = $session['session_id'];
            $course_id = $session['course_id'];
            
            // Check if student is enrolled in this course
            $stmt->close();
            $stmt = $conn->prepare("SELECT * FROM course_student_list WHERE course_id = ? AND student_id = ?");
            $stmt->bind_param("ii", $course_id, $student_id);
            $stmt->execute();
            $enrollment = $stmt->get_result();
            
            if ($enrollment->num_rows === 0) {
                $message = '<div class="error-message">You are not enrolled in this course.</div>';
            } else {
                // Check if already marked attendance
                $stmt->close();
                $stmt = $conn->prepare("SELECT * FROM attendance WHERE session_id = ? AND student_id = ?");
                $stmt->bind_param("ii", $session_id, $student_id);
                $stmt->execute();
                $existing = $stmt->get_result();
                
                if ($existing->num_rows > 0) {
                    $message = '<div class="error-message">You have already marked attendance for this session.</div>';
                } else {
                    // Mark attendance
                    $check_in_time = date('H:i:s');
                    $status = 'present';
                    
                    // Check if late (more than 15 minutes after start time)
                    $start_time = strtotime($session['date'] . ' ' . $session['start_time']);
                    $current_time = time();
                    $time_diff = ($current_time - $start_time) / 60; // in minutes
                    
                    if ($time_diff > 15) {
                        $status = 'late';
                    }
                    
                    $stmt->close();
                    $stmt = $conn->prepare("INSERT INTO attendance (session_id, student_id, status, check_in_time) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiss", $session_id, $student_id, $status, $check_in_time);
                    
                    if ($stmt->execute()) {
                        $status_text = $status === 'present' ? 'Present' : 'Late';
                        $message = '<div class="success-message">Attendance marked successfully! Status: <strong>' . $status_text . '</strong><br>
                                   Course: ' . htmlspecialchars($session['course_code']) . ' - ' . htmlspecialchars($session['course_name']) . '<br>
                                   Topic: ' . htmlspecialchars($session['topic']) . '<br>
                                   Date: ' . date('M d, Y', strtotime($session['date'])) . '</div>';
                    } else {
                        $message = '<div class="error-message">Failed to mark attendance. Please try again.</div>';
                    }
                }
            }
        }
        $stmt->close();
    }
}

// Get today's sessions for enrolled courses
$today = date('Y-m-d');
$today_sessions_query = "SELECT s.*, c.course_code, c.course_name,
                         (SELECT COUNT(*) FROM attendance WHERE session_id = s.session_id AND student_id = $student_id) as marked
                         FROM sessions s
                         JOIN courses c ON s.course_id = c.course_id
                         JOIN course_student_list csl ON c.course_id = csl.course_id
                         WHERE csl.student_id = $student_id 
                         AND s.date = '$today'
                         ORDER BY s.start_time";
$today_sessions = $conn->query($today_sessions_query);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Student Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>Mark My Attendance</h1>
        
        <?php echo $message; ?>
        
        <div class="form-section">
            <h2>Enter Attendance Code</h2>
            <p style="color: #666; margin-bottom: 20px;">Enter the 6-digit code provided by your instructor to mark your attendance.</p>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="attendance_code">Attendance Code *</label>
                    <input type="text" 
                           id="attendance_code" 
                           name="attendance_code" 
                           placeholder="Enter 6-digit code" 
                           maxlength="6"
                           pattern="[0-9]{6}"
                           required
                           style="font-size: 24px; text-align: center; letter-spacing: 5px;">
                </div>
                
                <button type="submit" name="mark_attendance" class="btn btn-primary">Mark Attendance</button>
            </form>
        </div>
        
        <?php if ($today_sessions->num_rows > 0): ?>
        <div class="table-section">
            <h2>Today's Sessions (<?php echo date('F d, Y'); ?>)</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Course</th>
                        <th>Topic</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($session = $today_sessions->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('h:i A', strtotime($session['start_time'])) . ' - ' . date('h:i A', strtotime($session['end_time'])); ?></td>
                        <td><?php echo htmlspecialchars($session['course_code'] . ' - ' . $session['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($session['topic']); ?></td>
                        <td><?php echo htmlspecialchars($session['location']); ?></td>
                        <td>
                            <?php if ($session['marked'] > 0): ?>
                                <span class="status-badge status-present">Marked</span>
                            <?php else: ?>
                                <span class="status-badge" style="background-color: #fff3cd; color: #856404;">Not Marked</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="background: white; padding: 25px; border-radius: 12px; text-align: center; color: #666;">
            <p>You have no sessions scheduled for today.</p>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="my_attendance.php" class="btn btn-secondary">View My Attendance History</a>
        </div>
    </div>
    
    <script src="../js/logout.js"></script>
</body>
</html>
