/**
 * CampusBite Client-Side JavaScript
 */
document.addEventListener('DOMContentLoaded', () => {
    // On phones the nav row scrolls sideways; bring the current page's link into view
    const activeNav = document.querySelector('.cb-nav a.is-active');
    if (activeNav && activeNav.parentElement.scrollWidth > activeNav.parentElement.clientWidth) {
        activeNav.scrollIntoView({ block: 'nearest', inline: 'center' });
    }

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

    // A link to a form or conversation further down (#payout, #message-12): the browser jumps there before the page has finished
    // laying out, so jump again once it has loaded, and put the cursor in the first field
    const jumpTarget = location.hash.length > 1 ? document.getElementById(location.hash.slice(1)) : null;
    if (jumpTarget && jumpTarget.querySelector('form')) {
        window.addEventListener('load', () => {
            jumpTarget.scrollIntoView({ block: 'start' });
            if (jumpTarget.tagName !== 'SECTION') return;
            const field = jumpTarget.querySelector('input:not([type=hidden]), select');
            if (field) field.focus({ preventScroll: true });
        });
    }

    // Opening a "Reply" puts the cursor in its text box
    document.querySelectorAll('details.cb-reply-form').forEach(details => {
        details.addEventListener('toggle', () => {
            if (details.open) details.querySelector('textarea').focus();
        });
    });

    // Wide tables scroll sideways on small screens: shade the edge that has more columns behind it
    document.querySelectorAll('.cb-table-wrap').forEach(wrap => {
        const update = () => {
            const max = wrap.scrollWidth - wrap.clientWidth;
            wrap.classList.toggle('has-more-left', max > 1 && wrap.scrollLeft > 1);
            wrap.classList.toggle('has-more-right', max > 1 && wrap.scrollLeft < max - 1);
        };
        wrap.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
    });

    // Kitchens: check for new orders every 20 seconds while a kitchen page is open. A new order updates the Orders
    // badge and the tab title, plays a short chime and shows a message; the Orders page reloads itself unless
    // someone is filling in a form there.
    const orderWatch = document.querySelector('[data-order-watch]');
    if (orderWatch) {
        const badge = orderWatch.querySelector('[data-new-count]');
        const live = document.getElementById('cb-order-live');
        const baseTitle = document.title.replace(/^\(\d+\) /, '');
        let latest = Number(orderWatch.dataset.latest) || 0;
        let placedBefore = badge && !badge.hidden ? Number(badge.firstChild.nodeValue) : 0;
        let audio = null;

        // Browsers only allow sound after the person has used the page, so get the audio ready on the first click or key
        const unlockAudio = () => {
            try {
                audio = audio || new (window.AudioContext || window.webkitAudioContext)();
                if (audio.state === 'suspended') audio.resume();
            } catch (e) { audio = null; }
        };
        document.addEventListener('pointerdown', unlockAudio, { once: true });
        document.addEventListener('keydown', unlockAudio, { once: true });

        const chime = () => {
            if (!audio || audio.state !== 'running') return;
            [880, 1175].forEach((freq, i) => {
                const start = audio.currentTime + i * 0.18;
                const osc = audio.createOscillator();
                const gain = audio.createGain();
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.0001, start);
                gain.gain.exponentialRampToValueAtTime(0.25, start + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.35);
                osc.connect(gain).connect(audio.destination);
                osc.start(start);
                osc.stop(start + 0.4);
            });
        };

        const showCount = (placed) => {
            if (badge) {
                badge.hidden = placed < 1;
                badge.firstChild.nodeValue = placed;
            }
            document.title = placed > 0 ? '(' + placed + ') ' + baseTitle : baseTitle;
        };

        const onOrdersPage = /kitchen_orders\.php$/.test(location.pathname) && !/[?&]view=(all|done)/.test(location.search);
        const busy = () => {
            const field = document.activeElement;
            return (field && field.matches('input, select, textarea')) || document.querySelector('details[open]');
        };

        const announce = (placed, fresh) => {
            const text = fresh === 1 ? 'New order just came in.' : fresh + ' new orders just came in.';
            if (live) live.textContent = text + ' ' + placed + ' waiting to start.';
            if (onOrdersPage && !busy()) {
                location.reload();
                return;
            }
            let toast = document.getElementById('cb-new-order-toast');
            if (!toast) {
                const toasts = document.querySelector('.cb-toasts');
                if (!toasts) return;
                toast = document.createElement('div');
                toast.id = 'cb-new-order-toast';
                toast.className = 'cb-toast cb-toast-error cb-toast-order';
                toast.innerHTML = '<i class="bi bi-bell-fill" aria-hidden="true"></i><p></p>'
                    + '<button type="button" aria-label="Dismiss message"><i class="bi bi-x-lg" aria-hidden="true"></i></button>';
                toast.querySelector('button').addEventListener('click', () => toast.remove());
                toasts.appendChild(toast);
            }
            const p = toast.querySelector('p');
            p.textContent = text + ' ';
            const link = document.createElement('a');
            link.href = orderWatch.href;
            link.textContent = onOrdersPage ? 'Show it' : 'Open orders';
            p.appendChild(link);
        };

        const check = () => {
            if (document.hidden) return;
            fetch(orderWatch.dataset.orderWatch, { credentials: 'same-origin', cache: 'no-store' })
                .then(response => (response.ok ? response.json() : null))
                .then(state => {
                    if (!state) return;
                    showCount(state.placed);
                    // Order numbers are shared by all kitchens, so count the new ones from the waiting orders instead
                    if (state.latest > latest) {
                        const fresh = Math.max(1, state.placed - placedBefore);
                        latest = state.latest;
                        chime();
                        announce(state.placed, fresh);
                    }
                    placedBefore = state.placed;
                })
                .catch(() => {});
        };

        showCount(placedBefore);
        setInterval(check, 20000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) check(); });
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
