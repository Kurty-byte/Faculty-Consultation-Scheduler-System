<?php
// Clean and validate input data
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Format date for display
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

// Format time for display
function formatTime($time) {
    return date('g:i A', strtotime($time));
}

// Get day name from date
function getDayName($date) {
    return strtolower(date('l', strtotime($date)));
}

// Set a flash message
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Display flash message
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_message']['type'];
        $message = $_SESSION['flash_message']['message'];
        
        echo "<div class='alert alert-{$type}'>{$message}</div>";
        
        // Remove the message after displaying
        unset($_SESSION['flash_message']);
    }
}

// Get student ID from user ID
function getStudentIdFromUserId($userId) {
    $result = fetchRow("SELECT student_id FROM students WHERE user_id = ?", [$userId]);
    return $result ? $result['student_id'] : null;
}

// Get faculty ID from user ID
function getFacultyIdFromUserId($userId) {
    $result = fetchRow("SELECT faculty_id FROM faculty WHERE user_id = ?", [$userId]);
    return $result ? $result['faculty_id'] : null;
}

// Get user full name
function getUserFullName($userId) {
    $result = fetchRow("SELECT first_name, last_name FROM users WHERE user_id = ?", [$userId]);
    
    if ($result) {
        return $result['first_name'] . ' ' . $result['last_name'];
    }
    
    return 'Unknown User';
}

// Check if a timestamp is in the past
function isPast($timestamp) {
    return strtotime($timestamp) < time();
}

// Calculate time difference in hours
function getHoursDifference($timestamp1, $timestamp2) {
    $difference = abs(strtotime($timestamp1) - strtotime($timestamp2));
    return floor($difference / (60 * 60));
}
?>