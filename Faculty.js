// Faculty.js
document.addEventListener('DOMContentLoaded', function() {
    loadFacultyData();
    
    document.getElementById('addCourseForm').addEventListener('submit', handleAddCourse);
    document.getElementById('sessionForm').addEventListener('submit', handleCreateSession);
});

function loadFacultyData() {
    fetch('Faculty_data.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                window.location.href = 'login.html';
                return;
            }
            
            document.getElementById('welcomeMessage').textContent = `Welcome ${data.full_name}`;
            loadCourses(data.courses);
            loadSessions(data.sessions);
            loadStats(data.stats);
        })
        .catch(error => console.error('Error:', error));
}

function loadCourses(courses) {
    const tbody = document.getElementById('coursesTable');
    tbody.innerHTML = '';
    
    courses.forEach(course => {
        const row = `
            <tr>
                <td>${course.course_id}</td>
                <td>${course.course_name}</td>
                <td>${course.student_count}</td>
                <td>
                    <button class="btn2" onclick="editCourse('${course.course_id}')">Edit</button>
                    <button class="btn2" onclick="deleteCourse('${course.course_id}')">Delete</button>
                    <button class="btn2" onclick="showSessionModal('${course.course_id}')">Create Session</button>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

function loadSessions(sessions) {
    const tbody = document.getElementById('sessionsTable');
    tbody.innerHTML = '';
    
    sessions.forEach(session => {
        const row = `
            <tr>
                <td>${session.course_name}</td>
                <td>${session.session_date}</td>
                <td>${session.session_time}</td>
                <td>${session.attendance_rate || 0}%</td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

function loadStats(stats) {
    const container = document.getElementById('statsContainer');
    container.innerHTML = `
        <div class="course-item">
            <h4>Overall Attendance</h4>
            <p>Average: ${stats.avg_attendance || 0}%</p>
        </div>
        <div class="course-item">
            <h4>Students at Risk</h4>
            <p>Below 75%: ${stats.at_risk || 0} students</p>
        </div>
        <div class="course-item">
            <h4>Perfect Attendance</h4>
            <p>100%: ${stats.perfect || 0} students</p>
        </div>
    `;
}

function showAddCourseModal() {
    document.getElementById('addCourseModal').style.display = 'block';
}

function showSessionModal(courseId) {
    document.getElementById('sessionCourseId').value = courseId;
    document.getElementById('sessionModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function handleAddCourse(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    fetch('Faculty_process.php?action=add_course', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            closeModal('addCourseModal');
            loadFacultyData();
            e.target.reset();
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => console.error('Error:', error));
}

function handleCreateSession(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    fetch('Faculty_process.php?action=create_session', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            closeModal('sessionModal');
            loadFacultyData();
            e.target.reset();
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => console.error('Error:', error));
}

function deleteCourse(courseId) {
    if (!confirm('Are you sure you want to delete this course?')) return;
    
    const formData = new FormData();
    formData.append('course_id', courseId);
    
    fetch('Faculty_process.php?action=delete_course', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            loadFacultyData();
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => console.error('Error:', error));
}

function editCourse(courseId) {
    alert('Edit functionality for ' + courseId);
}

function showMessage(message, type) {
    const messageArea = document.getElementById('messageArea');
    messageArea.innerHTML = `
        <div style="background: ${type === 'success' ? '#44ff44' : '#ff4444'}; color: white; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            ${message}
        </div>
    `;
    setTimeout(() => { messageArea.innerHTML = ''; }, 3000);
}

window.addEventListener('click', function(e) {
    if (e.target.className === 'modal') {
        e.target.style.display = 'none';
    }
});