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
error_log("EditPost.php - Session ID: " . session_id());
error_log("EditPost.php - Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive'));
error_log("EditPost.php - Session variables: " . print_r($_SESSION, true));

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
error_log("EditPost.php - User is logged in: " . ($isLoggedIn ? 'yes' : 'no'));

// Redirect to login if not logged in
if (!$isLoggedIn) {
    header('Location: /public/login.php');
    exit();
}

// Check if post ID is provided
if (!isset($_GET['id'])) {
    header('Location: /index.php');
    exit();
}

$post_id = intval($_GET['id']);

// Initialize variables
$categories = [];
$post = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post - Blog Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <!-- Include Quill.js for rich text editing -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <div class="container my-5">
        <header class="text-center mb-5">
            <h1>Edit Post</h1>
            <p class="lead">Update your post content and settings</p>
        </header>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div id="loadingContainer" class="text-center py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading post data...</p>
                </div>

                <div id="errorContainer" class="alert alert-danger d-none">
                    <h4 class="alert-heading">Error</h4>
                    <p id="errorMessage">An error occurred while loading the post.</p>
                    <hr>
                    <p class="mb-0">
                        <a href="/index.php" class="alert-link">Return to Home</a>
                    </p>
                </div>

                <div id="formContainer" class="card d-none">
                    <div class="card-body">
                        <!-- Success and Error Alerts -->
                        <div id="successAlert" class="alert alert-success d-none">
                            Post updated successfully! Redirecting to your post...
                        </div>
                        <div id="errorAlert" class="alert alert-danger d-none">
                            An error occurred. Please try again.
                        </div>

                        <!-- Post Form -->
                        <form id="postForm">
                            <input type="hidden" id="postId" name="postId" value="<?php echo $post_id; ?>">
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                                <div class="form-text">Choose a descriptive title for your post.</div>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">Select a category</option>
                                    <!-- Categories will be loaded dynamically -->
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="featuredImage" class="form-label">Featured Image URL (Optional)</label>
                                <input type="url" class="form-control" id="featuredImage" name="featuredImage" placeholder="https://example.com/image.jpg">
                                <div class="form-text">Enter a URL for your featured image.</div>
                            </div>

                            <div class="mb-3">
                                <label for="editor" class="form-label">Content</label>
                                <div id="editor" style="height: 300px;"></div>
                                <input type="hidden" id="content" name="content">
                                <div class="form-text">Write your post content here. You can format text, add links, and more.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="statusDraft" value="draft">
                                    <label class="form-check-label" for="statusDraft">
                                        Save as Draft
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="statusPublished" value="published">
                                    <label class="form-check-label" for="statusPublished">
                                        Publish
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="statusArchived" value="archived">
                                    <label class="form-check-label" for="statusArchived">
                                        Archive
                                    </label>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-outline-secondary me-md-2" onclick="window.location.href='/public/post.php?id=<?php echo $post_id; ?>'">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Post</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const postId = <?php echo $post_id; ?>;
            const loadingContainer = document.getElementById('loadingContainer');
            const errorContainer = document.getElementById('errorContainer');
            const errorMessage = document.getElementById('errorMessage');
            const formContainer = document.getElementById('formContainer');
            const postForm = document.getElementById('postForm');
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');
            
            // Initialize Quill editor
            const quill = new Quill('#editor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['link', 'image'],
                        ['clean']
                    ]
                },
                placeholder: 'Write your post content here...'
            });
            
            // Fetch categories
            fetch('/backend/api/categories.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data.length > 0) {
                        const categorySelect = document.getElementById('category');
                        
                        result.data.forEach(category => {
                            const option = document.createElement('option');
                            option.value = category.category_id;
                            option.textContent = category.name;
                            categorySelect.appendChild(option);
                        });
                        
                        // After loading categories, fetch post data
                        fetchPostData();
                    } else {
                        // If categories can't be loaded, still fetch post data
                        fetchPostData();
                    }
                })
                .catch(error => {
                    console.error('Error fetching categories:', error);
                    // If categories can't be loaded, still fetch post data
                    fetchPostData();
                });
            
            // Function to fetch post data
            function fetchPostData() {
                fetch(`/backend/api/posts.php?action=single&id=${postId}`)
                    .then(response => response.json())
                    .then(result => {
                        // Hide loading container
                        loadingContainer.classList.add('d-none');
                        
                        if (result.success) {
                            // Show form container
                            formContainer.classList.remove('d-none');
                            
                            // Populate form with post data
                            const post = result.data;
                            document.getElementById('title').value = post.title;
                            
                            if (post.category_id) {
                                document.getElementById('category').value = post.category_id;
                            }
                            
                            if (post.featured_image) {
                                document.getElementById('featuredImage').value = post.featured_image;
                            }
                            
                            // Set content in Quill editor
                            quill.root.innerHTML = post.content;
                            
                            // Set status radio button
                            const statusRadio = document.getElementById(`status${post.status.charAt(0).toUpperCase() + post.status.slice(1)}`);
                            if (statusRadio) {
                                statusRadio.checked = true;
                            }
                        } else {
                            // Show error container
                            errorMessage.textContent = result.message || 'Failed to load post data.';
                            errorContainer.classList.remove('d-none');
                        }
                    })
                    .catch(error => {
                        // Hide loading container
                        loadingContainer.classList.add('d-none');
                        
                        // Show error container
                        console.error('Error fetching post:', error);
                        errorMessage.textContent = 'An error occurred while loading the post.';
                        errorContainer.classList.remove('d-none');
                    });
            }
            
            // Handle form submission
            postForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get form data
                const title = document.getElementById('title').value;
                const category_id = document.getElementById('category').value || null;
                const featured_image = document.getElementById('featuredImage').value || null;
                
                // Get the text content from Quill (without HTML)
                let content = quill.getText();
                
                // Trim any extra whitespace
                content = content.trim();
                
                const status = document.querySelector('input[name="status"]:checked').value;
                
                // Set content to hidden input
                document.getElementById('content').value = content;
                
                // Create post data object
                const postData = {
                    action: 'update',
                    post_id: postId,
                    title: title,
                    content: content,
                    category_id: category_id,
                    featured_image: featured_image,
                    status: status
                };
                
                // Show loading state
                const submitButton = postForm.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.textContent;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
                
                // Hide any previous alerts
                successAlert.classList.add('d-none');
                errorAlert.classList.add('d-none');
                
                // Submit data to API
                fetch('/backend/api/posts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(postData),
                    credentials: 'same-origin' // Include cookies in the request
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(result => {
                    // Reset loading state
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                    
                    if (result.success) {
                        // Show success message
                        successAlert.classList.remove('d-none');
                        
                        // Redirect to the post after a delay
                        setTimeout(() => {
                            window.location.href = `/public/post.php?id=${postId}`;
                        }, 2000);
                    } else {
                        // Show error message
                        errorAlert.textContent = result.message || 'An error occurred. Please try again.';
                        errorAlert.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    // Reset loading state
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                    
                    // Show error message
                    console.error('Error:', error);
                    errorAlert.textContent = 'An error occurred. Please try again.';
                    errorAlert.classList.remove('d-none');
                });
            });
        });
    </script>
</body>
</html>