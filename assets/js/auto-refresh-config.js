/**
 * Auto-Refresh Configuration for FCSS
 * Centralized settings for notification refresh system
 */

const AutoRefreshConfig = {
    intervals: {
        dashboard: 2000,
        notifications: 2000,
        appointments: 2000,
        default: 2000
    },
    
    // Page detection patterns
    pageTypes: {
        dashboard: ['/dashboard.php'],
        notifications: ['/notifications.php'],
        appointments: ['/view_appointments.php', '/appointment_details.php', '/book_appointment.php'],
        faculty_management: ['/consultation_hours.php', '/manage_schedules.php']
    },
    
    // Toast notification settings
    toast: {
        duration: 5000,        // 5 seconds
        position: 'top-right', // Position on screen
        maxVisible: 3,         // Maximum visible toasts
        fadeOutTime: 500       // Fade out animation time
    },
    
    // Badge animation settings
    badge: {
        pulseOnNew: true,      // Pulse animation for new notifications
        bounceDuration: 1000,  // Bounce animation duration
        glowEffect: true       // Glow effect for new notifications
    },
    
    // Performance settings
    performance: {
        pauseWhenHidden: true,     // Pause when tab is hidden
        maxRetries: 3,             // Max retry attempts for failed requests
        retryDelay: 10000,         // Delay between retries (10 seconds)
        batchSize: 10              // Batch size for bulk operations
    },
    
    // Debug settings
    debug: {
        enabled: false,            // Enable debug logging
        logRefresh: false,         // Log refresh attempts
        logToasts: false           // Log toast notifications
    }
};

// Utility function to get current page type
function getCurrentPageType() {
    const currentPath = window.location.pathname;
    
    for (const [pageType, patterns] of Object.entries(AutoRefreshConfig.pageTypes)) {
        if (patterns.some(pattern => currentPath.includes(pattern))) {
            return pageType;
        }
    }
    
    return 'default';
}

// Utility function to get refresh interval for current page
function getCurrentRefreshInterval() {
    const pageType = getCurrentPageType();
    return AutoRefreshConfig.intervals[pageType] || AutoRefreshConfig.intervals.default;
}

// Export for use in other scripts
if (typeof window !== 'undefined') {
    window.AutoRefreshConfig = AutoRefreshConfig;
    window.getCurrentPageType = getCurrentPageType;
    window.getCurrentRefreshInterval = getCurrentRefreshInterval;
}