<?php
/**
 * Automatic Breadcrumb System
 * Implements Section 3.1 requirement for "automatic breadcrumb generation"
 * Uses recursive page hierarchy from sys_pages table (Section 8)
 */
function generateBreadcrumbs($pdo, $current_page) {
    // Get current page details
    $page_query = "SELECT page_id, page_title, page_url, parent_id 
                  FROM sys_pages 
                  WHERE page_url = ?";
    $stmt = $pdo->prepare($page_query);
    $stmt->execute([$current_page]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current) return '';
    
    $breadcrumbs = [];
    
    // Start from current page and work up to root
    $page = $current;
    while ($page) {
        array_unshift($breadcrumbs, $page);
        
        // Break if at root (parent_id = 0)
        if ($page['parent_id'] == 0) break;
        
        // Get parent page
        $parent_query = "SELECT page_id, page_title, page_url, parent_id 
                        FROM sys_pages 
                        WHERE page_id = ?";
        $stmt = $pdo->prepare($parent_query);
        $stmt->execute([$page['parent_id']]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Generate HTML
    $output = '<ol class="breadcrumb float-sm-right">';
    
    // Home link
    $base = defined('BASE_URL') ? BASE_URL : '';
    $output .= '<li class="breadcrumb-item"><a href="' . $base . '/dashboard.php">Home</a></li>';
    
    // Add each breadcrumb
    foreach ($breadcrumbs as $index => $crumb) {
        $is_last = ($index === count($breadcrumbs) - 1);
        
        $url = htmlspecialchars($crumb['page_url']);
        if (strpos($url, 'http') !== 0 && strpos($url, '#') !== 0 && defined('BASE_URL')) {
            $url = rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
        }
        
        $output .= '<li class="breadcrumb-item ' . ($is_last ? 'active' : '') . '">';
        $output .= $is_last ? htmlspecialchars($crumb['page_title']) 
                           : '<a href="' . $url . '">' . htmlspecialchars($crumb['page_title']) . '</a>';
        $output .= '</li>';
    }
    
    $output .= '</ol>';
    return $output;
}

// Usage in header.php:
// <?= generateBreadcrumbs($pdo, basename($_SERVER['PHP_SELF'])) ?>