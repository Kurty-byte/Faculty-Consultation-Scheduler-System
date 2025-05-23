<?php
// Get all active faculty members
function getAllActiveFaculty() {
    return fetchRows(
        "SELECT f.faculty_id, u.first_name, u.last_name, d.department_name, f.office_email, f.office_phone_number 
         FROM faculty f 
         JOIN users u ON f.user_id = u.user_id 
         JOIN departments d ON f.department_id = d.department_id 
         WHERE u.is_active = 1 AND f.status = 'active' 
         ORDER BY u.last_name, u.first_name"
    );
}

// Get faculty details by ID
function getFacultyDetails($facultyId) {
    return fetchRow(
        "SELECT f.faculty_id, u.first_name, u.last_name, d.department_name, f.office_email, f.office_phone_number 
         FROM faculty f 
         JOIN users u ON f.user_id = u.user_id 
         JOIN departments d ON f.department_id = d.department_id 
         WHERE f.faculty_id = ?",
        [$facultyId]
    );
}

// Get faculty name
function getFacultyName($facultyId) {
    $faculty = getFacultyDetails($facultyId);
    
    if ($faculty) {
        return $faculty['first_name'] . ' ' . $faculty['last_name'];
    }
    
    return 'Unknown Faculty';
}

// Get faculty by department
function getFacultyByDepartment($departmentId) {
    return fetchRows(
        "SELECT f.faculty_id, u.first_name, u.last_name, f.office_email, f.office_phone_number 
         FROM faculty f 
         JOIN users u ON f.user_id = u.user_id 
         WHERE f.department_id = ? AND u.is_active = 1 AND f.status = 'active' 
         ORDER BY u.last_name, u.first_name",
        [$departmentId]
    );
}

// Get all departments with active faculty
function getDepartmentsWithFaculty() {
    return fetchRows(
        "SELECT DISTINCT d.department_id, d.department_name 
         FROM departments d 
         JOIN faculty f ON d.department_id = f.department_id 
         JOIN users u ON f.user_id = u.user_id 
         WHERE u.is_active = 1 AND f.status = 'active' 
         ORDER BY d.department_name"
    );
}

// Get available consultation slots for a faculty (using new timeslot functions)
function getAvailableConsultationSlots($facultyId, $fromDate = null, $toDate = null) {
    // Include the timeslot functions
    require_once 'timeslot_functions.php';
    
    return getAvailableTimeSlotsForFaculty($facultyId, $fromDate, $toDate);
}

// Check if faculty has consultation hours set up
function hasConsultationHoursSetup($facultyId) {
    $result = fetchRow(
        "SELECT COUNT(*) as count FROM consultation_hours 
         WHERE faculty_id = ? AND is_active = 1",
        [$facultyId]
    );
    
    return $result['count'] > 0;
}

// Get faculty consultation schedule summary (simplified without breaks)
function getFacultyScheduleSummary($facultyId) {
    $consultationHours = fetchRows(
        "SELECT day_of_week, start_time, end_time, notes FROM consultation_hours 
         WHERE faculty_id = ? AND is_active = 1 
         ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')",
        [$facultyId]
    );
    
    return [
        'consultation_hours' => $consultationHours
    ];
}

// Get total available hours per week for a faculty
function getFacultyWeeklyHours($facultyId) {
    $consultationHours = fetchRows(
        "SELECT start_time, end_time FROM consultation_hours 
         WHERE faculty_id = ? AND is_active = 1",
        [$facultyId]
    );
    
    $totalHours = 0;
    foreach ($consultationHours as $hours) {
        $start = strtotime($hours['start_time']);
        $end = strtotime($hours['end_time']);
        $totalHours += ($end - $start) / 3600; // Convert to hours
    }
    
    return $totalHours;
}

// Get number of available slots per week for a faculty
function getFacultyWeeklySlots($facultyId) {
    $totalHours = getFacultyWeeklyHours($facultyId);
    return $totalHours * 2; // 30-minute slots = 2 per hour
}

// Check if a specific day has consultation hours
function hasDayConsultationHours($facultyId, $dayOfWeek) {
    $result = fetchRow(
        "SELECT COUNT(*) as count FROM consultation_hours 
         WHERE faculty_id = ? AND day_of_week = ? AND is_active = 1",
        [$facultyId, $dayOfWeek]
    );
    
    return $result['count'] > 0;
}

// Get consultation hours for a specific day
function getDayConsultationHours($facultyId, $dayOfWeek) {
    return fetchRows(
        "SELECT * FROM consultation_hours 
         WHERE faculty_id = ? AND day_of_week = ? AND is_active = 1 
         ORDER BY start_time",
        [$facultyId, $dayOfWeek]
    );
}

// DEPRECATED: Keep for backward compatibility but mark as deprecated
function getAvailableSchedulesForFaculty($facultyId, $fromDate = null, $toDate = null) {
    // This function is deprecated, use getAvailableConsultationSlots instead
    return getAvailableConsultationSlots($facultyId, $fromDate, $toDate);
}

// DEPRECATED: Keep for backward compatibility
function checkSlotBooked($scheduleId, $date, $startTime, $endTime) {
    $result = fetchRow(
        "SELECT COUNT(*) as count FROM appointments 
         WHERE schedule_id = ? AND appointment_date = ? 
         AND start_time = ? AND end_time = ?
         AND is_cancelled = 0",
        [$scheduleId, $date, $startTime, $endTime]
    );
    
    return $result['count'] > 0;
}
?>