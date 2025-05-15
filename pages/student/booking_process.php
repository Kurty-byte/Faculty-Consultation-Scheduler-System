<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has student role
requireRole('student');

// Include required functions
require_once '../../includes/appointment_functions.php';
require_once '../../includes/faculty_functions.php';  // Make sure this is included

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize inputs
    $scheduleId = (int)$_POST['schedule_id'];
    $date = sanitize($_POST['date']);
    $startTime = sanitize($_POST['start_time']);
    $endTime = sanitize($_POST['end_time']);
    $modality = sanitize($_POST['modality']);
    $remarks = sanitize($_POST['remarks']);
    
    // Get platform or location based on modality
    $platform = null;
    $location = null;
    
    if ($modality === 'virtual') {
        $platform = isset($_POST['platform']) ? sanitize($_POST['platform']) : null;
    } else {
        $location = isset($_POST['location']) ? sanitize($_POST['location']) : null;
    }
    
    // Get student ID
    $student = fetchRow("SELECT student_id FROM students WHERE user_id = ?", [$_SESSION['user_id']]);
    
    if (!$student) {
        setFlashMessage('danger', 'Student profile not found.');
        redirect('pages/student/view_faculty.php');
    }
    
    $studentId = $student['student_id'];
    
    // Get the faculty ID from the schedule
    $schedule = fetchRow("SELECT faculty_id FROM availability_schedules WHERE schedule_id = ?", [$scheduleId]);
    $facultyId = $schedule ? $schedule['faculty_id'] : null;
    
    // Create the appointment
    try {
        $appointmentId = createAppointment($studentId, $scheduleId, $date, $startTime, $endTime, $remarks, $modality, $platform, $location);
        
        setFlashMessage('success', 'Your appointment request has been submitted successfully. Please wait for the faculty member to approve it.');
        redirect('pages/student/view_appointments.php');
    } catch (Exception $e) {
        setFlashMessage('danger', 'Failed to book appointment: ' . $e->getMessage());
        
        if ($facultyId) {
            redirect('pages/student/faculty_schedule.php?id=' . $facultyId);
        } else {
            redirect('pages/student/view_faculty.php');
        }
    }
} else {
    // Not a POST request, redirect to faculty directory
    redirect('pages/student/view_faculty.php');
}
?>