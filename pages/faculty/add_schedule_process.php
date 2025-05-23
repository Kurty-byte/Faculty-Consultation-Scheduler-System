<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Include required functions
require_once '../../includes/schedule_functions.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize inputs
    $dayOfWeek = sanitize($_POST['day_of_week']);
    $startTime = sanitize($_POST['start_time']);
    $endTime = sanitize($_POST['end_time']);
    
    // Validate time inputs
    if ($startTime >= $endTime) {
        setFlashMessage('danger', 'End time must be after start time.');
        redirect('pages/faculty/add_schedule.php');
    }
    
    // Get faculty ID
    $facultyId = getFacultyIdFromUserId($_SESSION['user_id']);
    
    // Create the schedule
    $result = createSchedule($facultyId, $dayOfWeek, $startTime, $endTime);
    
    if ($result) {
        setFlashMessage('success', 'Schedule created successfully.');
        redirect('pages/faculty/manage_schedules.php');
    } else {
        setFlashMessage('danger', 'Failed to create schedule. Please try again.');
        redirect('pages/faculty/add_schedule.php');
    }
} else {
    // Not a POST request, redirect to add schedule page
    redirect('pages/faculty/add_schedule.php');
}
?>