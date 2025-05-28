<?php
// Clean and validate input data - UPDATED VERSION
function sanitize($data) {
    global $conn;
    
    // First trim whitespace
    $data = trim($data);
    
    // Don't double-escape - just escape for database storage
    $data = mysqli_real_escape_string($conn, $data);
    
    return $data;
}

// Separate function for HTML output sanitization
function sanitizeForOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Function to display text content properly (handles line breaks and HTML entities)
function displayTextContent($data) {
    // First decode any HTML entities that might have been encoded
    $data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');
    
    // Normalize line breaks - convert all variations to \n
    $data = str_replace(["\r\n", "\r"], "\n", $data);
    
    // Remove any literal \r\n text that might be in the data
    $data = str_replace(['\\r\\n', '\\n', '\\r'], "", $data);

    // Remove escaped characters (like \r\n, \n, \r)
    $data = stripslashes($data);
    
    // Safely encode for HTML output
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    // Then convert line breaks to HTML breaks
    $data = nl2br($data);
    
    return $data;
}

// Alternative function for plain text display (no HTML breaks)
function displayPlainText($data) {
    // Decode HTML entities
    $data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');
    
    // Remove escaped characters
    $data = stripslashes($data);
    
    // Normalize and remove line breaks for plain text
    $data = str_replace(["\r\n", "\r", "\n", '\\r\\n', '\\n', '\\r'], ' ', $data);
    
    // Clean up multiple spaces
    $data = preg_replace('/\s+/', ' ', $data);
    
    return trim($data);
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