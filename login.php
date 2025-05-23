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
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember_me">
                        <span class="checkmark"></span>
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-password">Forgot Password?</a>
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

<style>
/* Enhanced Login Page Styles */
.login-container {
    max-width: 500px;
    margin: 50px auto;
    padding: 0;
    background: none;
    border-radius: 0;
    box-shadow: none;
}

.login-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
    color: white;
    border-radius: 15px 15px 0 0;
    position: relative;
    overflow: hidden;
}

.login-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: 
        radial-gradient(circle at 30% 70%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 70% 30%, rgba(255,255,255,0.05) 0%, transparent 50%);
    animation: float 6s ease-in-out infinite;
}

.login-header h1 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: white;
    font-weight: 700;
    position: relative;
    z-index: 2;
}

.login-header p {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 1rem;
    position: relative;
    z-index: 2;
}

.login-breadcrumb {
    position: relative;
    z-index: 2;
}

.login-breadcrumb a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.2);
    display: inline-block;
}

.login-breadcrumb a:hover {
    color: white;
    background: rgba(255,255,255,0.1);
    transform: translateY(-2px);
}

.login-box {
    background: white;
    border-radius: 0 0 15px 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    overflow: hidden;
}

.login-form-header {
    padding: 2rem 2rem 1rem;
    text-align: center;
    border-bottom: 1px solid #f0f0f0;
}

.login-form-header h2 {
    font-size: 1.5rem;
    color: var(--dark);
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.login-form-header p {
    color: var(--gray);
    margin-bottom: 0;
}

.login-form {
    padding: 2rem;
}

.input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 1rem;
    z-index: 2;
    font-size: 1.1rem;
    color: var(--gray);
}

.input-group .form-control {
    padding-left: 3rem;
    padding-right: 3rem;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    height: 50px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.input-group .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.1);
    transform: translateY(-2px);
}

.password-toggle {
    position: absolute;
    right: 1rem;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    color: var(--gray);
    font-size: 1rem;
    transition: all 0.2s ease;
    z-index: 2;
}

.password-toggle:hover {
    color: var(--primary);
    transform: scale(1.1);
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 1rem 0;
}

.remember-me {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 0.9rem;
    color: var(--gray);
}

.remember-me input[type="checkbox"] {
    display: none;
}

.checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid #ddd;
    border-radius: 4px;
    margin-right: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.remember-me input[type="checkbox"]:checked + .checkmark {
    background-color: var(--primary);
    border-color: var(--primary);
}

.remember-me input[type="checkbox"]:checked + .checkmark::after {
    content: '✓';
    color: white;
    font-weight: bold;
    font-size: 0.8rem;
}

.forgot-password {
    color: var(--primary);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.forgot-password:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

.btn-login {
    width: 100%;
    height: 50px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-login::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn-login:hover::before {
    left: 100%;
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(78, 115, 223, 0.3);
}

.login-divider {
    text-align: center;
    margin: 1.5rem 0;
    position: relative;
}

.login-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e9ecef;
}

.login-divider span {
    background: white;
    color: var(--gray);
    padding: 0 1rem;
    font-size: 0.9rem;
    position: relative;
    z-index: 1;
}

.login-register-link {
    text-align: center;
    margin: 0;
    color: var(--gray);
    font-size: 0.95rem;
}

.register-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
}

.register-link:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

/* Animations */
@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-10px) rotate(5deg);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .login-container {
        margin: 20px auto;
        max-width: 95%;
    }
    
    .login-header {
        padding: 1.5rem;
    }
    
    .login-header h1 {
        font-size: 1.75rem;
    }
    
    .login-form {
        padding: 1.5rem;
    }
    
    .form-options {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}

@media (max-width: 480px) {
    .login-header h1 {
        font-size: 1.5rem;
    }
    
    .login-header p {
        font-size: 1rem;
    }
    
    .input-group .form-control {
        height: 45px;
        font-size: 0.95rem;
    }
    
    .btn-login {
        height: 45px;
        font-size: 1rem;
    }
}

/* Loading state */
.btn-login.loading {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-login.loading .btn-icon {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Focus styles for accessibility */
.form-control:focus,
.btn:focus,
.remember-me:focus,
.forgot-password:focus,
.register-link:focus {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .login-header {
        background: var(--dark);
    }
    
    .input-group .form-control {
        border-width: 3px;
    }
    
    .btn-login {
        border: 2px solid var(--primary-dark);
    }
}
</style>

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
            toggleIcon.textContent = '🙈';
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