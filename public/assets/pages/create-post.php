<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Output sanitisation function
function sanitizeOutput($data) {
    // ENT_QUOTES converts both double and single quotes
    // ENT_SUBSTITUTE replaces invalid UTF-8 sequences with a substitute character
    return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

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
error_log("CreatePost.php - Session ID: " . sanitizeOutput(session_id()));
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
    <title>Create New Post - BlogVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <!-- Include Quill.js for rich text editing -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        /* Tag styles */
        .tag-item {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            border-radius: 0.25rem;
            transition: all 0.2s ease-in-out;
        }
        
        .tag-item .btn-close {
            font-size: 0.5rem;
            margin-left: 0.5rem;
            padding: 0.25rem;
        }
        
        .tag-item:hover {
            opacity: 0.9;
        }
        
        #tagContainer {
            min-height: 2rem;
        }
    </style>
    <!-- Include Emoji Picker Library -->
    <script src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1"></script>
    <script src="https://cdn.jsdelivr.net/npm/@joeattardi/emoji-button@4.6.2/dist/index.min.js"></script>
    
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <div class="container my-5">
        <header class="text-center mb-2">
            <h1>Create New Post</h1>
            <p class="lead">Share your thoughts, ideas, and experiences with the world.</p>
            <hr>
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
                        <form id="postForm" method="post" action="/backend/api/posts.php">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <!-- Using sanitizeOutput to pre-populate the title field safely -->
                                <input type="text" class="form-control" id="title" name="title" required 
                                       value="<?php echo isset($_POST['title']) ? sanitizeOutput($_POST['title']) : ''; ?>">
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
                                <label for="tags" class="form-label">Tags</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="tagInput" placeholder="Add a tag...">
                                    <button class="btn btn-outline-secondary" type="button" id="addTagButton">Add</button>
                                </div>
                                <div class="form-text">Enter tags separated by commas or add them one by one. Tags help users find your content.</div>
                                <div id="tagContainer" class="mt-2 d-flex flex-wrap gap-2">
                                    <!-- Tags will be added here dynamically -->
                                </div>
                                <input type="hidden" id="tags" name="tags" value="">
                            </div>

                            <div class="mb-3">
                                <label for="featuredImage" class="form-label">Featured Image</label>
                                
                                <!-- Upload Image Option -->
                                <div id="uploadImageSection">
                                    <div class="input-group mb-3">
                                        <input type="file" class="form-control" id="imageUpload">
                                        <button class="btn btn-outline-secondary" type="button" id="uploadButton">Upload</button>
                                    </div>
                                    <div class="form-text">Upload a file for your post (max 5MB, PHP files not allowed).</div>
                                    <div id="uploadProgress" class="progress mt-2 d-none">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                                
                                <input type="hidden" id="featuredImage" name="featuredImage">
                                
                                <!-- Image Preview (shared between both options) -->
                                <div id="imagePreview" class="mt-2 d-none">
                                    <img src="" class="img-thumbnail" style="max-height: 200px;">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="editor" class="form-label">Content</label>
                                <div id="editor" style="height: 300px;"></div>
                                <input type="hidden" id="content" name="content" value="<?php echo isset($_POST['content']) ? sanitizeOutput($_POST['content']) : ''; ?>">
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
                        [{ 'color': [] }, { 'background': [] }],
                        ['clean']
                    ]
                },
                placeholder: 'Write your post content here...'
            });

            // Word and character count
            const wordCountElement = document.createElement('p');
            wordCountElement.classList.add('text-muted', 'mt-1'); // Styling
            wordCountElement.textContent = 'Word count: 0 | Character count: 0';
            document.getElementById('editor').parentNode.appendChild(wordCountElement);

            quill.on('text-change', function () {
                const text = quill.getText().trim();
                const wordCount = text ? text.split(/\s+/).length : 0;
                const charCount = text.length;

                wordCountElement.textContent = `Word count: ${wordCount} | Character count: ${charCount}`;
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
                });
                
            // Tag handling functionality
            const tagInput = document.getElementById('tagInput');
            const addTagButton = document.getElementById('addTagButton');
            const tagContainer = document.getElementById('tagContainer');
            const tagsInput = document.getElementById('tags');
            
            function addTag(tagName) {
                tagName = tagName.trim();
                if (!tagName) return;
                
                const existingTags = Array.from(document.querySelectorAll('.tag-item')).map(el => el.dataset.tagName.toLowerCase());
                if (existingTags.includes(tagName.toLowerCase())) {
                    const existingTag = Array.from(document.querySelectorAll('.tag-item')).find(
                        el => el.dataset.tagName.toLowerCase() === tagName.toLowerCase()
                    );
                    existingTag.classList.add('bg-warning');
                    setTimeout(() => {
                        existingTag.classList.remove('bg-warning');
                    }, 1000);
                    return;
                }
                
                const tagElement = document.createElement('span');
                tagElement.className = 'badge bg-primary tag-item';
                tagElement.dataset.tagName = tagName;
                tagElement.innerHTML = `${tagName} <button type="button" class="btn-close btn-close-white" aria-label="Remove tag"></button>`;
                
                const closeButton = tagElement.querySelector('.btn-close');
                closeButton.addEventListener('click', function() {
                    tagElement.remove();
                    updateTagsInput();
                });
                
                tagContainer.appendChild(tagElement);
                tagInput.value = '';
                updateTagsInput();
            }
            
            function updateTagsInput() {
                const tagElements = document.querySelectorAll('.tag-item');
                const tags = Array.from(tagElements).map(el => el.dataset.tagName);
                tagsInput.value = JSON.stringify(tags);
            }
            
            addTagButton.addEventListener('click', function() {
                addTag(tagInput.value);
            });
            
            tagInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addTag(tagInput.value);
                }
            });
            
            tagInput.addEventListener('input', function(e) {
                const value = tagInput.value;
                if (value.includes(',')) {
                    const tags = value.split(',');
                    for (let i = 0; i < tags.length - 1; i++) {
                        addTag(tags[i]);
                    }
                    tagInput.value = tags[tags.length - 1];
                }
            });
            
            // Image upload functionality
            const imageUpload = document.getElementById('imageUpload');
            const uploadButton = document.getElementById('uploadButton');
            const uploadProgress = document.getElementById('uploadProgress');
            const progressBar = uploadProgress.querySelector('.progress-bar');
            
            const featuredImageInput = document.getElementById('featuredImage');
            const imagePreview = document.getElementById('imagePreview');
            const previewImage = imagePreview.querySelector('img');
            
            uploadButton.addEventListener('click', function() {
                if (!imageUpload.files.length) {
                    alert('Please select an image to upload');
                    return;
                }
                
                const file = imageUpload.files[0];
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size exceeds the maximum allowed size (5MB)');
                    return;
                }
                
                // Front-end validation to prevent .php files
                // Note: This is only client-side validation and can be bypassed with tools like Burp Suite
                const fileName = file.name.toLowerCase();
                if (fileName.endsWith('.php')) {
                    alert('PHP files are not allowed for security reasons');
                    return;
                }
                
                // The back-end remains vulnerable for educational purposes with Burp Suite
                
                const formData = new FormData();
                formData.append('image', file);
                
                uploadProgress.classList.remove('d-none');
                progressBar.style.width = '0%';
                
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
                                featuredImageInput.value = response.data.url;
                                previewImage.src = response.data.url;
                                imagePreview.classList.remove('d-none');
                                
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
            
            // URL option removed
            
            // Handle form submission
            const postForm = document.getElementById('postForm');
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');
            
            postForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const title = document.getElementById('title').value;
                const category_id = document.getElementById('category').value || null;
                const featured_image = document.getElementById('featuredImage').value || null;
                
                let content = quill.getText().trim();
                const status = document.querySelector('input[name="status"]:checked').value;
                
                document.getElementById('content').value = content;
                
                const tagElements = document.querySelectorAll('.tag-item');
                const tags = Array.from(tagElements).map(el => el.dataset.tagName);
                
                const postData = {
                    action: 'create',
                    title: title,
                    content: content,
                    category_id: category_id,
                    featured_image: featured_image,
                    status: status,
                    tags: tags
                };
                
                const submitButton = postForm.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.textContent;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...';
                
                successAlert.classList.add('d-none');
                errorAlert.classList.add('d-none');
                
                fetch('/backend/api/posts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(postData),
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(result => {
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                    
                    if (result.success) {
                        successAlert.classList.remove('d-none');
                        postForm.reset();
                        quill.root.innerHTML = '';
                        
                        setTimeout(() => {
                            if (status === 'draft') {
                                window.location.href = `/public/preview-draft?id=${result.data.post_id}`;
                            } else {
                                window.location.href = `/public/post?id=${result.data.post_id}`;
                            }
                        }, 2000);
                    } else {
                        errorAlert.textContent = result.message || 'An error occurred. Please try again.';
                        errorAlert.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                    console.error('Error:', error);
                    errorAlert.textContent = 'An error occurred. Please try again.';
                    errorAlert.classList.remove('d-none');
                });
            });
        });
    </script>
</body>
</html>
