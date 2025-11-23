async function processRequest(requestId, action) {
    console.log('Processing request:', requestId, action);
    
    const actionText = action === 'approve' ? 'approve' : 'reject';
    const actionTitle = actionText.charAt(0).toUpperCase() + actionText.slice(1);
    
    const result = await Swal.fire({
        title: actionTitle + ' Request?',
        text: 'Are you sure you want to ' + actionText + ' this request?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, ' + actionTitle,
        confirmButtonColor: action === 'approve' ? '#28a745' : '#dc3545',
        cancelButtonText: 'Cancel'
    });
    
    if (!result.isConfirmed) {
        console.log('User cancelled');
        return;
    }
    
    console.log('User confirmed, sending request...');
    
    try {
        const formData = new FormData();
        formData.append('request_id', requestId);
        formData.append('action', action);
        formData.append('ajax', '1');
        
        console.log('Fetching process_request.php...');
        
        const response = await fetch('process_request.php', {
            method: 'POST',
            body: formData
        });
        
        console.log('Response received:', response.status);
        
        const text = await response.text();
        console.log('Response text:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse JSON:', text);
            throw new Error('Invalid server response');
        }
        
        console.log('Parsed data:', data);
        
        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            });
            
            // Reload page to show updated list
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to process request'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred: ' + error.message
        });
    }
}

console.log('course_requests.js loaded');
