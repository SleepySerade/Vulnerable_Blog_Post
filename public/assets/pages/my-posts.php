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
error_log("MyPosts.php - Session ID: " . session_id());
error_log("MyPosts.php - Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive'));
error_log("MyPosts.php - Session variables: " . print_r($_SESSION, true));

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
error_log("MyPosts.php - User is logged in: " . ($isLoggedIn ? 'yes' : 'no'));

// Redirect to login if not logged in
if (!$isLoggedIn) {
    header('Location: /public/login');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Initialize empty arrays for posts
$published_posts = [];
$draft_posts = [];
$archived_posts = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Posts - Blog Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar'; ?>

    <div class="container my-5">
        <header class="mb-5">
            <h1>My Posts</h1>
            <p class="lead">Manage your blog posts</p>
            <a href="/public/assets/pages/create-post" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create New Post
            </a>
        </header>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="text-center my-5">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading your posts...</p>
        </div>

        <!-- Error Alert -->
        <div id="errorAlert" class="alert alert-danger d-none">
            <i class="bi bi-exclamation-triangle-fill"></i> An error occurred while loading your posts. Please try again later.
        </div>

        <!-- No Posts Alert -->
        <div id="noPostsAlert" class="alert alert-info d-none">
            <i class="bi bi-info-circle-fill"></i> You haven't created any posts yet. 
            <a href="/public/assets/pages/create-post" class="alert-link">Create your first post</a>.
        </div>

        <!-- Tabs for different post statuses -->
        <ul class="nav nav-tabs mb-4" id="postsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="published-tab" data-bs-toggle="tab" data-bs-target="#published" type="button" role="tab">
                    Published <span id="publishedCount" class="badge bg-primary">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="drafts-tab" data-bs-toggle="tab" data-bs-target="#drafts" type="button" role="tab">
                    Drafts <span id="draftsCount" class="badge bg-secondary">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="archived-tab" data-bs-toggle="tab" data-bs-target="#archived" type="button" role="tab">
                    Archived <span id="archivedCount" class="badge bg-secondary">0</span>
                </button>
            </li>
        </ul>

        <!-- Tab content -->
        <div class="tab-content" id="postsTabContent">
            <!-- Published Posts -->
            <div class="tab-pane fade show active" id="published" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Views</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="publishedPostsTable">
                            <!-- Published posts will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Draft Posts -->
            <div class="tab-pane fade" id="drafts" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="draftPostsTable">
                            <!-- Draft posts will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Archived Posts -->
            <div class="tab-pane fade" id="archived" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Views</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="archivedPostsTable">
                            <!-- Archived posts will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this post? This action cannot be undone.</p>
                        <p id="deletePostTitle" class="fw-bold"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loadingIndicator = document.getElementById('loadingIndicator');
            const errorAlert = document.getElementById('errorAlert');
            const noPostsAlert = document.getElementById('noPostsAlert');
            
            const publishedPostsTable = document.getElementById('publishedPostsTable');
            const draftPostsTable = document.getElementById('draftPostsTable');
            const archivedPostsTable = document.getElementById('archivedPostsTable');
            
            const publishedCount = document.getElementById('publishedCount');
            const draftsCount = document.getElementById('draftsCount');
            const archivedCount = document.getElementById('archivedCount');
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            const deletePostTitle = document.getElementById('deletePostTitle');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            
            let postToDelete = null;
            
            // Fetch user's posts
            fetch('/backend/api/posts.php?action=by_author&author_id=<?php echo $user_id; ?>')
                .then(response => response.json())
                .then(result => {
                    // Hide loading indicator
                    loadingIndicator.classList.add('d-none');
                    
                    if (result.success) {
                        const posts = result.data;
                        
                        if (posts.length === 0) {
                            // Show no posts alert
                            noPostsAlert.classList.remove('d-none');
                        } else {
                            // Categorize posts by status
                            const published = posts.filter(post => post.status === 'published');
                            const drafts = posts.filter(post => post.status === 'draft');
                            const archived = posts.filter(post => post.status === 'archived');
                            
                            // Update counts
                            publishedCount.textContent = published.length;
                            draftsCount.textContent = drafts.length;
                            archivedCount.textContent = archived.length;
                            
                            // Display posts in tables
                            displayPosts(published, publishedPostsTable, true);
                            displayPosts(drafts, draftPostsTable, false);
                            displayPosts(archived, archivedPostsTable, true);
                        }
                    } else {
                        // Show error alert
                        errorAlert.classList.remove('d-none');
                        console.error('Error fetching posts:', result.message);
                    }
                })
                .catch(error => {
                    // Hide loading indicator
                    loadingIndicator.classList.add('d-none');
                    
                    // Show error alert
                    errorAlert.classList.remove('d-none');
                    console.error('Error:', error);
                });
            
            // Function to display posts in a table
            function displayPosts(posts, tableElement, showViews) {
                if (posts.length === 0) {
                    // Show empty message
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = showViews ? 4 : 3;
                    cell.className = 'text-center text-muted';
                    cell.textContent = 'No posts found';
                    row.appendChild(cell);
                    tableElement.appendChild(row);
                    return;
                }
                
                // Sort posts by date (newest first)
                posts.sort((a, b) => new Date(b.updated_at || b.created_at) - new Date(a.updated_at || a.created_at));
                
                // Add posts to table
                posts.forEach(post => {
                    const row = document.createElement('tr');
                    
                    // Title cell
                    const titleCell = document.createElement('td');
                    const titleLink = document.createElement('a');
                    titleLink.href = `/public/post?id=${post.post_id}`;
                    titleLink.textContent = post.title;
                    titleCell.appendChild(titleLink);
                    row.appendChild(titleCell);
                    
                    // Date cell
                    const dateCell = document.createElement('td');
                    const date = new Date(post.updated_at || post.created_at);
                    dateCell.textContent = date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                    row.appendChild(dateCell);
                    
                    // Views cell (for published and archived posts)
                    if (showViews) {
                        const viewsCell = document.createElement('td');
                        viewsCell.textContent = post.views_count || 0;
                        row.appendChild(viewsCell);
                    }
                    
                    // Actions cell
                    const actionsCell = document.createElement('td');
                    
                    // Edit button
                    const editBtn = document.createElement('a');
                    editBtn.href = `/public/assets/pages/edit-post?id=${post.post_id}`;
                    editBtn.className = 'btn btn-sm btn-outline-primary me-2';
                    editBtn.innerHTML = '<i class="bi bi-pencil"></i> Edit';
                    actionsCell.appendChild(editBtn);
                    
                    // Delete button
                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'btn btn-sm btn-outline-danger';
                    deleteBtn.innerHTML = '<i class="bi bi-trash"></i> Delete';
                    deleteBtn.addEventListener('click', () => {
                        // Set post to delete
                        postToDelete = post;
                        deletePostTitle.textContent = post.title;
                        
                        // Show delete confirmation modal
                        deleteModal.show();
                    });
                    actionsCell.appendChild(deleteBtn);
                    
                    row.appendChild(actionsCell);
                    
                    tableElement.appendChild(row);
                });
            }
            
            // Handle delete confirmation
            confirmDeleteBtn.addEventListener('click', function() {
                if (!postToDelete) return;
                
                // Disable button and show loading state
                confirmDeleteBtn.disabled = true;
                confirmDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';
                
                // Send delete request
                fetch('/backend/api/posts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        post_id: postToDelete.post_id
                    }),
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(result => {
                    // Hide modal
                    deleteModal.hide();
                    
                    if (result.success) {
                        // Reload page to refresh posts
                        window.location.reload();
                    } else {
                        // Show error alert
                        alert('Error deleting post: ' + result.message);
                        
                        // Reset button
                        confirmDeleteBtn.disabled = false;
                        confirmDeleteBtn.innerHTML = 'Delete';
                    }
                })
                .catch(error => {
                    // Hide modal
                    deleteModal.hide();
                    
                    // Show error alert
                    alert('Error deleting post. Please try again later.');
                    console.error('Error:', error);
                    
                    // Reset button
                    confirmDeleteBtn.disabled = false;
                    confirmDeleteBtn.innerHTML = 'Delete';
                });
            });
        });
    </script>
</body>
</html>