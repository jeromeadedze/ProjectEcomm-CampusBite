/**
 * Kitchen login forms: shows the sign-in email (kitchen.firstname@campusbite.com) and the starting-password pattern
 * as the name is typed. The server makes the real ones (adding a number if the email is taken, and two random digits
 * to the password), using the same rules as CustomerController::kitchenSlug() / firstNameSlug().
 */
(function () {
    var FILLER = ['cafeteria', 'kitchen', 'kitchens', 'grill', 'grills', 'spot', 'lounge', 'berekuso', 'restaurant', 'canteen', 'cafe', 'the', 'and', 'of'];
    function kitchenSlug(name) {
        var words = name.toLowerCase().split(/[^a-z0-9]+/).filter(Boolean);
        var kept = words.filter(function (w) { return FILLER.indexOf(w) === -1; });
        return ((kept.length ? kept : words).join('') || 'kitchen').slice(0, 20);
    }
    function firstSlug(full) {
        return ((full.trim().split(/\s+/)[0] || '').toLowerCase().replace(/[^a-z0-9]/g, '') || 'staff').slice(0, 20);
    }
    function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

    document.querySelectorAll('[data-login-preview]').forEach(function (box) {
        var form = box.closest('form');
        var nameIn = form.querySelector('[name=customer_name]');
        var kitchenSel = form.querySelector('select[name=brand_id]');
        var auto = box.querySelector('[data-auto]'), custom = box.querySelector('[data-custom-email]');
        var emailInput = custom.querySelector('input'), toggle = box.querySelector('[data-email-toggle]');
        var emailOut = box.querySelector('[data-email]'), passOut = box.querySelector('[data-pass]');

        function kitchenName() {
            if (!kitchenSel) return box.dataset.kitchen || '';
            return kitchenSel.value ? kitchenSel.options[kitchenSel.selectedIndex].textContent.trim() : '';
        }
        function update() {
            var k = kitchenName(), n = nameIn.value.trim();
            if (!k || !n) {
                emailOut.textContent = (k ? kitchenSlug(k) : 'kitchen') + '.firstname@campusbite.com';
                passOut.textContent = (k ? cap(kitchenSlug(k)) : 'Kitchen') + '@Firstname + 2 digits';
                return;
            }
            emailOut.textContent = kitchenSlug(k) + '.' + firstSlug(n) + '@campusbite.com';
            passOut.textContent = cap(kitchenSlug(k)) + '@' + cap(firstSlug(n)) + ' + 2 digits';
        }
        function useCustom(on) {
            custom.hidden = !on;
            auto.hidden = on;
            emailInput.required = on;
            if (!on) emailInput.value = '';
            toggle.textContent = on ? 'Use the automatic email' : 'Use a different email';
        }
        nameIn.addEventListener('input', update);
        if (kitchenSel) kitchenSel.addEventListener('change', update);
        toggle.addEventListener('click', function () { useCustom(custom.hidden); if (!custom.hidden) emailInput.focus(); });
        useCustom(emailInput.value.trim() !== '');
        update();
    });
})();
