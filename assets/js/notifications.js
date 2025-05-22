/**
 * New Notification System JavaScript
 * Simple and clean notification management
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize notification badge update
    updateNotificationBadge();
    
    // Update badge every 30 seconds
    setInterval(updateNotificationBadge, 30000);
    
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
    
    // Helper function to get base URL
    function getBaseUrl() {
        const path = window.location.pathname;
        if (path.includes('/fcss/')) {
            return window.location.origin + '/fcss/';
        }
        return window.location.origin + '/';
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
            // User returned to page, update badge
            setTimeout(updateNotificationBudge, 1000);
        }
    });
    
    // Debug function (only available in development)
    if (window.location.hostname === 'localhost') {
        window.debugNotifications = function() {
            console.log('Notification system debug info:');
            console.log('Current badge:', document.getElementById('notificationBadge'));
            console.log('Notification icon:', document.querySelector('.notifications-icon'));
            console.log('Base URL:', getBaseUrl());
            
            // Test notification count
            fetch(getBaseUrl() + 'ajax/get_notification_count.php')
            .then(response => response.json())
            .then(data => {
                console.log('Current notification count:', data);
            });
        };
    }
});

// CSS for success messages
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
`;
document.head.appendChild(style);