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

    // Close the order panel; it stays closed until the cart changes (delegated: the panel is redrawn after each change)
    document.addEventListener('click', (event) => {
        if (!event.target.closest('#cb-panel-close')) return;
        const app = document.getElementById('cb-app');
        app.classList.remove('has-panel');
        try { localStorage.setItem('cbPanelClosed', app.dataset.panelSignature); } catch (e) {}
    });

    // ── Basket without page reloads ──────────────────────────────────────────────────────────────────────
    // Meal cards: + adds one and turns into the meal's count; tapping the count opens − n +, which folds back
    // after a few seconds. The order panel's − / + buttons work the same way. Each change goes to
    // actions/cart_api.php, which answers with the whole basket, so every counter, the order panel (wide screens)
    // and the basket bar (phones) stay in step. If the request fails, the forms still work as plain POSTs.
    const shopApp = document.getElementById('cb-app');
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (shopApp && csrfMeta) {
        const apiUrl = (shopApp.dataset.root || '..') + '/actions/cart_api.php';
        const shopPage = shopApp.dataset.shopPage === '1';
        let queue = Promise.resolve();
        const foldTimers = new WeakMap();

        const toast = (message) => {
            let box = document.querySelector('.cb-toasts');
            if (!box) { box = document.createElement('div'); box.className = 'cb-toasts'; shopApp.prepend(box); }
            const el = document.createElement('div');
            el.className = 'cb-toast cb-toast-error';
            el.setAttribute('role', 'alert');
            el.innerHTML = '<i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i><p></p>'
                + '<button type="button" aria-label="Dismiss message"><i class="bi bi-x-lg" aria-hidden="true"></i></button>';
            el.querySelector('p').textContent = message;
            el.querySelector('button').addEventListener('click', () => el.remove());
            box.appendChild(el);
            setTimeout(() => el.remove(), 6000);
        };

        // Draws one meal card's control for a quantity; open = show − n +
        const drawStep = (step, qty, open) => {
            const max = parseInt(step.dataset.max, 10) || 20;
            const title = step.dataset.title || 'this meal';
            step.dataset.qty = qty;
            const isOpen = qty > 0 && open;
            step.querySelector('[data-step-add]').hidden = qty > 0;
            const count = step.querySelector('[data-step-count]');
            count.hidden = qty === 0 || isOpen;
            count.textContent = qty;
            count.setAttribute('aria-label', qty + ' ' + title + ' in your basket. Change how many');
            step.querySelector('[data-step-open]').hidden = !isOpen;
            step.querySelector('[data-step-num]').textContent = qty;
            step.querySelector('[data-step-plus]').disabled = qty >= max;
            step.querySelector('[data-step-minus]').setAttribute('aria-label', (qty <= 1 ? 'Remove ' : 'One less ') + title);
        };

        // Fold an open counter back to its number a few seconds after the last tap
        const foldLater = (step) => {
            clearTimeout(foldTimers.get(step));
            foldTimers.set(step, setTimeout(() => {
                const hadFocus = step.contains(document.activeElement);
                drawStep(step, parseInt(step.dataset.qty, 10) || 0, false);
                if (hadFocus) (step.querySelector('[data-step-count]:not([hidden])') || step.querySelector('[data-step-add]')).focus();
            }, 3500));
        };

        // Applies the basket the server sent back
        const applyBasket = (state) => {
            document.querySelectorAll('[data-step]').forEach(step => {
                const qty = state.items[step.dataset.product] || 0;
                const open = !step.querySelector('[data-step-open]').hidden;
                drawStep(step, qty, open);
            });
            const bar = document.querySelector('[data-basket]');
            if (bar) {
                const countEl = bar.querySelector('[data-basket-count]');
                const changed = countEl.textContent !== String(state.count);
                bar.hidden = state.count === 0;
                countEl.textContent = state.count;
                bar.querySelector('[data-basket-total]').textContent = state.subtotal_text;
                bar.setAttribute('aria-label', 'View basket, ' + state.count + (state.count === 1 ? ' meal, ' : ' meals, ') + state.subtotal_text);
                if (changed) { countEl.classList.remove('is-bump'); void countEl.offsetWidth; countEl.classList.add('is-bump'); }
            }
            shopApp.classList.toggle('has-basket', !!bar && state.count > 0);
            const oldPanel = document.getElementById('cb-panel');
            if (state.panel) {
                const holder = document.createElement('div');
                holder.innerHTML = state.panel.trim();
                const fresh = holder.firstElementChild;
                if (oldPanel) oldPanel.replaceWith(fresh);
                else { const body = document.querySelector('.cb-body'); if (body) body.appendChild(fresh); }
            } else if (oldPanel) {
                oldPanel.remove();
            }
            // A change to the basket brings a closed panel back, as a page reload would
            shopApp.classList.toggle('has-panel', shopPage && state.count > 0);
            const cartBadge = document.querySelector('[data-cart-count]');
            if (cartBadge) { cartBadge.textContent = state.count; cartBadge.hidden = state.count === 0; }
            const cartPill = document.querySelector('[data-cart-pill]');
            if (cartPill) cartPill.setAttribute('aria-label', 'Cart, ' + state.count + ' items');
        };

        // Sets one meal's quantity in the basket. Requests run one at a time, in the order they were made.
        const setQty = (productId, qty, replace) => {
            queue = queue.then(() => {
                const body = new FormData();
                body.append('csrf_token', csrfMeta.content);
                body.append('product_id', productId);
                body.append('qty', qty);
                body.append('root', shopApp.dataset.root || '..');
                if (replace) body.append('replace', '1');
                return fetch(apiUrl, { method: 'POST', body, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        // Every answer carries the basket as it really is, so a refused change is undone on screen
                        if (data.items) applyBasket(data);
                        if (data.conflict) askNewBasket(data.conflict, productId, qty);
                        else if (!data.ok) toast(data.error || "That didn't work. Try again.");
                    })
                    .catch(() => { toast("Couldn't reach CampusBite. Check your connection and try again."); });
            });
            return queue;
        };
        // "Start a new basket?" when a meal from another kitchen is added (same sheet as views/layout/flash.php)
        const askNewBasket = (conflict, productId, qty) => {
            document.querySelectorAll('[data-sheet]').forEach(s => s.remove());
            const sheet = document.createElement('div');
            sheet.className = 'cb-sheet';
            sheet.setAttribute('data-sheet', '');
            sheet.innerHTML = '<div class="cb-sheet-backdrop" data-sheet-close></div>'
                + '<div class="cb-sheet-card" role="dialog" aria-modal="true" aria-labelledby="cb-sheet-title">'
                + '<span class="cb-sheet-icon" aria-hidden="true"><i class="bi bi-shop"></i></span>'
                + '<h2 id="cb-sheet-title">Start a new basket?</h2><p></p>'
                + '<button type="button" class="cb-checkout" data-sheet-replace>Start new basket</button>'
                + '<button type="button" class="cb-sheet-keep" data-sheet-close></button></div>';
            const p = sheet.querySelector('p');
            p.append('Your basket has meals from ');
            const strong = document.createElement('strong'); strong.textContent = conflict.bill_kitchen; p.append(strong);
            p.append('. Each order comes from one kitchen, so adding ' + conflict.meal + ' from ' + conflict.new_kitchen + ' empties it first.');
            sheet.querySelector('.cb-sheet-keep').textContent = 'Keep my ' + conflict.bill_kitchen + ' basket';
            sheet.querySelector('[data-sheet-replace]').addEventListener('click', () => {
                sheet.remove();
                setQty(productId, qty, true);
            });
            shopApp.appendChild(sheet); // inside #cb-app, where the colour tokens are defined
            sheet.querySelector('[data-sheet-replace]').focus();
        };

        // Clicking the backdrop or "Keep" closes the sheet (server-drawn or ours); so does Escape
        document.addEventListener('click', (event) => {
            const closer = event.target.closest('[data-sheet-close]');
            if (closer) closer.closest('[data-sheet]').remove();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            const sheet = document.querySelector('[data-sheet]');
            if (sheet) sheet.remove();
        });
        const serverSheet = document.querySelector('[data-sheet] .cb-checkout');
        if (serverSheet) serverSheet.focus();

        // Phones: scrolling down shrinks the basket bar to a round bag on the right; scrolling up (or reaching the top) brings it back
        const basketBar = document.querySelector('[data-basket]');
        if (basketBar) {
            let lastY = window.scrollY;
            window.addEventListener('scroll', () => {
                const y = window.scrollY;
                if (Math.abs(y - lastY) < 8) return; // ignore tiny wobbles
                basketBar.classList.toggle('is-mini', y > 80 && y > lastY);
                lastY = y;
            }, { passive: true });
        }

        // Meal cards
        document.querySelectorAll('[data-step]').forEach(step => {
            const current = () => parseInt(step.dataset.qty, 10) || 0;
            const max = parseInt(step.dataset.max, 10) || 20;
            step.addEventListener('submit', (event) => {
                event.preventDefault();
                const submitter = event.submitter;
                if (submitter && submitter.hasAttribute('data-step-count')) {
                    // The count opens − n +
                    drawStep(step, current(), true);
                    step.querySelector('[data-step-plus]').focus();
                    foldLater(step);
                    return;
                }
                // + : add the first one, shown straight away
                drawStep(step, 1, false);
                setQty(step.dataset.product, 1);
            });
            step.querySelector('[data-step-plus]').addEventListener('click', () => {
                const next = Math.min(max, current() + 1);
                drawStep(step, next, true);
                foldLater(step);
                setQty(step.dataset.product, next);
            });
            step.querySelector('[data-step-minus]').addEventListener('click', () => {
                const next = Math.max(0, current() - 1);
                drawStep(step, next, next > 0);
                if (next > 0) foldLater(step);
                else step.querySelector('[data-step-add]').focus();
                setQty(step.dataset.product, next);
            });
        });

        // The order panel's − / + buttons (the panel is redrawn after each change, so listen on the page)
        document.addEventListener('submit', (event) => {
            const form = event.target.closest('#cb-panel form.cb-qty');
            if (!form || !event.submitter) return;
            event.preventDefault();
            const pid = form.querySelector('input[name="p_id"]').value;
            setQty(pid, parseInt(event.submitter.value, 10) || 0);
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
