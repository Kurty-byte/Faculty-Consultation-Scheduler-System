<?php
// Include config file
$configPath = dirname(__FILE__) . '/../config.php';
if (!file_exists($configPath)) {
    $configPath = '../config.php';
}
require_once $configPath;

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Include required functions
require_once '../includes/faculty_functions.php';

// Get parameters
$facultyId = isset($_GET['faculty_id']) ? (int)$_GET['faculty_id'] : 0;
$date = isset($_GET['date']) ? sanitize($_GET['date']) : date('Y-m-d');

// Validate inputs
if (!$facultyId) {
    echo json_encode(['success' => false, 'message' => 'Faculty ID is required']);
    exit;
}

// Get available schedules for the specific date
$toDate = date('Y-m-d', strtotime($date . ' +1 day'));
$availableSchedules = getAvailableSchedulesForFaculty($facultyId, $date, $toDate);

// Filter for the specific date and time
$slotsForDate = [];
$currentTime = date('H:i:s');
$isToday = ($date === date('Y-m-d'));

foreach ($availableSchedules as $schedule) {
    if ($schedule['date'] === $date) {
        // Skip past times for today
        if ($isToday) {
            $slotStartTime = $schedule['start_time'];
            $bufferTime = date('H:i:s', strtotime($currentTime . ' +1 hour'));
            
            if ($slotStartTime <= $bufferTime) {
                continue; // Skip this slot
            }
        }
        
        $slotsForDate[] = [
            'schedule_id' => $schedule['schedule_id'],
            'start_time' => $schedule['start_time'],
            'end_time' => $schedule['end_time'],
            'formatted_time' => formatTime($schedule['start_time']) . ' - ' . formatTime($schedule['end_time'])
        ];
    }
}

echo json_encode([
    'success' => true,
    'slots' => $slotsForDate,
    'date' => $date,
    'formatted_date' => formatDate($date)
]);
?>