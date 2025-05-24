<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon.png">
    <meta name="description" content="Faculty Consultation Scheduler System - Streamline academic consultations with efficient scheduling">
    <meta name="keywords" content="faculty, consultation, scheduler, academic, university, appointments">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/forms.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/consultation-hours.css">
    
    <?php if (isset($isLandingPage) && $isLandingPage): ?>
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/landing.css">
    <?php endif; ?>
    
    <?php if (isset($extraCSS)): ?>
        <?php foreach ($extraCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo BASE_URL . 'assets/css/' . $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body<?php echo (isset($isLandingPage) && $isLandingPage) ? ' class="landing-page"' : ''; ?>>
    <header<?php echo (isset($isLandingPage) && $isLandingPage) ? ' class="landing-header"' : ''; ?>>
        <div class="container">
            <div class="logo">
                <a href="<?php echo BASE_URL; ?><?php echo isLoggedIn() ? (hasRole('faculty') ? 'pages/faculty/dashboard.php' : 'pages/student/dashboard.php') : 'home.php'; ?>">
                    <h1><?php echo SITE_NAME; ?></h1>
                </a>
            </div>
            
            <?php if (isLoggedIn()): ?>
                <nav>
                    <ul>
                        <?php if (hasRole('faculty')): ?>
                            <li><a href="<?php echo BASE_URL; ?>pages/faculty/dashboard.php" <?php echo (strpos($_SERVER['REQUEST_URI'], '/dashboard.php') !== false) ? 'class="active"' : ''; ?>>Dashboard</a></li>
                            <li><a href="<?php echo BASE_URL; ?>pages/faculty/consultation_hours.php" <?php echo (strpos($_SERVER['REQUEST_URI'], '/consultation_hours.php') !== false || strpos($_SERVER['REQUEST_URI'], '/set_consultation_hours.php') !== false) ? 'class="active"' : ''; ?>>Consultation Hours</a></li>
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
                </div>
            <?php else: ?>
                <!-- Navigation for non-logged in users (landing page) -->
                <?php if (isset($isLandingPage) && $isLandingPage): ?>
                    <nav class="landing-nav">
                        <ul>
                            <li><a href="<?php echo BASE_URL; ?>login.php">Login</a></li>
                            <li><a href="<?php echo BASE_URL; ?>register.php">Register</a></li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </header>
    
    <main<?php echo (isset($isLandingPage) && $isLandingPage) ? ' class="landing-main"' : ''; ?>>
        <?php if (!isset($isLandingPage) || !$isLandingPage): ?>
            <div class="container">
                <?php displayFlashMessage(); ?>
        <?php else: ?>
            <?php displayFlashMessage(); ?>
        <?php endif; ?>

<style>
/* Landing page specific header styles */
.landing-header {
    position: fixed !important;
    background: rgba(255,255,255,0.1) !important;
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    transition: all 0.3s ease;
}

.landing-header.scrolled {
    background: rgba(26, 32, 44, 0.95) !important;
    backdrop-filter: blur(20px);
}

.landing-header .logo a {
    text-decoration: none;
}

.landing-header .logo h1 {
    font-size: 1.8rem;
    font-weight: 700;
    background: linear-gradient(45deg, #ffffff, #f0f0f0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0;
    transition: all 0.3s ease;
}

.landing-header .logo h1:hover {
    transform: scale(1.05);
}

.landing-nav ul {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 1rem;
}

.landing-nav ul li a {
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    transition: all 0.3s ease;
    font-weight: 500;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
}

.landing-nav ul li a:hover {
    background: rgba(255,255,255,0.1);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.landing-main {
    padding: 0 !important;
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

/* Mobile responsive */
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
        display: none;
    }
    
    nav.show {
        display: block;
    }
    
    nav ul {
        padding: 1rem 0;
        flex-direction: column;
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
    
    nav ul li a.active::after {
        display: none;
    }
    
    .landing-header .container {
        flex-wrap: wrap;
    }
    
    .landing-nav {
        width: 100%;
        margin-top: 1rem;
    }
    
    .landing-nav ul {
        justify-content: center;
        flex-wrap: wrap;
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

/* Header scroll effect for landing page */
.landing-header-scroll-effect {
    background: rgba(26, 32, 44, 0.95) !important;
    backdrop-filter: blur(20px);
    box-shadow: 0 2px 20px rgba(0,0,0,0.1);
}
</style>

<script>
// Enhanced mobile navigation and landing page effects
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('nav');
    const landingHeader = document.querySelector('.landing-header');
    
    // Mobile navigation toggle
    if (navToggle && nav) {
        navToggle.addEventListener('click', function() {
            nav.classList.toggle('show');
            this.setAttribute('aria-expanded', nav.classList.contains('show'));
        });
        
        // Close navigation when clicking outside
        document.addEventListener('click', function(e) {
            if (!nav.contains(e.target) && !navToggle.contains(e.target)) {
                nav.classList.remove('show');
                if (navToggle) navToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
    
    // Landing page header scroll effect
    if (landingHeader) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 100) {
                landingHeader.classList.add('landing-header-scroll-effect');
            } else {
                landingHeader.classList.remove('landing-header-scroll-effect');
            }
        });
    }
    
    // Initialize enhanced notification system for logged-in users
    <?php if (isLoggedIn()): ?>
    // Auto-refresh configuration initialization
    window.autoRefreshConfig = {
        userRole: '<?php echo $_SESSION['role']; ?>',
        userId: <?php echo $_SESSION['user_id']; ?>,
        currentPage: '<?php echo basename($_SERVER['PHP_SELF']); ?>',
        baseUrl: '<?php echo BASE_URL; ?>',
        refreshInterval: <?php 
            // Set page-specific refresh intervals
            $currentPage = basename($_SERVER['PHP_SELF']);
            if (strpos($currentPage, 'dashboard.php') !== false) {
                echo '60000'; // 1 minute for dashboard
            } elseif (strpos($currentPage, 'notifications.php') !== false) {
                echo '30000'; // 30 seconds for notifications page
            } elseif (strpos($currentPage, 'appointment') !== false) {
                echo '90000'; // 1.5 minutes for appointment pages
            } else {
                echo '120000'; // 2 minutes for other pages
            }
        ?>
    };
    
    // Enhanced notification badge initialization
    const notificationBadge = document.getElementById('notificationBadge');
    if (notificationBadge) {
        // Add pulse animation for existing notifications
        notificationBadge.classList.add('has-unread');
        
        // Store initial count for comparison
        window.initialNotificationCount = parseInt(notificationBadge.textContent) || 0;
    }
    
    // Custom event listeners for page-specific updates
    document.addEventListener('notificationsUpdated', function(event) {
        const detail = event.detail;
        
        // Update page-specific elements if they exist
        updatePageSpecificElements(detail);
        
        // Log activity for debugging (only in development)
        <?php if (DEBUG_MODE): ?>
        console.log('Notifications updated:', detail);
        <?php endif; ?>
    });
    
    function updatePageSpecificElements(data) {
        // Update dashboard statistics if on dashboard page
        <?php if (strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false): ?>
        if (data.activitySummary) {
            updateDashboardStats(data.activitySummary);
        }
        <?php endif; ?>
        
        // Update appointment counters if on appointment pages
        const statBoxes = document.querySelectorAll('.stat-box .stat-number');
        if (statBoxes.length > 0 && data.activitySummary) {
            updateStatBoxes(data.activitySummary);
        }
    }
    
    function updateDashboardStats(summary) {
        // Update pending appointments count
        const pendingElement = document.querySelector('[data-stat="pending"] .stat-number');
        if (pendingElement && summary.pending_appointments !== undefined) {
            pendingElement.textContent = summary.pending_appointments;
        }
        
        // Update today's appointments count
        const todayElement = document.querySelector('[data-stat="today"] .stat-number');
        if (todayElement && summary.todays_appointments !== undefined) {
            todayElement.textContent = summary.todays_appointments;
        }
    }
    
    function updateStatBoxes(summary) {
        // This can be expanded based on your specific stat box implementations
        const pendingBox = document.querySelector('.stat-box:first-child .stat-number');
        if (pendingBox && summary.pending_appointments !== undefined) {
            pendingBox.textContent = summary.pending_appointments;
            
            // Add animation if count changed
            if (pendingBox.textContent !== pendingBox.dataset.lastValue) {
                pendingBox.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    pendingBox.style.transform = 'scale(1)';
                }, 200);
                pendingBox.dataset.lastValue = pendingBox.textContent;
            }
        }
    }
    
    // Page-specific enhancements
    <?php if (strpos($_SERVER['PHP_SELF'], 'notifications.php') !== false): ?>
    // Enhanced notifications page functionality
    setupNotificationsPageEnhancements();
    <?php endif; ?>
    
    <?php if (strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false): ?>
    // Enhanced dashboard functionality
    setupDashboardEnhancements();
    <?php endif; ?>
    
    function setupNotificationsPageEnhancements() {
        // Auto-scroll to new notifications
        const notificationsList = document.getElementById('notificationsList');
        if (notificationsList) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        // New notification added, scroll to top
                        notificationsList.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
            
            observer.observe(notificationsList, { childList: true });
        }
    }
    
    function setupDashboardEnhancements() {
        // Add real-time clock to dashboard
        const clockElement = document.querySelector('.dashboard-clock');
        if (clockElement) {
            updateClock();
            setInterval(updateClock, 1000);
        }
        
        function updateClock() {
            const now = new Date();
            clockElement.textContent = now.toLocaleTimeString();
        }
    }
    
    // Keyboard shortcuts for power users
    document.addEventListener('keydown', function(e) {
        // Alt + N to go to notifications
        if (e.altKey && e.key === 'n') {
            e.preventDefault();
            const notificationsLink = document.querySelector('a[href*="notifications.php"]');
            if (notificationsLink) {
                notificationsLink.click();
            }
        }
        
        // Alt + D to go to dashboard
        if (e.altKey && e.key === 'd') {
            e.preventDefault();
            const dashboardLink = document.querySelector('a[href*="dashboard.php"]');
            if (dashboardLink) {
                dashboardLink.click();
            }
        }
    });
    
    <?php endif; ?>
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Connection status monitoring
    let isOnline = navigator.onLine;
    
    window.addEventListener('online', function() {
        if (!isOnline) {
            isOnline = true;
            showConnectionStatus('back online', 'success');
            // Immediate notification update when back online
            if (window.NotificationManager) {
                window.NotificationManager.updateNotificationBadge();
            }
        }
    });
    
    window.addEventListener('offline', function() {
        isOnline = false;
        showConnectionStatus('offline', 'warning');
    });
    
    function showConnectionStatus(status, type) {
        const toast = document.createElement('div');
        toast.className = `notification-toast ${type}`;
        toast.innerHTML = `
            <div class="toast-icon">${type === 'success' ? '🌐' : '📡'}</div>
            <div class="toast-content">
                <div class="toast-title">Connection Status</div>
                <div class="toast-message">You are now ${status}</div>
            </div>
        `;
        
        const container = document.getElementById('notificationToastContainer');
        if (container) {
            container.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }
    }
});
</script>

<!-- Load auto-refresh configuration and enhanced notifications -->
<?php if (isLoggedIn()): ?>
<script src="<?php echo BASE_URL; ?>assets/js/auto-refresh-config.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/notifications.js"></script>
<?php endif; ?>

<?php if (isset($extraJS)): ?>
    <?php foreach ($extraJS as $js): ?>
        <script src="<?php echo BASE_URL . 'assets/js/' . $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>