// login.js
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const email = document.getElementById('loginEmail');
    const password = document.getElementById('loginPassword');
    
    // Check for registration success message
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('registered') === '1') {
        const successDiv = document.getElementById('successMessage');
        successDiv.textContent = 'Registration successful! Please login.';
        successDiv.style.display = 'block';
    }
    
    email.addEventListener('blur', validateEmail);
    password.addEventListener('blur', validatePassword);
    
    form.addEventListener('submit', function(e) {
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        
        if (!isEmailValid || !isPasswordValid) {
            e.preventDefault();
            showAlert('Please fix all errors before submitting', 'error');
        }
    });
    
    function validateEmail() {
        const emailValue = email.value.trim();
        const emailError = document.getElementById('emailError');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (emailValue === '') {
            showError(emailError, 'Email is required');
            return false;
        } else if (!emailRegex.test(emailValue)) {
            showError(emailError, 'Please enter a valid email address');
            return false;
        } else {
            clearError(emailError);
            return true;
        }
    }
    
    function validatePassword() {
        const passwordValue = password.value;
        const passwordError = document.getElementById('passwordError');
        
        if (passwordValue === '') {
            showError(passwordError, 'Password is required');
            return false;
        } else if (passwordValue.length < 6) {
            showError(passwordError, 'Password must be at least 6 characters');
            return false;
        } else {
            clearError(passwordError);
            return true;
        }
    }
    
    function showError(element, message) {
        element.textContent = message;
        element.style.color = '#ff4444';
        element.style.fontSize = '12px';
        element.style.marginTop = '5px';
        element.style.display = 'block';
    }
    
    function clearError(element) {
        element.textContent = '';
        element.style.display = 'none';
    }
    
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.textContent = message;
        alertDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'error' ? '#ff4444' : '#44ff44'};
            color: white;
            border-radius: 5px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    }
});