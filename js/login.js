document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    if (!email || !password) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please fill in all fields'
        });
        return;
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Email',
            text: 'Please enter a valid email address'
        });
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);
        formData.append('ajax', '1');
        
        const response = await fetch('login.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Login Successful',
                text: `Welcome back, ${data.username}!`,
                timer: 1500,
                showConfirmButton: false
            });
            
            window.location.href = 'dashboard.php';
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: data.message || 'Invalid credentials'
            });
        }
    } catch (error) {
        console.error('Login error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred during login. Please try again.'
        });
    }
});
