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
                            <li><a href="<?php echo BASE_URL; ?>pages/faculty/dashboard.php" <?php echo (strpos($_SERVER['REQUEST_URI'], '/dashboard.php') !== false) ? 'class="active"' : ''; ?>>Dashboard</a></li>
                            <li class="nav-dropdown">
                                <a href="<?php echo BASE_URL; ?>pages/faculty/consultation_hours.php" <?php echo (strpos($_SERVER['REQUEST_URI'], '/consultation_hours.php') !== false || strpos($_SERVER['REQUEST_URI'], '/manage_schedules.php') !== false) ? 'class="active"' : ''; ?>>
                                    Schedule Management
                                    <span class="dropdown-arrow">▼</span>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a href="<?php echo BASE_URL; ?>pages/faculty/consultation_hours.php">📋 Overview</a></li>
                                    <li><a href="<?php echo BASE_URL; ?>pages/faculty/set_consultation_hours.php">⏰ Set Hours</a></li>
                                    <li><a href="<?php echo BASE_URL; ?>pages/faculty/manage_breaks.php">☕ Manage Breaks</a></li>
                                </ul>
                            </li>
                            <li><a href="<?php echo BASE_URL; ?>pages/faculty/view_appointments.php" <?php echo (strpos($_SERVER['REQUEST_URI'], '/view_appointments.php') !== false || strpos($_SERVER['REQUEST_URI'], '/appointment_details.php') !== false) ? 'class="active"' : ''; ?>>Appointments</a></li>
                        <?php elseif (hasRole('student')): ?>
                            <li><a href="<?php echo BASE_URL; ?>pages/student/dashboard.php" <?php echo (strpos($_SERVER['REQUEST_URI'], '/dashboard.php') !== false) ? 'class="active"' : ''; ?>>Dashboard</a></li>
                            <li><a href="<?php echo BASE_URL; ?>pages/student/view_faculty.php" <?php echo (strpos($_SERVER['REQUEST_URI'], '/view_faculty.php') !== false || strpos($_SERVER['REQUEST_URI'], '/faculty_schedule.php') !== false || strpos($_SERVER['REQUEST_URI'], '/book_appointment.php') !== false) ? 'class="active"' : ''; ?>>Book Appointment</a></li>
                            <li><a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php" <?php echo (strpos($_SERVER['REQUEST_URI'], '/view_appointments.php') !== false || strpos($_SERVER['REQUEST_URI'], '/appointment_details.php') !== false) ? 'class="active"' : ''; ?>>My Appointments</a></li>
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

<style>
/* Enhanced Navigation Styles */
.nav-dropdown {
    position: relative;
}

.nav-dropdown .dropdown-arrow {
    font-size: 0.7rem;
    margin-left: 0.25rem;
    transition: transform 0.2s;
}

.nav-dropdown:hover .dropdown-arrow {
    transform: rotate(180deg);
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background-color: white;
    min-width: 200px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-radius: 6px;
    padding: 0.5rem 0;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1000;
    border: 1px solid #e9ecef;
}

.nav-dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-menu li {
    margin: 0;
}

.dropdown-menu a {
    display: block;
    padding: 0.75rem 1rem;
    color: var(--dark) !important;
    text-decoration: none;
    transition: background-color 0.2s;
    font-size: 0.9rem;
}

.dropdown-menu a:hover {
    background-color: #f8f9fc;
    color: var(--primary) !important;
}

.dropdown-menu a:before {
    content: none;
}

/* Active state for navigation */
nav ul li a.active {
    color: var(--primary) !important;
    font-weight: 600;
    position: relative;
}

nav ul li a.active::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: var(--primary);
    border-radius: 1px;
}

/* Mobile responsive dropdown */
@media (max-width: 768px) {
    .dropdown-menu {
        position: static;
        opacity: 1;
        visibility: visible;
        transform: none;
        box-shadow: none;
        background-color: rgba(255,255,255,0.1);
        border: none;
        padding-left: 1rem;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }
    
    .nav-dropdown:hover .dropdown-menu,
    .nav-dropdown.active .dropdown-menu {
        max-height: 200px;
    }
    
    .dropdown-menu a {
        color: rgba(255, 255, 255, 0.8) !important;
        padding: 0.5rem 1rem;
    }
    
    .dropdown-menu a:hover {
        background-color: rgba(255,255,255,0.1);
        color: white !important;
    }
    
    nav ul li a.active::after {
        display: none;
    }
}

/* Enhanced notification badge */
.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: var(--danger);
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    z-index: 1;
    border: 2px solid white;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(231, 74, 59, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(231, 74, 59, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(231, 74, 59, 0);
    }
}

/* Mobile navigation improvements */
@media (max-width: 768px) {
    .nav-toggle {
        display: block;
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 4px;
        transition: background-color 0.2s;
    }
    
    .nav-toggle:hover {
        background-color: rgba(255,255,255,0.1);
    }
    
    nav {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background-color: var(--dark);
        border-top: 1px solid rgba(255,255,255,0.1);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    nav ul {
        padding: 1rem 0;
    }
    
    nav ul li {
        margin: 0;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    nav ul li:last-child {
        border-bottom: none;
    }
    
    nav ul li a {
        display: block;
        padding: 1rem 1.5rem;
        border-left: 3px solid transparent;
        transition: all 0.2s;
    }
    
    nav ul li a.active {
        border-left-color: var(--primary);
        background-color: rgba(78, 115, 223, 0.1);
    }
    
    nav ul li a:hover {
        background-color: rgba(255,255,255,0.05);
        padding-left: 2rem;
    }
}

/* Breadcrumb-style navigation helper */
.nav-breadcrumb {
    background-color: #f8f9fc;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
    font-size: 0.85rem;
}

.nav-breadcrumb .container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--gray);
}

.nav-breadcrumb a {
    color: var(--primary);
    text-decoration: none;
}

.nav-breadcrumb a:hover {
    text-decoration: underline;
}

.nav-breadcrumb .separator {
    color: var(--gray);
    margin: 0 0.5rem;
}
</style>

<script>
// Enhanced mobile navigation
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('nav');
    
    if (navToggle && nav) {
        navToggle.addEventListener('click', function() {
            nav.classList.toggle('show');
            this.setAttribute('aria-expanded', nav.classList.contains('show'));
        });
        
        // Close navigation when clicking outside
        document.addEventListener('click', function(e) {
            if (!nav.contains(e.target) && !navToggle.contains(e.target)) {
                nav.classList.remove('show');
                navToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
    
    // Mobile dropdown toggle
    const dropdownToggles = document.querySelectorAll('.nav-dropdown > a');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                const dropdown = this.parentNode;
                dropdown.classList.toggle('active');
            }
        });
    });
    
    // Update notification badge if function exists
    if (typeof updateNotificationBadge === 'function') {
        updateNotificationBadge();
        setInterval(updateNotificationBadge, 30000);
    }
});
</script>