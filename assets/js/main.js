/**
 * PetNest - Shared JavaScript Utilities
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Navigation Toggle
    const mobileToggle = document.querySelector('.mobile-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (mobileToggle && navMenu) {
        mobileToggle.addEventListener('click', () => {
            navMenu.classList.toggle('show');
        });
    }

    // 2. Auto-dismiss Flash Alerts after 6 seconds
    const alerts = document.querySelectorAll('.alert:not(.emergency-banner)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 6000);
    });

    // 3. Date Validation for Booking forms
    const checkInInput = document.querySelector('input[name="check_in_date"]');
    const checkOutInput = document.querySelector('input[name="check_out_date"]');

    if (checkInInput && checkOutInput) {
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        checkInInput.min = today;

        checkInInput.addEventListener('change', () => {
            checkOutInput.min = checkInInput.value;
            if (checkOutInput.value && checkOutInput.value <= checkInInput.value) {
                checkOutInput.value = '';
            }
        });
    }

    // 4. Confirmation dialog for destructive actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const message = button.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});
