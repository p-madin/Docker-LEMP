document.addEventListener('DOMContentLoaded', () => {
    // Extracted validation logic for a single input
    function validateInput(input, form) {
        // Skip buttons, fieldsets, hidden inputs
        if (['submit', 'button', 'fieldset', 'hidden'].includes(input.type)) return true;

        let isValid = true;
        let message = '';

        if (!input.checkValidity()) {
            isValid = false;
            message = input.validationMessage;
            if (input.validity.valueMissing) {
                message = `The ${input.name} field is required.`;
            }
        }

        // Custom Match validation (e.g. confirm_password)
        if (input.name === 'confirm_password') {
            const passwordInput = form.querySelector('[name="password"]');
            if (passwordInput && input.value !== passwordInput.value) {
                isValid = false;
                message = "The confirm_password must match password.";
            }
        }

        const flexCell = input.closest('.flex-cell');
        const flexRow = input.closest('.flex-row');

        if (flexCell && flexRow) {
            // 1. Clear previous errors for this specific input
            const existingErrors = flexCell.querySelectorAll('.validation-error');
            existingErrors.forEach(err => err.remove());
            flexRow.classList.remove('client-error');
            flexRow.classList.remove('satisfied');

            // 2. Add feedback based on status
            if (!isValid) {
                flexRow.classList.add('client-error');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'validation-error client-validation-error';
                errorDiv.textContent = message;
                flexCell.appendChild(errorDiv);
            } else if (input.value.length > 0) {
                // Only show satisfied (green) if there's actually a value
                flexRow.classList.add('satisfied');
            }
        }

        return isValid;
    }

    // Attach real-time validation to all inputs using event delegation
    document.addEventListener('input', (e) => {
        const disableValidationToggle = document.getElementById('disable_client_validation');
        if (disableValidationToggle && disableValidationToggle.checked) return;

        const form = e.target.closest('form[novalidate]');
        if (form && ['INPUT', 'SELECT', 'TEXTAREA'].includes(e.target.tagName)) {
            validateInput(e.target, form);

            // Special case: if password changes, proactively revalidate confirm_password
            if (e.target.name === 'password') {
                const confirmPass = form.querySelector('[name="confirm_password"]');
                if (confirmPass && confirmPass.value.length > 0) {
                    validateInput(confirmPass, form);
                }
            }
        }
    });

    // Trigger initial validation on page load for specific forms
    document.querySelectorAll('form[novalidate][data-initial-validate]').forEach(form => {
        Array.from(form.elements).forEach(input => {
            if (['INPUT', 'SELECT', 'TEXTAREA'].includes(input.tagName)) {
                validateInput(input, form);
            }
        });
    });

    // Handle hyperlink-form <a> tag clicks (supports Ctrl/Cmd+click → new tab)
    document.addEventListener('click', (e) => {
        const link = e.target.closest('form.form_hyperlink a');
        if (!link) return;

        e.preventDefault();

        if (link.classList.contains('delete')) {
            if (!confirm('Are you sure you want to delete this resource?')) {
                return;
            }
        }
        const form = link.closest('form');
        if (!form) return;

        if (e.ctrlKey || e.metaKey) {
            form.target = '_blank';
            form.submit();
            form.target = '_self';
        } else {
            form.target = '_self';
            form.submit();
        }
    });

    // Handle form submission
    document.addEventListener('submit', async (e) => {
        const form = e.target;

        // Only run this on forms we control (those with novalidate)
        if (!form.hasAttribute('novalidate')) return;

        const disableValidationToggle = document.getElementById('disable_client_validation');
        if (disableValidationToggle && disableValidationToggle.checked) {
            // Let the browser submit the form normally (server-side validation only)
            return;
        }

        e.preventDefault();


        let isFormValid = true;

        // Validate all elements on submit
        Array.from(form.elements).forEach(input => {
            if (!validateInput(input, form)) {
                isFormValid = false;
            }
        });

        // Submit via Fetch if valid
        if (isFormValid) {
            try {
                const formData = new FormData(form);

                // Ensure unchecked checkboxes send "not checked"
                form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                    if (!checkbox.checked) {
                        formData.append(checkbox.name, 'not checked');
                    }
                });

                const response = await fetch(form.action, {
                    method: form.method || 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    try {
                        const result = await response.json();
                        if (result.redirect) {
                            window.location.href = result.redirect;
                        } else {
                            console.error('No redirect provided by server');
                        }
                    } catch (e) {
                        console.error("Failed to parse JSON response:", e);
                    }
                } else {
                    console.error('Server returned an error status:', response.status);
                    window.location.href = '/index.php?error=server_failure';
                }

            } catch (err) {
                console.error("Fetch submission failed:", err);
            }
        }
    });
});
