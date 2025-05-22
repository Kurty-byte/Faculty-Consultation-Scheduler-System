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
    
    // Validate and sanitize inputs
    $dayOfWeek = isset($_POST['day_of_week']) ? sanitize($_POST['day_of_week']) : '';
    $startTime = isset($_POST['start_time']) ? sanitize($_POST['start_time']) : '';
    $endTime = isset($_POST['end_time']) ? sanitize($_POST['end_time']) : '';
    $breakType = isset($_POST['break_type']) ? sanitize($_POST['break_type']) : 'lunch';
    $breakName = isset($_POST['break_name']) ? sanitize($_POST['break_name']) : null;
    
    // Validate required fields
    if (empty($dayOfWeek) || empty($startTime) || empty($endTime)) {
        setFlashMessage('danger', 'Please fill in all required fields.');
        redirect('pages/faculty/add_break.php');
    }
    
    // Validate time logic
    if (strtotime($endTime) <= strtotime($startTime)) {
        setFlashMessage('danger', 'End time must be after start time.');
        redirect('pages/faculty/add_break.php?day=' . $dayOfWeek);
    }
    
    // Validate break type
    $validBreakTypes = ['lunch', 'meeting', 'personal', 'other'];
    if (!in_array($breakType, $validBreakTypes)) {
        setFlashMessage('danger', 'Invalid break type selected.');
        redirect('pages/faculty/add_break.php?day=' . $dayOfWeek);
    }
    
    // Require break name for 'other' type
    if ($breakType === 'other' && empty($breakName)) {
        setFlashMessage('danger', 'Please provide a name/description for this break.');
        redirect('pages/faculty/add_break.php?day=' . $dayOfWeek);
    }
    
    // Check if faculty has consultation hours for this day
    $consultationHours = getConsultationHours($facultyId, $dayOfWeek);
    
    if (empty($consultationHours)) {
        setFlashMessage('danger', 'You don\'t have consultation hours set for ' . ucfirst($dayOfWeek) . '.');
        redirect('pages/faculty/add_break.php');
    }
    
    // Validate that break is within consultation hours
    $isWithinHours = false;
    foreach ($consultationHours as $hours) {
        if ($startTime >= $hours['start_time'] && $endTime <= $hours['end_time']) {
            $isWithinHours = true;
            break;
        }
    }
    
    if (!$isWithinHours) {
        setFlashMessage('danger', 'Break must be within your consultation hours for ' . ucfirst($dayOfWeek) . '.');
        redirect('pages/faculty/add_break.php?day=' . $dayOfWeek);
    }
    
    // Check for overlapping breaks
    $existingBreaks = getConsultationBreaks($facultyId, $dayOfWeek);
    foreach ($existingBreaks as $existingBreak) {
        $existingStart = $existingBreak['start_time'];
        $existingEnd = $existingBreak['end_time'];
        
        // Check for time overlap
        if (($startTime >= $existingStart && $startTime < $existingEnd) ||
            ($endTime > $existingStart && $endTime <= $existingEnd) ||
            ($startTime <= $existingStart && $endTime >= $existingEnd)) {
            
            setFlashMessage('danger', 'This break overlaps with an existing break (' . 
                formatTime($existingStart) . ' - ' . formatTime($existingEnd) . ').');
            redirect('pages/faculty/add_break.php?day=' . $dayOfWeek);
        }
    }
    
    // Check if break would conflict with existing appointments
    $conflictingAppointments = fetchRows(
        "SELECT a.appointment_id, a.start_time, a.end_time, u.first_name, u.last_name
         FROM appointments a
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id
         JOIN students st ON a.student_id = st.student_id
         JOIN users u ON st.user_id = u.user_id
         WHERE s.faculty_id = ? 
         AND a.appointment_date >= CURDATE()
         AND DAYOFWEEK(a.appointment_date) = ?
         AND ((a.start_time >= ? AND a.start_time < ?) OR 
              (a.end_time > ? AND a.end_time <= ?) OR
              (a.start_time <= ? AND a.end_time >= ?))
         AND a.is_cancelled = 0",
        [
            $facultyId,
            getDayOfWeekNumber($dayOfWeek),
            $startTime, $endTime,
            $startTime, $endTime,
            $startTime, $endTime
        ]
    );
    
    if (!empty($conflictingAppointments)) {
        $conflictMessage = 'This break would conflict with existing appointments: ';
        $conflicts = [];
        foreach ($conflictingAppointments as $appointment) {
            $conflicts[] = $appointment['first_name'] . ' ' . $appointment['last_name'] . 
                          ' (' . formatTime($appointment['start_time']) . ' - ' . formatTime($appointment['end_time']) . ')';
        }
        $conflictMessage .= implode(', ', $conflicts);
        
        setFlashMessage('warning', $conflictMessage . '. Please cancel these appointments first or choose a different time.');
        redirect('pages/faculty/add_break.php?day=' . $dayOfWeek);
    }
    
    // Create the break
    try {
        $result = createConsultationBreak($facultyId, $dayOfWeek, $startTime, $endTime, $breakType, $breakName);
        
        if ($result) {
            // Calculate how many appointment slots this break blocks
            $start = strtotime($startTime);
            $end = strtotime($endTime);
            $diffHours = ($end - $start) / 3600;
            $blockedSlots = ceil($diffHours * 2); // 30-minute slots
            
            $successMessage = 'Break added successfully for ' . ucfirst($dayOfWeek) . ' (' . 
                             formatTime($startTime) . ' - ' . formatTime($endTime) . ').';
            
            if ($blockedSlots > 0) {
                $successMessage .= ' This blocks ' . $blockedSlots . ' appointment slot' . ($blockedSlots > 1 ? 's' : '') . '.';
            }
            
            setFlashMessage('success', $successMessage);
            redirect('pages/faculty/manage_breaks.php');
        } else {
            throw new Exception('Failed to create break in database.');
        }
        
    } catch (Exception $e) {
        setFlashMessage('danger', 'Failed to add break: ' . $e->getMessage());
        redirect('pages/faculty/add_break.php?day=' . $dayOfWeek);
    }
    
} else {
    // Not a POST request, redirect to add break page
    redirect('pages/faculty/add_break.php');
}

// Helper function to convert day name to MySQL DAYOFWEEK number
function getDayOfWeekNumber($dayName) {
    $days = [
        'sunday' => 1,
        'monday' => 2,
        'tuesday' => 3,
        'wednesday' => 4,
        'thursday' => 5,
        'friday' => 6,
        'saturday' => 7
    ];
    
    return isset($days[strtolower($dayName)]) ? $days[strtolower($dayName)] : 1;
}
?>