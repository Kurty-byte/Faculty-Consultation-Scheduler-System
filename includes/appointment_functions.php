<?php
// First, include the faculty_functions.php file that contains the checkSlotBooked function
require_once 'faculty_functions.php';

// Create a new appointment
function createAppointment($studentId, $scheduleId, $date, $startTime, $endTime, $remarks, $modality, $platform = null, $location = null) {
    global $conn;
    
    // Start a transaction
    mysqli_begin_transaction($conn);
    
    try {
        // First check if the slot is still available
        if (checkSlotBooked($scheduleId, $date, $startTime, $endTime)) {
            // Slot is already booked
            throw new Exception("This slot is no longer available.");
        }
        
        // Insert the appointment
        $appointmentId = insertData(
            "INSERT INTO appointments (schedule_id, student_id, appointment_date, start_time, end_time, remarks, is_approved, is_cancelled, modality, platform, location) 
             VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?)",
            [$scheduleId, $studentId, $date, $startTime, $endTime, $remarks, $modality, $platform, $location]
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

// Get appointments for a student
function getStudentAppointments($studentId, $status = null) {
    $query = "SELECT a.*, s.day_of_week, u.first_name, u.last_name, d.department_name 
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

// Get appointment details
function getAppointmentDetails($appointmentId) {
    return fetchRow(
        "SELECT a.*, s.day_of_week, s.faculty_id, f.user_id as faculty_user_id, 
                uf.first_name as faculty_first_name, uf.last_name as faculty_last_name, 
                us.first_name as student_first_name, us.last_name as student_last_name,
                d.department_name 
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

// Approve an appointment
function approveAppointment($appointmentId, $notes = null) {
    global $conn;
    
    // Start a transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update the appointment
        $result = updateOrDeleteData(
            "UPDATE appointments SET is_approved = 1, updated_on = CURRENT_TIMESTAMP WHERE appointment_id = ?",
            [$appointmentId]
        );
        
        if (!$result) {
            throw new Exception("Failed to approve appointment.");
        }
        
        // Record in appointment history
        $historyResult = insertData(
            "INSERT INTO appointment_history (appointment_id, status_change, changed_by_user_id, notes) 
             VALUES (?, 'approved', ?, ?)",
            [$appointmentId, getCurrentUserId(), $notes]
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

// Reject an appointment
function rejectAppointment($appointmentId, $notes = null) {
    global $conn;
    
    // Start a transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update the appointment
        $result = updateOrDeleteData(
            "UPDATE appointments SET is_cancelled = 1, updated_on = CURRENT_TIMESTAMP WHERE appointment_id = ?",
            [$appointmentId]
        );
        
        if (!$result) {
            throw new Exception("Failed to reject appointment.");
        }
        
        // Record in appointment history
        $historyResult = insertData(
            "INSERT INTO appointment_history (appointment_id, status_change, changed_by_user_id, notes) 
             VALUES (?, 'rejected', ?, ?)",
            [$appointmentId, getCurrentUserId(), $notes]
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

// Cancel an appointment
function cancelAppointment($appointmentId, $notes = null) {
    global $conn;
    
    // Start a transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update the appointment
        $result = updateOrDeleteData(
            "UPDATE appointments SET is_cancelled = 1, updated_on = CURRENT_TIMESTAMP WHERE appointment_id = ?",
            [$appointmentId]
        );
        
        if (!$result) {
            throw new Exception("Failed to cancel appointment.");
        }
        
        // Record in appointment history
        $historyResult = insertData(
            "INSERT INTO appointment_history (appointment_id, status_change, changed_by_user_id, notes) 
             VALUES (?, 'cancelled', ?, ?)",
            [$appointmentId, getCurrentUserId(), $notes]
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
    
    // Create datetime string
    $appointmentDateTime = $appointment['appointment_date'] . ' ' . $appointment['start_time'];
    
    // Calculate hours difference
    $hoursDifference = getHoursDifference($appointmentDateTime, date('Y-m-d H:i:s'));
    
    // Can cancel if more than MIN_CANCEL_HOURS hours before appointment
    return $hoursDifference >= MIN_CANCEL_HOURS;
}

// Get appointment history
function getAppointmentHistory($appointmentId) {
    return fetchRows(
        "SELECT ah.*, u.first_name, u.last_name, u.role 
         FROM appointment_history ah 
         JOIN users u ON ah.changed_by_user_id = u.user_id 
         WHERE ah.appointment_id = ? 
         ORDER BY ah.changed_at DESC",
        [$appointmentId]
    );
}
?>