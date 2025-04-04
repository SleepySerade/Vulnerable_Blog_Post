/**
 * Dropdown Menu Fix
 * This script ensures that dropdown menus work properly in both desktop and mobile views
 */

document.addEventListener('DOMContentLoaded', function() {
    // Completely ignore the dark mode toggle button
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    if (darkModeToggle) {
        console.log("Dropdown fix: Found dark mode toggle, ignoring it");
    }
    
    // Initialize all dropdowns (excluding the dark mode toggle)
    const dropdownElementList = document.querySelectorAll('.dropdown-toggle:not(#dark-mode-toggle)');
    
    // Ensure dropdowns work on hover for desktop and click for mobile
    dropdownElementList.forEach(function(dropdownToggle) {
        const dropdown = dropdownToggle.parentElement;
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');
        
        if (!dropdownMenu) return;
        
        // For desktop: show dropdown on hover
        if (window.innerWidth >= 992) {
            dropdown.addEventListener('mouseenter', function() {
                dropdownMenu.classList.add('show');
                dropdownToggle.setAttribute('aria-expanded', 'true');
            });
            
            dropdown.addEventListener('mouseleave', function() {
                dropdownMenu.classList.remove('show');
                dropdownToggle.setAttribute('aria-expanded', 'false');
            });
        }
        
        // Ensure proper toggling on click (for both mobile and desktop)
        dropdownToggle.addEventListener('click', function(e) {
            // Don't prevent default for dark mode toggle
            if (!this.closest('#dark-mode-toggle')) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            const isExpanded = dropdownToggle.getAttribute('aria-expanded') === 'true';
            
            // Close all other dropdowns
            dropdownElementList.forEach(function(otherDropdownToggle) {
                if (otherDropdownToggle !== dropdownToggle) {
                    otherDropdownToggle.setAttribute('aria-expanded', 'false');
                    const otherDropdownMenu = otherDropdownToggle.parentElement.querySelector('.dropdown-menu');
                    if (otherDropdownMenu) {
                        otherDropdownMenu.classList.remove('show');
                    }
                }
            });
            
            // Toggle current dropdown
            dropdownToggle.setAttribute('aria-expanded', !isExpanded);
            dropdownMenu.classList.toggle('show');
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        // Skip if clicking on dark mode toggle or its parent
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        if (darkModeToggle && (e.target === darkModeToggle || darkModeToggle.contains(e.target) || e.target.closest('#dark-mode-toggle'))) {
            console.log("Dropdown fix: Click on dark mode toggle detected, ignoring");
            return;
        }
        
        if (!e.target.closest('.dropdown')) {
            dropdownElementList.forEach(function(dropdownToggle) {
                dropdownToggle.setAttribute('aria-expanded', 'false');
                const dropdownMenu = dropdownToggle.parentElement.querySelector('.dropdown-menu');
                if (dropdownMenu) {
                    dropdownMenu.classList.remove('show');
                }
            });
        }
    });
    
    // Update behavior on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth < 992) {
            // Remove hover events for mobile
            dropdownElementList.forEach(function(dropdownToggle) {
                const dropdown = dropdownToggle.parentElement;
                dropdown.removeEventListener('mouseenter', null);
                dropdown.removeEventListener('mouseleave', null);
            });
        }
    });
});