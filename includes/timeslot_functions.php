<?php
/**
 * Time Slot Generation and Management Functions
 * Simplified version without break functionality
 */

// Generate 30-minute time slots for a faculty member on a specific date
function generateTimeSlots($facultyId, $date) {
    $dayOfWeek = strtolower(date('l', strtotime($date)));
    
    // Get consultation hours for this day
    $consultationHours = getConsultationHours($facultyId, $dayOfWeek);
    
    if (empty($consultationHours)) {
        return [];
    }
    
    $slots = [];
    
    foreach ($consultationHours as $hours) {
        // Generate 30-minute slots within consultation hours
        $currentTime = strtotime($hours['start_time']);
        $endTime = strtotime($hours['end_time']);
        
        while ($currentTime < $endTime) {
            $slotStart = date('H:i:s', $currentTime);
            $slotEnd = date('H:i:s', $currentTime + (30 * 60)); // Add 30 minutes
            
            // Check if slot is available (not booked)
            if (isSlotAvailable($facultyId, $date, $slotStart, $slotEnd)) {
                $slots[] = [
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                    'formatted_time' => formatTime($slotStart) . ' - ' . formatTime($slotEnd),
                    'available' => true
                ];
            }
            
            $currentTime += (30 * 60); // Move to next 30-minute slot
        }
    }
    
    return $slots;
}

// Get consultation hours for a faculty member on a specific day
function getConsultationHours($facultyId, $dayOfWeek) {
    return fetchRows(
        "SELECT * FROM consultation_hours 
         WHERE faculty_id = ? AND day_of_week = ? AND is_active = 1 
         ORDER BY start_time",
        [$facultyId, $dayOfWeek]
    );
}

// Check if a time slot is available (not already booked)
function isSlotAvailable($facultyId, $date, $startTime, $endTime) {
    $result = fetchRow(
        "SELECT COUNT(*) as count FROM appointments a
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id
         WHERE s.faculty_id = ? AND a.appointment_date = ? 
         AND a.start_time = ? AND a.end_time = ?
         AND a.is_cancelled = 0",
        [$facultyId, $date, $startTime, $endTime]
    );
    
    return $result['count'] == 0;
}

// Get available slots for a faculty member within a date range
function getAvailableTimeSlotsForFaculty($facultyId, $fromDate = null, $toDate = null) {
    if (!$fromDate) {
        $fromDate = date('Y-m-d');
    }
    
    if (!$toDate) {
        $toDate = date('Y-m-d', strtotime('+30 days'));
    }
    
    $availableSlots = [];
    $currentDate = new DateTime($fromDate);
    $endDate = new DateTime($toDate);
    
    while ($currentDate <= $endDate) {
        $currentDateStr = $currentDate->format('Y-m-d');
        
        // Skip past dates
        if ($currentDateStr < date('Y-m-d')) {
            $currentDate->modify('+1 day');
            continue;
        }
        
        $slots = generateTimeSlots($facultyId, $currentDateStr);
        
        if (!empty($slots)) {
            $availableSlots[$currentDateStr] = [
                'date' => $currentDateStr,
                'formatted_date' => formatDate($currentDateStr),
                'day_name' => $currentDate->format('l'),
                'slots' => $slots
            ];
        }
        
        $currentDate->modify('+1 day');
    }
    
    return $availableSlots;
}

// Create consultation hours for a faculty member
function createConsultationHours($facultyId, $dayOfWeek, $startTime, $endTime, $notes = null) {
    // Validate time
    if (strtotime($endTime) <= strtotime($startTime)) {
        return false;
    }
    
    return insertData(
        "INSERT INTO consultation_hours (faculty_id, day_of_week, start_time, end_time, notes, is_active) 
         VALUES (?, ?, ?, ?, ?, 1)",
        [$facultyId, $dayOfWeek, $startTime, $endTime, $notes]
    );
}

// Update consultation hours
function updateConsultationHours($consultationHourId, $dayOfWeek, $startTime, $endTime, $isActive = 1, $notes = null) {
    // Validate time
    if (strtotime($endTime) <= strtotime($startTime)) {
        return false;
    }
    
    return updateOrDeleteData(
        "UPDATE consultation_hours 
         SET day_of_week = ?, start_time = ?, end_time = ?, notes = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP 
         WHERE consultation_hour_id = ?",
        [$dayOfWeek, $startTime, $endTime, $notes, $isActive, $consultationHourId]
    );
}

// Delete consultation hours
function deleteConsultationHours($consultationHourId) {
    return updateOrDeleteData(
        "DELETE FROM consultation_hours WHERE consultation_hour_id = ?",
        [$consultationHourId]
    );
}

// Get all consultation hours for a faculty member
function getFacultyConsultationHours($facultyId) {
    return fetchRows(
        "SELECT * FROM consultation_hours 
         WHERE faculty_id = ? 
         ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), 
         start_time",
        [$facultyId]
    );
}
?>