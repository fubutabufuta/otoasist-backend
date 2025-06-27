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

        // Create campaigns table if not exists
        $conn->exec("CREATE TABLE IF NOT EXISTS campaigns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_url VARCHAR(500),
            start_date DATE,
            end_date DATE,
            discount_percentage DECIMAL(5,2),
            is_active TINYINT(1) DEFAULT 1,
            provider_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (provider_id) REFERENCES service_providers(id) ON DELETE CASCADE
        )");

        // Query parameters
        $active_only = $_GET['active_only'] ?? 'true';
        $provider_id = $_GET['provider_id'] ?? null;
        $limit = (int)($_GET['limit'] ?? 20);
        $page = (int)($_GET['page'] ?? 1);
        $offset = ($page - 1) * $limit;

        // Build query
        $where = [];
        $params = [];

        if ($active_only === 'true') {
            $where[] = 'is_active = 1';
            $where[] = '(end_date IS NULL OR end_date >= CURDATE())';
        }

        if ($provider_id) {
            $where[] = 'provider_id = ?';
            $params[] = $provider_id;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get total count
        $countSql = "SELECT COUNT(*) FROM campaigns $whereClause";
        $countStmt = $conn->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetchColumn();

        // Get campaigns (without provider info for now)
        $sql = "SELECT id, title, description, image_url, start_date, end_date, 
                       discount_percentage, is_active, created_at
                FROM campaigns 
                $whereClause 
                ORDER BY created_at DESC 
                LIMIT $limit OFFSET $offset";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format response
        foreach ($campaigns as &$campaign) {
            $campaign['is_active'] = (bool)$campaign['is_active'];
            $campaign['discount_percentage'] = (float)$campaign['discount_percentage'];
            $campaign['created_at'] = date('Y-m-d H:i:s', strtotime($campaign['created_at']));

            // Check if campaign is currently active
            $now = date('Y-m-d');
            $isCurrentlyActive = true;

            if ($campaign['start_date'] && $campaign['start_date'] > $now) {
                $isCurrentlyActive = false;
            }

            if ($campaign['end_date'] && $campaign['end_date'] < $now) {
                $isCurrentlyActive = false;
            }

            $campaign['is_currently_active'] = $isCurrentlyActive && $campaign['is_active'];

            // Add days remaining
            if ($campaign['end_date']) {
                $endDate = new DateTime($campaign['end_date']);
                $currentDate = new DateTime();
                $daysRemaining = $currentDate->diff($endDate)->days;

                if ($endDate < $currentDate) {
                    $campaign['days_remaining'] = 0;
                    $campaign['status'] = 'expired';
                } else {
                    $campaign['days_remaining'] = $daysRemaining;
                    $campaign['status'] = 'active';
                }
            } else {
                $campaign['days_remaining'] = null;
                $campaign['status'] = 'active';
            }
        }

        // Return direct array format that Flutter expects
        http_response_code(200);
        echo json_encode($campaigns);
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
