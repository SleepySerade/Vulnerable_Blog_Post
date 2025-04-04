<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../posts.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get reaction counts or user's reaction
        handleGetRequest($userId);
        break;
        
    case 'POST':
        // Add or update reaction
        handlePostRequest($userId);
        break;
        
    case 'DELETE':
        // Remove reaction
        handleDeleteRequest($userId);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
}

/**
 * Handle GET requests
 * @param int $userId User ID
 */
function handleGetRequest($userId) {
    // Check if post ID is provided
    if (!isset($_GET['post_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Post ID is required'
        ]);
        return;
    }
    
    $postId = intval($_GET['post_id']);
    
    // Get reaction counts
    $countsResponse = getPostReactionCounts($postId);
    
    // Get user's reaction if requested
    if (isset($_GET['user_reaction']) && $_GET['user_reaction'] === 'true') {
        $userReactionResponse = getUserPostReaction($postId, $userId);
        $countsResponse['user_reaction'] = $userReactionResponse['data'];
    }
    
    echo json_encode($countsResponse);
}

/**
 * Handle POST requests
 * @param int $userId User ID
 */
function handlePostRequest($userId) {
    // Get JSON data from request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($data['post_id']) || !isset($data['reaction_type'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Post ID and reaction type are required'
        ]);
        return;
    }
    
    $postId = intval($data['post_id']);
    $reactionType = $data['reaction_type'];
    
    // Validate reaction type
    if ($reactionType !== 'like' && $reactionType !== 'dislike') {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid reaction type. Must be "like" or "dislike"'
        ]);
        return;
    }
    
    // Add or update reaction
    $response = addPostReaction($postId, $userId, $reactionType);
    
    echo json_encode($response);
}

/**
 * Handle DELETE requests
 * @param int $userId User ID
 */
function handleDeleteRequest($userId) {
    // Check if post ID is provided
    if (!isset($_GET['post_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Post ID is required'
        ]);
        return;
    }
    
    $postId = intval($_GET['post_id']);
    
    // Remove reaction
    $response = removePostReaction($postId, $userId);
    
    echo json_encode($response);
}