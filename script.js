// Configuration
const API_BASE_URL = 'http://localhost/Attendance-System-Website/api'; // Local XAMPP API base URL

// DOM Elements
const loginForm = document.getElementById('loginForm');
const adminIdInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const togglePasswordBtn = document.getElementById('togglePassword');
const eyeIconOff = document.getElementById('eyeIconOff');
const eyeIconOn = document.getElementById('eyeIconOn');
const loginButton = document.getElementById('loginButton');
const buttonText = document.getElementById('buttonText');
const spinner = document.getElementById('spinner');
const alert = document.getElementById('alert');

// Toggle Password Visibility
togglePasswordBtn.addEventListener('click', () => {
    const type = passwordInput.type === 'password' ? 'text' : 'password';
    passwordInput.type = type;
    
    eyeIconOff.classList.toggle('hidden');
    eyeIconOn.classList.toggle('hidden');
});

// Form Validation
function validateForm() {
    let isValid = true;
    const adminIdError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');
    
    // Reset errors
    adminIdError.textContent = '';
    passwordError.textContent = '';
    
    // Validate Email
    if (!adminIdInput.value.trim()) {
        adminIdError.textContent = 'Please enter your email';
        isValid = false;
    }
    
    // Validate Password
    if (!passwordInput.value) {
        passwordError.textContent = 'Please enter your password';
        isValid = false;
    }
    
    return isValid;
}

// Show Alert
function showAlert(message, type = 'error') {
    alert.textContent = message;
    alert.className = `alert ${type}`;
    alert.classList.remove('hidden');
    
    setTimeout(() => {
        alert.classList.add('hidden');
    }, 5000);
}

// Login Function
async function handleLogin(e) {
    e.preventDefault();
    
    if (!validateForm()) {
        return;
    }
    
    // Show loading state
    loginButton.disabled = true;
    buttonText.classList.add('hidden');
    spinner.classList.remove('hidden');
    
    try {
        const response = await fetch(`${API_BASE_URL}/login.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: adminIdInput.value.trim(),
                password: passwordInput.value,
            }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Save user data to localStorage
            localStorage.setItem('token', data.data.token);
            localStorage.setItem('user', JSON.stringify(data.data.user));
            
            // Redirect to admin dashboard
            window.location.href = 'admin.html';
        } else {
            showAlert(data.message || 'Login failed', 'error');
        }
    } catch (error) {
        console.error('Login error:', error);
        showAlert('Network error: ' + error.message, 'error');
    } finally {
        // Hide loading state
        loginButton.disabled = false;
        buttonText.classList.remove('hidden');
        spinner.classList.add('hidden');
    }
}

// Event Listeners
loginForm.addEventListener('submit', handleLogin);

// Check if user is already logged in
window.addEventListener('DOMContentLoaded', () => {
    const token = localStorage.getItem('token');
    const user = localStorage.getItem('user');
    
    if (token && user) {
        // Redirect to admin dashboard if already logged in
        window.location.href = 'admin.html';
    }
});

// Clear errors on input
adminIdInput.addEventListener('input', () => {
    document.getElementById('emailError').textContent = '';
});

passwordInput.addEventListener('input', () => {
    document.getElementById('passwordError').textContent = '';
});
