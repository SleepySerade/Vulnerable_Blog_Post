<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configure session parameters
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

// Set session cookie parameters
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session information
error_log("PreviewDraft.php - Session ID: " . session_id());
error_log("PreviewDraft.php - Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive'));
error_log("PreviewDraft.php - Session variables: " . print_r($_SESSION, true));

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
error_log("PreviewDraft.php - User is logged in: " . ($isLoggedIn ? 'yes' : 'no'));

// Redirect to login if not logged in
if (!$isLoggedIn) {
    header('Location: /public/login');
    exit();
}

// Check if post ID is provided
if (!isset($_GET['id'])) {
    header('Location: /index');
    exit();
}

$post_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Initialize post variable
$post = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Preview - BlogVerse</title>
    
    <!-- Basic meta tags -->
    <meta name="description" content="Preview your draft post">
    
    <!-- Open Graph meta tags for social sharing -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="Draft Preview - BlogVerse">
    <meta property="og:description" content="Preview your draft post">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    
    <!-- Twitter Card meta tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Draft Preview - BlogVerse">
    <meta name="twitter:description" content="Preview your draft post">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <div class="container my-5">
        <div class="alert alert-warning mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Draft Preview:</strong> This is a preview of your draft post. It is not visible to other users until you publish it.
        </div>
        
        <div id="postContainer">
            <div class="text-center mb-5">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading draft...</p>
            </div>
        </div>
        
        <div id="actionButtons" class="d-none">
            <div class="d-flex justify-content-between mb-5">
                <div>
                    <a href="/" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Home
                    </a>
                </div>
                <div>
                    <a id="editButton" href="#" class="btn btn-primary me-2">
                        <i class="bi bi-pencil"></i> Edit Draft
                    </a>
                    <button id="publishButton" class="btn btn-success">
                        <i class="bi bi-cloud-upload"></i> Publish Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const postId = <?php echo $post_id; ?>;
            const userId = <?php echo $user_id; ?>;
            const postContainer = document.getElementById('postContainer');
            const actionButtons = document.getElementById('actionButtons');
            const editButton = document.getElementById('editButton');
            const publishButton = document.getElementById('publishButton');
            
            // Fetch draft post
            fetch(`/backend/api/posts.php?action=draft&id=${postId}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Check if the current user is the author
                        if (result.data.author_id != userId) {
                            postContainer.innerHTML = `
                                <div class="alert alert-danger">
                                    <h4 class="alert-heading">Access Denied</h4>
                                    <p>You do not have permission to view this draft.</p>
                                    <hr>
                                    <p class="mb-0">
                                        <a href="/" class="alert-link">Return to Home</a>
                                    </p>
                                </div>
                            `;
                            return;
                        }
                        
                        // Display the draft post
                        displayPost(result.data);
                        
                        // Show action buttons
                        actionButtons.classList.remove('d-none');
                        
                        // Set up edit button
                        editButton.href = `/public/assets/pages/edit-post?id=${postId}`;
                        
                        // Set up publish button
                        publishButton.addEventListener('click', function() {
                            publishDraft(postId);
                        });
                    } else {
                        postContainer.innerHTML = `
                            <div class="alert alert-danger">
                                <h4 class="alert-heading">Error</h4>
                                <p>${result.message || 'Draft not found'}</p>
                                <hr>
                                <p class="mb-0">
                                    <a href="/" class="alert-link">Return to Home</a>
                                </p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching draft:', error);
                    postContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <h4 class="alert-heading">Error</h4>
                            <p>An error occurred while fetching the draft. Please try again later.</p>
                            <hr>
                            <p class="mb-0">
                                <a href="/" class="alert-link">Return to Home</a>
                            </p>
                        </div>
                    `;
                });
            
            // Function to display post
            function displayPost(post) {
                // Update page title
                document.title = `${post.title} (Draft) - BlogVerse`;
                
                // Update meta tags for better social sharing
                updateMetaTag('og:title', post.title + ' (Draft)');
                updateMetaTag('og:description', post.content.substring(0, 150) + '...');
                updateMetaTag('og:url', window.location.href);
                if (post.featured_image) {
                    updateMetaTag('og:image', post.featured_image);
                }
                
                // Create post HTML
                let postHTML = `
                    <article class="post-content">
                        <header class="mb-4">
                            <h1 class="fw-bolder mb-1">${post.title}</h1>
                            <div class="text-muted fst-italic mb-2">
                                Draft created on ${new Date(post.created_at).toLocaleDateString('en-US', { 
                                    year: 'numeric', 
                                    month: 'long', 
                                    day: 'numeric' 
                                })} by ${post.author_name || 'You'}
                            </div>
                            ${post.category_name ? `<a class="badge bg-secondary text-decoration-none link-light" href="/public/assets/pages/posts?category=${post.category_id}">${post.category_name}</a>` : ''}
                            <span class="badge bg-warning text-dark ms-2">Draft</span>
                        </header>
                `;
                
                // Add featured image if available
                if (post.featured_image) {
                    postHTML += `
                        <figure class="mb-4">
                            <img class="img-fluid rounded" src="${post.featured_image}" alt="${post.title}">
                        </figure>
                    `;
                }
                
                // Add post content
                postHTML += `
                        <section class="mb-5">
                            ${post.content.replace(/\n/g, '<br>')}
                        </section>
                    </article>
                `;
                
                // Update post container
                postContainer.innerHTML = postHTML;
            }
            
            // Function to publish draft
            function publishDraft(postId) {
                // Confirm before publishing
                if (!confirm('Are you sure you want to publish this draft? It will be visible to all users.')) {
                    return;
                }
                
                // Disable publish button
                publishButton.disabled = true;
                publishButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Publishing...';
                
                // Send request to publish
                fetch('/backend/api/posts.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'update_status',
                        post_id: postId,
                        status: 'published'
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Show success message
                        alert('Post published successfully!');
                        
                        // Redirect to the published post
                        window.location.href = `/public/post?id=${postId}`;
                    } else {
                        // Show error message
                        alert('Failed to publish post: ' + (result.message || 'Unknown error'));
                        
                        // Re-enable publish button
                        publishButton.disabled = false;
                        publishButton.innerHTML = '<i class="bi bi-cloud-upload"></i> Publish Now';
                    }
                })
                .catch(error => {
                    console.error('Error publishing draft:', error);
                    
                    // Show error message
                    alert('An error occurred while publishing the post. Please try again later.');
                    
                    // Re-enable publish button
                    publishButton.disabled = false;
                    publishButton.innerHTML = '<i class="bi bi-cloud-upload"></i> Publish Now';
                });
            }
            
            // Helper function to update or create meta tags
            function updateMetaTag(name, content) {
                // First, try to find an existing meta tag
                let metaTag = document.querySelector(`meta[property="${name}"], meta[name="${name}"]`);
                
                // If it doesn't exist, create it
                if (!metaTag) {
                    metaTag = document.createElement('meta');
                    // Determine if this is an Open Graph tag or a regular meta tag
                    if (name.startsWith('og:')) {
                        metaTag.setAttribute('property', name);
                    } else {
                        metaTag.setAttribute('name', name);
                    }
                    document.head.appendChild(metaTag);
                }
                
                // Set the content
                metaTag.setAttribute('content', content);
            }
        });
    </script>
</body>
</html>