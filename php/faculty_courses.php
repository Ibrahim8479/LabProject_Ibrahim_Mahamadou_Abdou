<?php
require_once 'auth_check.php';

if (getCurrentUserRole() !== 'faculty') {
    header('Location: dashboard.php');
    exit();
}

$conn = getDBConnection();
$faculty_id = getCurrentUserId();

$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM course_student_list WHERE course_id = c.course_id) as student_count,
          (SELECT COUNT(*) FROM sessions WHERE course_id = c.course_id) as session_count,
          (SELECT COUNT(*) FROM course_requests WHERE course_id = c.course_id AND status = 'pending') as pending_requests
          FROM courses c 
          WHERE c.faculty_id = $faculty_id
          ORDER BY c.created_at DESC";

$courses = $conn->query($query);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Faculty Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1>My Courses</h1>
            <button onclick="openCreateCourseModal()" class="btn btn-primary">+ Create New Course</button>
        </div>
        
        <?php if ($courses->num_rows > 0): ?>
            <div class="stats-grid">
                <?php while ($course = $courses->fetch_assoc()): ?>
                <div class="stat-card" style="text-align: left;">
                    <h3><?php echo htmlspecialchars($course['course_code']); ?></h3>
                    <h4 style="color: #2c3e50; margin: 0.5rem 0;"><?php echo htmlspecialchars($course['course_name']); ?></h4>
                    <p style="color: #7f8c8d; font-size: 0.9rem; margin: 0.5rem 0;">
                        <?php echo htmlspecialchars(substr($course['description'], 0, 100)) . (strlen($course['description']) > 100 ? '...' : ''); ?>
                    </p>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ecf0f1;">
                        <p style="font-size: 0.9rem; margin: 0.25rem 0;">
                            <strong>Credit Hours:</strong> <?php echo $course['credit_hours']; ?>
                        </p>
                        <p style="font-size: 0.9rem; margin: 0.25rem 0;">
                            <strong>Students Enrolled:</strong> <?php echo $course['student_count']; ?>
                        </p>
                        <p style="font-size: 0.9rem; margin: 0.25rem 0;">
                            <strong>Sessions:</strong> <?php echo $course['session_count']; ?>
                        </p>
                        <?php if ($course['pending_requests'] > 0): ?>
                        <p style="font-size: 0.9rem; margin: 0.25rem 0; color: #f39c12;">
                            <strong>Pending Requests:</strong> <?php echo $course['pending_requests']; ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <?php if ($course['pending_requests'] > 0): ?>
                        <a href="course_requests.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-warning">
                            Requests (<?php echo $course['pending_requests']; ?>)
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="error-message">
                You haven't created any courses yet. Click "Create New Course" to get started.
            </div>
        <?php endif; ?>
    </div>
    
    <div id="createCourseModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeCreateCourseModal()">&times;</span>
            <h2>Create New Course</h2>
            <form id="createCourseForm">
                <div class="form-group">
                    <label for="course_code">Course Code *</label>
                    <input type="text" id="course_code" name="course_code" required>
                </div>
                
                <div class="form-group">
                    <label for="course_name">Course Name *</label>
                    <input type="text" id="course_name" name="course_name" required>
                </div>
                
                <div class="form-group">
                    <label for="credit_hours">Credit Hours *</label>
                    <input type="number" id="credit_hours" name="credit_hours" min="1" max="6" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" onclick="closeCreateCourseModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Course</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../js/logout.js"></script>
    <script src="../js/faculty_courses.js"></script>
</body>
</html>
