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
    $scheduleId = (int)$_POST['schedule_id'];
    $dayOfWeek = sanitize($_POST['day_of_week']);
    $startTime = sanitize($_POST['start_time']);
    $endTime = sanitize($_POST['end_time']);
    $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    
    // Validate time inputs
    if ($startTime >= $endTime) {
        setFlashMessage('danger', 'End time must be after start time.');
        redirect('pages/faculty/edit_schedule.php?id=' . $scheduleId);
    }
    
    // Get faculty ID
    $facultyId = getFacultyIdFromUserId($_SESSION['user_id']);
    
    // Get schedule details to verify ownership
    $schedule = getScheduleById($scheduleId);
    
    // Check if schedule exists and belongs to this faculty
    if (!$schedule || $schedule['faculty_id'] != $facultyId) {
        setFlashMessage('danger', 'Schedule not found or you do not have permission to edit it.');
        redirect('pages/faculty/manage_schedules.php');
    }
    
    // Update the schedule
    $result = updateSchedule($scheduleId, $dayOfWeek, $startTime, $endTime, $isActive);
    
    if ($result) {
        setFlashMessage('success', 'Schedule updated successfully.');
        redirect('pages/faculty/manage_schedules.php');
    } else {
        setFlashMessage('danger', 'Failed to update schedule. Please try again.');
        redirect('pages/faculty/edit_schedule.php?id=' . $scheduleId);
    }
} else {
    // Not a POST request, redirect to manage schedules page
    redirect('pages/faculty/manage_schedules.php');
}
?>