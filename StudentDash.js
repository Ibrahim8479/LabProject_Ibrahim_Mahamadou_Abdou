// StudentDash.js
document.addEventListener('DOMContentLoaded', function() {
    loadStudentData();
    
    // Smooth scrolling
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

function loadStudentData() {
    fetch('StudentDash_data.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                window.location.href = 'login.html';
                return;
            }
            
            // Set welcome message
            document.getElementById('welcomeMessage').textContent = `Welcome ${data.full_name}`;
            
            // Load courses
            loadCourses(data.courses);
            
            // Load schedule
            loadSchedule(data.schedule);
            
            // Load grades
            loadGrades(data.grades);
            
            // Check for low attendance
            checkAttendanceWarnings(data.courses);
        })
        .catch(error => {
            console.error('Error loading data:', error);
        });
}

function loadCourses(courses) {
    const coursesList = document.getElementById('coursesList');
    coursesList.innerHTML = '';
    
    if (courses.length === 0) {
        coursesList.innerHTML = '<p>No courses enrolled yet.</p>';
        return;
    }
    
    courses.forEach(course => {
        const courseDiv = document.createElement('div');
        courseDiv.className = 'course';
        
        const attendance = course.attendance_rate || 0;
        
        // Color code based on attendance
        if (attendance < 75) {
            courseDiv.style.borderLeftColor = '#ff4444';
            courseDiv.style.background = '#ffe0e0';
        } else if (attendance < 85) {
            courseDiv.style.borderLeftColor = '#ffaa00';
            courseDiv.style.background = '#fff5e0';
        } else {
            courseDiv.style.borderLeftColor = '#44ff44';
            courseDiv.style.background = '#e0ffe0';
        }
        
        courseDiv.innerHTML = `
            <h4>${course.course_id} - ${course.course_name}</h4>
            <p>Attendance: ${attendance}%</p>
            <p>Sessions: ${course.attended_sessions}/${course.total_sessions}</p>
        `;
        
        coursesList.appendChild(courseDiv);
    });
}

function loadSchedule(schedule) {
    const scheduleTable = document.getElementById('scheduleTable');
    scheduleTable.innerHTML = '';
    
    if (schedule.length === 0) {
        scheduleTable.innerHTML = '<tr><td colspan="4" style="text-align:center;">No upcoming sessions</td></tr>';
        return;
    }
    
    schedule.forEach(session => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${session.course_id} - ${session.course_name}</td>
            <td>${session.session_date}</td>
            <td>${session.session_time}</td>
            <td>${session.location}</td>
        `;
        scheduleTable.appendChild(row);
    });
}

function loadGrades(grades) {
    const gradesTable = document.getElementById('gradesTable');
    gradesTable.innerHTML = '';
    
    if (grades.length === 0) {
        gradesTable.innerHTML = '<tr><td colspan="4" style="text-align:center;">No data available</td></tr>';
        return;
    }
    
    grades.forEach(grade => {
        const row = document.createElement('tr');
        const statusColor = grade.status.includes('Needs Attention') ? '#ff4444' : '#44ff44';
        
        row.innerHTML = `
            <td>${grade.course_id} - ${grade.course_name}</td>
            <td>${grade.attendance_rate || 0}%</td>
            <td>${grade.participation}</td>
            <td style="color: ${statusColor}; font-weight: bold;">${grade.status}</td>
        `;
        gradesTable.appendChild(row);
    });
}

function checkAttendanceWarnings(courses) {
    let hasLowAttendance = false;
    
    courses.forEach(course => {
        if (course.attendance_rate < 75) {
            hasLowAttendance = true;
        }
    });
    
    if (hasLowAttendance) {
        showNotification('Warning: You have courses with attendance below 75%', 'warning');
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'warning' ? '#ffaa00' : '#44ff44'};
        color: white;
        border-radius: 5px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);