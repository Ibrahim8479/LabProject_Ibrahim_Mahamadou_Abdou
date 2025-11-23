<?php
require_once 'auth_check.php';

if (getCurrentUserRole() !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$conn = getDBConnection();

$courses_query = "SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) as faculty_name,
                  (SELECT COUNT(*) FROM course_student_list WHERE course_id = c.course_id) as student_count
                  FROM courses c
                  JOIN users u ON c.faculty_id = u.user_id
                  ORDER BY c.course_code";
$courses = $conn->query($courses_query);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>Course Management</h1>
        
        <div class="table-section">
            <h2>All Courses</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Faculty</th>
                        <th>Credit Hours</th>
                        <th>Students</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($courses->num_rows > 0): ?>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['faculty_name']); ?></td>
                            <td><?php echo $course['credit_hours']; ?></td>
                            <td><?php echo $course['student_count']; ?></td>
                            <td><?php echo htmlspecialchars(substr($course['description'], 0, 50)) . '...'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No courses found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="../js/logout.js"></script>
</body>
</html>
