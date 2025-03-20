/**
 * Social Sharing Buttons
 * This script adds social sharing functionality to blog posts.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Find all post containers
    const postContainers = document.querySelectorAll('.post-container');
    
    // Process each post
    postContainers.forEach(function(container) {
        // Create social sharing container
        const shareContainer = document.createElement('div');
        shareContainer.className = 'social-share-container';
        
        // Get post information
        const postTitle = document.title || 'Check out this blog post';
        const postUrl = window.location.href;
        
        // Create share buttons
        const shareButtons = [
            {
                name: 'Facebook',
                icon: 'fab fa-facebook-f',
                url: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(postUrl)}`
            },
            {
                name: 'Twitter',
                icon: 'fab fa-twitter',
                url: `https://twitter.com/intent/tweet?text=${encodeURIComponent(postTitle)}&url=${encodeURIComponent(postUrl)}`
            },
            {
                name: 'LinkedIn',
                icon: 'fab fa-linkedin-in',
                url: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(postUrl)}`
            },
            {
                name: 'WhatsApp',
                icon: 'fab fa-whatsapp',
                url: `https://api.whatsapp.com/send?text=${encodeURIComponent(postTitle + ' ' + postUrl)}`
            },
            {
                name: 'Email',
                icon: 'fas fa-envelope',
                url: `mailto:?subject=${encodeURIComponent(postTitle)}&body=${encodeURIComponent('Check out this post: ' + postUrl)}`
            }
        ];
        
        // Create share heading
        const shareHeading = document.createElement('h5');
        shareHeading.className = 'share-heading';
        shareHeading.textContent = 'Share this post:';
        shareContainer.appendChild(shareHeading);
        
        // Create buttons container
        const buttonsContainer = document.createElement('div');
        buttonsContainer.className = 'share-buttons';
        
        // Add each button
        shareButtons.forEach(function(button) {
            const link = document.createElement('a');
            link.href = button.url;
            link.className = 'share-button';
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
            link.setAttribute('aria-label', `Share on ${button.name}`);
            link.setAttribute('title', `Share on ${button.name}`);
            
            // Add icon
            const icon = document.createElement('i');
            icon.className = button.icon;
            link.appendChild(icon);
            
            // Add button to container
            buttonsContainer.appendChild(link);
        });
        
        // Add buttons to share container
        shareContainer.appendChild(buttonsContainer);
        
        // Find where to insert the share container
        const postContent = container.querySelector('.post-content');
        if (postContent) {
            // Insert after post content
            postContent.parentNode.insertBefore(shareContainer, postContent.nextSibling);
        } else {
            // If no post content found, append to container
            container.appendChild(shareContainer);
        }
    });
});