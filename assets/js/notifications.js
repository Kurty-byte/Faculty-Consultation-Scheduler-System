/**
 * Dynamic Notification System JavaScript
 * Handles real-time time updates and notification management
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize notification badge update
    updateNotificationBadge();
    
    // Update badge every 30 seconds
    setInterval(updateNotificationBadge, 30000);
    
    // Update time displays every 30 seconds for better responsiveness
    if (document.querySelectorAll('[data-timestamp]').length > 0 || 
        document.querySelectorAll('.time-ago').length > 0) {
        updateTimeDisplays();
        setInterval(updateTimeDisplays, 30000);
    }
    
    // Function to update notification badge count
    function updateNotificationBadge() {
        fetch(getBaseUrl() + 'ajax/get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('notificationBadge');
                const notificationIcon = document.querySelector('.notifications-icon');
                
                if (data.count > 0) {
                    // Show or update badge
                    if (badge) {
                        badge.textContent = data.count;
                    } else if (notificationIcon && !notificationIcon.querySelector('.notification-badge')) {
                        // Create new badge
                        const newBadge = document.createElement('span');
                        newBadge.className = 'notification-badge';
                        newBadge.id = 'notificationBadge';
                        newBadge.textContent = data.count;
                        notificationIcon.appendChild(newBadge);
                    }
                } else {
                    // Remove badge if count is 0
                    if (badge) {
                        badge.remove();
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error updating notification count:', error);
        });
    }
    
    // Function to update all time displays dynamically
    function updateTimeDisplays() {
        // Update elements with data-timestamp attribute
        document.querySelectorAll('[data-timestamp]').forEach(function(element) {
            const timestamp = parseInt(element.dataset.timestamp);
            if (timestamp && timestamp > 0) {
                const timeAgo = calculateTimeAgo(timestamp);
                
                // Handle different prefixes for different contexts
                const originalText = element.textContent;
                if (originalText.includes('Approved ')) {
                    element.textContent = 'Approved ' + timeAgo;
                } else if (originalText.includes('Requested ')) {
                    element.textContent = 'Requested ' + timeAgo;
                } else if (originalText.includes('Cancelled ')) {
                    element.textContent = 'Cancelled ' + timeAgo;
                } else if (originalText.includes('Rejected ')) {
                    element.textContent = 'Rejected ' + timeAgo;
                } else {
                    element.textContent = timeAgo;
                }
            }
        });
        
        // Update generic time-ago elements
        document.querySelectorAll('.time-ago:not([data-timestamp])').forEach(function(element) {
            // Try to get timestamp from a hidden element or data attribute
            const timestampElement = element.parentNode.querySelector('.timestamp-data');
            if (timestampElement) {
                const timestamp = parseInt(timestampElement.textContent);
                if (timestamp && timestamp > 0) {
                    const timeAgo = calculateTimeAgo(timestamp);
                    element.textContent = timeAgo;
                }
            }
        });
    }
    
    // Calculate time ago from Unix timestamp
    function calculateTimeAgo(timestamp) {
        const now = Math.floor(Date.now() / 1000);
        const diff = now - timestamp;
        
        // Handle future dates or invalid timestamps
        if (diff < 0 || isNaN(diff)) {
            return 'Just now';
        }
        
        // Less than a minute
        if (diff < 60) {
            return 'Just now';
        }
        
        // Minutes
        if (diff < 3600) {
            const minutes = Math.floor(diff / 60);
            return minutes + ' minute' + (minutes > 1 ? 's' : '') + ' ago';
        }
        
        // Hours
        if (diff < 86400) {
            const hours = Math.floor(diff / 3600);
            return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
        }
        
        // Days
        if (diff < 604800) {
            const days = Math.floor(diff / 86400);
            return days + ' day' + (days > 1 ? 's' : '') + ' ago';
        }
        
        // Weeks
        if (diff < 2592000) {
            const weeks = Math.floor(diff / 604800);
            return weeks + ' week' + (weeks > 1 ? 's' : '') + ' ago';
        }
        
        // Months
        if (diff < 31536000) {
            const months = Math.floor(diff / 2592000);
            return months + ' month' + (months > 1 ? 's' : '') + ' ago';
        }
        
        // Years
        const years = Math.floor(diff / 31536000);
        return years + ' year' + (years > 1 ? 's' : '') + ' ago';
    }
    
    // Helper function to get base URL
    function getBaseUrl() {
        const protocol = window.location.protocol;
        const host = window.location.host;
        const path = window.location.pathname;
        
        // Extract the base path dynamically
        let basePath = '/';
        if (path.includes('/fcss/')) {
            basePath = '/fcss/';
        }
        
        return protocol + '//' + host + basePath;
    }
    
    // Global functions for marking notifications (used by notification pages)
    window.markNotificationAsRead = function(notificationId, callback) {
        fetch(getBaseUrl() + 'ajax/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + notificationId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge();
                if (callback) callback(true, data);
            } else {
                console.error('Failed to mark notification as read:', data.message);
                if (callback) callback(false, data);
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
            if (callback) callback(false, { message: 'Network error occurred' });
        });
    };
    
    window.markAllNotificationsAsRead = function(callback) {
        fetch(getBaseUrl() + 'ajax/mark_all_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge();
                if (callback) callback(true, data);
            } else {
                console.error('Failed to mark all notifications as read:', data.message);
                if (callback) callback(false, data);
            }
        })
        .catch(error => {
            console.error('Error marking all notifications as read:', error);
            if (callback) callback(false, { message: 'Network error occurred' });
        });
    };
    
    // Auto-refresh notification pages every 2 minutes if user is on notifications page
    if (window.location.pathname.includes('/notifications.php')) {
        setInterval(function() {
            // Only refresh if there are no unread notifications visible
            const unreadCards = document.querySelectorAll('.notification-card.unread');
            if (unreadCards.length === 0) {
                // Check if there are new notifications
                fetch(getBaseUrl() + 'ajax/get_notification_count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.count > 0) {
                        // Reload page to show new notifications
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error checking for new notifications:', error);
                });
            }
        }, 120000); // 2 minutes
    }
    
    // Show success message when notifications are marked as read
    window.showNotificationSuccess = function(message) {
        // Create a temporary success message
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success';
        successDiv.style.position = 'fixed';
        successDiv.style.top = '20px';
        successDiv.style.right = '20px';
        successDiv.style.zIndex = '9999';
        successDiv.style.minWidth = '300px';
        successDiv.textContent = message || 'Notification marked as read';
        
        document.body.appendChild(successDiv);
        
        // Remove after 3 seconds
        setTimeout(() => {
            successDiv.style.opacity = '0';
            setTimeout(() => {
                if (successDiv.parentNode) {
                    successDiv.parentNode.removeChild(successDiv);
                }
            }, 300);
        }, 3000);
    };
    
    // Handle page visibility change to update notifications when user returns
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // User returned to page, update badge and time displays
            setTimeout(() => {
                updateNotificationBadge();
                updateTimeDisplays();
            }, 1000);
        }
    });
    
    // Expose updateTimeDisplays function globally for manual updates
    window.updateTimeDisplays = updateTimeDisplays;
    window.calculateTimeAgo = calculateTimeAgo;
    
    // Debug function (only available in development)
    if (window.location.hostname === 'localhost') {
        window.debugNotifications = function() {
            console.log('Notification system debug info:');
            console.log('Current badge:', document.getElementById('notificationBadge'));
            console.log('Notification icon:', document.querySelector('.notifications-icon'));
            console.log('Base URL:', getBaseUrl());
            console.log('Time display elements:', document.querySelectorAll('[data-timestamp], .time-ago').length);
            
            // Test notification count
            fetch(getBaseUrl() + 'ajax/get_notification_count.php')
            .then(response => response.json())
            .then(data => {
                console.log('Current notification count:', data);
            });
        };
    }
});

// CSS for success messages and animations
const style = document.createElement('style');
style.textContent = `
.alert {
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.375rem;
    transition: opacity 0.3s ease;
}

.alert-success {
    color: #0f5132;
    background-color: #d1e7dd;
    border-color: #badbcc;
}

.time-ago {
    transition: opacity 0.3s ease;
}

.time-ago.updating {
    opacity: 0.7;
}

@keyframes timeUpdate {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.time-ago.animate-update {
    animation: timeUpdate 0.5s ease;
}
`;
document.head.appendChild(style);