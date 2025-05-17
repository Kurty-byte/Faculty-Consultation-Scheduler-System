s<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/forms.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/calendar.css">
    <?php if (isset($extraCSS)): ?>
    <?php foreach ($extraCSS as $css): ?>
        <link rel="stylesheet" href="<?php echo BASE_URL . 'assets/css/' . $css; ?>">
    <?php endforeach; ?>
<?php endif; ?>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><?php echo SITE_NAME; ?></h1>
            </div>
            
            <?php if (isLoggedIn()): ?>
                <nav>
                    <ul>
                        <?php if (hasRole('faculty')): ?>
                            <li><a href="<?php echo BASE_URL; ?>pages/faculty/dashboard.php">Dashboard</a></li>
                            <li><a href="<?php echo BASE_URL; ?>pages/faculty/manage_schedules.php">Manage Schedules</a></li>
                            <li><a href="<?php echo BASE_URL; ?>pages/faculty/view_appointments.php">View Appointments</a></li>
                        <?php elseif (hasRole('student')): ?>
                            <li><a href="<?php echo BASE_URL; ?>pages/student/dashboard.php">Dashboard</a></li>
                            <li><a href="<?php echo BASE_URL; ?>pages/student/view_faculty.php">Book Appointment</a></li>
                            <li><a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php">My Appointments</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo BASE_URL; ?>pages/auth/logout.php">Logout</a></li>
                    </ul>
                </nav>
                
                <div class="user-info">
                    Welcome, <?php echo $_SESSION['first_name']; ?>

                    <?php if (isLoggedIn()): ?>
    <div class="notifications-container">
        <div class="notifications-icon" id="notificationsToggle">
            <i class="icon-bell"></i>
            <?php 
            // Include notification functions if not already included
            if (!function_exists('countUnreadNotifications')) {
                require_once 'notification_functions.php';
            }
            
            $unreadCount = countUnreadNotifications();
            if ($unreadCount > 0): 
            ?>
                <span class="notification-badge"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
        </div>
        
        <div class="notifications-dropdown" id="notificationsDropdown">
            <div class="notifications-header">
                <h3>Notifications</h3>
                <?php if ($unreadCount > 0): ?>
                    <span class="notifications-count"><?php echo $unreadCount; ?> new</span>
                <?php endif; ?>
            </div>
            
            <div class="notifications-list">
                <?php 
                $notifications = getUserNotifications();
                if (empty($notifications)): 
                ?>
                    <div class="notification-empty">
                        <p>No notifications yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <a href="<?php echo $notification['link']; ?>" 
                           class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>"
                           data-id="<?php echo $notification['id']; ?>">
                            <div class="notification-icon">
                                <?php if ($notification['type'] == 'appointment_request'): ?>
                                    <i class="icon-calendar-plus"></i>
                                <?php elseif ($notification['type'] == 'appointment_approved'): ?>
                                    <i class="icon-check-circle"></i>
                                <?php elseif ($notification['type'] == 'appointment_rejected'): ?>
                                    <i class="icon-times-circle"></i>
                                <?php endif; ?>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title"><?php echo htmlspecialchars($notification['message']); ?></div>
                                <?php if (!empty($notification['details'])): ?>
                                    <div class="notification-details"><?php echo htmlspecialchars(substr($notification['details'], 0, 50)); ?><?php echo (strlen($notification['details']) > 50) ? '...' : ''; ?></div>
                                <?php endif; ?>
                                <div class="notification-time"><?php echo getTimeAgo($notification['timestamp']); ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($notifications)): ?>
                <div class="notifications-footer">
                    <a href="<?php echo BASE_URL; ?>pages/<?php echo $_SESSION['role']; ?>/view_appointments.php">View all</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </header>
    
    <main>
        <div class="container">
            <?php displayFlashMessage(); ?>