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
error_log("Posts.php - Session ID: " . session_id());
error_log("Posts.php - Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive'));
error_log("Posts.php - Session variables: " . print_r($_SESSION, true));

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
error_log("Posts.php - User is logged in: " . ($isLoggedIn ? 'yes' : 'no'));

// Initialize empty arrays for posts
$featured_posts = [];
$recent_posts = [];
$all_posts = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Posts - BlogVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        /* Category tabs styles */
        .category-tabs .nav-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 1.5rem;
        }
        
        .category-tabs .nav-link {
            border: none;
            color: #495057;
            font-weight: 500;
            padding: 0.75rem 1rem;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .category-tabs .nav-link:hover {
            color: var(--primary-color);
        }
        
        .category-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
            border: none;
        }
        
        .category-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        /* Dark mode styles for tabs */
        .dark-mode .category-tabs .nav-tabs {
            border-bottom-color: #444;
        }
        
        .dark-mode .category-tabs .nav-link {
            color: #ccc;
        }
        
        .dark-mode .category-tabs .nav-link:hover {
            color: #fff;
        }
        
        .dark-mode .category-tabs .nav-link.active {
            color: #fff;
        }
        
        /* Loading indicator */
        .loading-indicator, .category-loading {
            padding: 2rem 0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .category-tabs .nav-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 5px;
            }
            
            .category-tabs .nav-link {
                padding: 0.5rem 0.75rem;
            }
        }
    </style>
</head>
<body class="posts-page">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <div class="container my-5">
        <header class="text-center mb-1" data-aos="fade-down" data-aos-duration="1000">
            <h1>All Blog Posts</h1>
            <p class="lead">Discover interesting stories, insights, and experiences from our community</p>
            <hr>
        </header>

        <!-- Featured Posts -->
        <div class="container mb-5">
            <h2 class="mb-4">Featured Posts</h2>
            <div class="row">
                <?php foreach ($featured_posts as $post): ?>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="0">
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
                                    <?php
                                    // Strip HTML tags for excerpt
                                    $plain_content = strip_tags($post['content']);
                                    echo substr($plain_content, 0, 150) . '...';
                                    ?>
                                </p>
                                <a href="/public/post?id=<?php echo $post['post_id']; ?>" class="btn btn-primary">Read More</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Featured Posts by Category -->
        <div class="container mb-5" id="featuredByCategory">
            <h2 class="mb-4">Featured by Category</h2>
            <div class="category-tabs mb-4">
                <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
                    <!-- Category tabs will be loaded dynamically -->
                </ul>
            </div>
            <div class="tab-content" id="categoryTabContent">
                <!-- Category content will be loaded dynamically -->
                <div class="text-center py-4 loading-indicator">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading categories...</p>
                </div>
            </div>
        </div>

        <!-- Recent Posts -->
        <div class="container mb-5">
            <h2 class="mb-4">Recent Posts</h2>
            <div class="row">
                <?php foreach ($recent_posts as $post): ?>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
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
                                    <?php
                                    // Strip HTML tags for excerpt
                                    $plain_content = strip_tags($post['content']);
                                    echo substr($plain_content, 0, 100) . '...';
                                    ?>
                                </p>
                                <a href="/public/post?id=<?php echo $post['post_id']; ?>" class="btn btn-outline-primary">Read More</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- All Posts -->
        <div class="container mb-5">
            <h2 class="mb-4">All Posts</h2>
            <div class="row">
                <?php foreach ($all_posts as $post): ?>
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
                                        By <?php echo htmlspecialchars($post['author_name']); ?> • 
                                        <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                        <?php if ($post['category_name']): ?>
                                            • <?php echo htmlspecialchars($post['category_name']); ?>
                                        <?php endif; ?>
                                    </small>
                                </p>
                                <p class="card-text">
                                    <?php
                                    // Strip HTML tags for excerpt
                                    $plain_content = strip_tags($post['content']);
                                    echo substr($plain_content, 0, 120) . '...';
                                    ?>
                                </p>
                                <a href="/public/post?id=<?php echo $post['post_id']; ?>" class="btn btn-outline-primary">Read More</a>
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
                    <a href="/public/register" class="btn btn-primary btn-lg">Get Started</a>
                <?php else: ?>
                    <a href="/public/assets/pages/create-post" class="btn btn-primary btn-lg">Create New Post</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Add AOS JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

    <script>
        // Initialize AOS
        
        AOS.init({
            duration: 800, // Duration of animation (in milliseconds)
            once: true,    // Whether animation should happen only once
            offset: 100,   // Offset (in pixels) from the original trigger point
            easing: 'ease-in-out', // Easing function
            delay: 0,      // Default delay
            anchorPlacement: 'top-bottom', // Defines which position of the element regarding to window should trigger the animation

        });
    </script>
    
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
            
            // Check if we're filtering by tag or category
            const urlParams = new URLSearchParams(window.location.search);
            const tagId = urlParams.get('tag');
            const categoryId = urlParams.get('category');
            
            // Update page title based on filter
            if (tagId) {
                document.querySelector('h1').textContent = 'Posts by Tag';
                document.querySelector('.lead').textContent = 'Browsing posts with a specific tag';
                
                // Fetch tag name
                fetch(`/backend/api/tags.php?action=all`)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            const tag = result.data.find(t => t.tag_id == tagId);
                            if (tag) {
                                document.querySelector('h1').textContent = `Posts Tagged: ${tag.name}`;
                                document.querySelector('.lead').textContent = `Browsing all posts with the tag "${tag.name}"`;
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching tag info:', error));
                
                // Fetch posts by tag
                fetch(`/backend/api/tags.php?action=posts_by_tag&tag_id=${tagId}`)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success && result.data.length > 0) {
                            // Hide featured and recent sections
                            document.querySelector('.container.mb-5:nth-of-type(1)').style.display = 'none';
                            document.querySelector('.container.mb-5:nth-of-type(2)').style.display = 'none';
                            document.getElementById('featuredByCategory').style.display = 'none';
                            
                            // Update "All Posts" heading to "Tagged Posts"
                            const allPostsSection = document.querySelector('.container.mb-5:nth-of-type(4)');
                            if (allPostsSection) {
                                allPostsSection.querySelector('h2').textContent = 'Tagged Posts';
                            }
                            
                            displayAllPosts(result.data);
                        } else {
                            // No posts found for this tag
                            const allPostsSection = document.querySelector('.container.mb-5:nth-of-type(4)');
                            const container = allPostsSection ? allPostsSection.querySelector('.row') : null;
                            if (container) {
                                container.innerHTML = '<div class="col-12"><div class="alert alert-info">No posts found with this tag.</div></div>';
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching posts by tag:', error));
            } else if (categoryId) {
                // Update page title for category filter
                document.querySelector('h1').textContent = 'Posts by Category';
                document.querySelector('.lead').textContent = 'Browsing posts in a specific category';
                
                // Fetch category name
                fetch(`/backend/api/categories.php`)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            const category = result.data.find(c => c.category_id == categoryId);
                            if (category) {
                                document.querySelector('h1').textContent = `Category: ${category.name}`;
                                document.querySelector('.lead').textContent = `Browsing all posts in the "${category.name}" category`;
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching category info:', error));
                
                // Fetch posts by category
                fetch(`/backend/api/posts.php?action=by_category&category_id=${categoryId}`)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success && result.data.length > 0) {
                            // Hide featured and recent sections
                            document.querySelector('.container.mb-5:nth-of-type(1)').style.display = 'none';
                            document.querySelector('.container.mb-5:nth-of-type(2)').style.display = 'none';
                            document.getElementById('featuredByCategory').style.display = 'none';
                            
                            // Update "All Posts" heading to "Category Posts"
                            const allPostsSection = document.querySelector('.container.mb-5:nth-of-type(4)');
                            if (allPostsSection) {
                                allPostsSection.querySelector('h2').textContent = 'Category Posts';
                            }
                            
                            displayAllPosts(result.data);
                        } else {
                            // No posts found for this category
                            const allPostsSection = document.querySelector('.container.mb-5:nth-of-type(4)');
                            const container = allPostsSection ? allPostsSection.querySelector('.row') : null;
                            if (container) {
                                container.innerHTML = '<div class="col-12"><div class="alert alert-info">No posts found in this category.</div></div>';
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching posts by category:', error));
            } else {
                // No filter, fetch all posts
                fetch('/backend/api/posts.php?action=all')
                    .then(response => response.json())
                    .then(result => {
                        if (result.success && result.data.length > 0) {
                            displayAllPosts(result.data);
                        }
                    })
                    .catch(error => console.error('Error fetching all posts:', error));
            }

            // Fetch categories for featured posts by category
            fetchCategoriesForTabs();
            
            // Navbar scroll effect
            const navbar = document.getElementById("navbar");

            window.addEventListener("scroll", function () {
                if (window.scrollY > 50) {
                    navbar.classList.add("scrolled"); // Add dark background
                } else {
                    navbar.classList.remove("scrolled"); // Keep it transparent
                }
            });
            
            // Function to fetch categories and create tabs
            function fetchCategoriesForTabs() {
                fetch('/backend/api/categories.php')
                    .then(response => response.json())
                    .then(result => {
                        if (result.success && result.data.length > 0) {
                            createCategoryTabs(result.data);
                        } else {
                            // Hide the section if no categories
                            document.getElementById('featuredByCategory').style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching categories:', error);
                        document.getElementById('featuredByCategory').style.display = 'none';
                    });
            }
            
            // Function to create category tabs
            function createCategoryTabs(categories) {
                const tabsContainer = document.getElementById('categoryTabs');
                const tabContent = document.getElementById('categoryTabContent');
                
                // Remove loading indicator
                tabContent.querySelector('.loading-indicator')?.remove();
                
                // Create tabs and content for each category
                categories.forEach((category, index) => {
                    // Create tab
                    const tabItem = document.createElement('li');
                    tabItem.className = 'nav-item';
                    tabItem.role = 'presentation';
                    
                    const tabButton = document.createElement('button');
                    tabButton.className = `nav-link ${index === 0 ? 'active' : ''}`;
                    tabButton.id = `category-${category.category_id}-tab`;
                    tabButton.setAttribute('data-bs-toggle', 'tab');
                    tabButton.setAttribute('data-bs-target', `#category-${category.category_id}`);
                    tabButton.setAttribute('type', 'button');
                    tabButton.setAttribute('role', 'tab');
                    tabButton.setAttribute('aria-controls', `category-${category.category_id}`);
                    tabButton.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
                    tabButton.textContent = category.name;
                    
                    tabItem.appendChild(tabButton);
                    tabsContainer.appendChild(tabItem);
                    
                    // Create content pane
                    const contentPane = document.createElement('div');
                    contentPane.className = `tab-pane fade ${index === 0 ? 'show active' : ''}`;
                    contentPane.id = `category-${category.category_id}`;
                    contentPane.setAttribute('role', 'tabpanel');
                    contentPane.setAttribute('aria-labelledby', `category-${category.category_id}-tab`);
                    
                    // Add loading indicator to content pane
                    contentPane.innerHTML = `
                        <div class="text-center py-4 category-loading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading posts for ${category.name}...</p>
                        </div>
                        <div class="row category-posts"></div>
                    `;
                    
                    tabContent.appendChild(contentPane);
                    
                    // Fetch posts for this category
                    fetchFeaturedPostsByCategory(category.category_id);
                });
                
                // Add event listener for tab changes
                const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
                tabButtons.forEach(button => {
                    button.addEventListener('shown.bs.tab', function(event) {
                        const categoryId = event.target.getAttribute('data-bs-target').replace('#category-', '');
                        // You could do something when a tab is shown, like analytics tracking
                        console.log(`Category tab ${categoryId} shown`);
                    });
                });
            }
            
            // Function to fetch featured posts by category
            function fetchFeaturedPostsByCategory(categoryId) {
                fetch(`/backend/api/posts.php?action=featured_by_category&category_id=${categoryId}&limit=3`)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            displayFeaturedPostsByCategory(categoryId, result.data);
                        } else {
                            // Show no posts message
                            const contentPane = document.getElementById(`category-${categoryId}`);
                            contentPane.querySelector('.category-loading').remove();
                            contentPane.querySelector('.category-posts').innerHTML = `
                                <div class="col-12">
                                    <div class="alert alert-info">No posts found in this category.</div>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error(`Error fetching posts for category ${categoryId}:`, error);
                        // Show error message
                        const contentPane = document.getElementById(`category-${categoryId}`);
                        contentPane.querySelector('.category-loading').remove();
                        contentPane.querySelector('.category-posts').innerHTML = `
                            <div class="col-12">
                                <div class="alert alert-danger">Error loading posts. Please try again later.</div>
                            </div>
                        `;
                    });
            }
            
            // Function to display featured posts by category
            function displayFeaturedPostsByCategory(categoryId, posts) {
                const contentPane = document.getElementById(`category-${categoryId}`);
                const postsContainer = contentPane.querySelector('.category-posts');
                const loadingIndicator = contentPane.querySelector('.category-loading');
                
                // Remove loading indicator
                if (loadingIndicator) {
                    loadingIndicator.remove();
                }
                
                // Clear container
                postsContainer.innerHTML = '';
                
                if (posts.length === 0) {
                    postsContainer.innerHTML = `
                        <div class="col-12">
                            <div class="alert alert-info">No posts found in this category.</div>
                        </div>
                    `;
                    return;
                }
                
                // Add posts to container
                posts.forEach(post => {
                    const postElement = createPostElement(post, true);
                    postsContainer.appendChild(postElement);
                });
            }
            
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
            
            // Function to display all posts
            function displayAllPosts(posts) {
                const container = document.querySelector('.container.mb-5:nth-of-type(4) .row');
                
                if (!container) return;
                
                // Clear loading or placeholder content
                container.innerHTML = '';
                
                // Add posts to container
                posts.forEach(post => {
                    const postElement = createPostElement(post, post.featured_image ? true : false);
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
                let metadataText = `By ${post.author_name}`;
                
                // Add date for non-featured posts
                if (!isFeatured || post.created_at) {
                    const date = new Date(post.created_at);
                    const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    metadataText += ` • ${formattedDate}`;
                }
                
                // Add category for featured posts
                if (post.category_name) {
                    if (isFeatured) {
                        metadataText += ` in ${post.category_name}`;
                    } else {
                        metadataText += ` • ${post.category_name}`;
                    }
                }
                
                small.textContent = metadataText;
                metadata.appendChild(small);
                cardBodyDiv.appendChild(metadata);
                
                // Add excerpt
                const excerpt = document.createElement('p');
                excerpt.className = 'card-text';
                const contentLength = isFeatured ? 150 : 100;
                
                // Strip HTML tags for excerpt
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = post.content;
                const plainText = tempDiv.textContent || tempDiv.innerText;
                excerpt.textContent = plainText.substring(0, contentLength) + '...';
                cardBodyDiv.appendChild(excerpt);
                
                // Add tags if available
                if (post.tags && post.tags.length > 0) {
                    const tagsDiv = document.createElement('div');
                    tagsDiv.className = 'd-flex flex-wrap gap-1 mb-2';
                    
                    post.tags.forEach(tag => {
                        const tagLink = document.createElement('a');
                        tagLink.href = `/public/assets/pages/posts?tag=${tag.tag_id}`;
                        tagLink.className = 'badge bg-secondary text-decoration-none link-light me-1 mb-1';
                        tagLink.textContent = tag.name;
                        tagsDiv.appendChild(tagLink);
                    });
                    
                    cardBodyDiv.appendChild(tagsDiv);
                }
                
                // Add read more button
                const link = document.createElement('a');
                link.href = `/public/post?id=${post.post_id}`;
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