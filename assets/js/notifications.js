/**
 * CAMS - Notification System
 * Handles elegant toast pop-ups for administrative actions.
 */

class NotificationManager {
    constructor() {
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
    }

    show(message, type = 'success', duration = 4000) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        // Define icons based on type
        const icons = {
            success: 'check-circle',
            error: 'alert-circle',
            info: 'info'
        };

        toast.innerHTML = `
            <i data-lucide="${icons[type] || 'info'}" size="20"></i>
            <span class="toast-message">${message}</span>
        `;

        this.container.appendChild(toast);
        
        // Render lucide icons for the new element
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Auto remove
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s ease-out forwards';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, duration);
    }
}

// Global instance
const notifier = new NotificationManager();

// Function for PHP to call
function showToast(message, type = 'success') {
    notifier.show(message, type);
}
