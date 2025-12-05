<?php
require_once 'auth_check.php';

if (getCurrentUserRole() !== 'faculty') {
    header('Location: dashboard.php');
    exit();
}

$conn = getDBConnection();
$faculty_id = getCurrentUserId();

$course_filter = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

$courses_query = "SELECT course_id, course_code, course_name 
                  FROM courses 
                  WHERE faculty_id = $faculty_id 
                  ORDER BY course_code";
$courses = $conn->query($courses_query);

$requests_query = "SELECT cr.*, c.course_code, c.course_name, 
                   CONCAT(u.first_name, ' ', u.last_name) AS student_name, 
                   u.email AS student_email
                   FROM course_requests cr
                   JOIN courses c ON cr.course_id = c.course_id
                   JOIN users u ON cr.student_id = u.user_id
                   WHERE c.faculty_id = $faculty_id 
                   AND cr.status = 'pending'";

if ($course_filter > 0) {
    $requests_query .= " AND cr.course_id = $course_filter";
}

$requests_query .= " ORDER BY cr.requested_at DESC";
$requests = $conn->query($requests_query);

$processed_query = "SELECT cr.*, c.course_code, c.course_name, 
                    CONCAT(u.first_name, ' ', u.last_name) AS student_name
                    FROM course_requests cr
                    JOIN courses c ON cr.course_id = c.course_id
                    JOIN users u ON cr.student_id = u.user_id
                    WHERE c.faculty_id = $faculty_id 
                    AND cr.status != 'pending'
                    ORDER BY cr.processed_at DESC
                    LIMIT 10";
$processed_requests = $conn->query($processed_query);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Requests - Faculty Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>Course Join Requests</h1>
        
        <div class="filter-section">
            <form method="GET" action="">
                <div class="form-group">
                    <label for="course_id">Filter by Course</label>
                    <div style="display: flex; gap: 1rem;">
                        <select id="course_id" name="course_id" onchange="this.form.submit()">
                            <option value="0">All Courses</option>
                            <?php 
                            $courses->data_seek(0);
                            while ($course = $courses->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $course['course_id']; ?>" 
                                        <?php echo ($course_filter == $course['course_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <?php if ($course_filter > 0): ?>
                            <a href="course_requests.php" class="btn btn-secondary">Clear Filter</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if ($requests->num_rows > 0): ?>
        <div class="table-section">
            <h2>Pending Requests (<?php echo $requests->num_rows; ?>)</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Message</th>
                        <th>Requested Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($request = $requests->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($request['student_email']); ?></td>
                        <td><?php echo htmlspecialchars($request['course_code'] . ' - ' . $request['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($request['message']); ?></td>
                        <td><?php echo date('M d, Y h:i A', strtotime($request['requested_at'])); ?></td>
                        <td>
                            <button class="btn btn-success" 
                                    onclick="processRequest(<?php echo $request['request_id']; ?>, 'approve')">
                                Approve
                            </button>
                            <button class="btn btn-danger" 
                                    onclick="processRequest(<?php echo $request['request_id']; ?>, 'reject')">
                                Reject
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p>No pending requests.</p>
        <?php endif; ?>

        <?php if ($processed_requests->num_rows > 0): ?>
        <div class="table-section">
            <h2>Recent Processed Requests</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Processed Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($request = $processed_requests->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($request['course_code'] . ' - ' . $request['course_name']); ?></td>
                        <td>
                            <?php if ($request['status'] === 'approved'): ?>
                                <span class="status-badge status-present">Approved</span>
                            <?php else: ?>
                                <span class="status-badge status-absent">Rejected</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y h:i A', strtotime($request['processed_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script src="../js/logout.js"></script>
    <script src="../js/course_requests.js"></script>

</body>
</html>
