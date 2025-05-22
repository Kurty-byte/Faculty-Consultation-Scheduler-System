<?php
// Include config file
require_once 'config.php';

// This script cleans up duplicate notification entries in appointment_history
// Run this once to clean your database

// Function to clean duplicate notification entries
function cleanDuplicateNotifications() {
    global $conn;
    
    echo "Starting cleanup of duplicate notifications...\n";
    
    // Remove duplicate 'read_by_faculty' entries - keep only the latest one per appointment
    $query1 = "DELETE ah1 FROM appointment_history ah1
               INNER JOIN appointment_history ah2 
               WHERE ah1.appointment_id = ah2.appointment_id 
               AND ah1.status_change = ah2.status_change 
               AND ah1.changed_by_user_id = ah2.changed_by_user_id
               AND ah1.status_change = 'read_by_faculty'
               AND ah1.history_id < ah2.history_id";
    
    $result1 = mysqli_query($conn, $query1);
    $affected1 = mysqli_affected_rows($conn);
    echo "Removed $affected1 duplicate 'read_by_faculty' entries.\n";
    
    // Remove duplicate 'read_by_student' entries - keep only the latest one per appointment
    $query2 = "DELETE ah1 FROM appointment_history ah1
               INNER JOIN appointment_history ah2 
               WHERE ah1.appointment_id = ah2.appointment_id 
               AND ah1.status_change = ah2.status_change 
               AND ah1.changed_by_user_id = ah2.changed_by_user_id
               AND ah1.status_change = 'read_by_student'
               AND ah1.history_id < ah2.history_id";
    
    $result2 = mysqli_query($conn, $query2);
    $affected2 = mysqli_affected_rows($conn);
    echo "Removed $affected2 duplicate 'read_by_student' entries.\n";
    
    // Remove duplicate 'cancelled' entries - keep only the latest one per appointment
    $query3 = "DELETE ah1 FROM appointment_history ah1
               INNER JOIN appointment_history ah2 
               WHERE ah1.appointment_id = ah2.appointment_id 
               AND ah1.status_change = ah2.status_change 
               AND ah1.status_change = 'cancelled'
               AND ah1.history_id < ah2.history_id";
    
    $result3 = mysqli_query($conn, $query3);
    $affected3 = mysqli_affected_rows($conn);
    echo "Removed $affected3 duplicate 'cancelled' entries.\n";
    
    $total = $affected1 + $affected2 + $affected3;
    echo "Total duplicate entries removed: $total\n";
    echo "Cleanup completed!\n";
    
    return $total;
}

// Run the cleanup if this script is accessed directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    echo "<pre>";
    cleanDuplicateNotifications();
    echo "</pre>";
    echo "<p><strong>Database cleanup completed!</strong></p>";
    echo "<p><a href='" . BASE_URL . "pages/faculty/notifications.php'>Go back to notifications</a></p>";
}
?>