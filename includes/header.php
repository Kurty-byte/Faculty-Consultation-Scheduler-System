<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/forms.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/calendar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/icons.css">
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
                        <!-- Clickable notification icon -->
                        <a href="<?php echo BASE_URL; ?>pages/<?php echo $_SESSION['role']; ?>/notifications.php" class="notifications-icon" title="View Notifications">
                            🔔
                            <?php 
                            // Include new notification system
                            $unreadCount = 0;
                            
                            // Check if notification system file exists
                            if (file_exists(__DIR__ . '/notification_system.php')) {
                                require_once __DIR__ . '/notification_system.php';
                                $unreadCount = countUnreadNotifications($_SESSION['user_id']);
                            }
                            
                            // Only show badge if there are unread notifications
                            if ($unreadCount > 0): 
                            ?>
                                <span class="notification-badge" id="notificationBadge"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </header>
    
    <main>
        <div class="container">
            <?php displayFlashMessage(); ?>