<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

class AdminDashboard
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getDashboardStats()
    {
        try {
            $stats = [];

            // Total users
            $stmt = $this->conn->query("SELECT COUNT(*) as count FROM users WHERE role_id = 1");
            $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Total providers
            $stmt = $this->conn->query("SELECT COUNT(*) as count FROM service_providers WHERE is_active = 1");
            $stats['total_providers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Active subscriptions
            $stmt = $this->conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE is_active = 1 AND end_date > NOW()");
            $stats['active_subscriptions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Total quotes this month
            $stmt = $this->conn->query("SELECT COUNT(*) as count FROM quote_requests WHERE MONTH(created_at) = MONTH(NOW())");
            $stats['monthly_quotes'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Revenue this month
            $stmt = $this->conn->query("
                SELECT COALESCE(SUM(sp.price), 0) as revenue 
                FROM subscriptions s 
                JOIN subscription_packages sp ON s.package_id = sp.id 
                WHERE MONTH(s.created_at) = MONTH(NOW()) AND s.payment_status = 'paid'
            ");
            $stats['monthly_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

            // Recent activities
            $stmt = $this->conn->query("
                SELECT 'user_registered' as type, u.full_name as title, u.created_at as date
                FROM users u 
                WHERE u.role_id = 1 
                ORDER BY u.created_at DESC 
                LIMIT 5
            ");
            $stats['recent_activities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->sendResponse(200, ['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            $this->sendResponse(500, ['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function sendResponse($code, $data)
    {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

$dashboard = new AdminDashboard();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $dashboard->getDashboardStats();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
