<nav class="navbar">
    <div class="nav-container">
        <a href="dashboard.php" class="nav-brand">AMS</a>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            
            <?php if ($_SESSION['role'] === 'student'): ?>
                <li><a href="student_courses.php">My Courses</a></li>
                <li><a href="search_courses.php">Join Courses</a></li>
                <li><a href="my_attendance.php">My Attendance</a></li>
            <?php elseif ($_SESSION['role'] === 'faculty'): ?>
                <li><a href="faculty_courses.php">My Courses</a></li>
                <li><a href="sessions.php">Sessions</a></li>
                <li><a href="take_attendance.php">Take Attendance</a></li>
                <li><a href="course_requests.php">Course Requests</a></li>
            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                <li><a href="courses.php">Courses</a></li>
                <li><a href="sessions.php">Sessions</a></li>
                <li><a href="users.php">Users</a></li>
            <?php endif; ?>
            
            <li class="nav-user">
                <span><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                <a href="#" class="logout-btn" data-logout>Logout</a>
            </li>
        </ul>
    </div>
</nav>
