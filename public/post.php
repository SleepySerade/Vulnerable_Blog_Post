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
error_log("Post.php - Session ID: " . session_id());
error_log("Post.php - Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive'));
error_log("Post.php - Session variables: " . print_r($_SESSION, true));

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
error_log("Post.php - User is logged in: " . ($isLoggedIn ? 'yes' : 'no'));

// Check if post ID is provided
if (!isset($_GET['id'])) {
    header('Location: /index');
    exit();
}

$post_id = intval($_GET['id']);

// Initialize post variable
$post = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Post - Blog Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <div class="container my-5">
        <div id="postContainer">
            <div class="text-center mb-5">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading post...</p>
            </div>
        </div>

        <!-- Comments Section -->
        <div id="commentsSection" class="mt-5" style="display: none;">
            <h3 class="mb-4">Comments</h3>
            <div id="commentsList">
                <!-- Comments will be loaded here -->
            </div>

            <?php if ($isLoggedIn): ?>
                <div class="card mt-4">
                    <div class="card-body">
                        <h4 class="card-title">Leave a Comment</h4>
                        <form id="commentForm">
                            <input type="hidden" id="postId" value="<?php echo $post_id; ?>">
                            <div class="mb-3">
                                <textarea class="form-control" id="commentContent" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-4">
                    <p class="mb-0">Please <a href="/public/login">login</a> to leave a comment.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Related Posts -->
        <div id="relatedPosts" class="mt-5" style="display: none;">
            <h3 class="mb-4">Related Posts</h3>
            <div class="row" id="relatedPostsList">
                <!-- Related posts will be loaded here -->
            </div>
        </div>
    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const postId = <?php echo $post_id; ?>;
            const postContainer = document.getElementById('postContainer');
            const commentsSection = document.getElementById('commentsSection');
            const commentsList = document.getElementById('commentsList');
            const relatedPosts = document.getElementById('relatedPosts');
            const relatedPostsList = document.getElementById('relatedPostsList');
            
            // Fetch post
            fetch(`/backend/api/posts.php?action=single&id=${postId}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        displayPost(result.data);
                        // Show comments section
                        commentsSection.style.display = 'block';
                        // Fetch comments
                        fetchComments(postId);
                        // Show related posts section
                        relatedPosts.style.display = 'block';
                        // Fetch related posts
                        fetchRelatedPosts(result.data.category_id);
                    } else {
                        postContainer.innerHTML = `
                            <div class="alert alert-danger">
                                <h4 class="alert-heading">Error</h4>
                                <p>${result.message}</p>
                                <hr>
                                <p class="mb-0">
                                    <a href="/index.php" class="alert-link">Return to Home</a>
                                </p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching post:', error);
                    postContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <h4 class="alert-heading">Error</h4>
                            <p>An error occurred while fetching the post. Please try again later.</p>
                            <hr>
                            <p class="mb-0">
                                <a href="/index.php" class="alert-link">Return to Home</a>
                            </p>
                        </div>
                    `;
                });
            
            // Function to display post
            function displayPost(post) {
                // Update page title
                document.title = `${post.title} - Blog Website`;
                
                // Create post HTML
                let postHTML = `
                    <article>
                        <header class="mb-4">
                            <h1 class="fw-bolder mb-1">${post.title}</h1>
                            <div class="text-muted fst-italic mb-2">
                                Posted on ${new Date(post.created_at).toLocaleDateString('en-US', { 
                                    year: 'numeric', 
                                    month: 'long', 
                                    day: 'numeric' 
                                })} by ${post.author_name}
                            </div>
                            ${post.category_name ? `<a class="badge bg-secondary text-decoration-none link-light" href="/public/assets/pages/posts.php?category=${post.category_id}">${post.category_name}</a>` : ''}
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
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <i class="bi bi-eye me-1"></i> ${post.views_count} views
                        </div>
                        <div>
                            <a href="/index.php" class="btn btn-outline-primary">Back to Posts</a>
                        </div>
                    </div>
                `;
                
                // Update post container
                postContainer.innerHTML = postHTML;
            }
            
            // Function to fetch comments
            function fetchComments(postId) {
                // This would be implemented if we had a comments API endpoint
                // For now, just show a placeholder
                commentsList.innerHTML = `
                    <div class="alert alert-info">
                        <p class="mb-0">Comments feature coming soon!</p>
                    </div>
                `;
            }
            
            // Function to fetch related posts
            function fetchRelatedPosts(categoryId) {
                if (!categoryId) {
                    // If no category, fetch recent posts instead
                    fetch('/backend/api/posts.php?action=recent')
                        .then(response => response.json())
                        .then(result => {
                            if (result.success && result.data.length > 0) {
                                displayRelatedPosts(result.data.filter(post => post.post_id != postId).slice(0, 3));
                            } else {
                                relatedPosts.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching related posts:', error);
                            relatedPosts.style.display = 'none';
                        });
                } else {
                    // Fetch posts from the same category
                    fetch(`/backend/api/posts.php?action=by_category&category_id=${categoryId}`)
                        .then(response => response.json())
                        .then(result => {
                            if (result.success && result.data.length > 0) {
                                // Filter out current post and limit to 3
                                const filteredPosts = result.data.filter(post => post.post_id != postId).slice(0, 3);
                                if (filteredPosts.length > 0) {
                                    displayRelatedPosts(filteredPosts);
                                } else {
                                    relatedPosts.style.display = 'none';
                                }
                            } else {
                                relatedPosts.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching related posts:', error);
                            relatedPosts.style.display = 'none';
                        });
                }
            }
            
            // Function to display related posts
            function displayRelatedPosts(posts) {
                if (posts.length === 0) {
                    relatedPosts.style.display = 'none';
                    return;
                }
                
                relatedPostsList.innerHTML = '';
                
                posts.forEach(post => {
                    const postElement = document.createElement('div');
                    postElement.className = 'col-md-4 mb-4';
                    
                    postElement.innerHTML = `
                        <div class="card h-100">
                            ${post.featured_image ? `<img src="${post.featured_image}" class="card-img-top" alt="${post.title}">` : ''}
                            <div class="card-body">
                                <h5 class="card-title">${post.title}</h5>
                                <p class="card-text text-muted">
                                    <small>
                                        By ${post.author_name} • 
                                        ${new Date(post.created_at).toLocaleDateString('en-US', { 
                                            month: 'short', 
                                            day: 'numeric', 
                                            year: 'numeric' 
                                        })}
                                    </small>
                                </p>
                                <p class="card-text">${post.content.substring(0, 100)}...</p>
                                <a href="/public/post.php?id=${post.post_id}" class="btn btn-outline-primary">Read More</a>
                            </div>
                        </div>
                    `;
                    
                    relatedPostsList.appendChild(postElement);
                });
            }
            
            // Handle comment form submission
            const commentForm = document.getElementById('commentForm');
            if (commentForm) {
                commentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const commentContent = document.getElementById('commentContent').value;
                    
                    // This would be implemented if we had a comments API endpoint
                    alert('Comment submission feature coming soon!');
                    
                    // Clear form
                    commentForm.reset();
                });
            }
        });
    </script>
</body>
</html>