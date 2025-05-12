// Notification System
const notificationSystem = {
    init() {
        this.container = document.getElementById('notificationSystem');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'notificationSystem';
            document.body.appendChild(this.container);
        }
    },

    show(message, type = 'info', duration = 3000) {
        if (!this.container) this.init();

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${this.getIcon(type)}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close">&times;</button>
        `;

        this.container.appendChild(notification);

        // Add close button functionality
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            notification.remove();
        });

        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                notification.remove();
            }, duration);
        }

        return notification;
    },

    success(message, duration = 3000) {
        return this.show(message, 'success', duration);
    },

    error(message, duration = 3000) {
        return this.show(message, 'error', duration);
    },

    warning(message, duration = 3000) {
        return this.show(message, 'warning', duration);
    },

    info(message, duration = 3000) {
        return this.show(message, 'info', duration);
    },

    getIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
};

// Initialize notification system when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    notificationSystem.init();
});

// Export for use in other files
window.notificationSystem = notificationSystem; 