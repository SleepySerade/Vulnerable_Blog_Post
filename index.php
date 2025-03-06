<?php
// Configure session parameters
ini_set('session.use_only_cookies', 1);

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
error_log("Index.php - Session ID: " . session_id());
error_log("Index.php - Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive'));
error_log("Index.php - Session variables: " . print_r($_SESSION, true));

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
error_log("Index.php - User is logged in: " . ($isLoggedIn ? 'yes' : 'no'));

// Initialize empty arrays for posts
$featured_posts = [];
$recent_posts = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Our Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="container-fluid bg-primary text-white py-5 mb-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4">Welcome to Our Blog</h1>
                    <p class="lead">Discover interesting stories, insights, and experiences from our community.</p>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="/public/register.php" class="btn btn-light btn-lg">Join Now</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Posts -->
    <div class="container mb-5">
        <h2 class="mb-4">Featured Posts</h2>
        <div class="row">
            <?php foreach ($featured_posts as $post): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if ($post['featured_image']): ?>
                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($post['title']); ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                            <p class="card-text text-muted">
                                <small>
                                    By <?php echo htmlspecialchars($post['author_name']); ?> in 
                                    <?php echo htmlspecialchars($post['category_name']); ?>
                                </small>
                            </p>
                            <p class="card-text">
                                <?php echo substr(htmlspecialchars($post['content']), 0, 150) . '...'; ?>
                            </p>
                            <a href="/public/post.php?id=<?php echo $post['post_id']; ?>" class="btn btn-primary">Read More</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Posts -->
    <div class="container mb-5">
        <h2 class="mb-4">Recent Posts</h2>
        <div class="row">
            <?php foreach ($recent_posts as $post): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                            <p class="card-text text-muted">
                                <small>
                                    By <?php echo htmlspecialchars($post['author_name']); ?> • 
                                    <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                </small>
                            </p>
                            <p class="card-text">
                                <?php echo substr(htmlspecialchars($post['content']), 0, 100) . '...'; ?>
                            </p>
                            <a href="/public/post.php?id=<?php echo $post['post_id']; ?>" class="btn btn-outline-primary">Read More</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="container-fluid bg-light py-5 mb-5">
        <div class="container text-center">
            <h2>Share Your Story</h2>
            <p class="lead mb-4">Join our community and start sharing your experiences with the world.</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="/public/register.php" class="btn btn-primary btn-lg">Get Started</a>
            <?php else: ?>
                <a href="/public/create-post.php" class="btn btn-primary btn-lg">Create New Post</a>
            <?php endif; ?>
        </div>
    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fetch featured posts
            fetch('/backend/api/posts.php?action=featured')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data.length > 0) {
                        displayFeaturedPosts(result.data);
                    }
                })
                .catch(error => console.error('Error fetching featured posts:', error));
            
            // Fetch recent posts
            fetch('/backend/api/posts.php?action=recent')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data.length > 0) {
                        displayRecentPosts(result.data);
                    }
                })
                .catch(error => console.error('Error fetching recent posts:', error));
            
            // Function to display featured posts
            function displayFeaturedPosts(posts) {
                const container = document.querySelector('.container.mb-5:nth-of-type(1) .row');
                
                if (!container) return;
                
                // Clear loading or placeholder content
                container.innerHTML = '';
                
                // Add posts to container
                posts.forEach(post => {
                    const postElement = createPostElement(post, true);
                    container.appendChild(postElement);
                });
            }
            
            // Function to display recent posts
            function displayRecentPosts(posts) {
                const container = document.querySelector('.container.mb-5:nth-of-type(2) .row');
                
                if (!container) return;
                
                // Clear loading or placeholder content
                container.innerHTML = '';
                
                // Add posts to container
                posts.forEach(post => {
                    const postElement = createPostElement(post, false);
                    container.appendChild(postElement);
                });
            }
            
            // Function to create a post element
            function createPostElement(post, isFeatured) {
                const colDiv = document.createElement('div');
                colDiv.className = 'col-md-4 mb-4';
                
                const cardDiv = document.createElement('div');
                cardDiv.className = 'card h-100';
                
                // Add featured image if available and it's a featured post
                if (isFeatured && post.featured_image) {
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
                const metadata = document.createElement('p');
                metadata.className = 'card-text text-muted';
                
                const small = document.createElement('small');
                if (isFeatured) {
                    small.textContent = `By ${post.author_name} in ${post.category_name || 'Uncategorized'}`;
                } else {
                    const date = new Date(post.created_at);
                    const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    small.textContent = `By ${post.author_name} • ${formattedDate}`;
                }
                
                metadata.appendChild(small);
                cardBodyDiv.appendChild(metadata);
                
                // Add excerpt
                const excerpt = document.createElement('p');
                excerpt.className = 'card-text';
                const contentLength = isFeatured ? 150 : 100;
                excerpt.textContent = post.content.substring(0, contentLength) + '...';
                cardBodyDiv.appendChild(excerpt);
                
                // Add read more button
                const link = document.createElement('a');
                link.href = `/public/post.php?id=${post.post_id}`;
                link.className = isFeatured ? 'btn btn-primary' : 'btn btn-outline-primary';
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