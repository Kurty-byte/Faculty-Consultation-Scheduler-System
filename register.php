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

// Get current academic year automatically
$currentYear = date('Y');
$nextYear = $currentYear + 1;
$currentAcademicYear = $currentYear . '-' . $nextYear;
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
                                   placeholder="Enter your first name" maxlength="50">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name" class="required">Last Name</label>
                        <div class="input-group">
                            <span class="input-icon">👤</span>
                            <input type="text" name="last_name" id="last_name" class="form-control" required 
                                   placeholder="Enter your last name" maxlength="50">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="middle_name">Middle Name (Optional)</label>
                    <div class="input-group">
                        <span class="input-icon">👤</span>
                        <input type="text" name="middle_name" id="middle_name" class="form-control" 
                               placeholder="Enter your middle name" maxlength="50">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="birthdate" class="required">Date of Birth</label>
                        <div class="input-group">
                            <span class="input-icon">📅</span>
                            <input type="date" name="birthdate" id="birthdate" class="form-control" required
                                   max="<?php echo date('Y-m-d', strtotime('-15 years')); ?>"
                                   min="<?php echo date('Y-m-d', strtotime('-80 years')); ?>">
                        </div>
                        <small class="form-text">You must be at least 15 years old to register</small>
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
                    <label for="address" class="required">Complete Address</label>
                    <div class="input-group">
                        <span class="input-icon">📍</span>
                        <textarea name="address" id="address" class="form-control" required rows="3" 
                                  placeholder="Enter your complete address (Street, Barangay, City, Province)" maxlength="255"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email" class="required">Email Address</label>
                        <div class="input-group">
                            <span class="input-icon">📧</span>
                            <input type="email" name="email" id="email" class="form-control" required 
                                   placeholder="your.email@example.com" maxlength="100">
                        </div>
                        <small class="form-text">This will be your login email</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_number" class="required">Phone Number</label>
                        <div class="input-group">
                            <span class="input-icon">📱</span>
                            <input type="tel" name="phone_number" id="phone_number" class="form-control" required 
                                   placeholder="09XX-XXX-XXXX" pattern="[0-9]{11}" maxlength="11">
                        </div>
                        <small class="form-text">11-digit Philippine mobile number</small>
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
                                <option value="6">6th Year (Graduate)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="academic_year" class="required">Academic Year</label>
                        <div class="input-group">
                            <span class="input-icon">📅</span>
                            <input type="text" name="academic_year" id="academic_year" class="form-control" 
                                   value="<?php echo $currentAcademicYear; ?>" readonly>
                        </div>
                        <small class="form-text">Current academic year (automatically set)</small>
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
                            <option value="transferee">Transferee</option>
                        </select>
                    </div>
                    <small class="form-text">Select your current enrollment status</small>
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
                                   placeholder="office.email@university.edu" maxlength="100">
                        </div>
                        <small class="form-text">Official institutional email address</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="office_phone_number" class="required">Office Phone</label>
                        <div class="input-group">
                            <span class="input-icon">☎️</span>
                            <input type="tel" name="office_phone_number" id="office_phone_number" class="form-control" 
                                   placeholder="02-8XXX-XXXX or 09XX-XXX-XXXX" maxlength="20">
                        </div>
                        <small class="form-text">Office landline or designated contact number</small>
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
                                   placeholder="Create a strong password" minlength="8" maxlength="50">
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
                                   placeholder="Confirm your password" minlength="8" maxlength="50">
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
                        <li id="req-special">One special character (!@#$%^&*)</li>
                    </ul>
                </div>
            </div>
            
            <!-- Submit Button -->
            <div class="form-section form-actions-final">
                <div class="terms-agreement">
                    <label class="terms-checkbox">
                        <input type="checkbox" name="agree_terms" required>
                        <span class="checkmark"></span>
                        <span class="terms-text">I agree that the information provided is accurate and I understand the system's terms of use.</span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-register" id="submitBtn">
                    <span class="btn-icon">🚀</span>
                    Create Account
                </button>
                
                <p class="register-login-link">
                    Already have an account? 
                    <a href="<?php echo BASE_URL; ?>login.php" class="login-link">Sign In</a>
                </p>
            </div>
        </form>
    </div>
</div>

<script>
// Enhanced register page functionality
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const phoneInput = document.getElementById('phone_number');
    
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
    
    // Phone number formatting and validation
    phoneInput.addEventListener('input', function() {
        let value = this.value.replace(/\D/g, ''); // Remove non-digits
        
        // Limit to 11 digits for Philippine numbers
        if (value.length > 11) {
            value = value.slice(0, 11);
        }
        
        this.value = value;
        
        // Validate Philippine mobile number format
        if (value.length === 11) {
            if (value.startsWith('09')) {
                this.setCustomValidity('');
                this.classList.remove('error');
                this.classList.add('success');
            } else {
                this.setCustomValidity('Philippine mobile numbers should start with 09');
                this.classList.add('error');
                this.classList.remove('success');
            }
        } else if (value.length > 0) {
            this.setCustomValidity('Philippine mobile numbers should be 11 digits');
            this.classList.add('error');
            this.classList.remove('success');
        } else {
            this.setCustomValidity('');
            this.classList.remove('error', 'success');
        }
    });
    
    // Enhanced password strength checker
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
            icon.textContent = '🚫';
        } else {
            field.type = 'password';
            icon.textContent = '👁️';
        }
    };
    
    // Helper functions
    function toggleRequiredFields(role) {
        const studentRequired = ['year_level', 'enrollment_status'];
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
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength++;
        
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
            'req-number': /[0-9]/.test(password),
            'req-special': /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
        };
        
        Object.keys(requirements).forEach(reqId => {
            const element = document.getElementById(reqId);
            if (element) {
                if (requirements[reqId]) {
                    element.classList.add('valid');
                } else {
                    element.classList.remove('valid');
                }
            }
        });
    }
    
    function validatePasswordMatch() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (confirmPassword && password !== confirmPassword) {
            confirmPasswordInput.classList.add('error');
            confirmPasswordInput.setCustomValidity('Passwords do not match');
        } else if (confirmPassword) {
            confirmPasswordInput.classList.remove('error');
            confirmPasswordInput.classList.add('success');
            confirmPasswordInput.setCustomValidity('');
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
        const emailFields = ['email', 'office_email'];
        emailFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && field.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(field.value)) {
                    showInputError(field, 'Please enter a valid email address');
                    isValid = false;
                }
            }
        });
        
        // Validate password requirements
        const password = passwordInput.value;
        if (password.length < 8) {
            showInputError(passwordInput, 'Password must be at least 8 characters');
            isValid = false;
        }
        
        if (!/[A-Z]/.test(password)) {
            showInputError(passwordInput, 'Password must contain at least one uppercase letter');
            isValid = false;
        }
        
        if (!/[a-z]/.test(password)) {
            showInputError(passwordInput, 'Password must contain at least one lowercase letter');
            isValid = false;
        }
        
        if (!/[0-9]/.test(password)) {
            showInputError(passwordInput, 'Password must contain at least one number');
            isValid = false;
        }
        
        // Validate password match
        if (password !== confirmPasswordInput.value) {
            showInputError(confirmPasswordInput, 'Passwords do not match');
            isValid = false;
        }
        
        // Validate phone number
        const phone = phoneInput.value;
        if (phone && (phone.length !== 11 || !phone.startsWith('09'))) {
            showInputError(phoneInput, 'Please enter a valid 11-digit Philippine mobile number starting with 09');
            isValid = false;
        }
        
        // Validate terms agreement
        const termsCheckbox = document.querySelector('input[name="agree_terms"]');
        if (!termsCheckbox.checked) {
            alert('Please agree to the terms and conditions');
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
});
</script>

<style>
/* Additional styles for improvements */
.form-actions-final {
    border-bottom: none !important;
    padding-bottom: 0 !important;
}

.terms-agreement {
    background-color: #f8f9fc;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid var(--primary);
    margin-bottom: 2rem;
}

.terms-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    cursor: pointer;
    line-height: 1.5;
}

.terms-checkbox input[type="checkbox"] {
    display: none;
}

.checkmark {
    width: 24px;
    height: 24px;
    border: 2px solid #ddd;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
    margin-top: 2px;
    background-color: white;
}

.terms-checkbox input[type="checkbox"]:checked + .checkmark {
    background-color: var(--primary);
    border-color: var(--primary);
}

.terms-checkbox input[type="checkbox"]:checked + .checkmark::after {
    content: '✓';
    color: white;
    font-weight: bold;
    font-size: 1rem;
}

.terms-text {
    color: var(--gray);
    font-size: 0.95rem;
}

.requirements-list li.valid {
    color: var(--success);
}

.requirements-list li.valid::before {
    content: '✓ ';
    font-weight: bold;
}

.form-control.success {
    border-color: var(--success);
    box-shadow: 0 0 0 0.2rem rgba(28, 200, 138, 0.25);
}

.form-control.error {
    border-color: var(--danger);
    box-shadow: 0 0 0 0.2rem rgba(231, 74, 59, 0.25);
}

.input-error {
    color: var(--danger);
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: flex;
    align-items: center;
}

.input-error::before {
    content: '⚠️ ';
    margin-right: 0.25rem;
}

.strength-fill.weak {
    width: 20%;
    background-color: var(--danger);
}

.strength-fill.fair {
    width: 40%;
    background-color: var(--warning);
}

.strength-fill.good {
    width: 70%;
    background-color: var(--info);
}

.strength-fill.strong {
    width: 100%;
    background-color: var(--success);
}

/* Remove the register-divider that was creating the extra line */
.register-divider {
    display: none;
}

/* Proper spacing for Create Account button and Sign In link */
.btn-register {
    margin-bottom: 1.5rem;
}

.register-login-link {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
    text-align: center;
}

/* Move calendar button to the right side for date inputs */
input[type="date"] {
    direction: rtl;
    text-align: left;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    position: absolute;
    right: 8px;
    cursor: pointer;
}

/* Firefox date input styling */
input[type="date"]::-moz-calendar-picker-indicator {
    position: absolute;
    right: 8px;
    cursor: pointer;
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>