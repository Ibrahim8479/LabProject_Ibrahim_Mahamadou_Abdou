async function logout() {
    try {
        const formData = new FormData();
        formData.append('ajax', '1');
        
        const response = await fetch('logout.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.logout) {
            await Swal.fire({
                icon: 'success',
                title: 'Logged Out',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            });
            
            window.location.href = 'login.php';
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Logout failed. Please try again.'
            });
        }
    } catch (error) {
        console.error('Logout error:', error);
        window.location.href = 'login.php';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const logoutButtons = document.querySelectorAll('.logout-btn, [data-logout]');
    
    logoutButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            logout();
        });
    });
});
