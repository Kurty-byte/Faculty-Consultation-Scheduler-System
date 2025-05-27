<?php
/**
 * Time Slot Generation and Management Functions
 * Improved version with booking filtering and past time filtering
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
    $isToday = ($date === date('Y-m-d'));
    $currentTime = $isToday ? time() : 0;
    
    foreach ($consultationHours as $hours) {
        // Generate 30-minute slots within consultation hours
        $currentSlotTime = strtotime($date . ' ' . $hours['start_time']);
        $endTime = strtotime($date . ' ' . $hours['end_time']);
        
        while ($currentSlotTime < $endTime) {
            $slotStart = date('H:i:s', $currentSlotTime);
            $slotEnd = date('H:i:s', $currentSlotTime + (30 * 60)); // Add 30 minutes
            
            // Skip past time slots if it's today
            if ($isToday && $currentSlotTime <= $currentTime) {
                $currentSlotTime += (30 * 60);
                continue;
            }
            
            // Check if slot is available (not booked)
            if (isSlotAvailable($facultyId, $date, $slotStart, $slotEnd)) {
                $slots[] = [
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                    'formatted_time' => formatTime($slotStart) . ' - ' . formatTime($slotEnd),
                    'available' => true
                ];
            }
            
            $currentSlotTime += (30 * 60); // Move to next 30-minute slot
        }
    }
    
    return $slots;
}

// Improved function to get available consultation slots with filtering
function getAvailableConsultationSlotsImproved($facultyId, $fromDate = null, $toDate = null) {
    if (!$fromDate) {
        $fromDate = date('Y-m-d');
    }
    
    if (!$toDate) {
        $toDate = date('Y-m-d', strtotime('+30 days'));
    }
    
    $availableSlots = [];
    $currentDate = new DateTime($fromDate);
    $endDate = new DateTime($toDate);
    $today = date('Y-m-d');
    $currentTime = date('H:i:s');
    
    while ($currentDate <= $endDate) {
        $currentDateStr = $currentDate->format('Y-m-d');
        
        // Skip past dates completely
        if ($currentDateStr < $today) {
            $currentDate->modify('+1 day');
            continue;
        }
        
        // Generate slots with improved filtering
        $slots = generateTimeSlotsImproved($facultyId, $currentDateStr);
        
        // Additional filtering for today's slots
        if ($currentDateStr === $today) {
            $slots = array_filter($slots, function($slot) use ($currentTime) {
                return $slot['start_time'] > date('H:i:s', strtotime($currentTime . ' +1 hour'));
            });
        }
        
        if (!empty($slots)) {
            $availableSlots[$currentDateStr] = [
                'date' => $currentDateStr,
                'formatted_date' => formatDate($currentDateStr),
                'day_name' => $currentDate->format('l'),
                'slots' => array_values($slots) // Re-index array after filtering
            ];
        }
        
        $currentDate->modify('+1 day');
    }
    
    return $availableSlots;
}

// Improved slot generation with better filtering
function generateTimeSlotsImproved($facultyId, $date) {
    $dayOfWeek = strtolower(date('l', strtotime($date)));
    
    // Get consultation hours for this day
    $consultationHours = getConsultationHours($facultyId, $dayOfWeek);
    
    if (empty($consultationHours)) {
        return [];
    }
    
    $slots = [];
    $isToday = ($date === date('Y-m-d'));
    $currentDateTime = new DateTime();
    
    foreach ($consultationHours as $hours) {
        // Generate 30-minute slots within consultation hours
        $currentSlotTime = strtotime($date . ' ' . $hours['start_time']);
        $endTime = strtotime($date . ' ' . $hours['end_time']);
        
        while ($currentSlotTime < $endTime) {
            $slotStart = date('H:i:s', $currentSlotTime);
            $slotEnd = date('H:i:s', $currentSlotTime + (30 * 60));
            
            // Enhanced filtering for past time slots
            if ($isToday) {
                $slotDateTime = new DateTime($date . ' ' . $slotStart);
                $bufferTime = clone $currentDateTime;
                $bufferTime->add(new DateInterval('PT1H')); // Add 1 hour buffer for booking
                
                if ($slotDateTime <= $bufferTime) {
                    $currentSlotTime += (30 * 60);
                    continue;
                }
            }
            
            // Check if slot is available (not booked and not cancelled)
            if (isSlotAvailableImproved($facultyId, $date, $slotStart, $slotEnd)) {
                $slots[] = [
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                    'formatted_time' => formatTime($slotStart) . ' - ' . formatTime($slotEnd),
                    'available' => true
                ];
            }
            
            $currentSlotTime += (30 * 60); // Move to next 30-minute slot
        }
    }
    
    return $slots;
}

// Improved availability check that properly filters booked appointments
function isSlotAvailableImproved($facultyId, $date, $startTime, $endTime) {
    // Check for any non-cancelled appointments in this time slot
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

// Get consultation hours for a faculty member on a specific day
function getConsultationHours($facultyId, $dayOfWeek) {
    return fetchRows(
        "SELECT * FROM consultation_hours 
         WHERE faculty_id = ? AND day_of_week = ? AND is_active = 1 
         ORDER BY start_time",
        [$facultyId, $dayOfWeek]
    );
}

// Check if a time slot is available (not already booked) - DEPRECATED, use isSlotAvailableImproved
function isSlotAvailable($facultyId, $date, $startTime, $endTime) {
    return isSlotAvailableImproved($facultyId, $date, $startTime, $endTime);
}

// Get available slots for a faculty member within a date range - IMPROVED
function getAvailableTimeSlotsForFaculty($facultyId, $fromDate = null, $toDate = null) {
    return getAvailableConsultationSlotsImproved($facultyId, $fromDate, $toDate);
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

// Get today's remaining slots for a faculty member
function getTodayRemainingSlots($facultyId) {
    $today = date('Y-m-d');
    $slots = generateTimeSlotsImproved($facultyId, $today);
    return count($slots);
}

// Get next available slot for a faculty member
function getNextAvailableSlot($facultyId) {
    $fromDate = date('Y-m-d');
    $toDate = date('Y-m-d', strtotime('+7 days')); // Check next 7 days
    
    $availableSlots = getAvailableConsultationSlotsImproved($facultyId, $fromDate, $toDate);
    
    foreach ($availableSlots as $dateSlots) {
        if (!empty($dateSlots['slots'])) {
            return [
                'date' => $dateSlots['date'],
                'formatted_date' => $dateSlots['formatted_date'],
                'day_name' => $dateSlots['day_name'],
                'first_slot' => $dateSlots['slots'][0]
            ];
        }
    }
    
    return null; // No available slots in the next 7 days
}

// Check if a faculty has any slots available today
function hasSlotsAvailableToday($facultyId) {
    $today = date('Y-m-d');
    $slots = generateTimeSlotsImproved($facultyId, $today);
    return !empty($slots);
}

// Get appointment count for a specific time slot (for debugging)
function getSlotAppointmentCount($facultyId, $date, $startTime, $endTime) {
    $result = fetchRow(
        "SELECT COUNT(*) as count,
                SUM(CASE WHEN a.is_cancelled = 0 THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN a.is_cancelled = 1 THEN 1 ELSE 0 END) as cancelled_count
         FROM appointments a
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id
         WHERE s.faculty_id = ? AND a.appointment_date = ? 
         AND a.start_time = ? AND a.end_time = ?",
        [$facultyId, $date, $startTime, $endTime]
    );
    
    return $result;
}
?>