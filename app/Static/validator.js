/**
 * validator.js - Merged Validation Engine (Phase 7)
 * 
 * Combines schema-driven rules (data-rules) with advanced visual feedback.
 */

class Validator {
    constructor() {
        this.submitted = false;
        this.init();
    }

    init() {
        document.addEventListener('submit', (e) => this.handleSubmit(e));
        document.addEventListener('blur', (e) => this.handleBlur(e), true);
        document.addEventListener('input', (e) => this.handleInput(e), true);
        document.addEventListener('click', (e) => this.handleHyperlinkClick(e));
    }

    handleHyperlinkClick(e) {
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
    }

    handleBlur(e) {
        if (this.shouldValidate(e.target)) {
            this.validateField(e.target);
        }
    }

    handleInput(e) {
        const input = e.target;
        if (!this.shouldValidate(input)) return;

        // Clear error as user types to give immediate positive feedback
        this.clearError(input);
        
        // Real-time validation: Validate as the user types
        this.validateField(input);

        // Proactive Revalidation: If password changes, re-check confirm_password
        if (input.name === 'password') {
            const form = input.closest('form');
            const confirmPass = form?.querySelector('[name="confirm_password"]');
            if (confirmPass && confirmPass.value.length > 0) {
                this.validateField(confirmPass);
            }
        }
    }

    shouldValidate(input) {
        return input.hasAttribute('data-rules') || input.name === 'confirm_password';
    }

    async handleSubmit(e) {
        const form = e.target;
        if (!form.classList.contains('flex-form')) return;

        // Skip if client validation is explicitly disabled
        const noValidateToggle = document.getElementById('disable_client_validation');
        if (noValidateToggle && noValidateToggle.checked) return;

        this.submitted = true;
        e.preventDefault();

        // 1. Client-side validation
        let isValid = true;
        const inputs = form.querySelectorAll('[data-rules], [name="confirm_password"]');
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        if (!isValid) {
            console.warn('Form validation failed on client.');
            return;
        }

        // 2. AJAX Submission
        await this.submitForm(form);
    }

    validateField(input) {
        const rulesString = input.getAttribute('data-rules') || '';
        const rules = rulesString ? rulesString.split('|') : [];
        const value = input.value.trim();
        const flexRow = input.closest('.flex-row');
        
        this.clearError(input);

        // Fallback for confirm_password if not in schema but present in DOM
        if (input.name === 'confirm_password' && !rules.some(r => r.startsWith('match'))) {
            rules.push('match:password');
        }

        const knownRules = ['required', 'min', 'max', 'numeric', 'email', 'match', 'unique'];

        for (let rule of rules) {
            let [ruleName, param] = rule.split(':');
            
            if (!knownRules.includes(ruleName)) {
                throw new Error(`Configuration Integrity Exception: Unknown validation rule "${ruleName}" encountered on field "${input.name}".`);
            }

            if (ruleName === 'required' && !value) {
                this.showError(input, 'This field is required.');
                return false;
            }
            
            if (value || ruleName === 'match') {
                if (ruleName === 'min' && value.length < parseInt(param)) {
                    this.showError(input, `Minimum length is ${param} characters.`);
                    return false;
                }
                if (ruleName === 'max' && value.length > parseInt(param)) {
                    this.showError(input, `Maximum length is ${param} characters.`);
                    return false;
                }
                if (ruleName === 'numeric' && isNaN(value)) {
                    this.showError(input, 'Must be a number.');
                    return false;
                }
                if (ruleName === 'email' && !/^\S+@\S+\.\S+$/.test(value)) {
                    this.showError(input, 'Invalid email address.');
                    return false;
                }
                if (ruleName === 'match') {
                    const form = input.closest('form');
                    const target = form.querySelector(`[name="${param}"]`);
                    if (target && value !== target.value) {
                        this.showError(input, `Does not match ${param}.`);
                        return false;
                    }
                }
            }
        }

        // If we reach here, the field is valid
        if (value.length > 0 && flexRow) {
            flexRow.classList.add('satisfied');
        }
        return true;
    }

    showError(input, message) {
        const container = input.closest('.flex-cell');
        const flexRow = input.closest('.flex-row');
        if (!container) return;

        let errorDiv = container.querySelector('.validation-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'validation-error';
            container.prepend(errorDiv);
        }
        errorDiv.textContent = message;
        input.classList.add('input-error');
        if (flexRow) {
            flexRow.classList.add('client-error');
            flexRow.classList.remove('satisfied');
        }
    }

    clearError(input) {
        const container = input.closest('.flex-cell');
        const flexRow = input.closest('.flex-row');
        if (!container) return;

        const errorDiv = container.querySelector('.validation-error');
        if (errorDiv) errorDiv.remove();
        
        input.classList.remove('input-error');
        if (flexRow) {
            flexRow.classList.remove('client-error');
            flexRow.classList.remove('satisfied');
        }
    }

    async submitForm(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.setAttribute('data-original-value', submitBtn.value);
            submitBtn.value = 'Processing...';
        }

        const formData = new FormData(form);
        const url = form.getAttribute('action') || window.location.href;

        // Ensure unchecked checkboxes send "not checked"
        form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            if (!checkbox.checked) {
                formData.append(checkbox.name, 'not checked');
            }
        });

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok) {
                if (result.redirect) {
                    window.location.href = result.redirect;
                } else if (result.success) {
                    this.handleSuccess(form, result);
                }
            } else {
                if (result.errors) {
                    this.displayServerErrors(form, result.errors);
                } else {
                    console.error('Server error:', result.message || 'Unknown error');
                }
            }
        } catch (error) {
            console.error('Submission failed:', error);
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.value = submitBtn.getAttribute('data-original-value');
            }
        }
    }

    displayServerErrors(form, errors) {
        for (let field in errors) {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                const messages = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
                this.showError(input, messages[0]);
            }
        }
    }

    handleSuccess(form, result) {
        if (result.html) {
            const container = form.closest('.container') || form.parentNode;
            container.innerHTML = result.html;
        } else {
            alert('Success!');
        }
    }
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    window.validator = new Validator();

    // Initial Validation for flashed data: 
    // Validate any field that already has a value on page load
    document.querySelectorAll('[data-rules], [name="confirm_password"]').forEach(input => {
        if (input.value.trim().length > 0) {
            window.validator.validateField(input);
        }
    });
});
