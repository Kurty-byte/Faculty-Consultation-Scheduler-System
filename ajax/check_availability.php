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
$scheduleId = isset($_POST['schedule_id']) ? (int)$_POST['schedule_id'] : 0;
$date = isset($_POST['date']) ? sanitize($_POST['date']) : '';
$startTime = isset($_POST['start_time']) ? sanitize($_POST['start_time']) : '';
$endTime = isset($_POST['end_time']) ? sanitize($_POST['end_time']) : '';

// Validate inputs
if (!$scheduleId || !$date || !$startTime || !$endTime) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Check if slot is available
$isBooked = checkSlotBooked($scheduleId, $date, $startTime, $endTime);

echo json_encode([
    'success' => true,
    'available' => !$isBooked,
    'message' => $isBooked ? 'Slot is already booked' : 'Slot is available'
]);
?>