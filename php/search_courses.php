<?php
require_once 'auth_check.php';

if (getCurrentUserRole() !== 'student') {
    header('Location: dashboard.php');
    exit();
}

$conn = getDBConnection();
$student_id = getCurrentUserId();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT c.*, 
          CONCAT(u.first_name, ' ', u.last_name) as faculty_name,
          (SELECT COUNT(*) FROM course_student_list WHERE course_id = c.course_id) as student_count,
          CASE 
              WHEN EXISTS (SELECT 1 FROM course_student_list WHERE course_id = c.course_id AND student_id = $student_id) THEN 'enrolled'
              WHEN EXISTS (SELECT 1 FROM course_requests WHERE course_id = c.course_id AND student_id = $student_id AND status = 'pending') THEN 'pending'
              ELSE 'available'
          END as enrollment_status
          FROM courses c
          JOIN users u ON c.faculty_id = u.user_id";

if (!empty($search)) {
    $search_param = "%$search%";
    $query .= " WHERE (c.course_code LIKE ? OR c.course_name LIKE ? OR c.description LIKE ?)";
}

$query .= " ORDER BY c.course_code";

if (!empty($search)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $courses = $stmt->get_result();
} else {
    $courses = $conn->query($query);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Courses - Student Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>Browse & Join Courses</h1>
        
        <div class="filter-section">
            <form method="GET" action="">
                <div class="form-group">
                    <label for="search">Search Courses</label>
                    <div style="display: flex; gap: 1rem;">
                        <input type="text" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by code, name, or description...">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if (!empty($search)): ?>
                        <a href="search_courses.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if ($courses->num_rows > 0): ?>
            <div class="table-section">
                <h2>Available Courses</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Faculty</th>
                            <th>Credit Hours</th>
                            <th>Students</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['faculty_name']); ?></td>
                            <td><?php echo $course['credit_hours']; ?></td>
                            <td><?php echo $course['student_count']; ?></td>
                            <td>
                                <?php if ($course['enrollment_status'] === 'enrolled'): ?>
                                    <span class="status-badge status-present">Enrolled</span>
                                <?php elseif ($course['enrollment_status'] === 'pending'): ?>
                                    <span class="status-badge status-late">Pending</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background-color: #e8f5e9; color: #2e7d32;">Available</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($course['enrollment_status'] === 'available'): ?>
                                    <button onclick="requestCourse(<?php echo $course['course_id']; ?>, '<?php echo htmlspecialchars($course['course_name']); ?>')" 
                                            class="btn btn-sm btn-success">Request to Join</button>
                                <?php elseif ($course['enrollment_status'] === 'enrolled'): ?>
                                    <span style="color: #27ae60;">Enrolled</span>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled>Request Pending</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="error-message">
                <?php if (!empty($search)): ?>
                    No courses found matching "<?php echo htmlspecialchars($search); ?>". Try a different search term.
                <?php else: ?>
                    No courses available at this time.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="../js/logout.js"></script>
    <script src="../js/search_courses.js"></script>
</body>
</html>
