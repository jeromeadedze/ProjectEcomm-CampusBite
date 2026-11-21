/**
 * CampusBite Client-Side JavaScript
 */
document.addEventListener('DOMContentLoaded', () => {
    // Success messages clear themselves; errors stay until dismissed
    document.querySelectorAll('.cb-toast-success').forEach(toast => {
        setTimeout(() => toast.remove(), 4500);
    });

    // Escape closes the newest message
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const toasts = document.querySelectorAll('.cb-toast');
        if (toasts.length) toasts[toasts.length - 1].remove();
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
        // The "price | Add to order" button in the same form shows the total for the chosen quantity
        const form = stepper.closest('form');
        const cta = form ? form.querySelector('[data-unit-price]') : null;
        const showTotal = () => {
            if (!cta) return;
            const total = Number(cta.dataset.unitPrice) * (Number(input.value) || 1);
            cta.querySelector('[data-cta-price]').textContent = 'GH₵ ' + total.toFixed(2);
        };
        stepper.querySelectorAll('[data-step]').forEach(button => {
            button.addEventListener('click', () => {
                const min = Number(input.min) || 1;
                const max = Number(input.max) || 99;
                const next = (Number(input.value) || min) + Number(button.dataset.step);
                input.value = Math.min(max, Math.max(min, next));
                showTotal();
            });
        });
        input.addEventListener('input', showTotal);
    });

    // A list of suggestions that fills a text field (checkout's popular drop points)
    document.querySelectorAll('select[data-fill]').forEach(select => {
        const target = document.getElementById(select.dataset.fill);
        if (!target) return;
        select.addEventListener('change', () => {
            if (select.value) target.value = select.value;
        });
    });

    // Arriving from a "Reject" or "Cancel" link (#cancel) opens the folded cancel form
    if (location.hash === '#cancel') {
        const cancel = document.getElementById('cancel');
        if (cancel && cancel.tagName === 'DETAILS') {
            cancel.open = true;
            cancel.scrollIntoView({ block: 'center' });
        }
    }

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
