async function requestCourse(courseId, courseName) {
    const { value: message } = await Swal.fire({
        title: 'Request to Join Course',
        html: `
            <p>You are requesting to join: <strong>${courseName}</strong></p>
            <textarea id="requestMessage" class="swal2-textarea" 
                      placeholder="Optional message to faculty (e.g., why you want to join this course)..."
                      rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; margin-top: 1rem;"></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: 'Send Request',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            return document.getElementById('requestMessage').value;
        }
    });
    
    if (message !== undefined) {
        try {
            const formData = new FormData();
            formData.append('course_id', courseId);
            formData.append('message', message);
            formData.append('ajax', '1');
            
            const response = await fetch('request_course.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Request Sent!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Request Failed',
                    text: data.message
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while sending the request'
            });
        }
    }
}
