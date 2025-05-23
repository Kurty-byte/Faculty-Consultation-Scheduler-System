<?php
// Include config file
require_once 'config.php';

// Check if already logged in
if (isLoggedIn()) {
    redirectToDashboard();
}

// Set page title
$pageTitle = 'Register';

// Include header
include 'includes/header.php';

// Get all departments for dropdown
$departments = fetchRows("SELECT * FROM departments ORDER BY department_name");
?>

<div class="register-container">
    <div class="register-header">
        <h1>Join Our Community</h1>
        <p>Create your account to start scheduling consultations</p>
        <div class="register-breadcrumb">
            <a href="<?php echo BASE_URL; ?>home.php">← Back to Home</a>
        </div>
    </div>
    
    <div class="register-box">
        <div class="register-form-header">
            <h2>Create Your Account</h2>
            <p>Fill in the information below to get started</p>
        </div>
        
        <form action="<?php echo BASE_URL; ?>pages/auth/register_process.php" method="POST" class="register-form" id="registerForm">
            <!-- Role Selection -->
            <div class="role-selection-section">
                <h3>Select Your Role</h3>
                <div class="role-options">
                    <div class="role-option">
                        <input type="radio" id="faculty" name="role" value="faculty" required>
                        <label for="faculty" class="role-label">
                            <div class="role-icon">👨‍🏫</div>
                            <div class="role-info">
                                <strong>Faculty Member</strong>
                                <p>Manage consultation hours and meet with students</p>
                            </div>
                            <div class="radio-indicator"></div>
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="student" name="role" value="student" required>
                        <label for="student" class="role-label">
                            <div class="role-icon">👨‍🎓</div>
                            <div class="role-info">
                                <strong>Student</strong>
                                <p>Book consultations with faculty members</p>
                            </div>
                            <div class="radio-indicator"></div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Basic Information -->
            <div class="form-section">
                <h3>Basic Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name" class="required">First Name</label>
                        <div class="input-group">
                            <span class="input-icon">👤</span>
                            <input type="text" name="first_name" id="first_name" class="form-control" required 
                                   placeholder="Enter your first name">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name" class="required">Last Name</label>
                        <div class="input-group">
                            <span class="input-icon">👤</span>
                            <input type="text" name="last_name" id="last_name" class="form-control" required 
                                   placeholder="Enter your last name">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="middle_name">Middle Name (Optional)</label>
                    <div class="input-group">
                        <span class="input-icon">👤</span>
                        <input type="text" name="middle_name" id="middle_name" class="form-control" 
                               placeholder="Enter your middle name">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="birthdate" class="required">Date of Birth</label>
                        <div class="input-group">
                            <span class="input-icon">📅</span>
                            <input type="date" name="birthdate" id="birthdate" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="department" class="required">Department</label>
                        <div class="input-group">
                            <span class="input-icon">🏢</span>
                            <select name="department_id" id="department" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo $department['department_id']; ?>">
                                        <?php echo $department['department_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="form-section">
                <h3>Contact Information</h3>
                <div class="form-group">
                    <label for="address" class="required">Address</label>
                    <div class="input-group">
                        <span class="input-icon">📍</span>
                        <textarea name="address" id="address" class="form-control" required rows="3" 
                                  placeholder="Enter your complete address"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email" class="required">Email Address</label>
                        <div class="input-group">
                            <span class="input-icon">📧</span>
                            <input type="email" name="email" id="email" class="form-control" required 
                                   placeholder="your.email@example.com">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_number" class="required">Phone Number</label>
                        <div class="input-group">
                            <span class="input-icon">📱</span>
                            <input type="tel" name="phone_number" id="phone_number" class="form-control" required 
                                   placeholder="+1 (555) 123-4567">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Student-specific fields -->
            <div class="form-section student-fields" style="display: none;">
                <h3>Academic Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="year_level" class="required">Year Level</label>
                        <div class="input-group">
                            <span class="input-icon">📚</span>
                            <select name="year_level" id="year_level" class="form-control">
                                <option value="">Select Year Level</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                                <option value="5">5th Year</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="academic_year" class="required">Academic Year</label>
                        <div class="input-group">
                            <span class="input-icon">📅</span>
                            <input type="text" name="academic_year" id="academic_year" class="form-control" 
                                   value="2024-2025" placeholder="e.g., 2024-2025">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="enrollment_status" class="required">Enrollment Status</label>
                    <div class="input-group">
                        <span class="input-icon">📝</span>
                        <select name="enrollment_status" id="enrollment_status" class="form-control">
                            <option value="">Select Status</option>
                            <option value="regular">Regular</option>
                            <option value="irregular">Irregular</option>
                            <option value="shiftee">Shiftee</option>
                            <option value="returnee">Returnee</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Faculty-specific fields -->
            <div class="form-section faculty-fields" style="display: none;">
                <h3>Professional Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="office_email" class="required">Office Email</label>
                        <div class="input-group">
                            <span class="input-icon">📧</span>
                            <input type="email" name="office_email" id="office_email" class="form-control" 
                                   placeholder="office.email@university.edu">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="office_phone_number" class="required">Office Phone</label>
                        <div class="input-group">
                            <span class="input-icon">☎️</span>
                            <input type="tel" name="office_phone_number" id="office_phone_number" class="form-control" 
                                   placeholder="Office phone number">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Security Information -->
            <div class="form-section">
                <h3>Security</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="required">Password</label>
                        <div class="input-group">
                            <span class="input-icon">🔒</span>
                            <input type="password" name="password" id="password" class="form-control" required 
                                   placeholder="Create a strong password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <span id="passwordToggleIcon">👁️</span>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <span class="strength-text" id="strengthText">Password strength</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="required">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-icon">🔒</span>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required 
                                   placeholder="Confirm your password">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <span id="confirmPasswordToggleIcon">👁️</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="password-requirements">
                    <h4>Password Requirements:</h4>
                    <ul class="requirements-list">
                        <li id="req-length">At least 8 characters</li>
                        <li id="req-uppercase">One uppercase letter</li>
                        <li id="req-lowercase">One lowercase letter</li>
                        <li id="req-number">One number</li>
                    </ul>
                </div>
            </div>
            
            <!-- Submit Button -->
            <div class="form-section">
                <button type="submit" class="btn btn-primary btn-register" id="submitBtn">
                    <span class="btn-icon">🚀</span>
                    Create Account
                </button>
            </div>
            
            <!-- Login Link -->
            <div class="register-divider">
                <span>or</span>
            </div>
            
            <div class="form-section">
                <p class="register-login-link">
                    Already have an account? 
                    <a href="<?php echo BASE_URL; ?>login.php" class="login-link">Sign In</a>
                </p>
            </div>
        </form>
    </div>
</div>

<style>
/* Enhanced Register Page Styles */
.register-container {
    max-width: 800px;
    margin: 30px auto;
    padding: 0;
    background: none;
    border-radius: 0;
    box-shadow: none;
}

.register-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, var(--success) 0%, var(--primary) 100%);
    color: white;
    border-radius: 15px 15px 0 0;
    position: relative;
    overflow: hidden;
}

.register-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: 
        radial-gradient(circle at 30% 70%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 70% 30%, rgba(255,255,255,0.05) 0%, transparent 50%);
    animation: float 8s ease-in-out infinite;
}

.register-header h1,
.register-header p,
.register-breadcrumb {
    position: relative;
    z-index: 2;
}

.register-header h1 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: white;
    font-weight: 700;
}

.register-header p {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 1rem;
}

.register-breadcrumb a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.2);
    display: inline-block;
}

.register-breadcrumb a:hover {
    color: white;
    background: rgba(255,255,255,0.1);
    transform: translateY(-2px);
}

.register-box {
    background: white;
    border-radius: 0 0 15px 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    overflow: hidden;
}

.register-form-header {
    padding: 2rem 2rem 1rem;
    text-align: center;
    border-bottom: 1px solid #f0f0f0;
}

.register-form-header h2 {
    font-size: 1.5rem;
    color: var(--dark);
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.register-form-header p {
    color: var(--gray);
    margin-bottom: 0;
}

.register-form {
    padding: 2rem;
}

.form-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #f0f0f0;
}

.form-section:last-of-type {
    border-bottom: none;
    margin-bottom: 0;
}

.form-section h3 {
    font-size: 1.2rem;
    color: var(--primary);
    margin-bottom: 1.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-section h3::before {
    content: '📋';
    font-size: 1rem;
}

/* Role Selection */
.role-selection-section h3::before {
    content: '👥';
}

.role-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.role-option {
    position: relative;
}

.role-option input[type="radio"] {
    display: none;
}

.role-label {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    background-color: white;
    position: relative;
}

.role-label:hover {
    border-color: var(--primary);
    background-color: rgba(78, 115, 223, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.role-option input[type="radio"]:checked + .role-label {
    border-color: var(--primary);
    background-color: rgba(78, 115, 223, 0.1);
    box-shadow: 0 0 0 4px rgba(78, 115, 223, 0.1);
}

.role-icon {
    font-size: 2rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.role-info {
    flex: 1;
}

.role-info strong {
    display: block;
    color: var(--dark);
    margin-bottom: 0.25rem;
    font-size: 1.1rem;
}

.role-info p {
    margin: 0;
    font-size: 0.9rem;
    color: var(--gray);
}

.radio-indicator {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 20px;
    height: 20px;
    border: 2px solid #ddd;
    border-radius: 50%;
    transition: all 0.2s;
}

.role-option input[type="radio"]:checked + .role-label .radio-indicator {
    border-color: var(--primary);
    background-color: var(--primary);
}

.role-option input[type="radio"]:checked + .role-label .radio-indicator::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background-color: white;
    border-radius: 50%;
}

/* Form Layout */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
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

.input-group textarea.form-control {
    height: auto;
    padding-top: 1rem;
    padding-bottom: 1rem;
    resize: vertical;
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

/* Password Strength Indicator */
.password-strength {
    margin-top: 0.5rem;
}

.strength-bar {
    width: 100%;
    height: 4px;
    background-color: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 0.25rem;
}

.strength-fill {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-fill.weak {
    width: 25%;
    background-color: var(--danger);
}

.strength-fill.fair {
    width: 50%;
    background-color: var(--warning);
}

.strength-fill.good {
    width: 75%;
    background-color: var(--info);
}

.strength-fill.strong {
    width: 100%;
    background-color: var(--success);
}

.strength-text {
    font-size: 0.85rem;
    color: var(--gray);
}

/* Password Requirements */
.password-requirements {
    margin-top: 1rem;
    padding: 1rem;
    background: #f8f9fc;
    border-radius: 8px;
    border-left: 3px solid var(--primary);
}

.password-requirements h4 {
    font-size: 0.9rem;
    color: var(--dark);
    margin-bottom: 0.75rem;
    font-weight: 600;
}

.requirements-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

.requirements-list li {
    font-size: 0.85rem;
    color: var(--gray);
    padding: 0.25rem 0;
    position: relative;
    padding-left: 1.5rem;
}

.requirements-list li::before {
    content: '❌';
    position: absolute;
    left: 0;
    transition: all 0.2s;
}

.requirements-list li.valid::before {
    content: '✅';
}

.requirements-list li.valid {
    color: var(--success);
}

/* Submit Button */
.btn-register {
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

.btn-register::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn-register:hover::before {
    left: 100%;
}

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(78, 115, 223, 0.3);
}

/* Divider */
.register-divider {
    text-align: center;
    margin: 1.5rem 0;
    position: relative;
}

.register-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e9ecef;
}

.register-divider span {
    background: white;
    color: var(--gray);
    padding: 0 1rem;
    font-size: 0.9rem;
    position: relative;
    z-index: 1;
}

/* Login Link */
.register-login-link {
    text-align: center;
    margin: 0;
    color: var(--gray);
    font-size: 0.95rem;
}

.login-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
}

.login-link:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

/* Role-specific field visibility */
.student-fields,
.faculty-fields {
    transition: all 0.3s ease;
}

/* Animations */
@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-10px) rotate(3deg);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .register-container {
        margin: 15px auto;
        max-width: 95%;
    }
    
    .register-header {
        padding: 1.5rem;
    }
    
    .register-header h1 {
        font-size: 1.75rem;
    }
    
    .register-form {
        padding: 1.5rem;
    }
    
    .role-options {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .requirements-list {
        grid-template-columns: 1fr;
    }
    
    .role-label {
        padding: 1.25rem;
    }
    
    .terms-section {
        padding: 1rem;
    }
}

@media (max-width: 480px) {
    .register-header h1 {
        font-size: 1.5rem;
    }
    
    .register-header p {
        font-size: 1rem;
    }
    
    .input-group .form-control {
        height: 45px;
        font-size: 0.95rem;
    }
    
    .btn-register {
        height: 45px;
        font-size: 1rem;
    }
    
    .role-icon {
        font-size: 1.5rem;
    }
    
    .role-info strong {
        font-size: 1rem;
    }
}

/* Loading and validation states */
.btn-register.loading {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-register.loading .btn-icon {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.form-control.error {
    border-color: var(--danger) !important;
    box-shadow: 0 0 0 0.2rem rgba(231, 74, 59, 0.25) !important;
}

.form-control.success {
    border-color: var(--success) !important;
    box-shadow: 0 0 0 0.2rem rgba(28, 200, 138, 0.25) !important;
}

.input-error {
    color: var(--danger);
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

/* Focus styles for accessibility */
.form-control:focus,
.btn:focus,
.terms-checkbox:focus,
.role-label:focus {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}
</style>

<script>
// Enhanced register page functionality
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    // Role selection handling
    const roleRadios = document.querySelectorAll('input[name="role"]');
    const studentFields = document.querySelector('.student-fields');
    const facultyFields = document.querySelector('.faculty-fields');
    
    roleRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'student') {
                studentFields.style.display = 'block';
                facultyFields.style.display = 'none';
                toggleRequiredFields('student');
            } else if (this.value === 'faculty') {
                studentFields.style.display = 'none';
                facultyFields.style.display = 'block';
                toggleRequiredFields('faculty');
            }
        });
    });
    
    // Password strength checker
    passwordInput.addEventListener('input', function() {
        checkPasswordStrength(this.value);
        checkPasswordRequirements(this.value);
        validatePasswordMatch();
    });
    
    confirmPasswordInput.addEventListener('input', function() {
        validatePasswordMatch();
    });
    
    // Form validation
    registerForm.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return;
        }
        
        // Add loading state
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="btn-icon">⏳</span> Creating Account...';
        
        // Re-enable after timeout (fallback)
        setTimeout(() => {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="btn-icon">🚀</span> Create Account';
        }, 15000);
    });
    
    // Password toggle functions
    window.togglePassword = function(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId === 'password' ? 'passwordToggleIcon' : 'confirmPasswordToggleIcon');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.textContent = '🙈';
        } else {
            field.type = 'password';
            icon.textContent = '👁️';
        }
    };
    
    // Helper functions
    function toggleRequiredFields(role) {
        const studentRequired = ['year_level', 'academic_year', 'enrollment_status'];
        const facultyRequired = ['office_email', 'office_phone_number'];
        
        // Remove all role-specific required attributes
        [...studentRequired, ...facultyRequired].forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) field.removeAttribute('required');
        });
        
        // Add required attributes for selected role
        const requiredFields = role === 'student' ? studentRequired : facultyRequired;
        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) field.setAttribute('required', '');
        });
    }
    
    function checkPasswordStrength(password) {
        const strengthBar = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        
        let strength = 0;
        let strengthClass = '';
        let strengthLabel = '';
        
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        switch (strength) {
            case 0:
            case 1:
                strengthClass = 'weak';
                strengthLabel = 'Weak';
                break;
            case 2:
                strengthClass = 'fair';
                strengthLabel = 'Fair';
                break;
            case 3:
                strengthClass = 'good';
                strengthLabel = 'Good';
                break;
            case 4:
            case 5:
                strengthClass = 'strong';
                strengthLabel = 'Strong';
                break;
        }
        
        strengthBar.className = `strength-fill ${strengthClass}`;
        strengthText.textContent = `Password strength: ${strengthLabel}`;
    }
    
    function checkPasswordRequirements(password) {
        const requirements = {
            'req-length': password.length >= 8,
            'req-uppercase': /[A-Z]/.test(password),
            'req-lowercase': /[a-z]/.test(password),
            'req-number': /[0-9]/.test(password)
        };
        
        Object.keys(requirements).forEach(reqId => {
            const element = document.getElementById(reqId);
            if (requirements[reqId]) {
                element.classList.add('valid');
            } else {
                element.classList.remove('valid');
            }
        });
    }
    
    function validatePasswordMatch() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (confirmPassword && password !== confirmPassword) {
            confirmPasswordInput.classList.add('error');
            showInputError(confirmPasswordInput, 'Passwords do not match');
        } else if (confirmPassword) {
            confirmPasswordInput.classList.remove('error');
            confirmPasswordInput.classList.add('success');
            removeInputError(confirmPasswordInput);
        }
    }
    
    function validateForm() {
        let isValid = true;
        
        // Validate required fields
        const requiredFields = registerForm.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                showInputError(field, 'This field is required');
                isValid = false;
            } else {
                removeInputError(field);
            }
        });
        
        // Validate email format
        const emailField = document.getElementById('email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailField.value && !emailRegex.test(emailField.value)) {
            showInputError(emailField, 'Please enter a valid email address');
            isValid = false;
        }
        
        // Validate password requirements
        const password = passwordInput.value;
        if (password.length < 8) {
            showInputError(passwordInput, 'Password must be at least 8 characters');
            isValid = false;
        }
        
        // Validate password match
        if (password !== confirmPasswordInput.value) {
            showInputError(confirmPasswordInput, 'Passwords do not match');
            isValid = false;
        }
        
        return isValid;
    }
    
    function showInputError(input, message) {
        removeInputError(input);
        
        input.classList.add('error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'input-error';
        errorDiv.textContent = message;
        
        input.parentNode.parentNode.appendChild(errorDiv);
    }
    
    function removeInputError(input) {
        input.classList.remove('error');
        
        const existingError = input.parentNode.parentNode.querySelector('.input-error');
        if (existingError) {
            existingError.remove();
        }
    }
    
    // Auto-focus first name field
    document.getElementById('first_name').focus();
    
    // Phone number formatting
    const phoneInput = document.getElementById('phone_number');
    phoneInput.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length >= 6) {
            value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
        } else if (value.length >= 3) {
            value = value.replace(/(\d{3})(\d{0,3})/, '($1) $2');
        }
        this.value = value;
    });
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>