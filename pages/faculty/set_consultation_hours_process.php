<?php
// Include config file
require_once '../../config.php';

// Check if user is logged in and has faculty role
requireRole('faculty');

// Include required functions
require_once '../../includes/timeslot_functions.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get faculty ID
    $facultyId = getFacultyIdFromUserId($_SESSION['user_id']);
    
    // Get form data
    $days = isset($_POST['days']) ? $_POST['days'] : [];
    
    if (empty($days)) {
        setFlashMessage('danger', 'Please select at least one day for consultation hours.');
        redirect('pages/faculty/set_consultation_hours.php');
    }
    
    // Start transaction
    global $conn;
    mysqli_begin_transaction($conn);
    
    try {
        // First, delete existing consultation hours for this faculty
        updateOrDeleteData(
            "DELETE FROM consultation_hours WHERE faculty_id = ?",
            [$facultyId]
        );
        
        // Process each day
        foreach ($days as $dayOfWeek => $dayData) {
            if (isset($dayData['enabled']) && $dayData['enabled'] == '1') {
                $startTime = sanitize($dayData['start_time']);
                $endTime = sanitize($dayData['end_time']);
                
                // Validate time inputs
                if (empty($startTime) || empty($endTime)) {
                    throw new Exception("Please set both start and end times for " . ucfirst($dayOfWeek));
                }
                
                if (strtotime($endTime) <= strtotime($startTime)) {
                    throw new Exception("End time must be after start time for " . ucfirst($dayOfWeek));
                }
                
                // Create consultation hours for this day
                $result = createConsultationHours($facultyId, $dayOfWeek, $startTime, $endTime);
                
                if (!$result) {
                    throw new Exception("Failed to create consultation hours for " . ucfirst($dayOfWeek));
                }
            }
        }
        
        // Commit the transaction
        mysqli_commit($conn);
        
        // Check if faculty has any breaks set up
        $existingBreaks = getFacultyConsultationBreaks($facultyId);
        
        if (empty($existingBreaks)) {
            // Suggest setting up breaks
            setFlashMessage('success', 'Consultation hours saved successfully! Would you like to add breaks (lunch, meetings) to your schedule?');
            redirect('pages/faculty/manage_breaks.php?first_time=1');
        } else {
            setFlashMessage('success', 'Consultation hours updated successfully!');
            redirect('pages/faculty/consultation_hours.php');
        }
        
    } catch (Exception $e) {
        // Rollback the transaction
        mysqli_rollback($conn);
        
        setFlashMessage('danger', 'Failed to save consultation hours: ' . $e->getMessage());
        redirect('pages/faculty/set_consultation_hours.php');
    }
} else {
    // Not a POST request, redirect to set consultation hours page
    redirect('pages/faculty/set_consultation_hours.php');
}
?>