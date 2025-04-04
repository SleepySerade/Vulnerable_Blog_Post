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
 * Add or update a post reaction (like/dislike)
 * @param int $postId Post ID
 * @param int $userId User ID
 * @param string $reactionType Reaction type ('like' or 'dislike')
 * @return array Response with success status and message
 */
function addPostReaction($postId, $userId, $reactionType) {
    global $conn;
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Check if post exists
        $checkPostQuery = "SELECT post_id FROM blog_posts WHERE post_id = ? AND status = 'published'";
        $checkStmt = $conn->prepare($checkPostQuery);
        $checkStmt->bind_param("i", $postId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $response['message'] = 'Post not found or not published';
            return $response;
        }
        
        // Check if user has already reacted to this post
        $checkReactionQuery = "SELECT reaction_id, reaction_type FROM post_reactions WHERE post_id = ? AND user_id = ?";
        $checkReactionStmt = $conn->prepare($checkReactionQuery);
        $checkReactionStmt->bind_param("ii", $postId, $userId);
        $checkReactionStmt->execute();
        $checkReactionResult = $checkReactionStmt->get_result();
        
        if ($checkReactionResult->num_rows > 0) {
            // User has already reacted, update the reaction
            $existingReaction = $checkReactionResult->fetch_assoc();
            
            // If the same reaction type, remove it (toggle off)
            if ($existingReaction['reaction_type'] === $reactionType) {
                $deleteQuery = "DELETE FROM post_reactions WHERE reaction_id = ?";
                $deleteStmt = $conn->prepare($deleteQuery);
                $deleteStmt->bind_param("i", $existingReaction['reaction_id']);
                $deleteStmt->execute();
                
                $response['success'] = true;
                $response['message'] = 'Reaction removed';
                $response['action'] = 'removed';
            } else {
                // Different reaction type, update it
                $updateQuery = "UPDATE post_reactions SET reaction_type = ? WHERE reaction_id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("si", $reactionType, $existingReaction['reaction_id']);
                $updateStmt->execute();
                
                $response['success'] = true;
                $response['message'] = 'Reaction updated';
                $response['action'] = 'updated';
            }
        } else {
            // User has not reacted yet, insert new reaction
            $insertQuery = "INSERT INTO post_reactions (post_id, user_id, reaction_type) VALUES (?, ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("iis", $postId, $userId, $reactionType);
            $insertStmt->execute();
            
            $response['success'] = true;
            $response['message'] = 'Reaction added';
            $response['action'] = 'added';
        }
        
        // Get updated reaction counts
        $counts = getPostReactionCounts($postId);
        $response['counts'] = $counts['data'];
        
    } catch (Exception $e) {
        $response['message'] = 'Error processing reaction: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Remove a post reaction
 * @param int $postId Post ID
 * @param int $userId User ID
 * @return array Response with success status and message
 */
function removePostReaction($postId, $userId) {
    global $conn;
    $response = ['success' => false, 'message' => ''];
    
    try {
        $query = "DELETE FROM post_reactions WHERE post_id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $postId, $userId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Reaction removed successfully';
        } else {
            $response['message'] = 'No reaction found to remove';
        }
        
        // Get updated reaction counts
        $counts = getPostReactionCounts($postId);
        $response['counts'] = $counts['data'];
        
    } catch (Exception $e) {
        $response['message'] = 'Error removing reaction: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Get reaction counts for a post
 * @param int $postId Post ID
 * @return array Response with success status and counts data
 */
function getPostReactionCounts($postId) {
    global $conn;
    $response = ['success' => false, 'message' => '', 'data' => ['likes' => 0, 'dislikes' => 0]];
    
    try {
        $query = "
            SELECT
                reaction_type,
                COUNT(*) as count
            FROM post_reactions
            WHERE post_id = ?
            GROUP BY reaction_type
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            if ($row['reaction_type'] === 'like') {
                $response['data']['likes'] = (int)$row['count'];
            } else if ($row['reaction_type'] === 'dislike') {
                $response['data']['dislikes'] = (int)$row['count'];
            }
        }
        
        $response['success'] = true;
        
    } catch (Exception $e) {
        $response['message'] = 'Error getting reaction counts: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Get a user's reaction to a post
 * @param int $postId Post ID
 * @param int $userId User ID
 * @return array Response with success status and user's reaction
 */
function getUserPostReaction($postId, $userId) {
    global $conn;
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    try {
        $query = "SELECT reaction_type FROM post_reactions WHERE post_id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $postId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $response['data'] = $row['reaction_type'];
        }
        
        $response['success'] = true;
        
    } catch (Exception $e) {
        $response['message'] = 'Error getting user reaction: ' . $e->getMessage();
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