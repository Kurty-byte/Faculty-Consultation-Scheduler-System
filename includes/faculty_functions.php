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

// NEW: Get available consultation slots for a faculty (replaces old function)
function getAvailableConsultationSlots($facultyId, $fromDate = null, $toDate = null) {
    // Include the new timeslot functions
    require_once 'timeslot_functions.php';
    
    return getAvailableTimeSlotsForFaculty($facultyId, $fromDate, $toDate);
}

// NEW: Check if faculty has consultation hours set up
function hasConsultationHoursSetup($facultyId) {
    $result = fetchRow(
        "SELECT COUNT(*) as count FROM consultation_hours 
         WHERE faculty_id = ? AND is_active = 1",
        [$facultyId]
    );
    
    return $result['count'] > 0;
}

// NEW: Get faculty consultation schedule summary
function getFacultyScheduleSummary($facultyId) {
    $consultationHours = fetchRows(
        "SELECT day_of_week, start_time, end_time FROM consultation_hours 
         WHERE faculty_id = ? AND is_active = 1 
         ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')",
        [$facultyId]
    );
    
    $breaks = fetchRows(
        "SELECT day_of_week, start_time, end_time, break_type, break_name FROM consultation_breaks 
         WHERE faculty_id = ? AND is_active = 1 
         ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')",
        [$facultyId]
    );
    
    return [
        'consultation_hours' => $consultationHours,
        'breaks' => $breaks
    ];
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