<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has student role
requireRole('student');

// Include required functions
require_once '../../includes/appointment_functions.php';
require_once '../../includes/faculty_functions.php';
require_once '../../includes/timeslot_functions.php';
require_once '../../includes/notification_system.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize inputs
    $facultyId = isset($_POST['faculty_id']) ? (int)$_POST['faculty_id'] : 0;
    $date = isset($_POST['date']) ? sanitize($_POST['date']) : '';
    $startTime = isset($_POST['start_time']) ? sanitize($_POST['start_time']) : '';
    $endTime = isset($_POST['end_time']) ? sanitize($_POST['end_time']) : '';
    $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 30;
    $modality = isset($_POST['modality']) ? sanitize($_POST['modality']) : '';
    $remarks = isset($_POST['remarks']) ? sanitize($_POST['remarks']) : '';
    $consultationTopic = isset($_POST['consultation_topic']) ? sanitize($_POST['consultation_topic']) : '';
    $preparation = isset($_POST['preparation']) ? sanitize($_POST['preparation']) : '';
    $studentPhone = isset($_POST['student_phone']) ? sanitize($_POST['student_phone']) : '';
    
    // Get platform or location based on modality
    $platform = null;
    $location = null;
    
    if ($modality === 'virtual') {
        $platform = isset($_POST['platform']) ? sanitize($_POST['platform']) : null;
    } else {
        $location = isset($_POST['location']) ? sanitize($_POST['location']) : null;
    }
    
    // Validate required fields
    if (!$facultyId || !$date || !$startTime || !$endTime || !$modality || !$remarks) {
        setFlashMessage('danger', 'Please fill in all required fields.');
        redirect('pages/student/view_faculty.php');
    }
    
    // Validate modality
    if (!in_array($modality, ['physical', 'virtual'])) {
        setFlashMessage('danger', 'Invalid consultation type selected.');
        redirect('pages/student/view_faculty.php');
    }
    
    // Validate date is not in the past
    if ($date < date('Y-m-d')) {
        setFlashMessage('danger', 'Cannot book appointments for past dates.');
        redirect('pages/student/view_faculty.php');
    }
    
    // Validate remarks length
    if (strlen($remarks) > 500) {
        setFlashMessage('danger', 'Description must be 500 characters or less.');
        redirect('pages/student/book_appointment.php?faculty_id=' . $facultyId . '&date=' . $date . '&start=' . $startTime . '&end=' . $endTime);
    }
    
    // Get student ID
    $student = fetchRow("SELECT student_id FROM students WHERE user_id = ?", [$_SESSION['user_id']]);
    
    if (!$student) {
        setFlashMessage('danger', 'Student profile not found.');
        redirect('pages/student/view_faculty.php');
    }
    
    $studentId = $student['student_id'];
    
    // Verify faculty exists and has consultation hours
    $faculty = getFacultyDetails($facultyId);
    if (!$faculty) {
        setFlashMessage('danger', 'Faculty not found.');
        redirect('pages/student/view_faculty.php');
    }
    
    if (!hasConsultationHoursSetup($facultyId)) {
        setFlashMessage('danger', 'This faculty member has not set up their consultation hours yet.');
        redirect('pages/student/view_faculty.php');
    }
    
    // IMPROVED VALIDATION: Use the new validation functions
    $validationErrors = validateAppointmentTimeSlot($facultyId, $date, $startTime, $endTime);
    
    if (!empty($validationErrors)) {
        setFlashMessage('danger', 'Booking failed: ' . implode(' ', $validationErrors));
        redirect('pages/student/faculty_schedule.php?id=' . $facultyId);
    }
    
    // Additional check: verify the time slot is still available using improved function
    if (!isSlotAvailableImproved($facultyId, $date, $startTime, $endTime)) {
        setFlashMessage('danger', 'This time slot is no longer available. Please select another slot.');
        redirect('pages/student/faculty_schedule.php?id=' . $facultyId);
    }
    
    // Check if student already has an appointment at this exact time
    $conflictingAppointment = fetchRow(
        "SELECT appointment_id FROM appointments 
         WHERE student_id = ? AND appointment_date = ? 
         AND start_time = ? AND end_time = ? 
         AND is_cancelled = 0",
        [$studentId, $date, $startTime, $endTime]
    );
    
    if ($conflictingAppointment) {
        setFlashMessage('danger', 'You already have an appointment at this time.');
        redirect('pages/student/view_appointments.php');
    }
    
    // Check if student has overlapping appointments
    $overlappingAppointments = fetchRows(
        "SELECT appointment_id FROM appointments 
         WHERE student_id = ? AND appointment_date = ? 
         AND is_cancelled = 0
         AND ((start_time < ? AND end_time > ?) OR 
              (start_time < ? AND end_time > ?) OR
              (start_time >= ? AND end_time <= ?))",
        [$studentId, $date, $startTime, $startTime, $endTime, $endTime, $startTime, $endTime]
    );
    
    if (!empty($overlappingAppointments)) {
        setFlashMessage('danger', 'You have a conflicting appointment at this time.');
        redirect('pages/student/view_appointments.php');
    }
    
    // We need to create a temporary schedule entry for the new appointment system
    // This bridges the gap between old appointment system and new consultation hours
    $tempScheduleId = createTemporaryScheduleImproved($facultyId, $date, $startTime, $endTime);
    
    if (!$tempScheduleId) {
        setFlashMessage('danger', 'Failed to process appointment request. Please try again.');
        redirect('pages/student/faculty_schedule.php?id=' . $facultyId);
    }
    
    // Build comprehensive remarks including all form data
    $fullRemarks = "Topic: " . $consultationTopic . "\n\n";
    $fullRemarks .= "Description: " . $remarks;
    
    if (!empty($preparation)) {
        $fullRemarks .= "\n\nPreparation: " . $preparation;
    }
    
    if (!empty($studentPhone)) {
        $fullRemarks .= "\n\nPhone: " . $studentPhone;
    }
    
    // Create the appointment with improved error handling
    try {
        // Start transaction for atomic operation
        global $conn;
        mysqli_begin_transaction($conn);
        
        $appointmentId = createAppointment(
            $studentId, 
            $tempScheduleId, 
            $date, 
            $startTime, 
            $endTime, 
            $fullRemarks, 
            $modality, 
            $platform, 
            $location
        );
        
        if ($appointmentId) {
            // Update student contact info if provided
            if (!empty($studentPhone)) {
                updateOrDeleteData(
                    "UPDATE users SET phone_number = ? WHERE user_id = ?",
                    [$studentPhone, $_SESSION['user_id']]
                );
            }
            
            // Create notification for faculty using new system
            $notificationResult = createBookingNotification($appointmentId);
            
            $successMessage = 'Your consultation appointment request has been submitted successfully!';
            
            if ($notificationResult) {
                $successMessage .= ' ' . $faculty['first_name'] . ' ' . $faculty['last_name'] . 
                                  ' has been notified and will review your request.';
            }
            
            $successMessage .= ' You will receive a notification once your appointment is approved or if any changes are needed.';
            
            // Commit transaction
            mysqli_commit($conn);
            
            setFlashMessage('success', $successMessage);
            
            // Redirect to appointments page with success
            redirect('pages/student/view_appointments.php?highlight=' . $appointmentId);
        } else {
            throw new Exception('Failed to create appointment in database.');
        }
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        
        // Clean up temporary schedule if appointment creation failed
        if (isset($tempScheduleId)) {
            deleteTemporaryScheduleImproved($tempScheduleId);
        }
        
        setFlashMessage('danger', 'Failed to book appointment: ' . $e->getMessage());
        redirect('pages/student/faculty_schedule.php?id=' . $facultyId);
    }
    
} else {
    // Not a POST request, redirect to faculty directory
    redirect('pages/student/view_faculty.php');
}

/**
 * Create a temporary schedule entry for the appointment - IMPROVED VERSION
 * This bridges the gap between the old appointment system and new consultation hours
 */
function createTemporaryScheduleImproved($facultyId, $date, $startTime, $endTime) {
    global $conn;
    
    $dayOfWeek = strtolower(date('l', strtotime($date)));
    
    // Check if a similar schedule already exists
    $existingSchedule = fetchRow(
        "SELECT schedule_id FROM availability_schedules 
         WHERE faculty_id = ? AND day_of_week = ? 
         AND start_time = ? AND end_time = ?",
        [$facultyId, $dayOfWeek, $startTime, $endTime]
    );
    
    if ($existingSchedule) {
        return $existingSchedule['schedule_id'];
    }
    
    // Create new temporary schedule with better metadata
    $scheduleId = insertData(
        "INSERT INTO availability_schedules (faculty_id, day_of_week, start_time, end_time, is_active) 
         VALUES (?, ?, ?, ?, 1)",
        [$facultyId, $dayOfWeek, $startTime, $endTime]
    );
    
    return $scheduleId;
}

/**
 * Delete temporary schedule entry if appointment creation fails - IMPROVED VERSION
 */
function deleteTemporaryScheduleImproved($scheduleId) {
    // Only delete if it's a non-recurring schedule (temporary) and has no appointments
    $appointmentCount = fetchRow(
        "SELECT COUNT(*) as count FROM appointments WHERE schedule_id = ?",
        [$scheduleId]
    );
    
    if ($appointmentCount['count'] == 0) {
        updateOrDeleteData(
            "DELETE FROM availability_schedules 
             WHERE schedule_id = ?",
            [$scheduleId]
        );
    }
}

/**
 * Validate student booking limits
 */
function validateStudentBookingLimits($studentId, $date) {
    // Check daily limit (e.g., max 3 appointments per day)
    $dailyCount = fetchRow(
        "SELECT COUNT(*) as count FROM appointments 
         WHERE student_id = ? AND appointment_date = ? AND is_cancelled = 0",
        [$studentId, $date]
    )['count'];
    
    if ($dailyCount >= 3) {
        return ['error' => 'You can only book up to 3 appointments per day.'];
    }
    
    // Check weekly limit (e.g., max 5 appointments per week)
    $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($date)));
    $weekEnd = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
    
    $weeklyCount = fetchRow(
        "SELECT COUNT(*) as count FROM appointments 
         WHERE student_id = ? AND appointment_date BETWEEN ? AND ? AND is_cancelled = 0",
        [$studentId, $weekStart, $weekEnd]
    )['count'];
    
    if ($weeklyCount >= 5) {
        return ['error' => 'You can only book up to 5 appointments per week.'];
    }
    
    return ['success' => true];
}

/**
 * Log booking attempt for analytics
 */
function logBookingAttempt($studentId, $facultyId, $date, $startTime, $endTime, $success, $error = null) {
    // This could be used for analytics and debugging
    error_log("Booking attempt: Student {$studentId}, Faculty {$facultyId}, Date {$date}, Time {$startTime}-{$endTime}, Success: " . ($success ? 'Yes' : 'No') . ($error ? ", Error: {$error}" : ''));
}
?>