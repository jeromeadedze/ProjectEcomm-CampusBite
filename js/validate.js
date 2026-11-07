/**
 * Sign-up form checks. actions/register_action.php repeats these rules on the server.
 */
(function () {
    const form = document.getElementById('registerForm');
    if (!form) return;

    const rules = [
        {
            id: 'customer_name',
            errorId: 'name_error',
            valid: (value) => value.trim().length >= 2,
            message: 'Enter your full name.',
        },
        {
            id: 'customer_email',
            errorId: 'email_error',
            valid: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim()),
            message: 'Enter a valid email address.',
        },
        {
            id: 'customer_pass',
            errorId: 'pass_error',
            valid: (value) => /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(value),
            message: "This password doesn't meet the rules above.",
        },
        {
            id: 'customer_contact',
            errorId: 'contact_error',
            valid: (value) => /^[0-9+\-\s]{7,15}$/.test(value.trim()),
            message: 'Enter a valid phone number, such as 0241234567.',
        },
    ];

    function setError(rule, input, message) {
        document.getElementById(rule.errorId).textContent = message;
        if (message) {
            input.setAttribute('aria-invalid', 'true');
        } else {
            input.removeAttribute('aria-invalid');
        }
    }

    rules.forEach((rule) => {
        const input = document.getElementById(rule.id);
        input.addEventListener('input', () => setError(rule, input, ''));
    });

    form.addEventListener('submit', (event) => {
        let firstInvalid = null;

        rules.forEach((rule) => {
            const input = document.getElementById(rule.id);
            const isValid = rule.valid(input.value);
            setError(rule, input, isValid ? '' : rule.message);
            if (!isValid && !firstInvalid) firstInvalid = input;
        });

        if (firstInvalid) {
            event.preventDefault();
            firstInvalid.focus();
        }
    });
})();
