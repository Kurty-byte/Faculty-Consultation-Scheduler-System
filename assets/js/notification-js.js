/**
 * File: assets/js/notifications.js
 * Description: JavaScript for handling notifications
 */

document.addEventListener('DOMContentLoaded', function() {
    const notificationsToggle = document.getElementById('notificationsToggle');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    
    if (!notificationsToggle || !notificationsDropdown) {
        return;
    }
    
    // Toggle dropdown when clicking the notification icon
    notificationsToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationsDropdown.classList.toggle('show');
        
        // If dropdown is shown, check for new notifications
        if (notificationsDropdown.classList.contains('show')) {
            checkForNewNotifications();
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (notificationsDropdown.classList.contains('show') && 
            !notificationsDropdown.contains(e.target) && 
            e.target !== notificationsToggle) {
            notificationsDropdown.classList.remove('show');
        }
    });
    
    // Prevent dropdown from closing when clicking inside it
    notificationsDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Mark notification as read when clicked
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(function(item) {
        item.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-id');
            markAsRead(notificationId);
            
            // Remove unread styling immediately
            this.classList.remove('unread');
        });
    });
    
    // Function to mark notification as read
    function markAsRead(notificationId) {
        fetch('../../ajax/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'notification_id=' + notificationId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update unread count in the UI
                updateNotificationBadge();
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    }
    
    // Function to update notification badge
    function updateNotificationBadge() {
        fetch('../../ajax/get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.notification-badge');
            const countSpan = document.querySelector('.notifications-count');
            
            if (data.count > 0) {
                // Update or create badge
                if (badge) {
                    badge.textContent = data.count;
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'notification-badge';
                    newBadge.textContent = data.count;
                    notificationsToggle.appendChild(newBadge);
                }
                
                // Update count in header if exists
                if (countSpan) {
                    countSpan.textContent = data.count + ' new';
                }
            } else {
                // Remove badge if exists
                if (badge) {
                    badge.remove();
                }
                
                // Update count in header if exists
                if (countSpan) {
                    countSpan.textContent = '';
                }
            }
        })
        .catch(error => {
            console.error('Error updating notification count:', error);
        });
    }
    
    // Function to check for new notifications
    function checkForNewNotifications() {
        fetch('../../ajax/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.notifications) {
                updateNotificationsList(data.notifications);
            }
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
        });
    }
    
    // Function to update the notifications list
    function updateNotificationsList(notifications) {
        const notificationsList = document.querySelector('.notifications-list');
        
        if (!notificationsList) {
            return;
        }
        
        if (notifications.length === 0) {
            notificationsList.innerHTML = `
                <div class="notification-empty">
                    <p>No notifications yet</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        
        notifications.forEach(notification => {
            let iconClass = '';
            
            switch(notification.type) {
                case 'appointment_request':
                    iconClass = 'icon-calendar-plus';
                    break;
                case 'appointment_approved':
                    iconClass = 'icon-check-circle';
                    break;
                case 'appointment_rejected':
                    iconClass = 'icon-times-circle';
                    break;
            }
            
            html += `
                <a href="${notification.link}" 
                   class="notification-item ${!notification.is_read ? 'unread' : ''}"
                   data-id="${notification.id}">
                    <div class="notification-icon">
                        <i class="${iconClass}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${escapeHtml(notification.message)}</div>
                        ${notification.details ? `<div class="notification-details">${escapeHtml(notification.details.substring(0, 50))}${notification.details.length > 50 ? '...' : ''}</div>` : ''}
                        <div class="notification-time">${notification.time_ago}</div>
                    </div>
                </a>
            `;
        });
        
        notificationsList.innerHTML = html;
        
        // Re-attach click events
        const newNotificationItems = notificationsList.querySelectorAll('.notification-item');
        newNotificationItems.forEach(function(item) {
            item.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-id');
                markAsRead(notificationId);
                this.classList.remove('unread');
            });
        });
    }
    
    // Helper function to escape HTML
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    // Check for new notifications periodically (every 60 seconds)
    setInterval(function() {
        updateNotificationBadge();
    }, 60000);
    
    // Initialize browser notifications
    initBrowserNotifications();
    
    // Function to initialize browser notifications
    function initBrowserNotifications() {
        // Check if the browser supports notifications
        if (!("Notification" in window)) {
            console.log("This browser does not support desktop notification");
            return;
        }
        
        // Request permission for notifications
        if (Notification.permission !== "granted" && Notification.permission !== "denied") {
            // Create a button to request permission
            const permissionBtn = document.createElement('div');
            permissionBtn.className = 'notification-permission-request';
            permissionBtn.innerHTML = `
                <p>Enable push notifications for appointment updates?</p>
                <button class="btn btn-sm btn-primary">Enable Notifications</button>
                <button class="btn btn-sm btn-secondary">No Thanks</button>
            `;
            
            document.body.appendChild(permissionBtn);
            
            const enableBtn = permissionBtn.querySelector('.btn-primary');
            const declineBtn = permissionBtn.querySelector('.btn-secondary');
            
            enableBtn.addEventListener('click', function() {
                Notification.requestPermission().then(function(permission) {
                    if (permission === "granted") {
                        permissionBtn.remove();
                        startNotificationPolling();
                    }
                });
            });
            
            declineBtn.addEventListener('click', function() {
                permissionBtn.remove();
            });
        } else if (Notification.permission === "granted") {
            startNotificationPolling();
        }
    }
    
    // Poll for new notifications that need push notification
    function startNotificationPolling() {
        let lastNotificationTime = localStorage.getItem('lastNotificationTime') || 0;
        
        // Check every 2 minutes
        setInterval(function() {
            fetch('../../ajax/get_new_notifications.php?since=' + lastNotificationTime)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.notifications && data.notifications.length > 0) {
                    // Update last notification time
                    lastNotificationTime = data.current_time;
                    localStorage.setItem('lastNotificationTime', lastNotificationTime);
                    
                    // Send browser notifications for each new notification
                    data.notifications.forEach(notification => {
                        sendBrowserNotification(notification);
                    });
                    
                    // Update UI as well
                    updateNotificationBadge();
                    
                    // If dropdown is open, update the list
                    if (notificationsDropdown.classList.contains('show')) {
                        checkForNewNotifications();
                    }
                }
            })
            .catch(error => {
                console.error('Error checking for new notifications:', error);
            });
        }, 120000); // 2 minutes
    }
    
    // Function to send browser notification
    function sendBrowserNotification(notification) {
        let title = '';
        let icon = '';
        
        switch(notification.type) {
            case 'appointment_request':
                title = 'New Appointment Request';
                icon = '../../assets/images/calendar-plus.png';
                break;
            case 'appointment_approved':
                title = 'Appointment Approved';
                icon = '../../assets/images/check-circle.png';
                break;
            case 'appointment_rejected':
                title = 'Appointment Rejected';
                icon = '../../assets/images/times-circle.png';
                break;
        }
        
        const options = {
            body: notification.message,
            icon: icon,
            tag: 'notification-' + notification.id,
            data: {
                url: notification.link
            }
        };
        
        const notification_obj = new Notification(title, options);
        
        notification_obj.onclick = function() {
            window.open(this.data.url, '_blank');
            this.close();
        };
    }
});
