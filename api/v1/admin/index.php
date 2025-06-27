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

class AdminAPI
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));

        // Admin authentication check
        if (!$this->authenticateAdmin()) {
            $this->sendResponse(401, ['error' => 'Yetkisiz erişim']);
            return;
        }

        try {
            switch ($method) {
                case 'GET':
                    $this->handleGet($segments);
                    break;
                case 'POST':
                    $this->handlePost($segments);
                    break;
                case 'PUT':
                    $this->handlePut($segments);
                    break;
                case 'DELETE':
                    $this->handleDelete($segments);
                    break;
                default:
                    $this->sendResponse(405, ['error' => 'Method not allowed']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, ['error' => $e->getMessage()]);
        }
    }

    private function authenticateAdmin()
    {
        // Simple token-based authentication
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? '';

        if (empty($token)) {
            return false;
        }

        // Remove 'Bearer ' prefix
        $token = str_replace('Bearer ', '', $token);

        // Verify admin token (simplified)
        $stmt = $this->conn->prepare("
            SELECT u.id, u.role_id 
            FROM users u 
            WHERE u.phone = ? AND u.role_id = 3
        ");

        // For demo, we'll use phone as token
        $stmt->execute([$token]);
        return $stmt->rowCount() > 0;
    }

    private function handleGet($segments)
    {
        $action = $segments[3] ?? '';

        switch ($action) {
            case 'dashboard':
                $this->getDashboardStats();
                break;
            case 'users':
                $this->getUsers();
                break;
            case 'providers':
                $this->getProviders();
                break;
            case 'packages':
                $this->getPackages();
                break;
            case 'services':
                $this->getServices();
                break;
            case 'subscriptions':
                $this->getSubscriptions();
                break;
            case 'sliders':
                $this->getSliders();
                break;
            case 'news':
                $this->getNews();
                break;
            case 'campaigns':
                $this->getCampaigns();
                break;
            case 'quotes':
                $this->getQuotes();
                break;
            case 'quote-requests':
                $this->getQuoteRequests();
                break;
            case 'provider-requests':
                $this->getProviderRequests();
                break;
            default:
                $this->sendResponse(404, ['error' => 'Endpoint not found']);
        }
    }

    private function handlePost($segments)
    {
        $action = $segments[3] ?? '';
        $data = json_decode(file_get_contents('php://input'), true);

        switch ($action) {
            case 'packages':
                $this->createPackage($data);
                break;
            case 'services':
                $this->createService($data);
                break;
            case 'sliders':
                $this->createSlider($data);
                break;
            case 'news':
                $this->createNews($data);
                break;
            case 'assign-package':
                $this->assignPackageToProvider($data);
                break;
            default:
                $this->sendResponse(404, ['error' => 'Endpoint not found']);
        }
    }

    private function handlePut($segments)
    {
        $action = $segments[3] ?? '';
        $id = $segments[4] ?? null;
        $data = json_decode(file_get_contents('php://input'), true);

        switch ($action) {
            case 'users':
                if ($id) {
                    $this->updateUser($id, $data);
                } else {
                    $this->sendResponse(400, ['error' => 'User ID required']);
                }
                break;
            case 'providers':
                if ($id) {
                    $this->updateProvider($id, $data);
                } else {
                    $this->sendResponse(400, ['error' => 'Provider ID required']);
                }
                break;
            case 'packages':
                if ($id) {
                    $this->updatePackage($id, $data);
                } else {
                    $this->sendResponse(400, ['error' => 'Package ID required']);
                }
                break;
            case 'sliders':
                if ($id) {
                    $this->updateSlider($id, $data);
                } else {
                    $this->sendResponse(400, ['error' => 'Slider ID required']);
                }
                break;
            case 'news':
                if ($id) {
                    $this->updateNews($id, $data);
                } else {
                    $this->sendResponse(400, ['error' => 'News ID required']);
                }
                break;
            case 'quotes':
                if ($id) {
                    $this->updateQuote($id, $data);
                } else {
                    $this->sendResponse(400, ['error' => 'Quote ID required']);
                }
                break;
            case 'quote-requests':
                if ($id) {
                    $this->updateQuoteRequest($id, $data);
                } else {
                    $this->sendResponse(400, ['error' => 'Quote Request ID required']);
                }
                break;
            default:
                $this->sendResponse(404, ['error' => 'Endpoint not found']);
        }
    }

    private function handleDelete($segments)
    {
        $action = $segments[3] ?? '';
        $id = $segments[4] ?? null;

        if (!$id) {
            $this->sendResponse(400, ['error' => 'ID required']);
            return;
        }

        switch ($action) {
            case 'users':
                $this->deleteUser($id);
                break;
            case 'providers':
                $this->deleteProvider($id);
                break;
            case 'packages':
                $this->deletePackage($id);
                break;
            case 'sliders':
                $this->deleteSlider($id);
                break;
            case 'news':
                $this->deleteNews($id);
                break;
            case 'quotes':
                $this->deleteQuote($id);
                break;
            case 'quote-requests':
                $this->deleteQuoteRequest($id);
                break;
            default:
                $this->sendResponse(404, ['error' => 'Endpoint not found']);
        }
    }

    private function getDashboardStats()
    {
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

        // Revenue this month (from subscriptions)
        $stmt = $this->conn->query("
            SELECT COALESCE(SUM(sp.price), 0) as revenue 
            FROM subscriptions s 
            JOIN subscription_packages sp ON s.package_id = sp.id 
            WHERE MONTH(s.created_at) = MONTH(NOW()) AND s.payment_status = 'paid'
        ");
        $stats['monthly_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

        $this->sendResponse(200, ['data' => $stats]);
    }

    private function getUsers()
    {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $offset = ($page - 1) * $limit;

        $stmt = $this->conn->prepare("
            SELECT u.*, ur.name as role_name, 
                   COUNT(v.id) as vehicle_count,
                   COUNT(qr.id) as quote_count
            FROM users u
            LEFT JOIN user_roles ur ON u.role_id = ur.id
            LEFT JOIN vehicles v ON u.id = v.user_id
            LEFT JOIN quote_requests qr ON u.id = qr.user_id
            WHERE u.role_id = 1
            GROUP BY u.id
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        $stmt->execute(['limit' => (int)$limit, 'offset' => (int)$offset]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM users WHERE role_id = 1");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $this->sendResponse(200, [
            'data' => $users,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    private function getProviders()
    {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $offset = ($page - 1) * $limit;

        $stmt = $this->conn->prepare("
            SELECT sp.*, u.full_name, u.phone, u.email,
                   s.end_date as subscription_end,
                   sp_pkg.name as package_name,
                   COUNT(DISTINCT ps.service_type_id) as service_count,
                   COUNT(DISTINCT q.id) as quote_count
            FROM service_providers sp
            LEFT JOIN users u ON sp.user_id = u.id
            LEFT JOIN subscriptions s ON sp.id = s.provider_id AND s.is_active = 1
            LEFT JOIN subscription_packages sp_pkg ON s.package_id = sp_pkg.id
            LEFT JOIN provider_services ps ON sp.id = ps.provider_id
            LEFT JOIN quotes q ON sp.id = q.provider_id
            GROUP BY sp.id
            ORDER BY sp.created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        $stmt->execute(['limit' => (int)$limit, 'offset' => (int)$offset]);
        $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM service_providers");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $this->sendResponse(200, [
            'data' => $providers,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    private function getPackages()
    {
        // Create package_services table if not exists
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS package_services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                package_id INT NOT NULL,
                service_type_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (package_id) REFERENCES subscription_packages(id) ON DELETE CASCADE,
                FOREIGN KEY (service_type_id) REFERENCES service_types(id) ON DELETE CASCADE,
                UNIQUE KEY unique_package_service (package_id, service_type_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $stmt = $this->conn->query("
            SELECT sp.*, 
                   COUNT(DISTINCT s.id) as subscription_count,
                   GROUP_CONCAT(st.name) as included_services
            FROM subscription_packages sp
            LEFT JOIN subscriptions s ON sp.id = s.package_id AND s.is_active = 1
            LEFT JOIN package_services ps ON sp.id = ps.package_id
            LEFT JOIN service_types st ON ps.service_type_id = st.id
            GROUP BY sp.id
            ORDER BY sp.price ASC
        ");

        $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->sendResponse(200, ['data' => $packages]);
    }

    private function getServices()
    {
        $stmt = $this->conn->query("
            SELECT st.*, 
                   COUNT(DISTINCT ps.provider_id) as provider_count,
                   COUNT(DISTINCT qr.id) as request_count
            FROM service_types st
            LEFT JOIN provider_services ps ON st.id = ps.service_type_id
            LEFT JOIN quote_requests qr ON st.id = qr.service_type_id
            GROUP BY st.id
            ORDER BY st.name ASC
        ");

        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->sendResponse(200, ['data' => $services]);
    }

    private function createPackage($data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO subscription_packages (name, description, price, duration_months, max_requests_per_month)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['duration_months'],
            $data['max_requests_per_month']
        ]);

        $packageId = $this->conn->lastInsertId();

        // Add services to package
        if (!empty($data['service_ids'])) {
            $stmt = $this->conn->prepare("INSERT INTO package_services (package_id, service_type_id) VALUES (?, ?)");
            foreach ($data['service_ids'] as $serviceId) {
                $stmt->execute([$packageId, $serviceId]);
            }
        }

        $this->sendResponse(201, ['message' => 'Paket başarıyla oluşturuldu', 'id' => $packageId]);
    }

    private function createService($data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO service_types (name, description, icon)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['icon']
        ]);

        $serviceId = $this->conn->lastInsertId();
        $this->sendResponse(201, ['message' => 'Hizmet başarıyla oluşturuldu', 'id' => $serviceId]);
    }

    private function sendResponse($code, $data)
    {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // ====== KULLANICI YÖNETİMİ ======

    private function updateUser($id, $data)
    {
        $stmt = $this->conn->prepare("
            UPDATE users 
            SET full_name = ?, email = ?, phone = ?, role_id = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['phone'],
            $data['role_id'],
            $id
        ]);

        $this->sendResponse(200, ['message' => 'Kullanıcı başarıyla güncellendi']);
    }

    private function deleteUser($id)
    {
        // Soft delete - kullanıcıyı deaktif et
        $stmt = $this->conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);

        $this->sendResponse(200, ['message' => 'Kullanıcı başarıyla silindi']);
    }

    // ====== SERVİS SAĞLAYICI YÖNETİMİ ======

    private function updateProvider($id, $data)
    {
        $stmt = $this->conn->prepare("
            UPDATE service_providers 
            SET company_name = ?, contact_person = ?, phone = ?, email = ?, 
                address = ?, city = ?, is_verified = ?, is_active = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['company_name'],
            $data['contact_person'],
            $data['phone'],
            $data['email'],
            $data['address'],
            $data['city'],
            $data['is_verified'] ?? 0,
            $data['is_active'] ?? 1,
            $id
        ]);

        $this->sendResponse(200, ['message' => 'Servis sağlayıcı başarıyla güncellendi']);
    }

    private function deleteProvider($id)
    {
        $stmt = $this->conn->prepare("UPDATE service_providers SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);

        $this->sendResponse(200, ['message' => 'Servis sağlayıcı başarıyla silindi']);
    }

    // ====== PAKET YÖNETİMİ ======

    private function updatePackage($id, $data)
    {
        $stmt = $this->conn->prepare("
            UPDATE subscription_packages 
            SET name = ?, description = ?, price = ?, duration_months = ?, 
                max_requests_per_month = ?, is_active = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['duration_months'],
            $data['max_requests_per_month'],
            $data['is_active'] ?? 1,
            $id
        ]);

        $this->sendResponse(200, ['message' => 'Paket başarıyla güncellendi']);
    }

    private function deletePackage($id)
    {
        $stmt = $this->conn->prepare("UPDATE subscription_packages SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);

        $this->sendResponse(200, ['message' => 'Paket başarıyla silindi']);
    }

    private function assignPackageToProvider($data)
    {
        $providerId = $data['provider_id'];
        $packageId = $data['package_id'];
        $startDate = $data['start_date'] ?? date('Y-m-d H:i:s');

        // Calculate end date based on package duration
        $stmt = $this->conn->prepare("SELECT duration_months FROM subscription_packages WHERE id = ?");
        $stmt->execute([$packageId]);
        $package = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$package) {
            $this->sendResponse(404, ['error' => 'Paket bulunamadı']);
            return;
        }

        $endDate = date('Y-m-d H:i:s', strtotime($startDate . ' +' . $package['duration_months'] . ' months'));

        // Deactivate existing subscriptions
        $stmt = $this->conn->prepare("UPDATE subscriptions SET is_active = 0 WHERE provider_id = ?");
        $stmt->execute([$providerId]);

        // Create new subscription
        $stmt = $this->conn->prepare("
            INSERT INTO subscriptions (provider_id, package_id, start_date, end_date, is_active, payment_status)
            VALUES (?, ?, ?, ?, 1, 'paid')
        ");

        $stmt->execute([$providerId, $packageId, $startDate, $endDate]);

        $this->sendResponse(201, ['message' => 'Paket başarıyla atandı', 'end_date' => $endDate]);
    }

    // ====== ABONELİK YÖNETİMİ ======

    private function getSubscriptions()
    {
        $stmt = $this->conn->query("
            SELECT s.*, sp.name as package_name, sp.price, sp.duration_months,
                   srv.company_name, u.full_name as provider_name
            FROM subscriptions s
            JOIN subscription_packages sp ON s.package_id = sp.id
            JOIN service_providers srv ON s.provider_id = srv.id
            JOIN users u ON srv.user_id = u.id
            ORDER BY s.created_at DESC
        ");

        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->sendResponse(200, ['data' => $subscriptions]);
    }

    // ====== SLIDER YÖNETİMİ ======

    private function getSliders()
    {
        // Create sliders table if not exists
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS sliders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                image_url VARCHAR(500),
                link_url VARCHAR(500),
                is_active TINYINT(1) DEFAULT 1,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $stmt = $this->conn->query("
            SELECT * FROM sliders 
            ORDER BY sort_order ASC, created_at DESC
        ");

        $sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->sendResponse(200, ['data' => $sliders]);
    }

    private function createSlider($data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO sliders (title, description, image_url, link_url, is_active, sort_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['title'],
            $data['description'] ?? '',
            $data['image_url'] ?? '',
            $data['link_url'] ?? '',
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0
        ]);

        $sliderId = $this->conn->lastInsertId();
        $this->sendResponse(201, ['message' => 'Slider başarıyla oluşturuldu', 'id' => $sliderId]);
    }

    private function updateSlider($id, $data)
    {
        $stmt = $this->conn->prepare("
            UPDATE sliders 
            SET title = ?, description = ?, image_url = ?, link_url = ?, 
                is_active = ?, sort_order = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['image_url'],
            $data['link_url'],
            $data['is_active'],
            $data['sort_order'],
            $id
        ]);

        $this->sendResponse(200, ['message' => 'Slider başarıyla güncellendi']);
    }

    private function deleteSlider($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM sliders WHERE id = ?");
        $stmt->execute([$id]);

        $this->sendResponse(200, ['message' => 'Slider başarıyla silindi']);
    }

    // ====== HABER YÖNETİMİ ======

    private function getNews()
    {
        $stmt = $this->conn->query("
            SELECT n.*, 
                   CASE WHEN n.is_sponsored = 1 THEN 'Sponsor' ELSE 'Normal' END as news_type
            FROM news n 
            ORDER BY n.is_sponsored DESC, n.created_at DESC
        ");

        $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->sendResponse(200, ['data' => $news]);
    }

    private function createNews($data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO news (title, content, excerpt, image_url, category, is_featured, is_sponsored, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['title'],
            $data['content'],
            $data['excerpt'] ?? '',
            $data['image_url'] ?? '',
            $data['category'] ?? 'general',
            $data['is_featured'] ?? 0,
            $data['is_sponsored'] ?? 0,
            $data['is_active'] ?? 1
        ]);

        $newsId = $this->conn->lastInsertId();
        $this->sendResponse(201, ['message' => 'Haber başarıyla oluşturuldu', 'id' => $newsId]);
    }

    private function updateNews($id, $data)
    {
        $stmt = $this->conn->prepare("
            UPDATE news 
            SET title = ?, content = ?, excerpt = ?, image_url = ?, 
                category = ?, is_featured = ?, is_sponsored = ?, is_active = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['title'],
            $data['content'],
            $data['excerpt'],
            $data['image_url'],
            $data['category'],
            $data['is_featured'],
            $data['is_sponsored'],
            $data['is_active'],
            $id
        ]);

        $this->sendResponse(200, ['message' => 'Haber başarıyla güncellendi']);
    }

    private function deleteNews($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM news WHERE id = ?");
        $stmt->execute([$id]);

        $this->sendResponse(200, ['message' => 'Haber başarıyla silindi']);
    }

    // ====== KAMPANYA YÖNETİMİ ======

    private function getCampaigns()
    {
        // Implement campaign management if needed
        $this->sendResponse(200, ['data' => [], 'message' => 'Kampanya yönetimi henüz eklenmedi']);
    }

    // ====== TEKLİF YÖNETİMİ ======

    private function getQuotes()
    {
        $stmt = $this->conn->query("
            SELECT q.*, qr.title as request_title, qr.description as request_description,
                   u.full_name as user_name, u.phone as user_phone,
                   sp.company_name as provider_name,
                   v.brand, v.model, v.plate
            FROM quotes q
            JOIN quote_requests qr ON q.request_id = qr.id
            JOIN users u ON qr.user_id = u.id
            JOIN service_providers sp ON q.provider_id = sp.id
            JOIN vehicles v ON qr.vehicle_id = v.id
            ORDER BY q.created_at DESC
        ");

        $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->sendResponse(200, ['data' => $quotes]);
    }

    private function getQuoteRequests()
    {
        $stmt = $this->conn->query("
            SELECT qr.*, u.full_name as user_name, u.phone as user_phone,
                   v.brand, v.model, v.plate,
                   COUNT(q.id) as quote_count
            FROM quote_requests qr
            JOIN users u ON qr.user_id = u.id
            JOIN vehicles v ON qr.vehicle_id = v.id
            LEFT JOIN quotes q ON qr.id = q.request_id
            GROUP BY qr.id
            ORDER BY qr.created_at DESC
        ");

        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->sendResponse(200, ['data' => $requests]);
    }

    private function updateQuote($id, $data)
    {
        $stmt = $this->conn->prepare("
            UPDATE quotes 
            SET title = ?, description = ?, price = ?, status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['price'],
            $data['status'],
            $id
        ]);

        $this->sendResponse(200, ['message' => 'Teklif başarıyla güncellendi']);
    }

    private function updateQuoteRequest($id, $data)
    {
        $stmt = $this->conn->prepare("
            UPDATE quote_requests 
            SET title = ?, description = ?, status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['status'],
            $id
        ]);

        $this->sendResponse(200, ['message' => 'Teklif talebi başarıyla güncellendi']);
    }

    private function deleteQuote($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM quotes WHERE id = ?");
        $stmt->execute([$id]);

        $this->sendResponse(200, ['message' => 'Teklif başarıyla silindi']);
    }

    private function deleteQuoteRequest($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM quote_requests WHERE id = ?");
        $stmt->execute([$id]);

        $this->sendResponse(200, ['message' => 'Teklif talebi başarıyla silindi']);
    }

    // ====== SERVİS SAĞLAYICI TALEPLERİ ======

    private function getProviderRequests()
    {
        // Create provider_requests table if not exists
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS provider_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                provider_id INT NOT NULL,
                request_type ENUM('sponsorship', 'slider', 'advertisement', 'other') NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                content TEXT,
                budget DECIMAL(10,2),
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                admin_notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (provider_id) REFERENCES service_providers(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $stmt = $this->conn->query("
            SELECT pr.*, sp.company_name as provider_name, u.full_name as contact_person
            FROM provider_requests pr
            JOIN service_providers sp ON pr.provider_id = sp.id
            JOIN users u ON sp.user_id = u.id
            ORDER BY pr.created_at DESC
        ");

        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->sendResponse(200, ['data' => $requests]);
    }
}

$api = new AdminAPI();
$api->handleRequest();
