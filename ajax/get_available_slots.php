<?php
/*
 * AJAX Endpoint: Get Available Consultation Slots
 * Returns real-time availability for faculty consultation slots
 */

// Include config file
$configPath = dirname(__FILE__) . '/../config.php';
if (!file_exists($configPath)) {
    $configPath = '../config.php';
}
require_once $configPath;

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false, 
        'message' => 'User not authenticated',
        'error_code' => 'AUTH_REQUIRED'
    ]);
    exit;
}

// Include required functions
require_once '../includes/faculty_functions.php';
require_once '../includes/timeslot_functions.php';

try {
    // Get and validate parameters
    $facultyId = isset($_GET['faculty_id']) ? (int)$_GET['faculty_id'] : 0;
    $date = isset($_GET['date']) ? sanitize($_GET['date']) : '';
    $fromDate = isset($_GET['from_date']) ? sanitize($_GET['from_date']) : '';
    $toDate = isset($_GET['to_date']) ? sanitize($_GET['to_date']) : '';
    
    // Validate required parameters
    if (!$facultyId) {
        throw new Exception('Faculty ID is required', 400);
    }
    
    // Validate faculty exists
    $faculty = getFacultyDetails($facultyId);
    if (!$faculty) {
        throw new Exception('Faculty not found', 404);
    }
    
    // Check if faculty has consultation hours set up
    if (!hasConsultationHoursSetup($facultyId)) {
        throw new Exception('Faculty has not set up consultation hours yet', 404);
    }
    
    $response = [
        'success' => true,
        'faculty' => [
            'id' => $facultyId,
            'name' => $faculty['first_name'] . ' ' . $faculty['last_name'],
            'department' => $faculty['department_name']
        ]
    ];
    
    // Handle single date request
    if (!empty($date)) {
        // Validate date format and not in past
        if (!validateDate($date)) {
            throw new Exception('Invalid date format', 400);
        }
        
        if ($date < date('Y-m-d')) {
            throw new Exception('Cannot get slots for past dates', 400);
        }
        
        // Generate slots for specific date
        $slots = generateTimeSlots($facultyId, $date);
        
        $response['date'] = $date;
        $response['formatted_date'] = formatDate($date);
        $response['day_name'] = date('l', strtotime($date));
        $response['slots'] = array_map('formatSlotForResponse', $slots);
        $response['slot_count'] = count($slots);
        
    } else {
        // Handle date range request
        if (empty($fromDate)) {
            $fromDate = date('Y-m-d');
        }
        
        if (empty($toDate)) {
            $toDate = date('Y-m-d', strtotime('+14 days'));
        }
        
        // Validate date range
        if (!validateDate($fromDate) || !validateDate($toDate)) {
            throw new Exception('Invalid date format', 400);
        }
        
        if ($fromDate > $toDate) {
            throw new Exception('From date cannot be after to date', 400);
        }
        
        if ($fromDate < date('Y-m-d')) {
            $fromDate = date('Y-m-d');
        }
        
        // Limit range to prevent excessive queries
        $daysDiff = (strtotime($toDate) - strtotime($fromDate)) / (24 * 60 * 60);
        if ($daysDiff > 30) {
            throw new Exception('Date range cannot exceed 30 days', 400);
        }
        
        // Get available slots for date range
        $availableSlots = getAvailableConsultationSlots($facultyId, $fromDate, $toDate);
        
        $response['from_date'] = $fromDate;
        $response['to_date'] = $toDate;
        $response['date_range'] = array_map('formatDateSlotsForResponse', $availableSlots);
        $response['total_days'] = count($availableSlots);
        $response['total_slots'] = array_sum(array_map(function($day) { return count($day['slots']); }, $availableSlots));
    }
    
    // Add metadata
    $response['generated_at'] = date('Y-m-d H:i:s');
    $response['timezone'] = date_default_timezone_get();
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => getErrorCode($e->getMessage()),
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Validate date format (YYYY-MM-DD)
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Format slot data for API response
 */
function formatSlotForResponse($slot) {
    return [
        'start_time' => $slot['start_time'],
        'end_time' => $slot['end_time'],
        'formatted_time' => $slot['formatted_time'],
        'available' => $slot['available'],
        'duration_minutes' => 30,
        'booking_url' => BASE_URL . 'pages/student/book_appointment.php?' . http_build_query([
            'faculty_id' => $_GET['faculty_id'],
            'date' => $_GET['date'] ?? null,
            'start' => $slot['start_time'],
            'end' => $slot['end_time']
        ])
    ];
}

/**
 * Format date slots data for API response
 */
function formatDateSlotsForResponse($dateSlots) {
    return [
        'date' => $dateSlots['date'],
        'formatted_date' => $dateSlots['formatted_date'],
        'day_name' => $dateSlots['day_name'],
        'slots' => array_map('formatSlotForDateResponse', $dateSlots['slots']),
        'slot_count' => count($dateSlots['slots'])
    ];
}

/**
 * Format slot for date range response
 */
function formatSlotForDateResponse($slot) {
    return [
        'start_time' => $slot['start_time'],
        'end_time' => $slot['end_time'],
        'formatted_time' => $slot['formatted_time'],
        'available' => $slot['available'],
        'duration_minutes' => 30
    ];
}

/**
 * Get error code from message
 */
function getErrorCode($message) {
    $errorCodes = [
        'Faculty ID is required' => 'MISSING_FACULTY_ID',
        'Faculty not found' => 'FACULTY_NOT_FOUND',
        'Faculty has not set up consultation hours yet' => 'NO_CONSULTATION_HOURS',
        'Invalid date format' => 'INVALID_DATE_FORMAT',
        'Cannot get slots for past dates' => 'PAST_DATE_NOT_ALLOWED',
        'From date cannot be after to date' => 'INVALID_DATE_RANGE',
        'Date range cannot exceed 30 days' => 'DATE_RANGE_TOO_LARGE'
    ];
    
    return $errorCodes[$message] ?? 'UNKNOWN_ERROR';
}
?>