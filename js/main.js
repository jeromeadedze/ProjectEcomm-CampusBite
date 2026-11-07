/**
 * CampusBite Client-Side JavaScript
 */
document.addEventListener('DOMContentLoaded', () => {
    // Success messages clear themselves; errors stay until dismissed
    document.querySelectorAll('.cb-toast-success').forEach(toast => {
        setTimeout(() => toast.remove(), 4500);
    });

    // Auto-dismiss alerts after 4.5s
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 4500);
    });
});
