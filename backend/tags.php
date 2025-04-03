<?php
require_once 'connect_db.php';

/**
 * Get all available tags
 * @return array Array of all tags
 */
function getAllTags() {
    global $conn;
    $response = ['success' => false, 'message' => '', 'data' => []];
    
    try {
        $query = "SELECT * FROM tags ORDER BY name ASC";
        $result = $conn->query($query);
        
        $tags = [];
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $tags;
        
    } catch (Exception $e) {
        $response['message'] = 'Error retrieving tags: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Get tags for a specific post
 * @param int $postId Post ID
 * @return array Array of tags for the post
 */
function getPostTags($postId) {
    global $conn;
    $response = ['success' => false, 'message' => '', 'data' => []];
    
    try {
        $query = "
            SELECT t.* 
            FROM tags t
            JOIN post_tags pt ON t.tag_id = pt.tag_id
            WHERE pt.post_id = ?
            ORDER BY t.name ASC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tags = [];
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $tags;
        
    } catch (Exception $e) {
        $response['message'] = 'Error retrieving post tags: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Get posts by tag
 * @param int $tagId Tag ID
 * @param int $limit Number of posts to retrieve
 * @param int $offset Number of posts to skip (for pagination)
 * @return array Array of posts with the specified tag
 */
function getPostsByTag($tagId, $limit = 10, $offset = 0) {
    global $conn;
    $response = ['success' => false, 'message' => '', 'data' => []];
    
    try {
        $query = "
            SELECT p.*, u.username as author_name, c.name as category_name 
            FROM blog_posts p
            JOIN users u ON p.author_id = u.user_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            JOIN post_tags pt ON p.post_id = pt.post_id
            WHERE pt.tag_id = ? AND p.status = 'published'
            ORDER BY p.created_at DESC
            LIMIT ?, ?
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $tagId, $offset, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $posts;
        
    } catch (Exception $e) {
        $response['message'] = 'Error retrieving posts by tag: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Add a tag to a post
 * @param int $postId Post ID
 * @param string $tagName Tag name
 * @return array Response with success status and message
 */
function addTagToPost($postId, $tagName) {
    global $conn;
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Check if post exists
        $checkPostQuery = "SELECT post_id FROM blog_posts WHERE post_id = ?";
        $checkStmt = $conn->prepare($checkPostQuery);
        $checkStmt->bind_param("i", $postId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            throw new Exception('Post not found');
        }
        
        // Normalize tag name (trim and lowercase)
        $tagName = trim($tagName);
        
        // Check if tag exists, if not create it
        $checkTagQuery = "SELECT tag_id FROM tags WHERE name = ?";
        $checkTagStmt = $conn->prepare($checkTagQuery);
        $checkTagStmt->bind_param("s", $tagName);
        $checkTagStmt->execute();
        $checkTagResult = $checkTagStmt->get_result();
        
        if ($checkTagResult->num_rows > 0) {
            // Tag exists, get its ID
            $tagRow = $checkTagResult->fetch_assoc();
            $tagId = $tagRow['tag_id'];
        } else {
            // Tag doesn't exist, create it
            $createTagQuery = "INSERT INTO tags (name) VALUES (?)";
            $createTagStmt = $conn->prepare($createTagQuery);
            $createTagStmt->bind_param("s", $tagName);
            $createTagStmt->execute();
            $tagId = $conn->insert_id;
        }
        
        // Check if post already has this tag
        $checkPostTagQuery = "SELECT * FROM post_tags WHERE post_id = ? AND tag_id = ?";
        $checkPostTagStmt = $conn->prepare($checkPostTagQuery);
        $checkPostTagStmt->bind_param("ii", $postId, $tagId);
        $checkPostTagStmt->execute();
        $checkPostTagResult = $checkPostTagStmt->get_result();
        
        if ($checkPostTagResult->num_rows > 0) {
            // Post already has this tag
            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Tag already associated with post';
            return $response;
        }
        
        // Add tag to post
        $addTagQuery = "INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)";
        $addTagStmt = $conn->prepare($addTagQuery);
        $addTagStmt->bind_param("ii", $postId, $tagId);
        $addTagStmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $response['success'] = true;
        $response['message'] = 'Tag added to post successfully';
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $response['message'] = 'Error adding tag to post: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Remove a tag from a post
 * @param int $postId Post ID
 * @param int $tagId Tag ID
 * @return array Response with success status and message
 */
function removeTagFromPost($postId, $tagId) {
    global $conn;
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Check if post exists
        $checkPostQuery = "SELECT post_id FROM blog_posts WHERE post_id = ?";
        $checkStmt = $conn->prepare($checkPostQuery);
        $checkStmt->bind_param("i", $postId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            throw new Exception('Post not found');
        }
        
        // Check if tag exists
        $checkTagQuery = "SELECT tag_id FROM tags WHERE tag_id = ?";
        $checkTagStmt = $conn->prepare($checkTagQuery);
        $checkTagStmt->bind_param("i", $tagId);
        $checkTagStmt->execute();
        $checkTagResult = $checkTagStmt->get_result();
        
        if ($checkTagResult->num_rows === 0) {
            throw new Exception('Tag not found');
        }
        
        // Remove tag from post
        $removeTagQuery = "DELETE FROM post_tags WHERE post_id = ? AND tag_id = ?";
        $removeTagStmt = $conn->prepare($removeTagQuery);
        $removeTagStmt->bind_param("ii", $postId, $tagId);
        $removeTagStmt->execute();
        
        if ($removeTagStmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Tag removed from post successfully';
        } else {
            $response['message'] = 'Post does not have this tag';
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error removing tag from post: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Add multiple tags to a post
 * @param int $postId Post ID
 * @param array $tagNames Array of tag names
 * @return array Response with success status and message
 */
function addTagsToPost($postId, $tagNames) {
    global $conn;
    $response = ['success' => false, 'message' => '', 'data' => ['added' => 0, 'failed' => 0]];
    
    try {
        // Check if post exists
        $checkPostQuery = "SELECT post_id FROM blog_posts WHERE post_id = ?";
        $checkStmt = $conn->prepare($checkPostQuery);
        $checkStmt->bind_param("i", $postId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            throw new Exception('Post not found');
        }
        
        $addedCount = 0;
        $failedCount = 0;
        
        foreach ($tagNames as $tagName) {
            $result = addTagToPost($postId, $tagName);
            if ($result['success']) {
                $addedCount++;
            } else {
                $failedCount++;
            }
        }
        
        $response['success'] = true;
        $response['message'] = "Added $addedCount tags, failed to add $failedCount tags";
        $response['data']['added'] = $addedCount;
        $response['data']['failed'] = $failedCount;
        
    } catch (Exception $e) {
        $response['message'] = 'Error adding tags to post: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Update tags for a post (replace all existing tags with new ones)
 * @param int $postId Post ID
 * @param array $tagNames Array of tag names
 * @return array Response with success status and message
 */
function updatePostTags($postId, $tagNames) {
    global $conn;
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Check if post exists
        $checkPostQuery = "SELECT post_id FROM blog_posts WHERE post_id = ?";
        $checkStmt = $conn->prepare($checkPostQuery);
        $checkStmt->bind_param("i", $postId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            throw new Exception('Post not found');
        }
        
        // Remove all existing tags for the post
        $removeTagsQuery = "DELETE FROM post_tags WHERE post_id = ?";
        $removeTagsStmt = $conn->prepare($removeTagsQuery);
        $removeTagsStmt->bind_param("i", $postId);
        $removeTagsStmt->execute();
        
        // Add new tags
        $addedCount = 0;
        
        foreach ($tagNames as $tagName) {
            // Normalize tag name (trim and lowercase)
            $tagName = trim($tagName);
            
            if (empty($tagName)) {
                continue;
            }
            
            // Check if tag exists, if not create it
            $checkTagQuery = "SELECT tag_id FROM tags WHERE name = ?";
            $checkTagStmt = $conn->prepare($checkTagQuery);
            $checkTagStmt->bind_param("s", $tagName);
            $checkTagStmt->execute();
            $checkTagResult = $checkTagStmt->get_result();
            
            if ($checkTagResult->num_rows > 0) {
                // Tag exists, get its ID
                $tagRow = $checkTagResult->fetch_assoc();
                $tagId = $tagRow['tag_id'];
            } else {
                // Tag doesn't exist, create it
                $createTagQuery = "INSERT INTO tags (name) VALUES (?)";
                $createTagStmt = $conn->prepare($createTagQuery);
                $createTagStmt->bind_param("s", $tagName);
                $createTagStmt->execute();
                $tagId = $conn->insert_id;
            }
            
            // Add tag to post
            $addTagQuery = "INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)";
            $addTagStmt = $conn->prepare($addTagQuery);
            $addTagStmt->bind_param("ii", $postId, $tagId);
            $addTagStmt->execute();
            
            $addedCount++;
        }
        
        // Commit transaction
        $conn->commit();
        
        $response['success'] = true;
        $response['message'] = "Updated post tags successfully, added $addedCount tags";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $response['message'] = 'Error updating post tags: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Search for tags by name (partial match)
 * @param string $query Search query
 * @param int $limit Maximum number of results to return
 * @return array Array of matching tags
 */
function searchTags($query, $limit = 10) {
    global $conn;
    $response = ['success' => false, 'message' => '', 'data' => []];
    
    try {
        $searchQuery = "%" . $query . "%";
        
        $query = "
            SELECT * FROM tags
            WHERE name LIKE ?
            ORDER BY name ASC
            LIMIT ?
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $searchQuery, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tags = [];
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $tags;
        
    } catch (Exception $e) {
        $response['message'] = 'Error searching tags: ' . $e->getMessage();
    }
    
    return $response;
}