/**
 * CampusBite Client-Side JavaScript
 */
document.addEventListener('DOMContentLoaded', () => {
    // Success messages clear themselves; errors stay until dismissed
    document.querySelectorAll('.cb-toast-success').forEach(toast => {
        setTimeout(() => toast.remove(), 4500);
    });

    // Close the order panel; it stays closed until the cart changes
    const panelClose = document.getElementById('cb-panel-close');
    if (panelClose) {
        panelClose.addEventListener('click', () => {
            const app = document.getElementById('cb-app');
            app.classList.remove('has-panel');
            try { localStorage.setItem('cbPanelClosed', app.dataset.panelSignature); } catch (e) {}
        });
    }

    // Quantity steppers on the product page: the − and + buttons nudge the number input within its min and max
    document.querySelectorAll('[data-stepper]').forEach(stepper => {
        const input = stepper.querySelector('input');
        stepper.querySelectorAll('[data-step]').forEach(button => {
            button.addEventListener('click', () => {
                const min = Number(input.min) || 1;
                const max = Number(input.max) || 99;
                const next = (Number(input.value) || min) + Number(button.dataset.step);
                input.value = Math.min(max, Math.max(min, next));
            });
        });
    });

    // Ask before a destructive form submits
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
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
