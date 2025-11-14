// signup.js
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('signupForm');
    const fullName = document.getElementById('fullName');
    const email = document.getElementById('signupEmail');
    const password = document.getElementById('signupPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    
    fullName.addEventListener('blur', validateName);
    email.addEventListener('blur', validateEmail);
    password.addEventListener('blur', validatePassword);
    confirmPassword.addEventListener('blur', validateConfirmPassword);
    
    form.addEventListener('submit', function(e) {
        const isNameValid = validateName();
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        const isConfirmValid = validateConfirmPassword();
        
        if (!isNameValid || !isEmailValid || !isPasswordValid || !isConfirmValid) {
            e.preventDefault();
            showAlert('Please fix all errors before submitting', 'error');
        }
    });
    
    function validateName() {
        const nameValue = fullName.value.trim();
        const nameError = document.getElementById('nameError');
        
        if (nameValue === '') {
            showError(nameError, 'Full name is required');
            return false;
        } else if (nameValue.length < 3) {
            showError(nameError, 'Name must be at least 3 characters');
            return false;
        } else if (!/^[a-zA-Z\s]+$/.test(nameValue)) {
            showError(nameError, 'Name can only contain letters and spaces');
            return false;
        } else {
            clearError(nameError);
            return true;
        }
    }
    
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
        } else if (!/(?=.*[a-z])/.test(passwordValue)) {
            showError(passwordError, 'Password must contain at least one lowercase letter');
            return false;
        } else if (!/(?=.*[A-Z])/.test(passwordValue)) {
            showError(passwordError, 'Password must contain at least one uppercase letter');
            return false;
        } else if (!/(?=.*\d)/.test(passwordValue)) {
            showError(passwordError, 'Password must contain at least one number');
            return false;
        } else {
            clearError(passwordError);
            return true;
        }
    }
    
    function validateConfirmPassword() {
        const confirmValue = confirmPassword.value;
        const confirmError = document.getElementById('confirmError');
        
        if (confirmValue === '') {
            showError(confirmError, 'Please confirm your password');
            return false;
        } else if (confirmValue !== password.value) {
            showError(confirmError, 'Passwords do not match');
            return false;
        } else {
            clearError(confirmError);
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