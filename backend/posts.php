<?php
require_once 'connect_db.php';

/**
 * Get featured posts (latest published posts)
 * @param int $limit Number of posts to retrieve
 * @return array Array of featured posts
 */
function getFeaturedPosts($limit = 3) {
    global $conn;
    $response = ['success' => false, 'message' => '', 'data' => []];
    
    try {
        $query = "
            SELECT p.*, u.username as author_name, c.name as category_name 
            FROM blog_posts p
            JOIN users u ON p.author_id = u.user_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE p.status = 'published'
            ORDER BY p.created_at DESC
            LIMIT ?
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $posts;
        
    } catch (Exception $e) {
        $response['message'] = 'Error retrieving featured posts: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Get recent posts (excluding featured ones if needed)
 * @param int $limit Number of posts to retrieve
 * @param int $offset Number of posts to skip (for pagination)
 * @return array Array of recent posts
 */
function getRecentPosts($limit = 6, $offset = 0) {
    global $conn;
    $response = ['success' => false, 'message' => '', 'data' => []];
    
    try {
        $query = "
            SELECT p.*, u.username as author_name, c.name as category_name 
            FROM blog_posts p
            JOIN users u ON p.author_id = u.user_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE p.status = 'published'
            ORDER BY p.created_at DESC
            LIMIT ?, ?
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $offset, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $posts;
        
    } catch (Exception $e) {
        $response['message'] = 'Error retrieving recent posts: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Get a single post by ID
 * @param int $postId Post ID
 * @return array Post data
 */
function getPostById($postId) {
    global $conn;
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    try {
        $query = "
            SELECT p.*, u.username as author_name, c.name as category_name 
            FROM blog_posts p
            JOIN users u ON p.author_id = u.user_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE p.post_id = ? AND p.status = 'published'
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $response['success'] = true;
            $response['data'] = $result->fetch_assoc();
        } else {
            $response['message'] = 'Post not found';
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error retrieving post: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Get posts by category
 * @param int $categoryId Category ID
 * @param int $limit Number of posts to retrieve
 * @param int $offset Number of posts to skip (for pagination)
 * @return array Array of posts in the category
 */
function getPostsByCategory($categoryId, $limit = 10, $offset = 0) {
    global $conn;
    $response = ['success' => false, 'message' => '', 'data' => []];
    
    try {
        $query = "
            SELECT p.*, u.username as author_name, c.name as category_name 
            FROM blog_posts p
            JOIN users u ON p.author_id = u.user_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE p.category_id = ? AND p.status = 'published'
            ORDER BY p.created_at DESC
            LIMIT ?, ?
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $categoryId, $offset, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $posts;
        
    } catch (Exception $e) {
        $response['message'] = 'Error retrieving posts by category: ' . $e->getMessage();
    }
    
    return $response;
}