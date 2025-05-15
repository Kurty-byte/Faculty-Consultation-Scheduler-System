<?php
// Include config file
require_once '../../config.php';

// Check if already logged in
if (isLoggedIn()) {
    redirectToDashboard();
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate form data
    $errors = [];
    
    // Required fields
    $requiredFields = [
        'role', 'first_name', 'last_name', 'birthdate', 'address', 
        'email', 'phone_number', 'department_id', 'password', 'confirm_password'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = 'All required fields must be filled out.';
            break;
        }
    }
    
    // Role-specific required fields
    if ($_POST['role'] == 'faculty') {
        if (empty($_POST['office_email']) || empty($_POST['office_phone_number'])) {
            $errors[] = 'Faculty members must provide office email and phone number.';
        }
    } elseif ($_POST['role'] == 'student') {
        if (empty($_POST['year_level']) || empty($_POST['academic_year']) || empty($_POST['enrollment_status'])) {
            $errors[] = 'Students must provide year level, academic year, and enrollment status.';
        }
    } else {
        $errors[] = 'Invalid role selected.';
    }
    
    // Password validation
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $errors[] = 'Passwords do not match.';
    }
    
    // Email validation
    $email = sanitize($_POST['email']);
    $emailCheck = fetchRow("SELECT user_id FROM users WHERE email = ?", [$email]);
    
    if ($emailCheck) {
        $errors[] = 'Email address is already registered.';
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Sanitize inputs
        $role = sanitize($_POST['role']);
        $firstName = sanitize($_POST['first_name']);
        $lastName = sanitize($_POST['last_name']);
        $middleName = !empty($_POST['middle_name']) ? sanitize($_POST['middle_name']) : null;
        $birthdate = sanitize($_POST['birthdate']);
        $address = sanitize($_POST['address']);
        $phoneNumber = sanitize($_POST['phone_number']);
        $departmentId = sanitize($_POST['department_id']);
        $password = $_POST['password'];
        $passwordHash = hashPassword($password);
        
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert user data
            $userQuery = "INSERT INTO users (password_hash, first_name, last_name, middle_name, birthdate, address, email, phone_number, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $userParams = [$passwordHash, $firstName, $lastName, $middleName, $birthdate, $address, $email, $phoneNumber, $role];
            $userId = insertData($userQuery, $userParams);
            
            if (!$userId) {
                throw new Exception("Failed to create user account.");
            }
            
            // Insert role-specific data
            if ($role == 'faculty') {
                $officeEmail = sanitize($_POST['office_email']);
                $officePhoneNumber = sanitize($_POST['office_phone_number']);
                
                $facultyQuery = "INSERT INTO faculty (user_id, department_id, office_email, office_phone_number) VALUES (?, ?, ?, ?)";
                $facultyParams = [$userId, $departmentId, $officeEmail, $officePhoneNumber];
                $facultyResult = insertData($facultyQuery, $facultyParams);
                
                if (!$facultyResult) {
                    throw new Exception("Failed to create faculty profile.");
                }
            } elseif ($role == 'student') {
                $yearLevel = sanitize($_POST['year_level']);
                $academicYear = sanitize($_POST['academic_year']);
                $enrollmentStatus = sanitize($_POST['enrollment_status']);
                
                $studentQuery = "INSERT INTO students (user_id, department_id, year_level, academic_year, enrollment_status) VALUES (?, ?, ?, ?, ?)";
                $studentParams = [$userId, $departmentId, $yearLevel, $academicYear, $enrollmentStatus];
                $studentResult = insertData($studentQuery, $studentParams);
                
                if (!$studentResult) {
                    throw new Exception("Failed to create student profile.");
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Set success message
            setFlashMessage('success', 'Registration successful! You can now log in.');
            redirect('index.php');
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            
            // Set error message
            setFlashMessage('danger', 'Registration failed: ' . $e->getMessage());
            redirect('register.php');
        }
    } else {
        // Set error messages
        setFlashMessage('danger', implode('<br>', $errors));
        redirect('register.php');
    }
} else {
    // Not a POST request, redirect to registration page
    redirect('register.php');
}
?>