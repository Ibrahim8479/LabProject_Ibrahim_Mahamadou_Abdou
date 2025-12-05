document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    const first_name = formData.get('first_name').trim();
    const last_name = formData.get('last_name').trim();
    const email = formData.get('email').trim();
    const password = formData.get('password');
    const confirm_password = formData.get('confirm_password');
    const role = formData.get('role');
    
    if (!first_name || !last_name || !email || !password || !role) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please fill in all required fields'
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
    
    if (password.length < 6) {
        Swal.fire({
            icon: 'error',
            title: 'Weak Password',
            text: 'Password must be at least 6 characters long'
        });
        return;
    }
    
    if (password !== confirm_password) {
        Swal.fire({
            icon: 'error',
            title: 'Password Mismatch',
            text: 'Passwords do not match'
        });
        return;
    }
    
    try {
        formData.append('ajax', '1');
        
        const response = await fetch('signup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Registration Successful!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            });
            
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Registration Failed',
                text: data.message
            });
        }
    } catch (error) {
        console.error('Registration error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred during registration. Please try again.'
        });
    }
});
