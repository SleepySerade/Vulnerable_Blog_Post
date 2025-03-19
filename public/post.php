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
                                    <a href="/index" class="alert-link">Return to Home</a>
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
                                <a href="/index" class="alert-link">Return to Home</a>
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
                            ${post.category_name ? `<a class="badge bg-secondary text-decoration-none link-light" href="/public/assets/pages/posts?category=${post.category_id}">${post.category_name}</a>` : ''}
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
                            <a href="/index" class="btn btn-outline-primary">Back to Posts</a>
                        </div>
                    </div>
                `;
                
                // Update post container
                postContainer.innerHTML = postHTML;
            }
            
            // Function to fetch comments
            function fetchComments(postId) {
                fetch(`/backend/api/comments.php?action=by_post&post_id=${postId}`)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            displayComments(result.data);
                        } else {
                            commentsList.innerHTML = `
                                <div class="alert alert-warning">
                                    <p class="mb-0">${result.message || 'Error loading comments'}</p>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching comments:', error);
                        commentsList.innerHTML = `
                            <div class="alert alert-danger">
                                <p class="mb-0">Failed to load comments. Please try again later.</p>
                            </div>
                        `;
                    });
            }
            
            // Function to display comments
            function displayComments(comments) {
                if (comments.length === 0) {
                    commentsList.innerHTML = `
                        <div class="alert alert-info">
                            <p class="mb-0">No comments yet. Be the first to comment!</p>
                        </div>
                    `;
                    return;
                }
                
                commentsList.innerHTML = '';
                
                comments.forEach(comment => {
                    const commentElement = document.createElement('div');
                    commentElement.className = 'card mb-3';
                    commentElement.id = `comment-${comment.comment_id}`;
                    
                    // Create comment HTML
                    commentElement.innerHTML = `
                        <div class="card-body">
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    ${comment.profile_picture ?
                                        `<img src="${comment.profile_picture}" class="rounded-circle" width="50" height="50" alt="${comment.username}">` :
                                        `<div class="bg-secondary rounded-circle d-flex justify-content-center align-items-center" style="width: 50px; height: 50px;">
                                            <i class="bi bi-person-fill text-white"></i>
                                        </div>`
                                    }
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">${comment.username}</h6>
                                        <small class="text-muted">${new Date(comment.created_at).toLocaleString()}</small>
                                    </div>
                                    <div class="comment-content mt-2">${comment.content.replace(/\n/g, '<br>')}</div>
                                    
                                    <div class="mt-2 comment-actions">
                                        ${<?php echo $isLoggedIn ? 'true' : 'false'; ?> ? `
                                            <button class="btn btn-sm btn-outline-primary reply-btn" data-comment-id="${comment.comment_id}">
                                                <i class="bi bi-reply"></i> Reply
                                            </button>
                                            ${comment.user_id == <?php echo $isLoggedIn ? $_SESSION['user_id'] : '0'; ?> ? `
                                                <button class="btn btn-sm btn-outline-secondary edit-btn" data-comment-id="${comment.comment_id}">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-btn" data-comment-id="${comment.comment_id}">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            ` : ''}
                                        ` : ''}
                                    </div>
                                    
                                    <div class="reply-form-container mt-3" style="display: none;"></div>
                                    
                                    <div class="edit-form-container mt-3" style="display: none;"></div>
                                </div>
                            </div>
                            
                            ${comment.replies && comment.replies.length > 0 ? `
                                <div class="replies ms-5">
                                    ${comment.replies.map(reply => `
                                        <div class="card mb-2" id="comment-${reply.comment_id}">
                                            <div class="card-body">
                                                <div class="d-flex">
                                                    <div class="flex-shrink-0">
                                                        ${reply.profile_picture ?
                                                            `<img src="${reply.profile_picture}" class="rounded-circle" width="40" height="40" alt="${reply.username}">` :
                                                            `<div class="bg-secondary rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px;">
                                                                <i class="bi bi-person-fill text-white"></i>
                                                            </div>`
                                                        }
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <h6 class="mb-0">${reply.username}</h6>
                                                            <small class="text-muted">${new Date(reply.created_at).toLocaleString()}</small>
                                                        </div>
                                                        <div class="comment-content mt-2">${reply.content.replace(/\n/g, '<br>')}</div>
                                                        
                                                        <div class="mt-2 comment-actions">
                                                            ${<?php echo $isLoggedIn ? 'true' : 'false'; ?> && reply.user_id == <?php echo $isLoggedIn ? $_SESSION['user_id'] : '0'; ?> ? `
                                                                <button class="btn btn-sm btn-outline-secondary edit-btn" data-comment-id="${reply.comment_id}">
                                                                    <i class="bi bi-pencil"></i> Edit
                                                                </button>
                                                                <button class="btn btn-sm btn-outline-danger delete-btn" data-comment-id="${reply.comment_id}">
                                                                    <i class="bi bi-trash"></i> Delete
                                                                </button>
                                                            ` : ''}
                                                        </div>
                                                        
                                                        <div class="edit-form-container mt-3" style="display: none;"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            ` : ''}
                        </div>
                    `;
                    
                    commentsList.appendChild(commentElement);
                });
                
                // Add event listeners for comment actions
                addCommentEventListeners();
            }
            
            // Function to add event listeners to comment actions
            function addCommentEventListeners() {
                // Reply buttons
                document.querySelectorAll('.reply-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const commentId = this.getAttribute('data-comment-id');
                        const replyContainer = this.closest('.comment-actions').nextElementSibling;
                        
                        // Toggle reply form
                        if (replyContainer.style.display === 'none') {
                            // Create reply form
                            replyContainer.innerHTML = `
                                <form class="reply-form">
                                    <input type="hidden" name="parent_comment_id" value="${commentId}">
                                    <div class="mb-3">
                                        <textarea class="form-control" rows="2" placeholder="Write your reply..." required></textarea>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary me-2 cancel-reply-btn">Cancel</button>
                                        <button type="submit" class="btn btn-sm btn-primary">Submit Reply</button>
                                    </div>
                                </form>
                            `;
                            
                            // Show reply form
                            replyContainer.style.display = 'block';
                            
                            // Focus on textarea
                            replyContainer.querySelector('textarea').focus();
                            
                            // Add event listener to cancel button
                            replyContainer.querySelector('.cancel-reply-btn').addEventListener('click', function() {
                                replyContainer.style.display = 'none';
                            });
                            
                            // Add event listener to form submission
                            replyContainer.querySelector('form').addEventListener('submit', function(e) {
                                e.preventDefault();
                                
                                const parentCommentId = this.querySelector('input[name="parent_comment_id"]').value;
                                const content = this.querySelector('textarea').value;
                                
                                submitComment(postId, content, parentCommentId);
                                
                                // Hide reply form
                                replyContainer.style.display = 'none';
                            });
                        } else {
                            // Hide reply form
                            replyContainer.style.display = 'none';
                        }
                    });
                });
                
                // Edit buttons
                document.querySelectorAll('.edit-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const commentId = this.getAttribute('data-comment-id');
                        const commentElement = document.getElementById(`comment-${commentId}`);
                        const contentElement = commentElement.querySelector('.comment-content');
                        const currentContent = contentElement.innerHTML.replace(/<br>/g, '\n');
                        const editContainer = this.closest('.comment-actions').nextElementSibling;
                        
                        // Toggle edit form
                        if (editContainer.style.display === 'none') {
                            // Create edit form
                            editContainer.innerHTML = `
                                <form class="edit-form">
                                    <input type="hidden" name="comment_id" value="${commentId}">
                                    <div class="mb-3">
                                        <textarea class="form-control" rows="3" required>${currentContent}</textarea>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary me-2 cancel-edit-btn">Cancel</button>
                                        <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            `;
                            
                            // Show edit form
                            editContainer.style.display = 'block';
                            
                            // Focus on textarea
                            editContainer.querySelector('textarea').focus();
                            
                            // Add event listener to cancel button
                            editContainer.querySelector('.cancel-edit-btn').addEventListener('click', function() {
                                editContainer.style.display = 'none';
                            });
                            
                            // Add event listener to form submission
                            editContainer.querySelector('form').addEventListener('submit', function(e) {
                                e.preventDefault();
                                
                                const commentId = this.querySelector('input[name="comment_id"]').value;
                                const content = this.querySelector('textarea').value;
                                
                                updateComment(commentId, content);
                                
                                // Hide edit form
                                editContainer.style.display = 'none';
                            });
                        } else {
                            // Hide edit form
                            editContainer.style.display = 'none';
                        }
                    });
                });
                
                // Delete buttons
                document.querySelectorAll('.delete-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const commentId = this.getAttribute('data-comment-id');
                        
                        if (confirm('Are you sure you want to delete this comment? This action cannot be undone.')) {
                            deleteComment(commentId);
                        }
                    });
                });
            }
            
            // Function to submit a new comment
            function submitComment(postId, content, parentCommentId = null) {
                const data = {
                    post_id: postId,
                    content: content
                };
                
                if (parentCommentId) {
                    data.parent_comment_id = parentCommentId;
                }
                
                fetch('/backend/api/comments.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // If it's a new top-level comment
                        if (!parentCommentId) {
                            // Clear comment form
                            document.getElementById('commentContent').value = '';
                            
                            // Refresh comments
                            fetchComments(postId);
                        } else {
                            // If it's a reply, just refresh comments to show the new reply
                            fetchComments(postId);
                        }
                    } else {
                        alert(result.message || 'Failed to submit comment');
                    }
                })
                .catch(error => {
                    console.error('Error submitting comment:', error);
                    alert('An error occurred while submitting your comment. Please try again later.');
                });
            }
            
            // Function to update a comment
            function updateComment(commentId, content) {
                fetch('/backend/api/comments.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        comment_id: commentId,
                        content: content
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Update the comment content in the DOM
                        const commentElement = document.getElementById(`comment-${commentId}`);
                        const contentElement = commentElement.querySelector('.comment-content');
                        contentElement.innerHTML = content.replace(/\n/g, '<br>');
                    } else {
                        alert(result.message || 'Failed to update comment');
                    }
                })
                .catch(error => {
                    console.error('Error updating comment:', error);
                    alert('An error occurred while updating your comment. Please try again later.');
                });
            }
            
            // Function to delete a comment
            function deleteComment(commentId) {
                fetch(`/backend/api/comments.php?id=${commentId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Remove the comment from the DOM
                        const commentElement = document.getElementById(`comment-${commentId}`);
                        commentElement.remove();
                        
                        // If there are no more comments, show the "no comments" message
                        if (commentsList.children.length === 0) {
                            commentsList.innerHTML = `
                                <div class="alert alert-info">
                                    <p class="mb-0">No comments yet. Be the first to comment!</p>
                                </div>
                            `;
                        }
                    } else {
                        alert(result.message || 'Failed to delete comment');
                    }
                })
                .catch(error => {
                    console.error('Error deleting comment:', error);
                    alert('An error occurred while deleting your comment. Please try again later.');
                });
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
                                <a href="/public/post?id=${post.post_id}" class="btn btn-outline-primary">Read More</a>
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
                    
                    if (commentContent.trim() === '') {
                        alert('Please enter a comment');
                        return;
                    }
                    
                    submitComment(postId, commentContent);
                });
            }
        });
    </script>
</body>
</html>