<?php
// Initialize an empty array for search results
$search_results = [];
$search_query = '';
$error_message = '';

// Get the search query from the URL if it exists
if (isset($_GET['query'])) {
    $search_query = trim($_GET['query']);
}

// Define the sanitizeOutput function
function sanitizeOutput($data) {
    // ENT_QUOTES converts both double and single quotes
    // ENT_SUBSTITUTE replaces invalid UTF-8 sequences with a substitute character
    return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results<?php echo !empty($search_query) ? ' for "' . sanitizeOutput($search_query) . '"' : ''; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Your Custom Styles -->
    <link href="/public/assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Include the navbar -->
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <!-- Main Container for Search Results -->
    <div class="container mb-5" style="margin-top: 100px;"><!-- margin-top to avoid navbar overlap -->
        <h2 class="mb-4">Search Results<?php echo !empty($search_query) ? ' for "' . sanitizeOutput($search_query) . '"' : ''; ?></h2>
        
        <!-- Search form at the top of results page -->
        <div class="row mb-4">
            <div class="col-md-6 mx-auto">
                <form action="/public/assets/pages/search" method="GET" class="d-flex">
                    <input class="form-control me-2" 
                           type="search" 
                           name="query" 
                           placeholder="Search..." 
                           value="<?php echo sanitizeOutput($search_query); ?>"
                           required>
                    <button class="btn btn-primary" type="submit">Search</button>
                </form>
            </div>
        </div>
        
        <!-- Loading indicator -->
        <div id="loading" class="text-center my-5" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Searching...</p>
        </div>
        
        <!-- Error message container -->
        <div id="error-container" class="alert alert-danger" style="display: none;">
            <p id="error-message"></p>
        </div>
        
        <!-- Results container -->
        <div id="results-container" class="row">
            <!-- Results will be loaded here via JavaScript -->
            <div class="col-12 text-center" id="no-results" style="display: none;">
                <p>No Results</p>
            </div>
        </div>
        
        <!-- Initial state message when no search has been performed -->
        <?php if (empty($search_query)): ?>
        <div class="col-12 text-center mt-4">
            <div class="alert alert-info">
                <p>Enter a search term above to find blog posts.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Include the footer -->
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get elements
            const loadingElement = document.getElementById('loading');
            const errorContainer = document.getElementById('error-container');
            const errorMessage = document.getElementById('error-message');
            const resultsContainer = document.getElementById('results-container');
            const noResults = document.getElementById('no-results');
            
            // Get search query from URL
            const urlParams = new URLSearchParams(window.location.search);
            const searchQuery = urlParams.get('query');
            
            // If there's a search query, perform the search
            if (searchQuery) {
                // Show loading indicator
                loadingElement.style.display = 'block';
                
                // Hide any previous error messages
                errorContainer.style.display = 'none';
                
                // Clear previous results
                resultsContainer.innerHTML = '';
                noResults.style.display = 'none';
                
                // Fetch search results from API
                fetch(`/backend/api/search.php?query=${encodeURIComponent(searchQuery)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(result => {
                        // Hide loading indicator
                        loadingElement.style.display = 'none';
                        
                        if (result.success) {
                            // Process search results
                            const searchResults = result.data;
                            
                            if (searchResults.length > 0) {
                                // Display search results
                                searchResults.forEach(post => {
                                    const postElement = createPostElement(post);
                                    resultsContainer.appendChild(postElement);
                                });
                                
                                // Add fade-in effect
                                const fadeElements = document.querySelectorAll(".fade-in");
                                const observer = new IntersectionObserver((entries) => {
                                    entries.forEach(entry => {
                                        if (entry.isIntersecting) {
                                            entry.target.classList.add("visible");
                                        }
                                    });
                                }, {threshold: 0.3});
                                
                                fadeElements.forEach(element => observer.observe(element));
                            } else {
                                // Show no results message
                                noResults.style.display = 'block';
                                
                                // Add a more visible alert
                                const alertDiv = document.createElement('div');
                                alertDiv.className = 'col-12 mb-4';
                                alertDiv.innerHTML = `
                                    <div class="alert alert-warning">
                                        <h4 class="alert-heading">No Results</h4>
                                    </div>
                                `;
                                resultsContainer.insertBefore(alertDiv, noResults);
                            }
                        } else {
                            // Show no results message instead of error
                            noResults.style.display = 'block';
                            
                            // Add a more visible alert
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'col-12 mb-4';
                            alertDiv.innerHTML = `
                                <div class="alert alert-warning">
                                    <h4 class="alert-heading">No Results</h4>
                                </div>
                            `;
                            resultsContainer.insertBefore(alertDiv, noResults);
                            
                            // Log the error for debugging
                            console.error('API error:', result.message);
                        }
                    })
                    .catch(error => {
                        // Hide loading indicator
                        loadingElement.style.display = 'none';
                        
                        // Show no results message instead of error
                        noResults.style.display = 'block';
                        
                        // Add a more visible alert
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'col-12 mb-4';
                        alertDiv.innerHTML = `
                            <div class="alert alert-warning">
                                <h4 class="alert-heading">No Results</h4>
                            </div>
                        `;
                        resultsContainer.insertBefore(alertDiv, noResults);
                        
                        // Log the error for debugging
                        console.error('Network error:', error);
                    });
            }
            
            // Function to create a post element
            function createPostElement(post) {
                const colDiv = document.createElement('div');
                colDiv.className = 'col-md-4 mb-4 fade-in';
                
                const cardDiv = document.createElement('div');
                cardDiv.className = 'card h-100';
                
                // Add featured image if available
                if (post.featured_image) {
                    const img = document.createElement('img');
                    img.src = post.featured_image;
                    img.className = 'card-img-top';
                    img.alt = post.title;
                    cardDiv.appendChild(img);
                }
                
                const cardBodyDiv = document.createElement('div');
                cardBodyDiv.className = 'card-body';
                
                // Add title
                const title = document.createElement('h5');
                title.className = 'card-title';
                title.textContent = post.title;
                cardBodyDiv.appendChild(title);
                
                // Add metadata
                if (post.author_name || post.category_name) {
                    const metadata = document.createElement('p');
                    metadata.className = 'card-text text-muted';
                    
                    const small = document.createElement('small');
                    let metadataText = '';
                    
                    if (post.author_name) {
                        metadataText += `By ${post.author_name}`;
                    }
                    
                    if (post.category_name) {
                        if (metadataText) {
                            metadataText += ' in ';
                        }
                        metadataText += post.category_name;
                    }
                    
                    small.textContent = metadataText;
                    metadata.appendChild(small);
                    cardBodyDiv.appendChild(metadata);
                }
                
                // Add excerpt
                const excerpt = document.createElement('p');
                excerpt.className = 'card-text';
                excerpt.textContent = post.excerpt || '';
                cardBodyDiv.appendChild(excerpt);
                
                // Add read more button
                const link = document.createElement('a');
                link.href = `/public/post?id=${post.post_id}`;
                link.className = 'btn btn-primary';
                link.textContent = 'Read More';
                cardBodyDiv.appendChild(link);
                
                cardDiv.appendChild(cardBodyDiv);
                colDiv.appendChild(cardDiv);
                
                return colDiv;
            }
        });
    </script>
</body>
</html>
