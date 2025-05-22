<?php
// Include config file
require_once 'config.php';

// This script cleans up the old notification system
// Run this once after implementing the new system

echo "<h2>Cleanup Old Notification System</h2>";
echo "<pre>";

// Function to clean old notification entries from appointment_history
function cleanOldNotificationSystem() {
    global $conn;
    
    echo "Starting cleanup of old notification system...\n";
    
    // Remove old notification-related entries from appointment_history
    $oldNotificationTypes = [
        'read_by_faculty',
        'read_by_student', 
        'notification_booking',
        'notification_approved',
        'notification_rejected',
        'notification_cancellation',
        'notification_created',
        'notification_status_change',
        'notification_cancelled',
        'seen_by_faculty',
        'seen_by_student'
    ];
    
    $totalRemoved = 0;
    
    foreach ($oldNotificationTypes as $type) {
        $query = "DELETE FROM appointment_history WHERE status_change = ?";
        $result = updateOrDeleteData($query, [$type]);
        
        if ($result !== false) {
            echo "Removed $result entries of type '$type'\n";
            $totalRemoved += $result;
        }
    }
    
    echo "\nTotal old notification entries removed: $totalRemoved\n";
    
    // Show statistics
    echo "\n--- Current Statistics ---\n";
    
    // Count remaining appointment_history entries
    $remainingHistory = fetchRow("SELECT COUNT(*) as count FROM appointment_history");
    echo "Remaining appointment history entries: " . $remainingHistory['count'] . "\n";
    
    // Count new notifications
    $newNotifications = fetchRow("SELECT COUNT(*) as count FROM notifications");
    echo "New notification entries: " . $newNotifications['count'] . "\n";
    
    // Count unread notifications by role
    $unreadFaculty = fetchRow(
        "SELECT COUNT(*) as count FROM notifications n 
         JOIN users u ON n.user_id = u.user_id 
         WHERE n.is_read = 0 AND u.role = 'faculty'"
    );
    echo "Unread faculty notifications: " . $unreadFaculty['count'] . "\n";
    
    $unreadStudent = fetchRow(
        "SELECT COUNT(*) as count FROM notifications n 
         JOIN users u ON n.user_id = u.user_id 
         WHERE n.is_read = 0 AND u.role = 'student'"
    );
    echo "Unread student notifications: " . $unreadStudent['count'] . "\n";
    
    echo "\nCleanup completed successfully!\n";
    
    return $totalRemoved;
}

// Run the cleanup if this script is accessed directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    
    // Security check - only run on localhost
    if ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1') {
        die('This cleanup script can only be run on localhost for security reasons.');
    }
    
    $totalCleaned = cleanOldNotificationSystem();
    
    echo "</pre>";
    echo "<h3>Cleanup Summary</h3>";
    echo "<p><strong>$totalCleaned</strong> old notification entries were removed from the database.</p>";
    echo "<p>The new notification system is now active and clean!</p>";
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<ul>";
    echo "<li>✅ Test the new notification system by booking/approving/rejecting appointments</li>";
    echo "<li>✅ Verify that notification badges update correctly</li>";
    echo "<li>✅ Check that 'Mark as Read' functionality works</li>";
    echo "<li>✅ Test 'Mark All as Read' functionality</li>";
    echo "<li>⚠️ You can safely delete this cleanup script after testing</li>";
    echo "</ul>";
    
    echo "<p><a href='pages/faculty/notifications.php'>Test Faculty Notifications</a> | ";
    echo "<a href='pages/student/notifications.php'>Test Student Notifications</a></p>";
}
?>