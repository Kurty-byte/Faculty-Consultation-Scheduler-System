<?php
// Include config file
require_once 'config.php';

// Check if already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

// Set page title
$pageTitle = 'Login';

// Include header
include 'includes/header.php';
?>

<div class="login-container">
    <div class="login-header">
        <h1>Welcome Back</h1>
        <p>Sign in to access your consultation dashboard</p>
        <div class="login-breadcrumb">
            <a href="<?php echo BASE_URL; ?>home.php">← Back to Home</a>
        </div>
    </div>
    
    <div class="login-box">
        <div class="login-form-header">
            <h2>Login to Your Account</h2>
            <p>Enter your credentials to continue</p>
        </div>
        
        <form action="<?php echo BASE_URL; ?>pages/auth/login_process.php" method="POST" class="login-form">
            <div class="form-group">
                <label for="email" class="required">Email Address</label>
                <div class="input-group">
                    <span class="input-icon">📧</span>
                    <input type="email" name="email" id="email" class="form-control" required 
                           placeholder="Enter your email address" autocomplete="email">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password" class="required">Password</label>
                <div class="input-group">
                    <span class="input-icon">🔒</span>
                    <input type="password" name="password" id="password" class="form-control" required 
                           placeholder="Enter your password" autocomplete="current-password">
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <span id="passwordToggleIcon">👁️</span>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-login">
                    <span class="btn-icon">🔑</span>
                    Sign In
                </button>
            </div>
            
            <div class="login-divider">
                <span>or</span>
            </div>
            
            <div class="form-group">
                <p class="login-register-link">
                    Don't have an account? 
                    <a href="<?php echo BASE_URL; ?>register.php" class="register-link">Create Account</a>
                </p>
            </div>
        </form>
    </div>
</div>
<script>
// Enhanced login page functionality
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('.login-form');
    const submitBtn = document.querySelector('.btn-login');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    
    // Password toggle functionality
    window.togglePassword = function() {
        const passwordField = document.getElementById('password');
        const toggleIcon = document.getElementById('passwordToggleIcon');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleIcon.textContent = '🚫';
        } else {
            passwordField.type = 'password';
            toggleIcon.textContent = '👁️';
        }
    };
    
    // Form validation and enhancement
    if (loginForm) {
        // Real-time validation feedback
        emailInput.addEventListener('blur', function() {
            validateEmail(this);
        });
        
        passwordInput.addEventListener('blur', function() {
            validatePassword(this);
        });
        
        // Enhanced form submission
        loginForm.addEventListener('submit', function(e) {
            const isValid = validateForm();
            
            if (isValid) {
                // Add loading state
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="btn-icon">⏳</span> Signing In...';
                
                // Re-enable after timeout (fallback)
                setTimeout(() => {
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span class="btn-icon">🔑</span> Sign In';
                }, 10000);
            } else {
                e.preventDefault();
            }
        });
    }
    
    // Form validation functions
    function validateEmail(input) {
        const email = input.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!email) {
            showInputError(input, 'Email is required');
            return false;
        } else if (!emailRegex.test(email)) {
            showInputError(input, 'Please enter a valid email address');
            return false;
        } else {
            showInputSuccess(input);
            return true;
        }
    }
    
    function validatePassword(input) {
        const password = input.value;
        
        if (!password) {
            showInputError(input, 'Password is required');
            return false;
        } else if (password.length < 6) {
            showInputError(input, 'Password must be at least 6 characters');
            return false;
        } else {
            showInputSuccess(input);
            return true;
        }
    }
    
    function validateForm() {
        const emailValid = validateEmail(emailInput);
        const passwordValid = validatePassword(passwordInput);
        
        return emailValid && passwordValid;
    }
    
    function showInputError(input, message) {
        removeInputFeedback(input);
        
        input.style.borderColor = 'var(--danger)';
        input.style.boxShadow = '0 0 0 0.2rem rgba(231, 74, 59, 0.25)';
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'input-error';
        errorDiv.textContent = message;
        errorDiv.style.color = 'var(--danger)';
        errorDiv.style.fontSize = '0.85rem';
        errorDiv.style.marginTop = '0.25rem';
        
        input.parentNode.parentNode.appendChild(errorDiv);
    }
    
    function showInputSuccess(input) {
        removeInputFeedback(input);
        
        input.style.borderColor = 'var(--success)';
        input.style.boxShadow = '0 0 0 0.2rem rgba(28, 200, 138, 0.25)';
    }
    
    function removeInputFeedback(input) {
        input.style.borderColor = '';
        input.style.boxShadow = '';
        
        const existingError = input.parentNode.parentNode.querySelector('.input-error');
        if (existingError) {
            existingError.remove();
        }
    }
    
    // Auto-focus email field
    if (emailInput) {
        emailInput.focus();
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Alt + R to go to register
        if (e.altKey && e.key === 'r') {
            e.preventDefault();
            window.location.href = '<?php echo BASE_URL; ?>register.php';
        }
        
        // Alt + H to go to home
        if (e.altKey && e.key === 'h') {
            e.preventDefault();
            window.location.href = '<?php echo BASE_URL; ?>home.php';
        }
    });
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>