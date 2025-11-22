async function processRequest(requestId, action) {
    const actionText = action === 'approve' ? 'approve' : 'reject';
    const actionCapital = actionText.charAt(0).toUpperCase() + actionText.slice(1);
    
    const result = await Swal.fire({
        title: `${actionCapital} Request?`,
        text: `Are you sure you want to ${actionText} this student's request?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: `Yes, ${actionCapital}`,
        confirmButtonColor: action === 'approve' ? '#27ae60' : '#e74c3c',
        cancelButtonText: 'Cancel'
    });
    
    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('request_id', requestId);
            formData.append('action', action);
            formData.append('ajax', '1');
            
            const response = await fetch('process_request.php', {
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
                
                const row = document.getElementById(`request-${requestId}`);
                if (row) {
                    row.style.transition = 'opacity 0.3s';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        
                        const tbody = document.querySelector('.data-table tbody');
                        if (tbody && tbody.children.length === 0) {
                            window.location.reload();
                        }
                    }, 300);
                }
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
                text: 'An error occurred while processing the request'
            });
        }
    }
}
