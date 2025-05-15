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

<div class="login-container">
    <h1>Register for <?php echo SITE_NAME; ?></h1>
    
    <div class="login-box">
        <h2>Create an Account</h2>
        
        <form action="pages/auth/register_process.php" method="POST">
            <div class="form-group">
                <label for="role">Role</label>
                <select name="role" id="role" required>
                    <option value="faculty">Faculty</option>
                    <option value="student">Student</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" name="first_name" id="first_name" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" id="last_name" required>
            </div>
            
            <div class="form-group">
                <label for="middle_name">Middle Name (Optional)</label>
                <input type="text" name="middle_name" id="middle_name">
            </div>
            
            <div class="form-group">
                <label for="birthdate">Date of Birth</label>
                <input type="date" name="birthdate" id="birthdate" required>
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" name="address" id="address" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" required>
            </div>
            
            <div class="form-group">
                <label for="phone_number">Phone Number</label>
                <input type="text" name="phone_number" id="phone_number" required>
            </div>
            
            <div class="form-group">
                <label for="department">Department</label>
                <select name="department_id" id="department" required>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo $department['department_id']; ?>"><?php echo $department['department_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Student-specific fields -->
            <div class="form-group student-field" style="display: none;">
                <label for="year_level">Year Level</label>
                <select name="year_level" id="year_level">
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                    <option value="5">5th Year</option>
                </select>
            </div>
            
            <div class="form-group student-field" style="display: none;">
                <label for="academic_year">Academic Year</label>
                <input type="text" name="academic_year" id="academic_year" value="2024-2025">
            </div>
            
            <div class="form-group student-field" style="display: none;">
                <label for="enrollment_status">Enrollment Status</label>
                <select name="enrollment_status" id="enrollment_status">
                    <option value="regular">Regular</option>
                    <option value="irregular">Irregular</option>
                    <option value="shiftee">Shiftee</option>
                    <option value="returnee">Returnee</option>
                </select>
            </div>
            
            <!-- Faculty-specific fields -->
            <div class="form-group faculty-field">
                <label for="office_email">Office Email</label>
                <input type="email" name="office_email" id="office_email">
            </div>
            
            <div class="form-group faculty-field">
                <label for="office_phone_number">Office Phone Number</label>
                <input type="text" name="office_phone_number" id="office_phone_number">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Register</button>
            </div>
            
            <div class="form-group">
                <p>Already have an account? <a href="index.php">Login here</a></p>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle student/faculty specific fields
document.getElementById('role').addEventListener('change', function() {
    const studentFields = document.querySelectorAll('.student-field');
    const facultyFields = document.querySelectorAll('.faculty-field');
    
    if (this.value === 'student') {
        studentFields.forEach(field => field.style.display = 'block');
        facultyFields.forEach(field => field.style.display = 'none');
    } else {
        studentFields.forEach(field => field.style.display = 'none');
        facultyFields.forEach(field => field.style.display = 'block');
    }
});

// Trigger the change event to set initial visibility
document.getElementById('role').dispatchEvent(new Event('change'));
</script>

<?php
// Include footer
include 'includes/footer.php';
?>