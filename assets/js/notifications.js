/**
 * Enhanced Dynamic Notification System JavaScript
 * Handles real-time time updates and notification management with auto-refresh
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize notification system
    const NotificationManager = new EnhancedNotificationManager();
    NotificationManager.init();
});

class EnhancedNotificationManager {
    constructor() {
        this.lastKnownCount = 0;
        this.refreshInterval = null;
        this.isPageVisible = true;
        this.retryCount = 0;
        this.maxRetries = 3;
        this.toastContainer = null;
        this.activeToasts = new Set();
        
        // Get configuration
        this.config = window.AutoRefreshConfig || this.getDefaultConfig();
        this.refreshIntervalTime = getCurrentRefreshInterval ? getCurrentRefreshInterval() : 120000;
        
        // Initialize last known count from badge
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            this.lastKnownCount = parseInt(badge.textContent) || 0;
        }
    }
    
    getDefaultConfig() {
        return {
            intervals: { default: 120000 },
            toast: { duration: 5000, maxVisible: 3 },
            badge: { pulseOnNew: true },
            performance: { pauseWhenHidden: true }
        };
    }
    
    init() {
        this.createToastContainer();
        this.setupVisibilityChangeHandler();
        this.startAutoRefresh();
        this.updateTimeDisplays();
        this.setupPageSpecificHandlers();
        
        // Initial update
        this.updateNotificationBadge();
        
        // Update time displays every 30 seconds
        setInterval(() => this.updateTimeDisplays(), 30000);
        
        // Setup global functions for compatibility
        this.setupGlobalFunctions();
        
        console.log('Enhanced Notification Manager initialized');
    }
    
    createToastContainer() {
        this.toastContainer = document.createElement('div');
        this.toastContainer.className = 'notification-toast-container';
        this.toastContainer.id = 'notificationToastContainer';
        document.body.appendChild(this.toastContainer);
    }
    
    setupVisibilityChangeHandler() {
        document.addEventListener('visibilitychange', () => {
            this.isPageVisible = !document.hidden;
            
            if (this.isPageVisible) {
                // Page became visible - immediate update and resume auto-refresh
                this.updateNotificationBadge();
                this.updateTimeDisplays();
                this.startAutoRefresh();
            } else if (this.config.performance.pauseWhenHidden) {
                // Page hidden - pause auto-refresh to save resources
                this.stopAutoRefresh();
            }
        });
    }
    
    startAutoRefresh() {
        this.stopAutoRefresh(); // Clear any existing interval
        
        this.refreshInterval = setInterval(() => {
            if (this.isPageVisible || !this.config.performance.pauseWhenHidden) {
                this.updateNotificationBadge();
            }
        }, this.refreshIntervalTime);
    }
    
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }
    
    async updateNotificationBadge() {
        try {
            const response = await fetch(
                `${this.getBaseUrl()}ajax/get_notification_count.php?last_count=${this.lastKnownCount}&t=${Date.now()}`
            );
            
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const data = await response.json();
            
            if (data.success) {
                this.handleNotificationUpdate(data);
                this.retryCount = 0; // Reset retry count on success
            } else {
                throw new Error(data.message || 'Failed to get notification count');
            }
            
        } catch (error) {
            console.error('Error updating notification count:', error);
            this.handleUpdateError();
        }
    }
    
    handleNotificationUpdate(data) {
        const badge = document.getElementById('notificationBadge');
        const notificationIcon = document.querySelector('.notifications-icon');
        
        // Update badge count
        if (data.count > 0) {
            if (badge) {
                badge.textContent = data.count;
                
                // Add new notification animation if count increased
                if (data.has_new_notifications && this.config.badge.pulseOnNew) {
                    this.animateNewNotification(badge);
                }
            } else if (notificationIcon && !notificationIcon.querySelector('.notification-badge')) {
                // Create new badge
                const newBadge = document.createElement('span');
                newBadge.className = 'notification-badge';
                newBadge.id = 'notificationBadge';
                newBadge.textContent = data.count;
                notificationIcon.appendChild(newBadge);
                
                if (data.has_new_notifications) {
                    this.animateNewNotification(newBadge);
                }
            }
        } else {
            // Remove badge if count is 0
            if (badge) {
                badge.remove();
            }
        }
        
        // Show toast for new notifications
        if (data.has_new_notifications && data.latest_notification) {
            this.showNotificationToast(data.latest_notification);
        }
        
        // Update last known count
        this.lastKnownCount = data.count;
        
        // Trigger page-specific updates
        this.triggerPageSpecificUpdates(data);
    }
    
    animateNewNotification(badge) {
        badge.classList.remove('new-notification-bounce');
        // Force reflow
        badge.offsetHeight;
        badge.classList.add('new-notification-bounce');
        
        setTimeout(() => {
            badge.classList.remove('new-notification-bounce');
        }, this.config.badge.bounceDuration || 1000);
    }
    
    showNotificationToast(notification) {
        // Limit number of visible toasts
        if (this.activeToasts.size >= this.config.toast.maxVisible) {
            return;
        }
        
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.innerHTML = this.createToastHTML(notification);
        
        // Add to container
        this.toastContainer.appendChild(toast);
        this.activeToasts.add(toast);
        
        // Animate in
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Auto remove
        setTimeout(() => this.removeToast(toast), this.config.toast.duration);
        
        // Click to dismiss
        toast.addEventListener('click', () => this.removeToast(toast));
    }
    
    createToastHTML(notification) {
        const icon = this.getNotificationIcon(notification.type);
        
        return `
            <div class="toast-icon">${icon}</div>
            <div class="toast-content">
                <div class="toast-title">New Notification</div>
                <div class="toast-message">${this.escapeHtml(notification.message)}</div>
                <div class="toast-details">
                    ${notification.appointment_date} at ${notification.appointment_time}
                </div>
                <div class="toast-time">${notification.time_ago}</div>
            </div>
            <div class="toast-close">×</div>
        `;
    }
    
    getNotificationIcon(type) {
        const icons = {
            'appointment_request': '📅',
            'appointment_approved': '✅',
            'appointment_rejected': '❌',
            'appointment_cancelled': '⚠️'
        };
        return icons[type] || '🔔';
    }
    
    removeToast(toast) {
        toast.classList.add('fade-out');
        this.activeToasts.delete(toast);
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 500);
    }
    
    handleUpdateError() {
        this.retryCount++;
        
        if (this.retryCount < this.maxRetries) {
            // Retry after delay
            setTimeout(() => {
                this.updateNotificationBadge();
            }, this.config.performance?.retryDelay || 10000);
        } else {
            console.warn('Max retry attempts reached for notification updates');
            this.retryCount = 0; // Reset for future attempts
        }
    }
    
    updateTimeDisplays() {
        // Update elements with data-timestamp attribute
        document.querySelectorAll('[data-timestamp]').forEach(element => {
            const timestamp = parseInt(element.dataset.timestamp);
            if (timestamp && timestamp > 0) {
                const timeAgo = this.calculateTimeAgo(timestamp);
                
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
        document.querySelectorAll('.time-ago:not([data-timestamp])').forEach(element => {
            const timestampElement = element.parentNode.querySelector('.timestamp-data');
            if (timestampElement) {
                const timestamp = parseInt(timestampElement.textContent);
                if (timestamp && timestamp > 0) {
                    const timeAgo = this.calculateTimeAgo(timestamp);
                    element.textContent = timeAgo;
                }
            }
        });
    }
    
    calculateTimeAgo(timestamp) {
        const now = Math.floor(Date.now() / 1000);
        const diff = now - timestamp;
        
        if (diff < 0 || isNaN(diff)) return 'Just now';
        if (diff < 60) return 'Just now';
        if (diff < 3600) {
            const minutes = Math.floor(diff / 60);
            return minutes + ' minute' + (minutes > 1 ? 's' : '') + ' ago';
        }
        if (diff < 86400) {
            const hours = Math.floor(diff / 3600);
            return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
        }
        if (diff < 604800) {
            const days = Math.floor(diff / 86400);
            return days + ' day' + (days > 1 ? 's' : '') + ' ago';
        }
        if (diff < 2592000) {
            const weeks = Math.floor(diff / 604800);
            return weeks + ' week' + (weeks > 1 ? 's' : '') + ' ago';
        }
        if (diff < 31536000) {
            const months = Math.floor(diff / 2592000);
            return months + ' month' + (months > 1 ? 's' : '') + ' ago';
        }
        
        const years = Math.floor(diff / 31536000);
        return years + ' year' + (years > 1 ? 's' : '') + ' ago';
    }
    
    setupPageSpecificHandlers() {
        const currentPath = window.location.pathname;
        
        // Auto-refresh notification pages every 30 seconds
        if (currentPath.includes('/notifications.php')) {
            setInterval(() => {
                if (this.isPageVisible) {
                    this.checkForNewNotificationsOnPage();
                }
            }, 30000);
        }
        
        // Enhanced dashboard updates
        if (currentPath.includes('/dashboard.php')) {
            setInterval(() => {
                if (this.isPageVisible) {
                    this.updateDashboardData();
                }
            }, 60000);
        }
    }
    
    async checkForNewNotificationsOnPage() {
        const unreadCards = document.querySelectorAll('.notification-card.unread');
        if (unreadCards.length === 0) {
            try {
                const response = await fetch(`${this.getBaseUrl()}ajax/get_notification_count.php`);
                const data = await response.json();
                
                if (data.success && data.count > 0) {
                    // Reload page to show new notifications
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error checking for new notifications:', error);
            }
        }
    }
    
    async updateDashboardData() {
        // This could be expanded to update dashboard statistics
        // For now, just ensure notification count is current
        this.updateNotificationBadge();
    }
    
    triggerPageSpecificUpdates(data) {
        // Trigger custom events that pages can listen to
        const event = new CustomEvent('notificationsUpdated', {
            detail: {
                count: data.count,
                hasNew: data.has_new_notifications,
                activitySummary: data.activity_summary
            }
        });
        
        document.dispatchEvent(event);
    }
    
    setupGlobalFunctions() {
        // Maintain compatibility with existing code
        window.markNotificationAsRead = (notificationId, callback) => {
            fetch(`${this.getBaseUrl()}ajax/mark_notification_read.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'notification_id=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateNotificationBadge();
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
        
        window.markAllNotificationsAsRead = (callback) => {
            fetch(`${this.getBaseUrl()}ajax/mark_all_read.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateNotificationBadge();
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
        
        // Expose manager instance
        window.NotificationManager = this;
        window.updateTimeDisplays = () => this.updateTimeDisplays();
        window.calculateTimeAgo = (timestamp) => this.calculateTimeAgo(timestamp);
    }
    
    getBaseUrl() {
        const protocol = window.location.protocol;
        const host = window.location.host;
        const path = window.location.pathname;
        
        let basePath = '/';
        if (path.includes('/fcss/')) {
            basePath = '/fcss/';
        }
        
        return protocol + '//' + host + basePath;
    }
    
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    // Cleanup method
    destroy() {
        this.stopAutoRefresh();
        
        if (this.toastContainer && this.toastContainer.parentNode) {
            this.toastContainer.parentNode.removeChild(this.toastContainer);
        }
        
        this.activeToasts.clear();
    }
}

// Auto-cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.NotificationManager) {
        window.NotificationManager.destroy();
    }
});