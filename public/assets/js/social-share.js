/**
 * Social Sharing Buttons
 * This script adds social sharing functionality to blog posts.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initial processing
    processPosts();
    
    // Set up a mutation observer to detect when post content is loaded dynamically
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                // Check if any article elements were added
                const hasArticle = Array.from(mutation.addedNodes).some(node =>
                    node.nodeType === 1 && (
                        node.tagName === 'ARTICLE' ||
                        node.querySelector('article')
                    )
                );
                
                if (hasArticle) {
                    // If article was added, process posts
                    processPosts();
                }
            }
        });
    });
    
    // Observe the postContainer for changes
    const postContainer = document.getElementById('postContainer');
    if (postContainer) {
        observer.observe(postContainer, { childList: true, subtree: true });
    }
    
    // Function to process posts and add social sharing
    function processPosts() {
        console.log('Processing posts for social sharing...');
        
        // Find all post containers - support both class and ID selectors
        const postContainers = document.querySelectorAll('.post-container, #postContainer');
        
        // Process each post
        postContainers.forEach(function(container) {
            // Skip if this container already has social sharing
            if (container.querySelector('.social-share-container')) {
                return;
            }
            
            console.log('Adding social sharing to container:', container);
            
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
            const article = container.querySelector('article');
            if (article) {
                // Insert after the article
                article.parentNode.insertBefore(shareContainer, article.nextSibling);
            } else {
                // If no article found, look for post content
                const postContent = container.querySelector('.post-content');
                if (postContent) {
                    // Insert after post content
                    postContent.parentNode.insertBefore(shareContainer, postContent.nextSibling);
                } else {
                    // If no post content found, append to container
                    container.appendChild(shareContainer);
                }
            }
        });
    }
});