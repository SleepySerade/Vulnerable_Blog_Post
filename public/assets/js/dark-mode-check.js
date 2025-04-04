document.addEventListener("DOMContentLoaded", function () {
    const body = document.body;

    // Check if Dark Mode is already enabled in localStorage
    if (localStorage.getItem("darkMode") === "enabled") {
        body.classList.add("dark-mode");
        
        // Handle reCAPTCHA if it exists on the page
        const recaptchaInterval = setInterval(function() {
            const recaptchaIframe = document.querySelector('iframe[src*="recaptcha"]');
            if (recaptchaIframe) {
                // Force recaptcha to redraw with dark mode styles
                const recaptchaContainer = document.querySelector('.g-recaptcha');
                if (recaptchaContainer) {
                    recaptchaContainer.style.opacity = '0.99';
                    setTimeout(() => {
                        recaptchaContainer.style.opacity = '1';
                    }, 500);
                }
                clearInterval(recaptchaInterval);
            }
        }, 1000);
        
        // Clear interval after 10 seconds if reCAPTCHA hasn't loaded
        setTimeout(() => {
            clearInterval(recaptchaInterval);
        }, 10000);
    }
});