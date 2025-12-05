<?php
require_once 'auth_check.php';

if (getCurrentUserRole() !== 'faculty') {
    header('Location: dashboard.php');
    exit();
}

$conn = getDBConnection();
$user_id = getCurrentUserId();
$message = '';

$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : (isset($_POST['session_id']) ? intval($_POST['session_id']) : 0);

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $session_id = intval($_POST['session_id']);
    $attendance_data = $_POST['attendance'] ?? [];
    
    $conn->begin_transaction();
    
    try {
        foreach ($attendance_data as $student_id => $data) {
            $student_id = intval($student_id);
            $status = $data['status'];
            $check_in_time = !empty($data['check_in_time']) ? $data['check_in_time'] : null;
            $remarks = trim($data['remarks']);
            
            $check_stmt = $conn->prepare("SELECT attendance_id FROM attendance WHERE session_id = ? AND student_id = ?");
            $check_stmt->bind_param("ii", $session_id, $student_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE attendance SET status = ?, check_in_time = ?, remarks = ? WHERE session_id = ? AND student_id = ?");
                $stmt->bind_param("sssii", $status, $check_in_time, $remarks, $session_id, $student_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO attendance (session_id, student_id, status, check_in_time, remarks) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisss", $session_id, $student_id, $status, $check_in_time, $remarks);
            }
            
            $stmt->execute();
            $stmt->close();
            $check_stmt->close();
        }
        
        $conn->commit();
        $message = '<div class="success-message">Attendance recorded successfully!</div>';
    } catch (Exception $e) {
        $conn->rollback();
        $message = '<div class="error-message">Failed to record attendance.</div>';
    }
}

$sessions_query = "SELECT s.session_id, s.date, s.start_time, s.topic, c.course_code, c.course_name 
                   FROM sessions s 
                   JOIN courses c ON s.course_id = c.course_id 
                   WHERE c.faculty_id = $user_id 
                   ORDER BY s.date DESC, s.start_time DESC";
$sessions = $conn->query($sessions_query);

$session_details = null;
$students = [];

if ($session_id > 0) {
    $stmt = $conn->prepare("SELECT s.*, c.course_code, c.course_name, c.course_id 
                           FROM sessions s 
                           JOIN courses c ON s.course_id = c.course_id 
                           WHERE s.session_id = ? AND c.faculty_id = ?");
    $stmt->bind_param("ii", $session_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $session_details = $result->fetch_assoc();
    $stmt->close();
    
    if ($session_details) {
        $students_query = "SELECT u.user_id, u.first_name, u.last_name, u.email,
                          a.attendance_id, a.status, a.check_in_time, a.remarks
                          FROM users u
                          JOIN course_student_list csl ON u.user_id = csl.student_id
                          LEFT JOIN attendance a ON a.student_id = u.user_id AND a.session_id = ?
                          WHERE csl.course_id = ?
                          ORDER BY u.last_name, u.first_name";
        $stmt = $conn->prepare($students_query);
        $stmt->bind_param("ii", $session_id, $session_details['course_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Attendance - Attendance Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>Take Attendance</h1>
        
        <?php echo $message; ?>
        
        <div class="form-section">
            <h2>Select Session</h2>
            <form method="GET" action="">
                <div class="form-group">
                    <label for="session_id">Session</label>
                    <select id="session_id" name="session_id" onchange="this.form.submit()">
                        <option value="">Select a session</option>
                        <?php while ($session = $sessions->fetch_assoc()): ?>
                            <option value="<?php echo $session['session_id']; ?>" <?php echo ($session_id == $session['session_id']) ? 'selected' : ''; ?>>
                                <?php echo date('M d, Y', strtotime($session['date'])) . ' - ' . 
                                         htmlspecialchars($session['course_code']) . ' - ' . 
                                         htmlspecialchars($session['topic']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </div>
        
        <?php if ($session_details && count($students) > 0): ?>
        <div class="session-info">
            <h2>Session Details</h2>
            <p><strong>Course:</strong> <?php echo htmlspecialchars($session_details['course_code'] . ' - ' . $session_details['course_name']); ?></p>
            <p><strong>Topic:</strong> <?php echo htmlspecialchars($session_details['topic']); ?></p>
            <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($session_details['date'])); ?></p>
            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($session_details['start_time'])) . ' - ' . date('h:i A', strtotime($session_details['end_time'])); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($session_details['location']); ?></p>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
            <input type="hidden" name="submit_attendance" value="1">
            
            <div class="table-section">
                <h2>Mark Attendance</h2>
                <table class="data-table attendance-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Check-in Time</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td>
                                <select name="attendance[<?php echo $student['user_id']; ?>][status]" required>
                                    <option value="present" <?php echo ($student['status'] === 'present') ? 'selected' : ''; ?>>Present</option>
                                    <option value="absent" <?php echo ($student['status'] === 'absent') ? 'selected' : ''; ?>>Absent</option>
                                    <option value="late" <?php echo ($student['status'] === 'late') ? 'selected' : ''; ?>>Late</option>
                                </select>
                            </td>
                            <td>
                                <input type="time" name="attendance[<?php echo $student['user_id']; ?>][check_in_time]" 
                                       value="<?php echo $student['check_in_time'] ? $student['check_in_time'] : ''; ?>">
                            </td>
                            <td>
                                <input type="text" name="attendance[<?php echo $student['user_id']; ?>][remarks]" 
                                       value="<?php echo htmlspecialchars($student['remarks'] ?? ''); ?>" 
                                       placeholder="Optional">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Attendance</button>
                <button type="button" class="btn btn-secondary" onclick="markAll('present')">Mark All Present</button>
                <button type="button" class="btn btn-secondary" onclick="markAll('absent')">Mark All Absent</button>
            </div>
        </form>
        <?php elseif ($session_id > 0 && count($students) === 0): ?>
        <div class="error-message">No students enrolled in this course.</div>
        <?php endif; ?>
    </div>
    
    <script src="../js/logout.js"></script>
    <script>
        function markAll(status) {
            const selects = document.querySelectorAll('select[name*="[status]"]');
            selects.forEach(select => {
                select.value = status;
            });
        }
    </script>
</body>
</html>
