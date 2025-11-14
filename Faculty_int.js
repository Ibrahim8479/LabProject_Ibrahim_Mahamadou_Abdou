// Faculty_int.js
document.addEventListener('DOMContentLoaded', function() {
    loadInternData();
    
    document.getElementById('reportForm').addEventListener('submit', handleSubmitReport);
});

function loadInternData() {
    fetch('Faculty_int_data.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                window.location.href = 'login.html';
                return;
            }
            
            document.getElementById('welcomeMessage').textContent = `Welcome ${data.full_name}`;
            loadCourses(data.courses);
            loadSessions(data.sessions);
            loadReports(data.reports);
        })
        .catch(error => console.error('Error:', error));
}

function loadCourses(courses) {
    const container = document.getElementById('coursesList');
    container.innerHTML = '';
    
    if (courses.length === 0) {
        container.innerHTML = '<p>No courses assigned yet.</p>';
        return;
    }
    
    courses.forEach(course => {
        container.innerHTML += `
            <div class="course-item">
                <h4>${course.course_id} - ${course.course_name}</h4>
                <p>${course.faculty_name || 'No Faculty Assigned'}</p>
            </div>
        `;
    });
}

function loadSessions(sessions) {
    const tbody = document.getElementById('sessionsTable');
    tbody.innerHTML = '';
    
    if (sessions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No upcoming sessions</td></tr>';
        return;
    }
    
    sessions.forEach(session => {
        const row = `
            <tr>
                <td>${session.course_id} - ${session.course_name}</td>
                <td>${session.session_date}</td>
                <td>${session.session_time}</td>
                <td>${session.location}</td>
                <td>
                    <button class="btn2" onclick="showReportModal('${session.course_id}', '${session.session_id}')">Submit Report</button>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

function loadReports(reports) {
    const tbody = document.getElementById('reportsTable');
    tbody.innerHTML = '';
    
    if (reports.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No reports submitted yet</td></tr>';
        return;
    }
    
    reports.forEach(report => {
        const notes = report.notes.length > 50 ? report.notes.substring(0, 50) + '...' : report.notes;
        const row = `
            <tr>
                <td>${report.report_date}</td>
                <td>${report.course_id}</td>
                <td>${notes}</td>
                <td>
                    <button class="btn2" onclick="viewReport(${report.report_id})">View</button>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

function showReportModal(courseId, sessionId) {
    document.getElementById('reportCourseId').value = courseId;
    document.getElementById('reportSessionId').value = sessionId;
    document.getElementById('reportModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function handleSubmitReport(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    fetch('Faculty_int_process.php?action=submit_report', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            closeModal('reportModal');
            loadInternData();
            e.target.reset();
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => console.error('Error:', error));
}

function viewReport(reportId) {
    alert('View report ' + reportId);
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