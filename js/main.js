/**
 * CampusBite Client-Side JavaScript
 */
document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss alerts after 4.5s
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 4500);
    });
});
