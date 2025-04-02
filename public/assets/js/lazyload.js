/**
 * Lazy Loading Implementation
 * This script handles lazy loading of images to improve page load performance
 */
document.addEventListener('DOMContentLoaded', function() {
    // Check if the browser supports IntersectionObserver
    if ('IntersectionObserver' in window) {
        // Select all images that should be lazy loaded
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        // Create an observer instance
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                // If the image is in the viewport
                if (entry.isIntersecting) {
                    const img = entry.target;
                    
                    // Replace the data-src with src
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                    }
                    
                    // Replace the data-srcset with srcset if it exists
                    if (img.dataset.srcset) {
                        img.srcset = img.dataset.srcset;
                    }
                    
                    // Remove the data attributes once loaded
                    img.removeAttribute('data-src');
                    img.removeAttribute('data-srcset');
                    
                    // Add a class to fade in the image
                    img.classList.add('lazy-loaded');
                    
                    // Stop observing the image
                    observer.unobserve(img);
                }
            });
        }, {
            // Options for the observer
            rootMargin: '0px 0px 200px 0px', // Load images when they're 200px from entering the viewport
            threshold: 0.01 // Trigger when at least 1% of the image is visible
        });
        
        // Observe each image
        lazyImages.forEach(function(image) {
            imageObserver.observe(image);
        });
    } else {
        // Fallback for browsers that don't support IntersectionObserver
        // Load all images immediately
        const lazyImages = document.querySelectorAll('img[data-src]');
        lazyImages.forEach(function(img) {
            if (img.dataset.src) {
                img.src = img.dataset.src;
            }
            if (img.dataset.srcset) {
                img.srcset = img.dataset.srcset;
            }
            img.classList.add('lazy-loaded');
        });
    }
});