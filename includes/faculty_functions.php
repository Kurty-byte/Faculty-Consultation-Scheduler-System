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

// Get available schedules for a faculty
function getAvailableSchedulesForFaculty($facultyId, $fromDate = null, $toDate = null) {
    // Default to current date if not specified
    if (!$fromDate) {
        $fromDate = date('Y-m-d');
    }
    
    // Default to 30 days from now if not specified
    if (!$toDate) {
        $toDate = date('Y-m-d', strtotime('+30 days'));
    }
    
    // Get all active schedules for the faculty
    $schedules = fetchRows(
        "SELECT * FROM availability_schedules 
         WHERE faculty_id = ? AND is_active = 1 
         ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), 
         start_time",
        [$facultyId]
    );
    
    // Generate available dates based on schedules
    $availableDates = [];
    $currentDate = new DateTime($fromDate);
    $endDate = new DateTime($toDate);
    
    while ($currentDate <= $endDate) {
        $currentDayOfWeek = strtolower($currentDate->format('l'));
        $currentDateStr = $currentDate->format('Y-m-d');
        
        // Skip dates in the past
        if ($currentDateStr < date('Y-m-d')) {
            $currentDate->modify('+1 day');
            continue;
        }
        
        foreach ($schedules as $schedule) {
            if ($schedule['day_of_week'] === $currentDayOfWeek) {
                // The key fix: Include both recurring and non-recurring schedules
                // We will no longer skip non-recurring schedules
                
                // Check if this slot is already booked
                $isBooked = checkSlotBooked(
                    $schedule['schedule_id'], 
                    $currentDateStr, 
                    $schedule['start_time'], 
                    $schedule['end_time']
                );
                
                if (!$isBooked) {
                    $availableDates[] = [
                        'date' => $currentDateStr,
                        'day_of_week' => $currentDayOfWeek,
                        'schedule_id' => $schedule['schedule_id'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time']
                    ];
                }
            }
        }
        
        $currentDate->modify('+1 day');
    }
    
    return $availableDates;
}

// Check if a slot is already booked
function checkSlotBooked($scheduleId, $date, $startTime, $endTime) {
    $result = fetchRow(
        "SELECT COUNT(*) as count FROM appointments 
         WHERE schedule_id = ? AND appointment_date = ? 
         AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
         AND is_cancelled = 0",
        [$scheduleId, $date, $endTime, $startTime, $endTime, $startTime]
    );
    
    return $result['count'] > 0;
}
?>