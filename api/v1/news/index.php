<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {

        // Query parameters
        $category = $_GET['category'] ?? null;
        $featured = $_GET['featured'] ?? null;
        $sponsored = $_GET['sponsored'] ?? null;
        $limit = (int)($_GET['limit'] ?? 20);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $limit;

        // Build query
        $where = ['is_active = 1'];
        $params = [];

        if ($category) {
            $where[] = 'category = ?';
            $params[] = $category;
        }

        if ($featured !== null) {
            $where[] = 'is_featured = ?';
            $params[] = $featured == 'true' ? 1 : 0;
        }

        if ($sponsored !== null) {
            $where[] = 'is_sponsored = ?';
            $params[] = $sponsored == 'true' ? 1 : 0;
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countSql = "SELECT COUNT(*) FROM news WHERE $whereClause";
        $countStmt = $conn->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetchColumn();

        // Get news
        $sql = "SELECT id, title, excerpt as description, content, image_url, category, is_featured, is_sponsored, author, publish_date, view_count, created_at 
                FROM news 
                WHERE $whereClause 
                ORDER BY is_featured DESC, created_at DESC 
                LIMIT $limit OFFSET $offset";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $news = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format dates and add metadata
        foreach ($news as &$item) {
            $item['publish_date'] = $item['publish_date'] ? date('Y-m-d H:i:s', strtotime($item['publish_date'])) : date('Y-m-d H:i:s', strtotime($item['created_at']));
            $item['created_at'] = date('Y-m-d H:i:s', strtotime($item['created_at']));
            $item['is_featured'] = (bool)$item['is_featured'];
            $item['is_sponsored'] = (bool)$item['is_sponsored'];
            $item['is_active'] = true;
            $item['view_count'] = (int)$item['view_count'];

            // Add description if not available
            if (empty($item['description']) && !empty($item['content'])) {
                $item['description'] = substr(strip_tags($item['content']), 0, 150) . '...';
            }
        }

        // Return direct array format that Flutter expects
        http_response_code(200);
        echo json_encode($news);
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
