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
error_log("CreatePost.php - Session ID: " . session_id());
error_log("CreatePost.php - Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive'));
error_log("CreatePost.php - Session variables: " . print_r($_SESSION, true));

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
error_log("CreatePost.php - User is logged in: " . ($isLoggedIn ? 'yes' : 'no'));

// Redirect to login if not logged in
if (!$isLoggedIn) {
    header('Location: /public/login');
    exit();
}

// Initialize variables
$categories = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Post - Blog Website</title>
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
            <h1>Create New Post</h1>
            <p class="lead">Share your thoughts, ideas, and experiences with the world</p>
        </header>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-body">
                        <!-- Success and Error Alerts -->
                        <div id="successAlert" class="alert alert-success d-none">
                            Post created successfully! Redirecting to your post...
                        </div>
                        <div id="errorAlert" class="alert alert-danger d-none">
                            An error occurred. Please try again.
                        </div>

                        <!-- Post Form -->
                        <form id="postForm">
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
                                <label for="featuredImage" class="form-label">Featured Image</label>
                                <div class="input-group mb-3">
                                    <input type="file" class="form-control" id="imageUpload" accept="image/*">
                                    <button class="btn btn-outline-secondary" type="button" id="uploadButton">Upload</button>
                                </div>
                                <input type="hidden" id="featuredImage" name="featuredImage">
                                <div class="form-text">Upload an image for your post (max 5MB, JPEG, PNG, GIF, or WebP).</div>
                                <div id="uploadProgress" class="progress mt-2 d-none">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                </div>
                                <div id="imagePreview" class="mt-2 d-none">
                                    <img src="" class="img-thumbnail" style="max-height: 200px;">
                                </div>
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
                                    <input class="form-check-input" type="radio" name="status" id="statusDraft" value="draft" checked>
                                    <label class="form-check-label" for="statusDraft">
                                        Save as Draft
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="statusPublished" value="published">
                                    <label class="form-check-label" for="statusPublished">
                                        Publish Now
                                    </label>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-outline-secondary me-md-2" onclick="window.location.href='/public/index'">Cancel</button>
                                <button type="submit" class="btn btn-primary">Create Post</button>
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
                    }
                })
                .catch(error => {
                    console.error('Error fetching categories:', error);
                    // If categories can't be loaded, we can still create posts without a category
                });
            
            // Handle image upload
            const imageUpload = document.getElementById('imageUpload');
            const uploadButton = document.getElementById('uploadButton');
            const featuredImageInput = document.getElementById('featuredImage');
            const uploadProgress = document.getElementById('uploadProgress');
            const progressBar = uploadProgress.querySelector('.progress-bar');
            const imagePreview = document.getElementById('imagePreview');
            const previewImage = imagePreview.querySelector('img');
            
            uploadButton.addEventListener('click', function() {
                if (!imageUpload.files.length) {
                    alert('Please select an image to upload');
                    return;
                }
                
                const file = imageUpload.files[0];
                
                // Check file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size exceeds the maximum allowed size (5MB)');
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Allowed types: JPEG, PNG, GIF, WebP');
                    return;
                }
                
                // Create form data
                const formData = new FormData();
                formData.append('image', file);
                
                // Show progress bar
                uploadProgress.classList.remove('d-none');
                progressBar.style.width = '0%';
                
                // Upload file
                const xhr = new XMLHttpRequest();
                
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        progressBar.style.width = percentComplete + '%';
                    }
                });
                
                xhr.addEventListener('load', function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            
                            if (response.success) {
                                // Set featured image URL
                                featuredImageInput.value = response.data.url;
                                
                                // Show image preview
                                previewImage.src = response.data.url;
                                imagePreview.classList.remove('d-none');
                                
                                // Hide progress bar after a delay
                                setTimeout(() => {
                                    uploadProgress.classList.add('d-none');
                                }, 1000);
                            } else {
                                alert('Upload failed: ' + response.message);
                                uploadProgress.classList.add('d-none');
                            }
                        } catch (error) {
                            alert('Error parsing response: ' + error.message);
                            uploadProgress.classList.add('d-none');
                        }
                    } else {
                        alert('Upload failed with status: ' + xhr.status);
                        uploadProgress.classList.add('d-none');
                    }
                });
                
                xhr.addEventListener('error', function() {
                    alert('Upload failed. Please try again.');
                    uploadProgress.classList.add('d-none');
                });
                
                xhr.open('POST', '/backend/api/upload.php', true);
                xhr.send(formData);
            });
            
            // Handle form submission
            const postForm = document.getElementById('postForm');
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');
            
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
                    action: 'create',
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
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...';
                
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
                        
                        // Reset form
                        postForm.reset();
                        quill.root.innerHTML = '';
                        
                        // Redirect to the new post after a delay
                        setTimeout(() => {
                            window.location.href = `/public/post?id=${result.data.post_id}`;
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