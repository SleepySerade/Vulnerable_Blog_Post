/**
 * Back to Top Button Functionality
 * This script adds a "Back to Top" button that appears when the user scrolls down
 * and smoothly scrolls back to the top when clicked.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Create the back to top button element
    const backToTopBtn = document.createElement('button');
    backToTopBtn.id = 'back-to-top-btn';
    backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTopBtn.setAttribute('aria-label', 'Back to top');
    backToTopBtn.setAttribute('title', 'Back to top');
    
    // Add the button to the document
    document.body.appendChild(backToTopBtn);
    
    // Add event listener to scroll back to top when clicked
    backToTopBtn.addEventListener('click', function() {
        // Smooth scroll to top
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Show/hide the button based on scroll position
    window.addEventListener('scroll', function() {
        // Show button when user scrolls down 300px from the top
        if (window.scrollY > 300) {
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    });
});