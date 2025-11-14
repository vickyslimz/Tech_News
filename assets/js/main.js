// Hide spinner when page is fully loaded
window.addEventListener('load', function() {
    const spinner = document.getElementById('pageLoadingSpinner');
    if (spinner) {
        spinner.style.display = 'none';
    }
});

// Optional: Hide spinner when DOM is ready (faster)
document.addEventListener('DOMContentLoaded', function() {
    const spinner = document.getElementById('pageLoadingSpinner');
    if (spinner) {
        setTimeout(() => {
            spinner.style.display = 'none';
        }, 500); // Small delay for smooth transition
    }
});
