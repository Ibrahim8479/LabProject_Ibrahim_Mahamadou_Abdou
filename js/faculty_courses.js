function openCreateCourseModal() {
    document.getElementById('createCourseModal').style.display = 'flex';
}

function closeCreateCourseModal() {
    document.getElementById('createCourseModal').style.display = 'none';
    document.getElementById('createCourseForm').reset();
}

window.onclick = function(event) {
    const modal = document.getElementById('createCourseModal');
    if (event.target === modal) {
        closeCreateCourseModal();
    }
}

document.getElementById('createCourseForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('ajax', '1');
    
    try {
        const response = await fetch('create_course.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            });
            
            closeCreateCourseModal();
            window.location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while creating the course'
        });
    }
});
