<?php
/**
 * Database Migration Script for Consultation Hours System
 * This script migrates the old schedule system to the new consultation hours system
 * Run this ONCE after implementing the new system
 */

// Include config file
require_once 'config.php';

// Security check - only run on localhost or with proper authentication
if ($_SERVER['HTTP_HOST'] !== 'localhost' && $_SERVER['HTTP_HOST'] !== '127.0.0.1') {
    die('This migration script can only be run on localhost for security reasons.');
}

// Set execution time limit for long operations
set_time_limit(300); // 5 minutes

echo "<h1>Faculty Consultation System - Database Migration</h1>";
echo "<p><strong>Migration Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// Function to log migration steps
function logMigration($message, $type = 'info') {
    $timestamp = date('H:i:s');
    $color = $type === 'error' ? 'red' : ($type === 'success' ? 'green' : 'blue');
    echo "<p style='color: $color; margin: 5px 0;'><strong>[$timestamp]</strong> $message</p>";
    flush(); // Ensure output is displayed immediately
}

function runMigration() {
    global $conn;
    
    logMigration("Starting database migration for consultation hours system...");
    
    // Step 1: Backup existing data
    logMigration("Step 1: Creating backup of existing appointment data");
    backupExistingData();
    
    // Step 2: Analyze current system usage
    logMigration("Step 2: Analyzing current system usage");
    $systemStats = analyzeCurrentSystem();
    
    // Step 3: Clean up temporary/invalid schedules
    logMigration("Step 3: Cleaning up temporary and invalid schedule entries");
    cleanupInvalidSchedules();
    
    // Step 4: Optimize database tables
    logMigration("Step 4: Optimizing database tables and indexes");
    optimizeDatabase();
    
    // Step 5: Update appointment references
    logMigration("Step 5: Updating appointment references for compatibility");
    updateAppointmentReferences();
    
    // Step 6: Clean old notification entries (if they exist)
    logMigration("Step 6: Cleaning up old notification system entries");
    cleanupOldNotifications();
    
    // Step 7: Final verification
    logMigration("Step 7: Verifying migration integrity");
    $verificationResult = verifyMigration();
    
    if ($verificationResult['success']) {
        logMigration("✅ Migration completed successfully!", 'success');
        displayMigrationSummary($systemStats, $verificationResult);
    } else {
        logMigration("❌ Migration completed with warnings. Please review the issues below.", 'error');
        displayMigrationSummary($systemStats, $verificationResult);
    }
}

function backupExistingData() {
    global $conn;
    
    try {
        // Create backup table for appointments
        $backupQuery = "CREATE TABLE IF NOT EXISTS appointments_backup_" . date('Ymd') . " AS SELECT * FROM appointments";
        if (mysqli_query($conn, $backupQuery)) {
            logMigration("✓ Appointments backup created", 'success');
        }
        
        // Create backup table for schedules
        $backupQuery = "CREATE TABLE IF NOT EXISTS availability_schedules_backup_" . date('Ymd') . " AS SELECT * FROM availability_schedules";
        if (mysqli_query($conn, $backupQuery)) {
            logMigration("✓ Availability schedules backup created", 'success');
        }
        
    } catch (Exception $e) {
        logMigration("⚠ Backup creation failed: " . $e->getMessage(), 'error');
    }
}

function analyzeCurrentSystem() {
    global $conn;
    
    $stats = [];
    
    // Count total appointments
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments");
    $stats['total_appointments'] = mysqli_fetch_assoc($result)['count'];
    
    // Count active faculty with schedules
    $result = mysqli_query($conn, "SELECT COUNT(DISTINCT faculty_id) as count FROM availability_schedules");
    $stats['faculty_with_schedules'] = mysqli_fetch_assoc($result)['count'];
    
    // Count faculty with consultation hours
    $result = mysqli_query($conn, "SELECT COUNT(DISTINCT faculty_id) as count FROM consultation_hours");
    $stats['faculty_with_consultation_hours'] = mysqli_fetch_assoc($result)['count'];
    
    // Count pending appointments
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE is_approved = 0 AND is_cancelled = 0");
    $stats['pending_appointments'] = mysqli_fetch_assoc($result)['count'];
    
    // Count future appointments
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE appointment_date >= CURDATE() AND is_cancelled = 0");
    $stats['future_appointments'] = mysqli_fetch_assoc($result)['count'];
    
    logMigration("📊 System Analysis: {$stats['total_appointments']} total appointments, {$stats['faculty_with_schedules']} faculty with old schedules, {$stats['faculty_with_consultation_hours']} faculty with new consultation hours");
    
    return $stats;
}

function cleanupInvalidSchedules() {
    global $conn;
    
    try {
        // Remove schedules with invalid time ranges
        $result = mysqli_query($conn, "DELETE FROM availability_schedules WHERE start_time >= end_time");
        $invalidTimeRanges = mysqli_affected_rows($conn);
        
        // Remove duplicate temporary schedules (non-recurring with same time slots)
        $result = mysqli_query($conn, "
            DELETE s1 FROM availability_schedules s1
            INNER JOIN availability_schedules s2 
            WHERE s1.schedule_id > s2.schedule_id 
            AND s1.faculty_id = s2.faculty_id 
            AND s1.day_of_week = s2.day_of_week 
            AND s1.start_time = s2.start_time 
            AND s1.end_time = s2.end_time 
            AND s1.is_recurring = 0 
            AND s2.is_recurring = 0
        ");
        $duplicateSchedules = mysqli_affected_rows($conn);
        
        // Remove orphaned schedules (faculty no longer exists)
        $result = mysqli_query($conn, "
            DELETE s FROM availability_schedules s 
            LEFT JOIN faculty f ON s.faculty_id = f.faculty_id 
            WHERE f.faculty_id IS NULL
        ");
        $orphanedSchedules = mysqli_affected_rows($conn);
        
        logMigration("✓ Cleaned up $invalidTimeRanges invalid time ranges, $duplicateSchedules duplicate schedules, $orphanedSchedules orphaned schedules", 'success');
        
    } catch (Exception $e) {
        logMigration("⚠ Schedule cleanup failed: " . $e->getMessage(), 'error');
    }
}

function optimizeDatabase() {
    global $conn;
    
    try {
        // Add missing indexes for better performance
        $indexes = [
            "ALTER TABLE appointments ADD INDEX IF NOT EXISTS idx_faculty_date (schedule_id, appointment_date)",
            "ALTER TABLE appointments ADD INDEX IF NOT EXISTS idx_student_status (student_id, is_approved, is_cancelled)",
            "ALTER TABLE appointments ADD INDEX IF NOT EXISTS idx_date_time (appointment_date, start_time)",
            "ALTER TABLE consultation_hours ADD INDEX IF NOT EXISTS idx_faculty_day (faculty_id, day_of_week, is_active)",
            "ALTER TABLE consultation_breaks ADD INDEX IF NOT EXISTS idx_faculty_day_active (faculty_id, day_of_week, is_active)",
            "ALTER TABLE notifications ADD INDEX IF NOT EXISTS idx_user_read_created (user_id, is_read, created_at)"
        ];
        
        $addedIndexes = 0;
        foreach ($indexes as $indexQuery) {
            if (mysqli_query($conn, $indexQuery)) {
                $addedIndexes++;
            }
        }
        
        // Optimize tables
        $tables = ['appointments', 'availability_schedules', 'consultation_hours', 'consultation_breaks', 'notifications'];
        $optimizedTables = 0;
        
        foreach ($tables as $table) {
            if (mysqli_query($conn, "OPTIMIZE TABLE $table")) {
                $optimizedTables++;
            }
        }
        
        logMigration("✓ Added $addedIndexes database indexes, optimized $optimizedTables tables", 'success');
        
    } catch (Exception $e) {
        logMigration("⚠ Database optimization failed: " . $e->getMessage(), 'error');
    }
}

function updateAppointmentReferences() {
    global $conn;
    
    try {
        // Ensure all appointments have slot_duration set
        $result = mysqli_query($conn, "UPDATE appointments SET slot_duration = 30 WHERE slot_duration IS NULL OR slot_duration = 0");
        $updatedDurations = mysqli_affected_rows($conn);
        
        // Update appointment status for better querying
        mysqli_query($conn, "
            UPDATE appointments 
            SET updated_on = CURRENT_TIMESTAMP 
            WHERE updated_on IS NULL
        ");
        
        logMigration("✓ Updated $updatedDurations appointment durations to 30 minutes", 'success');
        
    } catch (Exception $e) {
        logMigration("⚠ Appointment reference update failed: " . $e->getMessage(), 'error');
    }
}

function cleanupOldNotifications() {
    global $conn;
    
    try {
        // Remove old notification-style entries from appointment_history
        $oldNotificationTypes = [
            'read_by_faculty', 'read_by_student', 'notification_booking',
            'notification_approved', 'notification_rejected', 'notification_cancellation',
            'notification_created', 'notification_status_change', 'notification_cancelled',
            'seen_by_faculty', 'seen_by_student'
        ];
        
        $totalRemoved = 0;
        foreach ($oldNotificationTypes as $type) {
            $result = mysqli_query($conn, "DELETE FROM appointment_history WHERE status_change = '$type'");
            $removed = mysqli_affected_rows($conn);
            $totalRemoved += $removed;
        }
        
        // Clean notifications older than 60 days
        $result = mysqli_query($conn, "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)");
        $oldNotifications = mysqli_affected_rows($conn);
        
        logMigration("✓ Removed $totalRemoved old notification entries, $oldNotifications old notifications", 'success');
        
    } catch (Exception $e) {
        logMigration("⚠ Notification cleanup failed: " . $e->getMessage(), 'error');
    }
}

function verifyMigration() {
    global $conn;
    
    $verification = ['success' => true, 'issues' => [], 'stats' => []];
    
    try {
        // Check if consultation hours table exists and has data
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM consultation_hours");
        if ($result) {
            $verification['stats']['consultation_hours'] = mysqli_fetch_assoc($result)['count'];
        } else {
            $verification['issues'][] = "Consultation hours table not accessible";
            $verification['success'] = false;
        }
        
        // Check if consultation breaks table exists
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM consultation_breaks");
        if ($result) {
            $verification['stats']['consultation_breaks'] = mysqli_fetch_assoc($result)['count'];
        } else {
            $verification['issues'][] = "Consultation breaks table not accessible";
            $verification['success'] = false;
        }
        
        // Check appointments integrity
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE slot_duration IS NULL OR slot_duration = 0");
        if ($result) {
            $invalidDurations = mysqli_fetch_assoc($result)['count'];
            if ($invalidDurations > 0) {
                $verification['issues'][] = "$invalidDurations appointments have invalid durations";
            }
        }
        
        // Check for orphaned appointments
        $result = mysqli_query($conn, "
            SELECT COUNT(*) as count 
            FROM appointments a 
            LEFT JOIN availability_schedules s ON a.schedule_id = s.schedule_id 
            WHERE s.schedule_id IS NULL
        ");
        if ($result) {
            $orphanedAppointments = mysqli_fetch_assoc($result)['count'];
            if ($orphanedAppointments > 0) {
                $verification['issues'][] = "$orphanedAppointments appointments have invalid schedule references";
                $verification['success'] = false;
            }
        }
        
        // Check notification system
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM notifications");
        if ($result) {
            $verification['stats']['notifications'] = mysqli_fetch_assoc($result)['count'];
        }
        
        logMigration("✓ Migration verification completed", 'success');
        
    } catch (Exception $e) {
        logMigration("⚠ Migration verification failed: " . $e->getMessage(), 'error');
        $verification['success'] = false;
        $verification['issues'][] = "Verification process failed: " . $e->getMessage();
    }
    
    return $verification;
}

function displayMigrationSummary($systemStats, $verificationResult) {
    echo "<hr>";
    echo "<h2>📋 Migration Summary</h2>";
    
    echo "<h3>System Statistics</h3>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Metric</th><th>Count</th></tr>";
    echo "<tr><td>Total Appointments</td><td>{$systemStats['total_appointments']}</td></tr>";
    echo "<tr><td>Faculty with Old Schedules</td><td>{$systemStats['faculty_with_schedules']}</td></tr>";
    echo "<tr><td>Faculty with Consultation Hours</td><td>{$systemStats['faculty_with_consultation_hours']}</td></tr>";
    echo "<tr><td>Pending Appointments</td><td>{$systemStats['pending_appointments']}</td></tr>";
    echo "<tr><td>Future Appointments</td><td>{$systemStats['future_appointments']}</td></tr>";
    echo "</table>";
    
    if (isset($verificationResult['stats'])) {
        echo "<h3>Post-Migration Statistics</h3>";
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Component</th><th>Count</th></tr>";
        foreach ($verificationResult['stats'] as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            echo "<tr><td>$label</td><td>$value</td></tr>";
        }
        echo "</table>";
    }
    
    if (!empty($verificationResult['issues'])) {
        echo "<h3 style='color: red;'>⚠️ Issues Found</h3>";
        echo "<ul>";
        foreach ($verificationResult['issues'] as $issue) {
            echo "<li style='color: red;'>$issue</li>";
        }
        echo "</ul>";
    }
    
    echo "<h3>✅ What's New</h3>";
    echo "<ul>";
    echo "<li><strong>Consultation Hours System:</strong> Faculty can now set weekly availability hours</li>";
    echo "<li><strong>Break Management:</strong> Faculty can add lunch breaks, meetings, and personal time</li>";
    echo "<li><strong>30-Minute Slots:</strong> All appointments are now standardized to 30-minute duration</li>";
    echo "<li><strong>Enhanced Notifications:</strong> Improved notification system with real-time updates</li>";
    echo "<li><strong>Better Performance:</strong> Optimized database indexes and queries</li>";
    echo "</ul>";
    
    echo "<h3>🔧 Next Steps</h3>";
    echo "<ol>";
    echo "<li><strong>Faculty Setup:</strong> Ask faculty to set up their consultation hours at <code>/pages/faculty/consultation_hours.php</code></li>";
    echo "<li><strong>Test Booking:</strong> Test the new 30-minute appointment booking system</li>";
    echo "<li><strong>Verify Notifications:</strong> Check that notifications are working correctly</li>";
    echo "<li><strong>Monitor Performance:</strong> Monitor system performance with the new optimizations</li>";
    echo "<li><strong>Cleanup:</strong> You can safely delete this migration script after testing</li>";
    echo "</ol>";
    
    echo "<div style='background: #f0f0f0; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<strong>📚 Documentation:</strong> The new system includes comprehensive consultation hour management, ";
    echo "30-minute standardized slots, break management, and enhanced notifications. ";
    echo "Faculty can access the new features through the updated navigation menu.";
    echo "</div>";
}

// Run the migration
echo "<div style='background: #fff3cd; padding: 15px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<strong>⚠️ Important:</strong> This migration script will modify your database. ";
echo "Make sure you have a backup before proceeding. This script is designed to run only once.";
echo "</div>";

if (isset($_GET['run']) && $_GET['run'] === 'migration') {
    runMigration();
} else {
    echo "<p><a href='?run=migration' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Start Migration</a></p>";
}

echo "<hr>";
echo "<p><small>Migration script completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>