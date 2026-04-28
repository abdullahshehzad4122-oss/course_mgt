<?php
/**
 * Dynamic Menu Builder - Business Logic Layer
 * Implements Section 6.2 and Section 8 (sys_pages table)
 */
class MenuBuilder {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function renderMenu($parent_id = 0) {
        $sql = "SELECT * FROM sys_pages 
                WHERE parent_id = ? 
                ORDER BY page_title";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$parent_id]);
        $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($pages)) return '';
        
        $output = ($parent_id == 0) 
            ? '<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">' 
            : '<ul class="nav nav-treeview">';
        
        foreach ($pages as $page) {
            if (!$this->hasPageAccess($page['page_id'], $page['page_url'])) continue;
            
            $output .= '<li class="nav-item">';
            
            $has_children = $this->hasChildren($page['page_id']);
            
            if ($has_children) {
                $output .= '<a href="#" class="nav-link">
                            <i class="nav-icon ' . htmlspecialchars($page['icon_class']) . '"></i>
                            <p>' . htmlspecialchars($page['page_title']) . '
                            <i class="right fas fa-angle-left"></i>
                            </p>
                            </a>';
                $output .= $this->renderMenu($page['page_id']);
            } else {
                $url = htmlspecialchars($page['page_url']);
                if (strpos($url, 'http') !== 0 && strpos($url, '#') !== 0 && defined('BASE_URL')) {
                    $url = rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
                }
                
                $output .= '<a href="' . $url . '" class="nav-link">
                            <i class="nav-icon ' . htmlspecialchars($page['icon_class']) . '"></i>
                            <p>' . htmlspecialchars($page['page_title']) . '</p>
                            </a>';
            }
            
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        return $output;
    }
    
    private function hasChildren($page_id) {
        $stmt = $this->pdo->prepare("SELECT 1 FROM sys_pages WHERE parent_id = ?");
        $stmt->execute([$page_id]);
        return $stmt->fetchColumn() !== false;
    }
    
    private function hasPageAccess($page_id, $page_url) {
        // For parent groups (url = '#'), check if any child page is accessible
        if ($page_url === '#') {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM sys_pages child
                JOIN role_access ra ON child.page_id = ra.page_id
                WHERE child.parent_id = ? AND ra.role_id = ? AND ra.can_view = 1
            ");
            $stmt->execute([$page_id, $_SESSION['role_id']]);
            return $stmt->fetchColumn() > 0;
        }
        
        // For normal pages, check by page_id
        $stmt = $this->pdo->prepare("
            SELECT can_view FROM role_access
            WHERE role_id = ? AND page_id = ?
        ");
        $stmt->execute([$_SESSION['role_id'], $page_id]);
        return (bool)$stmt->fetchColumn();
    }
}
?>