(() => {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.classList.add('disabled');
            }
        });
    }

    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0 && typeof bootstrap !== 'undefined') {
        setTimeout(() => {
            alerts.forEach((alert) => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    }
})();
