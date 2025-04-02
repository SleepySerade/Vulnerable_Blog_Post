/**
 * Reading Time Estimator
 * This script calculates and displays the estimated reading time for blog posts.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Find all article content containers
    const articles = document.querySelectorAll('.post-content');
    
    // Process each article
    articles.forEach(function(article) {
        // Get the text content
        const text = article.textContent || article.innerText;
        
        // Calculate reading time
        const readingTime = calculateReadingTime(text);
        
        // Find or create the reading time element
        let readingTimeElement = article.parentNode.querySelector('.reading-time');
        
        if (!readingTimeElement) {
            // Create element if it doesn't exist
            readingTimeElement = document.createElement('div');
            readingTimeElement.className = 'reading-time';
            
            // Insert before the article content
            article.parentNode.insertBefore(readingTimeElement, article);
        }
        
        // Add icon and text
        readingTimeElement.innerHTML = `
            <i class="far fa-clock"></i> ${readingTime} min read
        `;
    });
    
    /**
     * Calculate reading time in minutes
     * @param {string} text - The text to calculate reading time for
     * @return {number} - Reading time in minutes (rounded up)
     */
    function calculateReadingTime(text) {
        // Average reading speed (words per minute)
        const wordsPerMinute = 200;
        
        // Count words (split by spaces and filter out empty strings)
        const words = text.split(/\s+/).filter(Boolean).length;
        
        // Calculate reading time in minutes
        const readingTime = Math.ceil(words / wordsPerMinute);
        
        // Return at least 1 minute
        return Math.max(1, readingTime);
    }
});