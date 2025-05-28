/**
 * Enhanced Dynamic Notification System JavaScript - FINAL FIXED VERSION
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
        
        // FIX: Track dismissed notifications permanently
        this.dismissedNotifications = new Set();
        this.shownNotifications = new Set();
        this.lastNotificationTimestamp = null;
        
        // FIX: Load dismissed notifications from localStorage
        this.loadDismissedNotifications();
        
        // Get configuration
        this.config = window.AutoRefreshConfig || this.getDefaultConfig();
        this.refreshIntervalTime = getCurrentRefreshInterval ? getCurrentRefreshInterval() : 120000;
        
        // Initialize last known count from badge
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            this.lastKnownCount = parseInt(badge.textContent) || 0;
        }
        
        // Store initial timestamp to prevent showing old notifications
        this.initializationTime = Date.now();
    }
    
    // FIX: Load dismissed notifications from localStorage
    loadDismissedNotifications() {
        try {
            const dismissed = localStorage.getItem('dismissedNotifications');
            if (dismissed) {
                this.dismissedNotifications = new Set(JSON.parse(dismissed));
            }
        } catch (e) {
            console.warn('Could not load dismissed notifications:', e);
            this.dismissedNotifications = new Set();
        }
    }
    
    // FIX: Save dismissed notifications to localStorage
    saveDismissedNotifications() {
        try {
            localStorage.setItem('dismissedNotifications', JSON.stringify([...this.dismissedNotifications]));
        } catch (e) {
            console.warn('Could not save dismissed notifications:', e);
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
        
        this.toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            pointer-events: none;
        `;
        
        document.body.appendChild(this.toastContainer);
    }
    
    setupVisibilityChangeHandler() {
        document.addEventListener('visibilitychange', () => {
            this.isPageVisible = !document.hidden;
            
            if (this.isPageVisible) {
                this.updateNotificationBadge();
                this.updateTimeDisplays();
                this.startAutoRefresh();
            } else if (this.config.performance.pauseWhenHidden) {
                this.stopAutoRefresh();
            }
        });
    }
    
    startAutoRefresh() {
        this.stopAutoRefresh();
        
        if (this.refreshIntervalTime > 0) {
            this.refreshInterval = setInterval(() => {
                if (this.isPageVisible || !this.config.performance.pauseWhenHidden) {
                    this.updateNotificationBadge();
                }
            }, this.refreshIntervalTime);
        }
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
                this.retryCount = 0;
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
                
                if (data.has_new_notifications && this.config.badge.pulseOnNew) {
                    this.animateNewNotification(badge);
                }
            } else if (notificationIcon && !notificationIcon.querySelector('.notification-badge')) {
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
            if (badge) {
                badge.remove();
            }
        }
        
        // FIX: Show toast only for truly new notifications that haven't been dismissed
        if (data.has_new_notifications && data.latest_notification && this.shouldShowToast(data.latest_notification)) {
            this.showNotificationToast(data.latest_notification);
        }
        
        this.lastKnownCount = data.count;
        this.triggerPageSpecificUpdates(data);
    }
    
    // FIX: Improved logic to determine if we should show a toast
    shouldShowToast(notification) {
        // Don't show if notification has been dismissed
        if (this.dismissedNotifications.has(notification.id)) {
            return false;
        }
        
        // Don't show if we've already shown this notification in this session
        if (this.shownNotifications.has(notification.id)) {
            return false;
        }
        
        // Don't show toasts for notifications created before page load
        const notificationTime = new Date(notification.created_at).getTime();
        if (notificationTime < this.initializationTime) {
            return false;
        }
        
        // Don't show if we're on the notifications page
        if (window.location.pathname.includes('notifications.php')) {
            return false;
        }
        
        return true;
    }
    
    animateNewNotification(badge) {
        badge.classList.remove('new-notification-bounce');
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
        
        // Mark this notification as shown
        this.shownNotifications.add(notification.id);
        
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.dataset.notificationId = notification.id; // FIX: Store notification ID
        
        toast.style.cssText = `
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 16px;
            margin-bottom: 12px;
            max-width: 350px;
            border-left: 4px solid #4e73df;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            pointer-events: auto;
            cursor: pointer;
        `;
        
        toast.innerHTML = this.createToastHTML(notification);
        
        // Add to container
        this.toastContainer.appendChild(toast);
        this.activeToasts.add(toast);
        
        // Animate in
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
            toast.classList.add('show');
        }, 100);
        
        // Auto remove after duration
        const autoRemoveTimeout = setTimeout(() => this.removeToast(toast, false), this.config.toast.duration);
        
        // FIX: Handle manual dismissal (clicking X)
        const closeBtn = toast.querySelector('.toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                clearTimeout(autoRemoveTimeout); // Cancel auto-removal
                this.removeToast(toast, true); // Manual dismissal
            });
        }
        
        // Click anywhere else on toast to dismiss
        toast.addEventListener('click', (e) => {
            if (!e.target.classList.contains('toast-close')) {
                clearTimeout(autoRemoveTimeout);
                this.removeToast(toast, true);
            }
        });
    }
    
    createToastHTML(notification) {
        const icon = this.getNotificationIcon(notification.type);
        
        return `
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <div style="font-size: 20px; flex-shrink: 0;">${icon}</div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 600; color: #2c3e50; margin-bottom: 4px;">New Notification</div>
                    <div style="color: #555; margin-bottom: 8px; word-wrap: break-word;">${this.escapeHtml(notification.message)}</div>
                    <div style="font-size: 12px; color: #888;">
                        ${notification.appointment_date} at ${notification.appointment_time}
                    </div>
                    <div style="font-size: 11px; color: #999; margin-top: 4px;">${notification.time_ago}</div>
                </div>
                <div class="toast-close" style="font-size: 16px; color: #ccc; cursor: pointer; flex-shrink: 0; padding: 4px;">×</div>
            </div>
        `;
    }
    
    getNotificationIcon(type) {
        const icons = {
            'appointment_request': '📅',
            'appointment_approved': '✅',
            'appointment_rejected': '❌',
            'appointment_cancelled': '⚠️',
            'appointment_missed': '⚠️',
            'appointment_completed': '✅'
        };
        return icons[type] || '🔔';
    }
    
    // FIX: Enhanced removeToast method with proper dismissal handling
    removeToast(toast, manuallyDismissed = false) {
        const notificationId = toast.dataset.notificationId;
        
        // If manually dismissed, permanently dismiss this notification
        if (manuallyDismissed && notificationId) {
            this.dismissedNotifications.add(parseInt(notificationId));
            this.saveDismissedNotifications();
        }
        
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
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
            setTimeout(() => {
                this.updateNotificationBadge();
            }, this.config.performance?.retryDelay || 10000);
        } else {
            console.warn('Max retry attempts reached for notification updates');
            this.retryCount = 0;
        }
    }
    
    updateTimeDisplays() {
        document.querySelectorAll('[data-timestamp]').forEach(element => {
            const timestamp = parseInt(element.dataset.timestamp);
            if (timestamp && timestamp > 0) {
                const timeAgo = this.calculateTimeAgo(timestamp);
                
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
        
        if (currentPath.includes('/notifications.php')) {
            setInterval(() => {
                if (this.isPageVisible) {
                    this.checkForNewNotificationsOnPage();
                }
            }, 30000);
        }
        
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
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error checking for new notifications:', error);
            }
        }
    }
    
    async updateDashboardData() {
        this.updateNotificationBadge();
    }
    
    triggerPageSpecificUpdates(data) {
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
        this.shownNotifications.clear();
        this.saveDismissedNotifications();
    }
}

// Auto-cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.NotificationManager) {
        window.NotificationManager.destroy();
    }
});

// Add required CSS for notification badge animation
const style = document.createElement('style');
style.textContent = `
    .notification-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #dc3545;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 12px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: pulse 2s infinite;
        z-index: 10;
    }
    
    .notification-badge.new-notification-bounce {
        animation: bounce 0.6s ease-in-out;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    @keyframes bounce {
        0%, 20%, 60%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
        80% { transform: translateY(-5px); }
    }
    
    .notification-toast.show {
        opacity: 1 !important;
        transform: translateX(0) !important;
    }
    
    .notification-toast.fade-out {
        opacity: 0 !important;
        transform: translateX(100%) !important;
    }
    
    .toast-close:hover {
        color: #999 !important;
        background-color: rgba(0,0,0,0.1);
        border-radius: 50%;
    }
`;
document.head.appendChild(style);