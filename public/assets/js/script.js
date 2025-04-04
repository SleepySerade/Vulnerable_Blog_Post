// This script handles dark mode functionality across the site
console.log("Loading script.js for dark mode functionality");

// Wait for DOM to be fully loaded
document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM loaded in script.js");
    
    // Check if we're on the index page
    const isIndexPage = window.location.pathname === "/" ||
                       window.location.pathname === "/index.php" ||
                       window.location.pathname === "/index";
    
    console.log("Is index page:", isIndexPage);
    
    // If we're on the index page, let the inline script handle dark mode
    if (isIndexPage) {
        console.log("On index page - dark mode will be handled by inline script");
        
        // Just apply dark mode if needed, but don't add event listeners
        if (localStorage.getItem("darkMode") === "enabled") {
            document.body.classList.add("dark-mode");
            console.log("Applied dark mode from localStorage on index page");
        }
        
        return;
    }
    
    // For all other pages, handle dark mode normally
    console.log("Not on index page - handling dark mode normally");
    
    const toggleButton = document.getElementById("dark-mode-toggle");
    const body = document.body;

    // Check if Dark Mode is already enabled
    if (localStorage.getItem("darkMode") === "enabled") {
        body.classList.add("dark-mode");
        if (toggleButton) {
            toggleButton.textContent = "☀️ Light Mode";
            console.log("Applied dark mode from localStorage");
        }
    }

    // Only add event listener if the button exists
    if (toggleButton) {
        console.log("Found dark mode toggle button");
        
        // Remove any existing event listeners by cloning
        const newToggleButton = toggleButton.cloneNode(true);
        toggleButton.parentNode.replaceChild(newToggleButton, toggleButton);
        
        // Add fresh event listener
        newToggleButton.addEventListener("click", function (e) {
            console.log("Dark mode toggle clicked");
            
            // Prevent any default behavior
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle dark mode
            body.classList.toggle("dark-mode");

            // Save user preference
            if (body.classList.contains("dark-mode")) {
                localStorage.setItem("darkMode", "enabled");
                this.textContent = "☀️ Light Mode";
                console.log("Dark mode enabled");
            } else {
                localStorage.setItem("darkMode", "disabled");
                this.textContent = "🌙 Dark Mode";
                console.log("Dark mode disabled");
            }
        });
        
        console.log("Dark mode toggle button initialized");
    } else {
        console.warn("Dark mode toggle button not found");
    }

    // Active link highlighting
    const navLinks = document.querySelectorAll('.nav-link');

    // Highlight the active link based on the current URL
    const currentPath = window.location.pathname;

    navLinks.forEach((link) => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
});
