<?php
/**
 * Breadcrumbs Component
 * 
 * This file provides a reusable breadcrumbs component for navigation.
 * Usage: include this file and call generate_breadcrumbs() with the current page info.
 */

/**
 * Generate breadcrumbs HTML based on the current page
 * 
 * @param array $breadcrumbs Array of breadcrumb items, each with 'title' and 'url' keys
 * @return string HTML for the breadcrumbs
 */
function generate_breadcrumbs($breadcrumbs = []) {
    // Always start with Home
    $html = '<nav aria-label="breadcrumb" class="my-3">
        <ol class="breadcrumb">';
    
    // Add Home link
    $html .= '<li class="breadcrumb-item"><a href="/">Home</a></li>';
    
    // Add other breadcrumbs
    foreach ($breadcrumbs as $index => $crumb) {
        // Check if this is the last item (current page)
        $is_last = ($index === count($breadcrumbs) - 1);
        
        if ($is_last) {
            // Current page (no link, active)
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($crumb['title']) . '</li>';
        } else {
            // Previous pages (with links)
            $html .= '<li class="breadcrumb-item"><a href="' . htmlspecialchars($crumb['url']) . '">' . htmlspecialchars($crumb['title']) . '</a></li>';
        }
    }
    
    $html .= '</ol>
    </nav>';
    
    return $html;
}

/**
 * Auto-generate breadcrumbs based on the current URL, but with a logical site structure
 * rather than exposing the actual file paths
 *
 * @return string HTML for the breadcrumbs
 */
function auto_breadcrumbs() {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    
    // Create a logical site structure map instead of using actual file paths
    $site_structure = [
        // Main sections
        'home' => ['title' => 'Home', 'url' => '/'],
        'blog' => ['title' => 'Blog', 'url' => '/blog'],
        'about' => ['title' => 'About', 'url' => '/about'],
        'contact' => ['title' => 'Contact', 'url' => '/contact'],
        
        // Blog related pages
        'post' => ['title' => 'Post', 'url' => '/post', 'parent' => 'blog'],
        'search' => ['title' => 'Search Results', 'url' => '/search', 'parent' => 'blog'],
        'create-post' => ['title' => 'Create Post', 'url' => '/create-post', 'parent' => 'blog'],
        'edit-post' => ['title' => 'Edit Post', 'url' => '/edit-post', 'parent' => 'blog'],
        'category' => ['title' => 'Category', 'url' => '/category', 'parent' => 'blog'],
        
        // Admin section
        'admin' => ['title' => 'Admin', 'url' => '/admin'],
        'dashboard' => ['title' => 'Dashboard', 'url' => '/dashboard', 'parent' => 'admin'],
        'manage-posts' => ['title' => 'Manage Posts', 'url' => '/manage-posts', 'parent' => 'admin'],
        'manage-comments' => ['title' => 'Manage Comments', 'url' => '/manage-comments', 'parent' => 'admin'],
        'manage-users' => ['title' => 'Manage Users', 'url' => '/manage-users', 'parent' => 'admin'],
        'manage-categories' => ['title' => 'Manage Categories', 'url' => '/manage-categories', 'parent' => 'admin'],
    ];
    
    // Determine current page based on URL path
    $current_page = '';
    
    // Clean up the path to get the logical page name
    $clean_path = strtolower(trim($path, '/'));
    
    // Handle special cases for file paths that should map to logical pages
    if (strpos($clean_path, 'public/assets/pages/search') !== false) {
        $current_page = 'search';
    } elseif (strpos($clean_path, 'public/post') !== false) {
        $current_page = 'post';
    } elseif (strpos($clean_path, 'public/assets/pages/about') !== false) {
        $current_page = 'about';
    } elseif (strpos($clean_path, 'public/assets/pages/create-post') !== false) {
        $current_page = 'create-post';
    } elseif (strpos($clean_path, 'public/assets/pages/edit-post') !== false) {
        $current_page = 'edit-post';
    } elseif (strpos($clean_path, 'admin/dashboard') !== false) {
        $current_page = 'dashboard';
    } elseif (strpos($clean_path, 'admin/manage_posts') !== false) {
        $current_page = 'manage-posts';
    } elseif (strpos($clean_path, 'admin/manage_user') !== false) {
        $current_page = 'manage-users';
    } elseif ($clean_path === '' || $clean_path === 'index.php' || $clean_path === 'public/index.php') {
        $current_page = 'home';
    } else {
        // Default to the last segment of the path
        $segments = explode('/', $clean_path);
        $last_segment = end($segments);
        $current_page = str_replace('.php', '', $last_segment);
    }
    
    // Build breadcrumbs array
    $breadcrumbs = [];
    
    // Always start with Home
    $breadcrumbs[] = [
        'title' => 'Home',
        'url' => '/'
    ];
    
    // If we're on a page with a parent, add the parent
    if (isset($site_structure[$current_page]) &&
        isset($site_structure[$current_page]['parent']) &&
        isset($site_structure[$site_structure[$current_page]['parent']])) {
        
        $parent = $site_structure[$current_page]['parent'];
        $breadcrumbs[] = [
            'title' => $site_structure[$parent]['title'],
            'url' => $site_structure[$parent]['url']
        ];
    }
    
    // Add current page
    if (isset($site_structure[$current_page])) {
        $breadcrumbs[] = [
            'title' => $site_structure[$current_page]['title'],
            'url' => $site_structure[$current_page]['url']
        ];
        
        // For search pages, add the search query if available
        if ($current_page === 'search' && isset($_GET['query']) && !empty($_GET['query'])) {
            $search_query = htmlspecialchars(trim($_GET['query']));
            // Update the title to include the search query
            $last_index = count($breadcrumbs) - 1;
            $breadcrumbs[$last_index]['title'] = 'Search: "' . $search_query . '"';
        }
    } else {
        // Fallback for pages not in our structure
        $breadcrumbs[] = [
            'title' => ucfirst($current_page),
            'url' => '/' . $current_page
        ];
    }
    
    // If we're on a post page and have an ID, get the post title
    if (isset($_GET['id']) && $current_page === 'post') {
        $post_id = intval($_GET['id']);
        
        // Try to get post title from database
        require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/connect_db.php';
        
        try {
            $stmt = $conn->prepare("SELECT title FROM blog_posts WHERE post_id = ?");
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Replace the last breadcrumb with the post title
                $last_index = count($breadcrumbs) - 1;
                $breadcrumbs[$last_index]['title'] = $row['title'];
            }
        } catch (Exception $e) {
            // If there's an error, just use the default breadcrumb
        }
    }
    
    // If we're on a category page and have a category ID, get the category name
    if (isset($_GET['category_id']) && $current_page === 'category') {
        $category_id = intval($_GET['category_id']);
        
        // Try to get category name from database
        require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/connect_db.php';
        
        try {
            $stmt = $conn->prepare("SELECT name FROM categories WHERE category_id = ?");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // Replace the last breadcrumb with the category name
                $last_index = count($breadcrumbs) - 1;
                $breadcrumbs[$last_index]['title'] = $row['name'];
            }
        } catch (Exception $e) {
            // If there's an error, just use the default breadcrumb
        }
    }
    
    return generate_breadcrumbs($breadcrumbs);
}