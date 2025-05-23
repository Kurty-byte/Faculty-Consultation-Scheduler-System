<?php
// Get all active faculty members - FIXED to always include department_name
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

// Get faculty by department - FIXED to include department_name
function getFacultyByDepartment($departmentId) {
    return fetchRows(
        "SELECT f.faculty_id, u.first_name, u.last_name, d.department_name, f.office_email, f.office_phone_number 
         FROM faculty f 
         JOIN users u ON f.user_id = u.user_id 
         JOIN departments d ON f.department_id = d.department_id 
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

// Get available consultation slots for a faculty (using improved timeslot functions)
function getAvailableConsultationSlots($facultyId, $fromDate = null, $toDate = null) {
    // Include the timeslot functions
    require_once 'timeslot_functions.php';
    
    return getAvailableConsultationSlotsImproved($facultyId, $fromDate, $toDate);
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

// Get number of available slots per week for a faculty (improved to account for bookings)
function getFacultyWeeklySlots($facultyId) {
    $totalHours = getFacultyWeeklyHours($facultyId);
    $theoreticalSlots = $totalHours * 2; // 30-minute slots = 2 per hour
    
    // Get actual available slots for current week
    $startOfWeek = date('Y-m-d', strtotime('monday this week'));
    $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
    
    require_once 'timeslot_functions.php';
    $availableSlots = getAvailableConsultationSlotsImproved($facultyId, $startOfWeek, $endOfWeek);
    
    $actualAvailableSlots = 0;
    foreach ($availableSlots as $daySlots) {
        $actualAvailableSlots += count($daySlots['slots']);
    }
    
    return [
        'theoretical' => $theoreticalSlots,
        'available' => $actualAvailableSlots,
        'booked' => $theoreticalSlots - $actualAvailableSlots
    ];
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

// Get faculty availability status for dashboard
function getFacultyAvailabilityStatus($facultyId) {
    require_once 'timeslot_functions.php';
    
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $nextWeek = date('Y-m-d', strtotime('+7 days'));
    
    // Get today's remaining slots
    $todaySlots = generateTimeSlotsImproved($facultyId, $today);
    $tomorrowSlots = generateTimeSlotsImproved($facultyId, $tomorrow);
    
    // Get next 7 days availability
    $weekSlots = getAvailableConsultationSlotsImproved($facultyId, $today, $nextWeek);
    $totalWeekSlots = 0;
    foreach ($weekSlots as $daySlots) {
        $totalWeekSlots += count($daySlots['slots']);
    }
    
    return [
        'today_slots' => count($todaySlots),
        'tomorrow_slots' => count($tomorrowSlots),
        'week_slots' => $totalWeekSlots,
        'has_consultation_hours' => hasConsultationHoursSetup($facultyId)
    ];
}

// Get faculty booking statistics
function getFacultyBookingStats($facultyId, $period = 'month') {
    $dateCondition = '';
    $params = [$facultyId];
    
    switch ($period) {
        case 'today':
            $dateCondition = "AND a.appointment_date = CURDATE()";
            break;
        case 'week':
            $dateCondition = "AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $dateCondition = "AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
    }
    
    $stats = fetchRow(
        "SELECT 
            COUNT(*) as total_appointments,
            SUM(CASE WHEN a.is_approved = 1 AND a.is_cancelled = 0 THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN a.is_approved = 0 AND a.is_cancelled = 0 THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN a.is_cancelled = 1 THEN 1 ELSE 0 END) as cancelled,
            AVG(CASE WHEN a.is_approved = 1 THEN TIMESTAMPDIFF(HOUR, a.appointed_on, a.updated_on) END) as avg_approval_time_hours
         FROM appointments a
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id
         WHERE s.faculty_id = ? $dateCondition",
        $params
    );
    
    return $stats;
}

// DEPRECATED: Keep for backward compatibility but mark as deprecated
function getAvailableSchedulesForFaculty($facultyId, $fromDate = null, $toDate = null) {
    // This function is deprecated, use getAvailableConsultationSlots instead
    return getAvailableConsultationSlots($facultyId, $fromDate, $toDate);
}

// DEPRECATED: Keep for backward compatibility - improved version
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

// Check if faculty member is available for emergency slots
function isFacultyAvailableForEmergency($facultyId) {
    $today = date('Y-m-d');
    $currentTime = date('H:i:s');
    
    // Check if there are consultation hours set for today
    $dayOfWeek = strtolower(date('l'));
    $consultationHours = fetchRows(
        "SELECT * FROM consultation_hours 
         WHERE faculty_id = ? AND day_of_week = ? AND is_active = 1 
         AND start_time <= ? AND end_time > ?
         ORDER BY start_time",
        [$facultyId, $dayOfWeek, $currentTime, $currentTime]
    );
    
    return !empty($consultationHours);
}

// Get faculty workload summary
function getFacultyWorkloadSummary($facultyId) {
    $weeklyHours = getFacultyWeeklyHours($facultyId);
    $weeklySlots = getFacultyWeeklySlots($facultyId);
    $bookingStats = getFacultyBookingStats($facultyId, 'month');
    $availabilityStatus = getFacultyAvailabilityStatus($facultyId);
    
    return [
        'weekly_hours' => $weeklyHours,
        'weekly_slots' => $weeklySlots,
        'monthly_stats' => $bookingStats,
        'current_availability' => $availabilityStatus
    ];
}
?>