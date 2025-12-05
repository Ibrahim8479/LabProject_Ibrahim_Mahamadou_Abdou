<?php
require_once 'auth_check.php';

$conn = getDBConnection();
$session_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$session_query = "SELECT s.*, c.course_code, c.course_name, c.course_id
                 FROM sessions s
                 JOIN courses c ON s.course_id = c.course_id
                 WHERE s.session_id = $session_id";
$session_result = $conn->query($session_query);

if ($session_result->num_rows === 0) {
    header('Location: sessions.php');
    exit();
}

$session = $session_result->fetch_assoc();

$role = getCurrentUserRole();
$user_id = getCurrentUserId();

if ($role === 'faculty') {
    $verify = $conn->query("SELECT * FROM courses WHERE course_id = {$session['course_id']} AND faculty_id = $user_id");
    if ($verify->num_rows === 0) {
        header('Location: dashboard.php');
        exit();
    }
}

$attendance_query = "SELECT a.*, u.first_name, u.last_name, u.email
                    FROM attendance a
                    JOIN users u ON a.student_id = u.user_id
                    WHERE a.session_id = $session_id
                    ORDER BY u.last_name, u.first_name";
$attendance_records = $conn->query($attendance_query);

$stats = [
    'total' => 0,
    'present' => 0,
    'absent' => 0,
    'late' => 0
];

$attendance_records->data_seek(0);
while ($record = $attendance_records->fetch_assoc()) {
    $stats['total']++;
    $stats[$record['status']]++;
}

$attendance_rate = $stats['total'] > 0 ? 
    round((($stats['present'] + $stats['late']) / $stats['total']) * 100, 2) : 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Attendance - Attendance Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>Session Attendance</h1>
        
        <div class="session-info">
            <h2>Session Details</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div>
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($session['course_code'] . ' - ' . $session['course_name']); ?></p>
                    <p><strong>Topic:</strong> <?php echo htmlspecialchars($session['topic']); ?></p>
                </div>
                <div>
                    <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($session['date'])); ?></p>
                    <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($session['start_time'])) . ' - ' . date('h:i A', strtotime($session['end_time'])); ?></p>
                </div>
                <div>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($session['location']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Students</h3>
                <p class="stat-number"><?php echo $stats['total']; ?></p>
            </div>
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
            <div class="stat-card stat-info">
                <h3>Attendance Rate</h3>
                <p class="stat-number"><?php echo $attendance_rate; ?>%</p>
            </div>
        </div>
        
        <?php if ($attendance_records->num_rows > 0): ?>
        <div class="table-section">
            <h2>Attendance Records</h2>
            <table class="data-table">
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
                    <?php 
                    $attendance_records->data_seek(0);
                    while ($record = $attendance_records->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($record['email']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $record['status']; ?>">
                                <?php echo ucfirst($record['status']); ?>
                            </span>
                        </td>
                        <td><?php echo $record['check_in_time'] ? date('h:i A', strtotime($record['check_in_time'])) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="error-message">
            No attendance records found for this session.
        </div>
        <?php endif; ?>
        
        <div class="form-actions">
            <?php if ($role === 'faculty'): ?>
            <a href="take_attendance.php?session_id=<?php echo $session_id; ?>" class="btn btn-primary">Edit Attendance</a>
            <?php endif; ?>
            <a href="sessions.php" class="btn btn-secondary">Back to Sessions</a>
        </div>
    </div>
    
    <script src="../js/logout.js"></script>
</body>
</html>
