<?php
// First, include the faculty_functions.php file that contains the checkSlotBooked function
require_once 'faculty_functions.php';
require_once 'timeslot_functions.php';

// Create a new appointment (updated for consultation hours system with improved validation)
function createAppointment($studentId, $scheduleId, $date, $startTime, $endTime, $remarks, $modality, $platform = null, $location = null) {
    global $conn;
    
    // Start a transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get faculty_id from the schedule
        $schedule = fetchRow(
            "SELECT faculty_id FROM availability_schedules WHERE schedule_id = ?",
            [$scheduleId]
        );
        
        if (!$schedule) {
            throw new Exception("Invalid schedule ID.");
        }
        
        $facultyId = $schedule['faculty_id'];
        
        // Calculate slot duration
        $slotDuration = (strtotime($endTime) - strtotime($startTime)) / 60; // in minutes
        
        // Insert the appointment WITH faculty_id
        $appointmentId = insertData(
            "INSERT INTO appointments (schedule_id, student_id, faculty_id, appointment_date, start_time, end_time, remarks, is_approved, is_cancelled, modality, platform, location, slot_duration) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?)",
            [$scheduleId, $studentId, $facultyId, $date, $startTime, $endTime, $remarks, $modality, $platform, $location, $slotDuration]
        );
        
        if (!$appointmentId) {
            throw new Exception("Failed to create appointment.");
        }
        
        // Record in appointment history
        $historyResult = insertData(
            "INSERT INTO appointment_history (appointment_id, status_change, changed_by_user_id, notes) 
             VALUES (?, 'created', ?, 'Appointment created')",
            [$appointmentId, getCurrentUserId()]
        );
        
        if (!$historyResult) {
            throw new Exception("Failed to record appointment history.");
        }
        
        // Commit the transaction
        mysqli_commit($conn);
        
        return $appointmentId;
    } catch (Exception $e) {
        // Rollback the transaction
        mysqli_rollback($conn);
        
        // Re-throw the exception
        throw $e;
    }
}

// Get appointments for a student (updated with better queries)
function getStudentAppointments($studentId, $status = null) {
    $query = "SELECT a.*, s.day_of_week, u.first_name, u.last_name, d.department_name,
                     CASE 
                         WHEN a.is_cancelled = 1 THEN 'cancelled'
                         WHEN a.is_approved = 1 THEN 'approved' 
                         ELSE 'pending'
                     END as status_text
              FROM appointments a 
              JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
              JOIN faculty f ON s.faculty_id = f.faculty_id 
              JOIN users u ON f.user_id = u.user_id 
              JOIN departments d ON f.department_id = d.department_id 
              WHERE a.student_id = ?";
    
    $params = [$studentId];
    
    if ($status === 'pending') {
        $query .= " AND a.is_approved = 0 AND a.is_cancelled = 0";
    } else if ($status === 'approved') {
        $query .= " AND a.is_approved = 1 AND a.is_cancelled = 0";
    } else if ($status === 'cancelled') {
        $query .= " AND a.is_cancelled = 1";
    }
    
    $query .= " ORDER BY a.appointment_date ASC, a.start_time ASC";
    
    return fetchRows($query, $params);
}

// Get appointments for a faculty member (improved with better filtering)
function getFacultyAppointments($facultyId, $status = null, $fromDate = null, $toDate = null) {
    $query = "SELECT a.*, s.day_of_week, u.first_name, u.last_name, u.email,
                     CASE 
                         WHEN a.is_cancelled = 1 THEN 'cancelled'
                         WHEN a.is_approved = 1 THEN 'approved' 
                         ELSE 'pending'
                     END as status_text
              FROM appointments a 
              JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
              JOIN students st ON a.student_id = st.student_id 
              JOIN users u ON st.user_id = u.user_id 
              WHERE s.faculty_id = ?";
    
    $params = [$facultyId];
    
    if ($fromDate) {
        $query .= " AND a.appointment_date >= ?";
        $params[] = $fromDate;
    }
    
    if ($toDate) {
        $query .= " AND a.appointment_date <= ?";
        $params[] = $toDate;
    }
    
    if ($status === 'pending') {
        $query .= " AND a.is_approved = 0 AND a.is_cancelled = 0";
    } else if ($status === 'approved') {
        $query .= " AND a.is_approved = 1 AND a.is_cancelled = 0";
    } else if ($status === 'cancelled') {
        $query .= " AND a.is_cancelled = 1";
    }
    
    $query .= " ORDER BY a.appointment_date ASC, a.start_time ASC";
    
    return fetchRows($query, $params);
}

// Get appointment details
function getAppointmentDetails($appointmentId) {
    return fetchRow(
        "SELECT a.*, s.day_of_week, s.faculty_id, f.user_id as faculty_user_id, 
                uf.first_name as faculty_first_name, uf.last_name as faculty_last_name, uf.email as faculty_email,
                us.first_name as student_first_name, us.last_name as student_last_name, us.email as student_email,
                d.department_name, a.cancellation_reason, a.completed_at, a.is_missed, a.missed_by, a.missed_at, a.missed_reason,
                CASE 
                    WHEN a.is_missed = 1 THEN 'missed'
                    WHEN a.completed_at IS NOT NULL THEN 'completed'
                    WHEN a.is_cancelled = 1 THEN 'cancelled'
                    WHEN a.is_approved = 1 THEN 'approved' 
                    ELSE 'pending'
                END as status_text
         FROM appointments a 
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
         JOIN faculty f ON s.faculty_id = f.faculty_id 
         JOIN users uf ON f.user_id = uf.user_id 
         JOIN students st ON a.student_id = st.student_id 
         JOIN users us ON st.user_id = us.user_id 
         JOIN departments d ON f.department_id = d.department_id 
         WHERE a.appointment_id = ?",
        [$appointmentId]
    );
}

// Approve an appointment (enhanced with better error handling and slot validation)
function approveAppointment($appointmentId, $notes = null) {
    global $conn;
    
    // Start a transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get appointment details first
        $appointment = getAppointmentDetails($appointmentId);
        if (!$appointment) {
            throw new Exception("Appointment not found.");
        }
        
        if ($appointment['is_approved']) {
            throw new Exception("Appointment is already approved.");
        }
        
        if ($appointment['is_cancelled']) {
            throw new Exception("Cannot approve a cancelled appointment.");
        }
        
        // Check if appointment is in the past
        if ($appointment['appointment_date'] < date('Y-m-d')) {
            throw new Exception("Cannot approve appointments for past dates.");
        }
        
        // Additional check: verify the slot is still valid (no conflicts)
        $facultyId = $appointment['faculty_id'];
        $date = $appointment['appointment_date'];
        $startTime = $appointment['start_time'];
        $endTime = $appointment['end_time'];
        
        // Check for conflicting approved appointments (excluding this one)
        $conflicts = fetchRows(
            "SELECT a.appointment_id FROM appointments a
             JOIN availability_schedules s ON a.schedule_id = s.schedule_id
             WHERE s.faculty_id = ? AND a.appointment_date = ? 
             AND a.start_time = ? AND a.end_time = ?
             AND a.is_approved = 1 AND a.is_cancelled = 0
             AND a.appointment_id != ?",
            [$facultyId, $date, $startTime, $endTime, $appointmentId]
        );
        
        if (!empty($conflicts)) {
            throw new Exception("Cannot approve: Another appointment has already been approved for this time slot.");
        }
        
        // Update the appointment
        $result = updateOrDeleteData(
            "UPDATE appointments SET is_approved = 1, updated_on = CURRENT_TIMESTAMP WHERE appointment_id = ?",
            [$appointmentId]
        );
        
        if (!$result) {
            throw new Exception("Failed to approve appointment.");
        }
        
        // Record in appointment history
        $historyNotes = $notes ? $notes : 'Appointment approved by faculty';
        $historyResult = insertData(
            "INSERT INTO appointment_history (appointment_id, status_change, changed_by_user_id, notes) 
             VALUES (?, 'approved', ?, ?)",
            [$appointmentId, getCurrentUserId(), $historyNotes]
        );
        
        if (!$historyResult) {
            throw new Exception("Failed to record appointment history.");
        }
        
        // Commit the transaction
        mysqli_commit($conn);
        
        return true;
    } catch (Exception $e) {
        // Rollback the transaction
        mysqli_rollback($conn);
        
        // Re-throw the exception
        throw $e;
    }
}

// Reject an appointment (enhanced)
function rejectAppointment($appointmentId, $notes = null) {
    global $conn;
    
    // Start a transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get appointment details first
        $appointment = getAppointmentDetails($appointmentId);
        if (!$appointment) {
            throw new Exception("Appointment not found.");
        }
        
        if ($appointment['is_cancelled']) {
            throw new Exception("Appointment is already cancelled/rejected.");
        }
        
        // Update the appointment
        $result = updateOrDeleteData(
            "UPDATE appointments SET is_cancelled = 1, updated_on = CURRENT_TIMESTAMP WHERE appointment_id = ?",
            [$appointmentId]
        );
        
        if (!$result) {
            throw new Exception("Failed to reject appointment.");
        }
        
        // Record in appointment history
        $historyNotes = $notes ? $notes : 'Appointment rejected by faculty';
        $historyResult = insertData(
            "INSERT INTO appointment_history (appointment_id, status_change, changed_by_user_id, notes) 
             VALUES (?, 'rejected', ?, ?)",
            [$appointmentId, getCurrentUserId(), $historyNotes]
        );
        
        if (!$historyResult) {
            throw new Exception("Failed to record appointment history.");
        }
        
        // Commit the transaction
        mysqli_commit($conn);
        
        return true;
    } catch (Exception $e) {
        // Rollback the transaction
        mysqli_rollback($conn);
        
        // Re-throw the exception
        throw $e;
    }
}

// Cancel an appointment (enhanced with improved validation)
function cancelAppointment($appointmentId, $notes = null, $cancellationReason = null) {
    global $conn;
    
    // Start a transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get appointment details first
        $appointment = getAppointmentDetails($appointmentId);
        if (!$appointment) {
            throw new Exception("Appointment not found.");
        }
        
        if ($appointment['is_cancelled']) {
            throw new Exception("Appointment is already cancelled.");
        }
        
        // Check cancellation policy (24 hours before with improved logic)
        if (!canCancelAppointment($appointmentId)) {
            throw new Exception("Cannot cancel appointments less than " . MIN_CANCEL_HOURS . " hours before the scheduled time.");
        }
        
        // Update the appointment
        $result = updateOrDeleteData(
            "UPDATE appointments SET is_cancelled = 1, cancellation_reason = ?, updated_on = CURRENT_TIMESTAMP WHERE appointment_id = ?",
            [$cancellationReason, $appointmentId]
        );
        
        if (!$result) {
            throw new Exception("Failed to cancel appointment.");
        }
        
        // Record in appointment history
        $historyNotes = $notes ? $notes : 'Appointment cancelled';
        $historyResult = insertData(
            "INSERT INTO appointment_history (appointment_id, status_change, changed_by_user_id, notes) 
             VALUES (?, 'cancelled', ?, ?)",
            [$appointmentId, getCurrentUserId(), $historyNotes]
        );
        
        if (!$historyResult) {
            throw new Exception("Failed to record appointment history.");
        }
        
        // Commit the transaction
        mysqli_commit($conn);
        
        return true;
    } catch (Exception $e) {
        // Rollback the transaction
        mysqli_rollback($conn);
        
        // Re-throw the exception
        throw $e;
    }
}

// Check if an appointment can be cancelled (improved validation)
function canCancelAppointment($appointmentId) {
    $appointment = getAppointmentDetails($appointmentId);
    
    if (!$appointment) {
        return false;
    }
    
    // Cannot cancel past appointments
    if ($appointment['appointment_date'] < date('Y-m-d')) {
        return false;
    }
    
    // Cannot cancel already cancelled appointments
    if ($appointment['is_cancelled']) {
        return false;
    }
    
    // Create datetime objects for precise calculation
    $appointmentDateTime = new DateTime($appointment['appointment_date'] . ' ' . $appointment['start_time']);
    $currentDateTime = new DateTime();
    
    // Calculate the difference in hours
    $interval = $currentDateTime->diff($appointmentDateTime);
    $hoursDifference = ($interval->days * 24) + $interval->h + ($interval->i / 60);
    
    // Can cancel if more than MIN_CANCEL_HOURS hours before appointment
    return $hoursDifference >= MIN_CANCEL_HOURS;
}

// Get appointment history (enhanced)
function getAppointmentHistory($appointmentId) {
    return fetchRows(
        "SELECT ah.*, u.first_name, u.last_name, u.role,
                CASE 
                    WHEN ah.status_change = 'created' THEN 'Appointment Created'
                    WHEN ah.status_change = 'approved' THEN 'Approved'
                    WHEN ah.status_change = 'rejected' THEN 'Rejected'
                    WHEN ah.status_change = 'cancelled' THEN 'Cancelled'
                    ELSE UPPER(SUBSTRING(ah.status_change, 1, 1)) || SUBSTRING(ah.status_change, 2)
                END as status_label
         FROM appointment_history ah 
         JOIN users u ON ah.changed_by_user_id = u.user_id 
         WHERE ah.appointment_id = ? 
         ORDER BY ah.changed_at DESC",
        [$appointmentId]
    );
}

// Get upcoming appointments for dashboard (improved with better filtering)
function getUpcomingAppointments($userId, $role, $limit = 5) {
    if ($role === 'student') {
        $studentId = getStudentIdFromUserId($userId);
        return fetchRows(
            "SELECT a.*, u.first_name, u.last_name, d.department_name,
                    CASE 
                        WHEN a.appointment_date = CURDATE() THEN 'today'
                        WHEN a.appointment_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'tomorrow'
                        ELSE 'upcoming'
                    END as appointment_urgency
             FROM appointments a 
             JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
             JOIN faculty f ON s.faculty_id = f.faculty_id 
             JOIN users u ON f.user_id = u.user_id 
             JOIN departments d ON f.department_id = d.department_id 
             WHERE a.student_id = ? AND a.is_approved = 1 AND a.is_cancelled = 0 
             AND (a.appointment_date > CURDATE() OR 
                  (a.appointment_date = CURDATE() AND a.start_time > CURTIME()))
             ORDER BY a.appointment_date ASC, a.start_time ASC 
             LIMIT ?",
            [$studentId, $limit]
        );
    } elseif ($role === 'faculty') {
        $facultyId = getFacultyIdFromUserId($userId);
        return fetchRows(
            "SELECT a.*, u.first_name, u.last_name,
                    CASE 
                        WHEN a.appointment_date = CURDATE() THEN 'today'
                        WHEN a.appointment_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'tomorrow'
                        ELSE 'upcoming'
                    END as appointment_urgency
             FROM appointments a 
             JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
             JOIN students st ON a.student_id = st.student_id 
             JOIN users u ON st.user_id = u.user_id 
             WHERE s.faculty_id = ? AND a.is_approved = 1 AND a.is_cancelled = 0 
             AND (a.appointment_date > CURDATE() OR 
                  (a.appointment_date = CURDATE() AND a.start_time > CURTIME()))
             ORDER BY a.appointment_date ASC, a.start_time ASC 
             LIMIT ?",
            [$facultyId, $limit]
        );
    }
    
    return [];
}

// Check if user can modify appointment (improved function)
function canModifyAppointment($appointmentId, $userId, $userRole) {
    $appointment = getAppointmentDetails($appointmentId);
    
    if (!$appointment) {
        return false;
    }
    
    // Check if appointment is in the past
    if ($appointment['appointment_date'] < date('Y-m-d')) {
        return false;
    }
    
    // Check if appointment is today and time has passed
    if ($appointment['appointment_date'] === date('Y-m-d') && $appointment['start_time'] <= date('H:i:s')) {
        return false;
    }
    
    if ($userRole === 'student') {
        $studentId = getStudentIdFromUserId($userId);
        return $appointment['student_id'] == $studentId && !$appointment['is_cancelled'];
    } elseif ($userRole === 'faculty') {
        $facultyId = getFacultyIdFromUserId($userId);
        return $appointment['faculty_id'] == $facultyId;
    }
    
    return false;
}

// Get appointment statistics for dashboard (improved with more detailed stats)
function getAppointmentStatistics($userId, $userRole) {
    if ($userRole === 'student') {
        $studentId = getStudentIdFromUserId($userId);
        
        $pending = fetchRow("SELECT COUNT(*) as count FROM appointments WHERE student_id = ? AND is_approved = 0 AND is_cancelled = 0 AND is_missed = 0", [$studentId])['count'];
        $approved = fetchRow("SELECT COUNT(*) as count FROM appointments WHERE student_id = ? AND is_approved = 1 AND is_cancelled = 0 AND is_missed = 0 AND completed_at IS NULL AND (appointment_date > CURDATE() OR (appointment_date = CURDATE() AND start_time > CURTIME()))", [$studentId])['count'];
        $completed = fetchRow("SELECT COUNT(*) as count FROM appointments WHERE student_id = ? AND completed_at IS NOT NULL", [$studentId])['count'];
        $cancelled = fetchRow("SELECT COUNT(*) as count FROM appointments WHERE student_id = ? AND is_cancelled = 1", [$studentId])['count'];
        $missed = fetchRow("SELECT COUNT(*) as count FROM appointments WHERE student_id = ? AND is_missed = 1", [$studentId])['count'];
        
        return [
            'pending' => $pending,
            'approved' => $approved,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'missed' => $missed,
            'total' => $pending + $approved + $completed + $cancelled + $missed
        ];
        
    } elseif ($userRole === 'faculty') {
        $facultyId = getFacultyIdFromUserId($userId);
        
        $pending = fetchRow("SELECT COUNT(*) as count FROM appointments a JOIN availability_schedules s ON a.schedule_id = s.schedule_id WHERE s.faculty_id = ? AND a.is_approved = 0 AND a.is_cancelled = 0 AND a.is_missed = 0", [$facultyId])['count'];
        $approved = fetchRow("SELECT COUNT(*) as count FROM appointments a JOIN availability_schedules s ON a.schedule_id = s.schedule_id WHERE s.faculty_id = ? AND a.is_approved = 1 AND a.is_cancelled = 0 AND a.is_missed = 0 AND a.completed_at IS NULL AND (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.start_time > CURTIME()))", [$facultyId])['count'];
        $completed = fetchRow("SELECT COUNT(*) as count FROM appointments a JOIN availability_schedules s ON a.schedule_id = s.schedule_id WHERE s.faculty_id = ? AND a.completed_at IS NOT NULL", [$facultyId])['count'];
        $cancelled = fetchRow("SELECT COUNT(*) as count FROM appointments a JOIN availability_schedules s ON a.schedule_id = s.schedule_id WHERE s.faculty_id = ? AND a.is_cancelled = 1", [$facultyId])['count'];
        $missed = fetchRow("SELECT COUNT(*) as count FROM appointments a JOIN availability_schedules s ON a.schedule_id = s.schedule_id WHERE s.faculty_id = ? AND a.is_missed = 1", [$facultyId])['count'];
        
        return [
            'pending' => $pending,
            'approved' => $approved,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'missed' => $missed,
            'total' => $pending + $approved + $completed + $cancelled + $missed
        ];
    }
    
    return [];
}

// Get conflicting appointments for a time slot
function getConflictingAppointments($facultyId, $date, $startTime, $endTime, $excludeAppointmentId = null) {
    $query = "SELECT a.*, u.first_name, u.last_name 
              FROM appointments a
              JOIN availability_schedules s ON a.schedule_id = s.schedule_id
              JOIN students st ON a.student_id = st.student_id
              JOIN users u ON st.user_id = u.user_id
              WHERE s.faculty_id = ? AND a.appointment_date = ? 
              AND a.start_time = ? AND a.end_time = ?
              AND a.is_cancelled = 0";
    
    $params = [$facultyId, $date, $startTime, $endTime];
    
    if ($excludeAppointmentId) {
        $query .= " AND a.appointment_id != ?";
        $params[] = $excludeAppointmentId;
    }
    
    return fetchRows($query, $params);
}

// Check if a time slot has any appointments (including cancelled ones)
function hasAnyAppointments($facultyId, $date, $startTime, $endTime) {
    $result = fetchRow(
        "SELECT COUNT(*) as count FROM appointments a
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id
         WHERE s.faculty_id = ? AND a.appointment_date = ? 
         AND a.start_time = ? AND a.end_time = ?",
        [$facultyId, $date, $startTime, $endTime]
    );
    
    return $result['count'] > 0;
}

// Get appointment summary for a specific date
function getAppointmentSummaryForDate($facultyId, $date) {
    $appointments = fetchRows(
        "SELECT a.*, u.first_name, u.last_name,
                CASE 
                    WHEN a.is_cancelled = 1 THEN 'cancelled'
                    WHEN a.is_approved = 1 THEN 'approved' 
                    ELSE 'pending'
                END as status_text
         FROM appointments a
         JOIN availability_schedules s ON a.schedule_id = s.schedule_id
         JOIN students st ON a.student_id = st.student_id
         JOIN users u ON st.user_id = u.user_id
         WHERE s.faculty_id = ? AND a.appointment_date = ?
         ORDER BY a.start_time ASC",
        [$facultyId, $date]
    );
    
    $summary = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'cancelled' => 0,
        'appointments' => $appointments
    ];
    
    foreach ($appointments as $appointment) {
        $summary['total']++;
        if ($appointment['is_cancelled']) {
            $summary['cancelled']++;
        } elseif ($appointment['is_approved']) {
            $summary['approved']++;
        } else {
            $summary['pending']++;
        }
    }
    
    return $summary;
}

// Validate appointment time slot
function validateAppointmentTimeSlot($facultyId, $date, $startTime, $endTime) {
    $errors = [];
    
    // Check if date is in the past
    if ($date < date('Y-m-d')) {
        $errors[] = "Cannot create appointments for past dates.";
    }
    
    // Check if time is in the past for today's date
    if ($date === date('Y-m-d')) {
        $currentDateTime = new DateTime();
        $slotDateTime = new DateTime($date . ' ' . $startTime);
        $bufferTime = clone $currentDateTime;
        $bufferTime->add(new DateInterval('PT30M'));
        
        if ($slotDateTime <= $bufferTime) {
            $errors[] = "Cannot create appointments for past times or within 30 minutes of current time.";
        }
    }
    
    // Check if time slot is within consultation hours
    $dayOfWeek = strtolower(date('l', strtotime($date)));
    $consultationHours = getConsultationHours($facultyId, $dayOfWeek);
    
    $withinHours = false;
    foreach ($consultationHours as $hours) {
        if ($startTime >= $hours['start_time'] && $endTime <= $hours['end_time']) {
            $withinHours = true;
            break;
        }
    }
    
    if (!$withinHours) {
        $errors[] = "The selected time slot is not within the faculty's consultation hours.";
    }
    
    // Check for conflicts
    if (isSlotAvailableImproved($facultyId, $date, $startTime, $endTime) === false) {
        $errors[] = "This time slot is already booked.";
    }
    
    return $errors;
}

// Complete an appointment (mark as finished)
function completeAppointment($appointmentId, $notes = null) {
    global $conn;
    
    // Start a transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get appointment details first
        $appointment = getAppointmentDetails($appointmentId);
        if (!$appointment) {
            throw new Exception("Appointment not found.");
        }
        
        if (!$appointment['is_approved']) {
            throw new Exception("Only approved appointments can be marked as completed.");
        }
        
        if ($appointment['is_cancelled']) {
            throw new Exception("Cannot complete a cancelled appointment.");
        }
        
        if (!DEBUG_MODE) {
            // Check if appointment time has passed
            $appointmentDateTime = $appointment['appointment_date'] . ' ' . $appointment['end_time'];
            if (strtotime($appointmentDateTime) > time()) {
                throw new Exception("Cannot mark future appointments as completed.");
            }
        }

        // Update the appointment with completion timestamp
        $result = updateOrDeleteData(
            "UPDATE appointments SET completed_at = CURRENT_TIMESTAMP WHERE appointment_id = ?",
            [$appointmentId]
        );
        
        if (!$result) {
            throw new Exception("Failed to complete appointment.");
        }
        
        // Record in appointment history
        if ($notes) {
            $historyNotes = $notes;
        } else {
            $historyNotes = 'Appointment marked as completed by ' . getCurrentUserRole();
        }

        $historyResult = insertData(
            "INSERT INTO appointment_history (appointment_id, status_change, changed_by_user_id, notes) 
             VALUES (?, 'completed', ?, ?)",
            [$appointmentId, getCurrentUserId(), $historyNotes]
        );
        
        if (!$historyResult) {
            throw new Exception("Failed to record appointment history.");
        }
        
        // Commit the transaction
        mysqli_commit($conn);
        
        return true;
    } catch (Exception $e) {
        // Rollback the transaction
        mysqli_rollback($conn);
        
        // Re-throw the exception
        throw $e;
    }
}

// Check if an appointment can be marked as completed
function canCompleteAppointment($appointmentId) {
    $appointment = getAppointmentDetails($appointmentId);
    
    if (!$appointment) {
        return false;
    }
    
    // Must be approved, not cancelled, and not missed
    if (!$appointment['is_approved'] || $appointment['is_cancelled'] || $appointment['is_missed']) {
        return false;
    }
    
    // Must not already be completed
    if (!empty($appointment['completed_at'])) {
        return false;
    }

    if (DEBUG_MODE) {
        return true;
    }
    
    $appointmentDateTime = $appointment['appointment_date'] . ' ' . $appointment['end_time'];
    return strtotime($appointmentDateTime) <= time();
}

// Allow student to mark appointment as completed
function canStudentCompleteAppointment($appointmentId, $studentId) {
    $appointment = getAppointmentDetails($appointmentId);
    
    if (!$appointment || $appointment['student_id'] != $studentId) {
        return false;
    }
    
    // Must be approved, not cancelled, and not missed
    if (!$appointment['is_approved'] || $appointment['is_cancelled'] || $appointment['is_missed']) {
        return false;
    }
    
    // Must not already be completed
    if (!empty($appointment['completed_at'])) {
        return false;
    }
    
    // For students, allow completion if appointment time has passed
    if (DEBUG_MODE) {
        return true;
    } else {
        return false;
    }
}

// Mark an appointment as missed
function markAppointmentAsMissed($appointmentId, $missedBy, $reason = null) {
    global $conn;
    
    // Start a transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get appointment details first
        $appointment = getAppointmentDetails($appointmentId);
        if (!$appointment) {
            throw new Exception("Appointment not found.");
        }
        
        // Validate that appointment can be marked as missed
        if (!canMarkAppointmentAsMissed($appointmentId, $missedBy)) {
            throw new Exception("This appointment cannot be marked as missed at this time.");
        }
        
        if ($appointment['is_missed']) {
            throw new Exception("Appointment is already marked as missed.");
        }
        
        if ($appointment['is_cancelled']) {
            throw new Exception("Cannot mark a cancelled appointment as missed.");
        }
        
        if (!empty($appointment['completed_at'])) {
            throw new Exception("Cannot mark a completed appointment as missed.");
        }
        
        // Update the appointment
        $result = updateOrDeleteData(
            "UPDATE appointments SET is_missed = 1, missed_by = ?, missed_at = CURRENT_TIMESTAMP, missed_reason = ? WHERE appointment_id = ?",
            [$missedBy, $reason, $appointmentId]
        );
        
        if (!$result) {
            throw new Exception("Failed to mark appointment as missed.");
        }
        
        // Record in appointment history
        $historyNotes = $reason ? "Marked as missed by {$missedBy}: {$reason}" : "Marked as missed by {$missedBy}";
        $historyResult = insertData(
            "INSERT INTO appointment_history (appointment_id, status_change, changed_by_user_id, notes) 
             VALUES (?, 'missed', ?, ?)",
            [$appointmentId, getCurrentUserId(), $historyNotes]
        );
        
        if (!$historyResult) {
            throw new Exception("Failed to record appointment history.");
        }
        
        // Commit the transaction
        mysqli_commit($conn);
        
        return true;
    } catch (Exception $e) {
        // Rollback the transaction
        mysqli_rollback($conn);
        
        // Re-throw the exception
        throw $e;
    }
}

// Check if an appointment can be marked as missed
function canMarkAppointmentAsMissed($appointmentId, $userRole) {
    $appointment = getAppointmentDetails($appointmentId);
    
    if (!$appointment) {
        return false;
    }
    
    // Cannot mark if already missed, cancelled, or completed
    if ($appointment['is_missed'] || $appointment['is_cancelled'] || !empty($appointment['completed_at'])) {
        return false;
    }
    
    // Must be approved appointment
    if (!$appointment['is_approved']) {
        return false;
    }

    if (DEBUG_MODE) {
        return true;
    }
    
    // Check if appointment time has passed by at least 20 minutes
    $appointmentDateTime = new DateTime($appointment['appointment_date'] . ' ' . $appointment['start_time']);
    $currentDateTime = new DateTime();
    $missedThreshold = clone $appointmentDateTime;
    $missedThreshold->add(new DateInterval('PT20M')); // Add 20 minutes
    
    // Can only mark as missed if 20+ minutes have passed since start time
    if ($currentDateTime < $missedThreshold) {
        return false;
    }
    
    // Check if it's not too late to mark as missed (e.g., within 24 hours)
    $maxMissedTime = clone $appointmentDateTime;
    $maxMissedTime->add(new DateInterval('PT24H')); // Can mark as missed within 24 hours
    
    if ($currentDateTime > $maxMissedTime) {
        return false;
    }
    
    // Verify user has permission for this appointment
    if ($userRole === 'student') {
        $studentId = getStudentIdFromUserId($_SESSION['user_id']);
        return $appointment['student_id'] == $studentId;
    } elseif ($userRole === 'faculty') {
        $facultyId = getFacultyIdFromUserId($_SESSION['user_id']);
        return $appointment['faculty_id'] == $facultyId;
    }
    
    return false;
}

// Check if appointment is currently eligible to be marked as missed (for real-time checking)
function isAppointmentMissedEligible($appointmentId) {
    $appointment = getAppointmentDetails($appointmentId);
    
    if (!$appointment || !$appointment['is_approved'] || $appointment['is_cancelled'] || 
        $appointment['is_missed'] || !empty($appointment['completed_at'])) {
        return false;
    }
    
    $appointmentDateTime = new DateTime($appointment['appointment_date'] . ' ' . $appointment['start_time']);
    $currentDateTime = new DateTime();
    $missedThreshold = clone $appointmentDateTime;
    $missedThreshold->add(new DateInterval('PT20M'));
    
    return $currentDateTime >= $missedThreshold;
}


?>