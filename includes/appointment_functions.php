<?php
// First, include the faculty_functions.php file that contains the checkSlotBooked function
require_once 'faculty_functions.php';
require_once 'timeslot_functions.php';

// Create a new appointment (updated for consultation hours system)
function createAppointment($studentId, $scheduleId, $date, $startTime, $endTime, $remarks, $modality, $platform = null, $location = null) {
    global $conn;
    
    // Start a transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get schedule details to verify it exists
        $schedule = fetchRow(
            "SELECT faculty_id FROM availability_schedules WHERE schedule_id = ?",
            [$scheduleId]
        );
        
        if (!$schedule) {
            throw new Exception("Invalid schedule ID.");
        }
        
        // For consultation hours system, check if slot is still available
        $facultyId = $schedule['faculty_id'];
        $dayOfWeek = strtolower(date('l', strtotime($date)));
        $availableSlots = generateTimeSlots($facultyId, $date);
        
        $slotAvailable = false;
        foreach ($availableSlots as $slot) {
            if ($slot['start_time'] === $startTime && $slot['end_time'] === $endTime && $slot['available']) {
                $slotAvailable = true;
                break;
            }
        }
        
        if (!$slotAvailable) {
            throw new Exception("This time slot is no longer available.");
        }
        
        // Calculate slot duration
        $slotDuration = (strtotime($endTime) - strtotime($startTime)) / 60; // in minutes
        
        // Insert the appointment
        $appointmentId = insertData(
            "INSERT INTO appointments (schedule_id, student_id, appointment_date, start_time, end_time, remarks, is_approved, is_cancelled, modality, platform, location, slot_duration) 
             VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?)",
            [$scheduleId, $studentId, $date, $startTime, $endTime, $remarks, $modality, $platform, $location, $slotDuration]
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

// Get appointments for a faculty member (new function)
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

// Get appointment details (enhanced with more information)
function getAppointmentDetails($appointmentId) {
    return fetchRow(
        "SELECT a.*, s.day_of_week, s.faculty_id, f.user_id as faculty_user_id, 
                uf.first_name as faculty_first_name, uf.last_name as faculty_last_name, uf.email as faculty_email,
                us.first_name as student_first_name, us.last_name as student_last_name, us.email as student_email,
                d.department_name,
                CASE 
                    WHEN a.is_cancelled = 1 THEN 'cancelled'
                    WHEN a.is_approved = 1 THEN 'approved' 
                    ELSE 'pending'
                END as status_text,
                CASE 
                    WHEN a.appointment_date < CURDATE() THEN 'past'
                    WHEN a.appointment_date = CURDATE() THEN 'today'
                    ELSE 'future'
                END as time_status
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

// Approve an appointment (enhanced with better error handling)
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

// Cancel an appointment (enhanced)
function cancelAppointment($appointmentId, $notes = null) {
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
        
        // Check cancellation policy (24 hours before)
        if (!canCancelAppointment($appointmentId)) {
            throw new Exception("Cannot cancel appointments less than " . MIN_CANCEL_HOURS . " hours before the scheduled time.");
        }
        
        // Update the appointment
        $result = updateOrDeleteData(
            "UPDATE appointments SET is_cancelled = 1, updated_on = CURRENT_TIMESTAMP WHERE appointment_id = ?",
            [$appointmentId]
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

// Check if an appointment can be cancelled (24 hours before)
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
    
    // Create datetime string
    $appointmentDateTime = $appointment['appointment_date'] . ' ' . $appointment['start_time'];
    
    // Calculate hours difference
    $hoursDifference = getHoursDifference($appointmentDateTime, date('Y-m-d H:i:s'));
    
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

// Get upcoming appointments for dashboard (new function)
function getUpcomingAppointments($userId, $role, $limit = 5) {
    if ($role === 'student') {
        $studentId = getStudentIdFromUserId($userId);
        return fetchRows(
            "SELECT a.*, u.first_name, u.last_name, d.department_name
             FROM appointments a 
             JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
             JOIN faculty f ON s.faculty_id = f.faculty_id 
             JOIN users u ON f.user_id = u.user_id 
             JOIN departments d ON f.department_id = d.department_id 
             WHERE a.student_id = ? AND a.is_approved = 1 AND a.is_cancelled = 0 
             AND a.appointment_date >= CURDATE() 
             ORDER BY a.appointment_date ASC, a.start_time ASC 
             LIMIT ?",
            [$studentId, $limit]
        );
    } elseif ($role === 'faculty') {
        $facultyId = getFacultyIdFromUserId($userId);
        return fetchRows(
            "SELECT a.*, u.first_name, u.last_name
             FROM appointments a 
             JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
             JOIN students st ON a.student_id = st.student_id 
             JOIN users u ON st.user_id = u.user_id 
             WHERE s.faculty_id = ? AND a.is_approved = 1 AND a.is_cancelled = 0 
             AND a.appointment_date >= CURDATE() 
             ORDER BY a.appointment_date ASC, a.start_time ASC 
             LIMIT ?",
            [$facultyId, $limit]
        );
    }
    
    return [];
}

// Check if user can modify appointment (new function)
function canModifyAppointment($appointmentId, $userId, $userRole) {
    $appointment = getAppointmentDetails($appointmentId);
    
    if (!$appointment) {
        return false;
    }
    
    if ($userRole === 'student') {
        $studentId = getStudentIdFromUserId($userId);
        return $appointment['student_id'] == $studentId;
    } elseif ($userRole === 'faculty') {
        $facultyId = getFacultyIdFromUserId($userId);
        return $appointment['faculty_id'] == $facultyId;
    }
    
    return false;
}

// Get appointment statistics for dashboard (new function)
function getAppointmentStatistics($userId, $userRole) {
    if ($userRole === 'student') {
        $studentId = getStudentIdFromUserId($userId);
        
        $pending = fetchRow("SELECT COUNT(*) as count FROM appointments WHERE student_id = ? AND is_approved = 0 AND is_cancelled = 0", [$studentId])['count'];
        $approved = fetchRow("SELECT COUNT(*) as count FROM appointments WHERE student_id = ? AND is_approved = 1 AND is_cancelled = 0 AND appointment_date >= CURDATE()", [$studentId])['count'];
        $completed = fetchRow("SELECT COUNT(*) as count FROM appointments WHERE student_id = ? AND is_approved = 1 AND is_cancelled = 0 AND appointment_date < CURDATE()", [$studentId])['count'];
        $cancelled = fetchRow("SELECT COUNT(*) as count FROM appointments WHERE student_id = ? AND is_cancelled = 1", [$studentId])['count'];
        
        return [
            'pending' => $pending,
            'approved' => $approved,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'total' => $pending + $approved + $completed + $cancelled
        ];
        
    } elseif ($userRole === 'faculty') {
        $facultyId = getFacultyIdFromUserId($userId);
        
        $pending = fetchRow("SELECT COUNT(*) as count FROM appointments a JOIN availability_schedules s ON a.schedule_id = s.schedule_id WHERE s.faculty_id = ? AND a.is_approved = 0 AND a.is_cancelled = 0", [$facultyId])['count'];
        $approved = fetchRow("SELECT COUNT(*) as count FROM appointments a JOIN availability_schedules s ON a.schedule_id = s.schedule_id WHERE s.faculty_id = ? AND a.is_approved = 1 AND a.is_cancelled = 0 AND a.appointment_date >= CURDATE()", [$facultyId])['count'];
        $completed = fetchRow("SELECT COUNT(*) as count FROM appointments a JOIN availability_schedules s ON a.schedule_id = s.schedule_id WHERE s.faculty_id = ? AND a.is_approved = 1 AND a.is_cancelled = 0 AND a.appointment_date < CURDATE()", [$facultyId])['count'];
        $cancelled = fetchRow("SELECT COUNT(*) as count FROM appointments a JOIN availability_schedules s ON a.schedule_id = s.schedule_id WHERE s.faculty_id = ? AND a.is_cancelled = 1", [$facultyId])['count'];
        
        return [
            'pending' => $pending,
            'approved' => $approved,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'total' => $pending + $approved + $completed + $cancelled
        ];
    }
    
    return [];
}
?>