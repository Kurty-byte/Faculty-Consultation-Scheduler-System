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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/login.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/register.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/toast.css">
    
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
    <?php 
    // Determine page type more accurately
    $currentPage = basename($_SERVER['PHP_SELF']);
    $isAuthPage = in_array($currentPage, ['login.php', 'register.php']);
    $isHomePage = ($currentPage === 'home.php');
    $isLandingPageActual = (isset($isLandingPage) && $isLandingPage && $isHomePage);
    ?>
    
    <header<?php 
        // Apply different header classes based on page type
        if ($isLandingPageActual) {
            echo ' class="landing-header"';
        } elseif ($isAuthPage) {
            echo ' class="auth-header"';
        }
    ?>>
        <div class="container">
            <?php if (isLoggedIn()): ?>
                <!-- Logged in user header -->
                <div class="header-main">
                    <div class="logo-section">
                        <div class="logo">
                            <a href="<?php echo BASE_URL; ?><?php echo hasRole('faculty') ? 'pages/faculty/dashboard.php' : 'pages/student/dashboard.php'; ?>">
                                <h1><?php echo SITE_NAME; ?></h1>
                            </a>
                        </div>
                        <!-- Navigation directly under the title -->
                        <nav class="main-navigation">
                            <ul>
                                <?php if (hasRole('faculty')): ?>
                                    <li><a href="<?php echo BASE_URL; ?>pages/faculty/dashboard.php" <?php echo (strpos($_SERVER['REQUEST_URI'], '/dashboard.php') !== false) ? 'class="active"' : ''; ?>>Dashboard</a></li>
                                    <li><a href="<?php echo BASE_URL; ?>pages/faculty/consultation_hours.php" <?php echo (strpos($_SERVER['REQUEST_URI'], '/consultation_hours.php') !== false || strpos($_SERVER['REQUEST_URI'], '/set_consultation_hours.php') !== false) ? 'class="active"' : ''; ?>>Consultation Hours</a></li>
                                    <li><a href="<?php echo BASE_URL; ?>pages/faculty/view_appointments.php" <?php echo (strpos($_SERVER['REQUEST_URI'], '/view_appointments.php') !== false || strpos($_SERVER['REQUEST_URI'], '/appointment_details.php') !== false) ? 'class="active"' : ''; ?>>My Appointments</a></li>
                                <?php elseif (hasRole('student')): ?>
                                    <li><a href="<?php echo BASE_URL; ?>pages/student/dashboard.php" <?php echo (strpos($_SERVER['REQUEST_URI'], '/dashboard.php') !== false) ? 'class="active"' : ''; ?>>Dashboard</a></li>
                                    <li><a href="<?php echo BASE_URL; ?>pages/student/view_faculty.php" <?php echo (strpos($_SERVER['REQUEST_URI'], '/view_faculty.php') !== false || strpos($_SERVER['REQUEST_URI'], '/faculty_schedule.php') !== false || strpos($_SERVER['REQUEST_URI'], '/book_appointment.php') !== false) ? 'class="active"' : ''; ?>>Book Appointment</a></li>
                                    <li><a href="<?php echo BASE_URL; ?>pages/student/view_appointments.php" <?php echo (strpos($_SERVER['REQUEST_URI'], '/view_appointments.php') !== false || strpos($_SERVER['REQUEST_URI'], '/appointment_details.php') !== false) ? 'class="active"' : ''; ?>>My Appointments</a></li>
                                <?php endif; ?>
                                <!-- Logout button -->
                                <li><a href="<?php echo BASE_URL; ?>pages/auth/logout.php">Logout</a></li>
                            </ul>
                        </nav>
                    </div>
                    
                    <!-- User info section on the right -->
                    <div class="user-info-section">
                        <span class="user-role-badge"><?php echo DEBUG_MODE ? "DEBUG": "NON-DEBUG"?></span>
                        <span class="user-role-badge"><?php echo ucfirst($_SESSION['role']); ?></span>
                        <span class="user-welcome">Welcome, <?php echo $_SESSION['first_name']; ?></span>
                        <span class="user-divider">|</span>
                        <div class="notifications-container">
                            <a href="<?php echo BASE_URL; ?>pages/<?php echo $_SESSION['role']; ?>/notifications.php" class="notifications-icon" title="View Notifications">
                                🔔
                                <?php 
                                $unreadCount = 0;
                                if (file_exists(__DIR__ . '/notification_system.php')) {
                                    require_once __DIR__ . '/notification_system.php';
                                    $unreadCount = countUnreadNotifications($_SESSION['user_id']);
                                }
                                if ($unreadCount > 0): 
                                ?>
                                    <span class="notification-badge" id="notificationBadge"><?php echo $unreadCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Header for non-logged-in users -->
                <?php if ($isLandingPageActual): ?>
                    <!-- Landing page header (home.php only) -->
                    <div class="logo">
                        <a href="<?php echo BASE_URL; ?>home.php">
                            <h1><?php echo SITE_NAME; ?></h1>
                        </a>
                    </div>
                    <nav class="landing-nav">
                        <ul>
                            <?php if ($currentPage === 'home.php'): ?>
                                <li><a href="<?php echo BASE_URL; ?>login.php">Login</a></li>
                                <li><a href="<?php echo BASE_URL; ?>register.php">Register</a></li>
                            <?php elseif ($currentPage === 'login.php'): ?>
                                <li><a href="<?php echo BASE_URL; ?>home.php">Home</a></li>
                                <li><a href="<?php echo BASE_URL; ?>register.php">Register</a></li>
                            <?php elseif ($currentPage === 'register.php'): ?>
                                <li><a href="<?php echo BASE_URL; ?>home.php">Home</a></li>
                                <li><a href="<?php echo BASE_URL; ?>login.php">Login</a></li>
                            <?php else: ?>
                                <li><a href="<?php echo BASE_URL; ?>home.php">Home</a></li>
                                <li><a href="<?php echo BASE_URL; ?>login.php">Login</a></li>
                                <li><a href="<?php echo BASE_URL; ?>register.php">Register</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php else: ?>
                    <!-- Auth pages header (login.php, register.php) - Same as landing page -->
                    <div class="logo">
                        <a href="<?php echo BASE_URL; ?>home.php">
                            <h1><?php echo SITE_NAME; ?></h1>
                        </a>
                    </div>
                    <nav class="landing-nav">
                        <ul>
                            <?php if ($currentPage === 'login.php'): ?>
                                <li><a href="<?php echo BASE_URL; ?>home.php">Home</a></li>
                                <li><a href="<?php echo BASE_URL; ?>register.php">Register</a></li>
                            <?php elseif ($currentPage === 'register.php'): ?>
                                <li><a href="<?php echo BASE_URL; ?>home.php">Home</a></li>
                                <li><a href="<?php echo BASE_URL; ?>login.php">Login</a></li>
                            <?php else: ?>
                                <li><a href="<?php echo BASE_URL; ?>login.php">Login</a></li>
                                <li><a href="<?php echo BASE_URL; ?>register.php">Register</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </header>
    
    <main<?php echo ($isLandingPageActual) ? ' class="landing-main"' : ''; ?>>
        <?php if (!$isLandingPageActual): ?>
            <div class="container">
                <?php displayFlashMessage(); ?>
        <?php else: ?>
            <?php displayFlashMessage(); ?>
        <?php endif; ?>

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
    
    // Landing page header scroll effect (only for actual landing page - home.php)
    if (landingHeader && document.body.classList.contains('landing-page')) {
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