<?php
// Get all schedules for a faculty
function getFacultySchedules($facultyId) {
    return fetchRows(
        "SELECT * FROM availability_schedules 
         WHERE faculty_id = ? 
         ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), 
         start_time",
        [$facultyId]
    );
}

// Get a specific schedule by ID
function getScheduleById($scheduleId) {
    return fetchRow(
        "SELECT * FROM availability_schedules WHERE schedule_id = ?",
        [$scheduleId]
    );
}

// Create a new schedule
function createSchedule($facultyId, $dayOfWeek, $startTime, $endTime) {
    // Validate time (end time must be after start time)
    if (strtotime($endTime) <= strtotime($startTime)) {
        return false;
    }
    
    return insertData(
        "INSERT INTO availability_schedules (faculty_id, day_of_week, start_time, end_time, is_active) 
         VALUES (?, ?, ?, ?, 1)",
        [$facultyId, $dayOfWeek, $startTime, $endTime]
    );
}

// Update an existing schedule
function updateSchedule($scheduleId, $dayOfWeek, $startTime, $endTime, $isActive) {
    // Validate time (end time must be after start time)
    if (strtotime($endTime) <= strtotime($startTime)) {
        return false;
    }
    
    return updateOrDeleteData(
        "UPDATE availability_schedules 
         SET day_of_week = ?, start_time = ?, end_time = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP 
         WHERE schedule_id = ?",
        [$dayOfWeek, $startTime, $endTime, $isActive, $scheduleId]
    );
}

// Delete a schedule
function deleteSchedule($scheduleId) {
    // Check if the schedule has active appointments (not cancelled)
    $hasActiveAppointments = fetchRow(
        "SELECT COUNT(*) as count FROM appointments 
         WHERE schedule_id = ? AND is_cancelled = 0",
        [$scheduleId]
    );
    
    if ($hasActiveAppointments && $hasActiveAppointments['count'] > 0) {
        // Instead of deleting, mark as inactive if there are active appointments
        return updateOrDeleteData(
            "UPDATE availability_schedules SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE schedule_id = ?",
            [$scheduleId]
        );
    } else {
        // No active appointments, safe to delete
        return updateOrDeleteData(
            "DELETE FROM availability_schedules WHERE schedule_id = ?",
            [$scheduleId]
        );
    }
}

// Check if a schedule has any appointments (regardless of status)
function scheduleHasAppointments($scheduleId) {
    $result = fetchRow(
        "SELECT COUNT(*) as count FROM appointments WHERE schedule_id = ?",
        [$scheduleId]
    );
    
    return $result && $result['count'] > 0;
}

// Check if a schedule has any active appointments
function scheduleHasActiveAppointments($scheduleId) {
    $result = fetchRow(
        "SELECT COUNT(*) as count FROM appointments 
         WHERE schedule_id = ? AND is_cancelled = 0",
        [$scheduleId]
    );
    
    return $result && $result['count'] > 0;
}

// Format day of week for display
function formatDayOfWeek($dayOfWeek) {
    return ucfirst($dayOfWeek);
}

// Get all days of week
function getDaysOfWeek() {
    return [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday'
    ];
}


?>