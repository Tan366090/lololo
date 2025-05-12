class NotificationService {
    show(type, message) {
        Swal.fire({
            icon: type,
            text: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }

    showSuccess(message) {
        this.show('success', message);
    }

    showError(message) {
        this.show('error', message);
    }

    showWarning(message) {
        this.show('warning', message);
    }

    showInfo(message) {
        this.show('info', message);
    }
} 