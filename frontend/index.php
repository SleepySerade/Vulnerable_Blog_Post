<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../backend/api/connect_db.php';

// Get featured posts (latest 3 published posts)
$featured_posts_query = "
    SELECT p.*, u.username as author_name, c.name as category_name 
    FROM blog_posts p
    JOIN users u ON p.author_id = u.user_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.status = 'published'
    ORDER BY p.created_at DESC
    LIMIT 3
";
$featured_posts = $conn->query($featured_posts_query)->fetch_all(MYSQLI_ASSOC);

// Get recent posts (excluding featured ones)
$recent_posts_query = "
    SELECT p.*, u.username as author_name, c.name as category_name 
    FROM blog_posts p
    JOIN users u ON p.author_id = u.user_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.status = 'published'
    ORDER BY p.created_at DESC
    LIMIT 6
";
$recent_posts = $conn->query($recent_posts_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Our Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'inlcude/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="container-fluid bg-primary text-white py-5 mb-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4">Welcome to Our Blog</h1>
                    <p class="lead">Discover interesting stories, insights, and experiences from our community.</p>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="register.php" class="btn btn-light btn-lg">Join Now</a>
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
                            <a href="post.php?id=<?php echo $post['post_id']; ?>" class="btn btn-primary">Read More</a>
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
                            <a href="post.php?id=<?php echo $post['post_id']; ?>" class="btn btn-outline-primary">Read More</a>
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
                <a href="register.php" class="btn btn-primary btn-lg">Get Started</a>
            <?php else: ?>
                <a href="create-post.php" class="btn btn-primary btn-lg">Create New Post</a>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'inlcude/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>