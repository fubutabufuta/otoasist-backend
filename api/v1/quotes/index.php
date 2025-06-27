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

class QuoteAPI
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
                default:
                    $this->sendResponse(405, ['error' => 'Method not allowed']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, ['error' => $e->getMessage()]);
        }
    }

    private function handleGet($segments)
    {
        $action = $segments[4] ?? '';
        $id = $segments[5] ?? null;

        switch ($action) {
            case 'requests':
                if ($id) {
                    $this->getQuoteRequest($id);
                } else {
                    $this->getQuoteRequests();
                }
                break;
            case 'user':
                $userId = $_GET['user_id'] ?? null;
                if ($userId) {
                    $this->getUserQuoteRequests($userId);
                } else {
                    $this->sendResponse(400, ['error' => 'User ID required']);
                }
                break;
            case 'provider':
                $providerId = $_GET['provider_id'] ?? null;
                if ($providerId) {
                    $this->getProviderQuotes($providerId);
                } else {
                    $this->sendResponse(400, ['error' => 'Provider ID required']);
                }
                break;
            default:
                $this->sendResponse(404, ['error' => 'Endpoint not found']);
        }
    }

    private function handlePost($segments)
    {
        $action = $segments[4] ?? '';
        $data = json_decode(file_get_contents('php://input'), true);

        switch ($action) {
            case 'request':
                $this->createQuoteRequest($data);
                break;
            case 'quote':
                $this->createQuote($data);
                break;
            default:
                $this->sendResponse(404, ['error' => 'Endpoint not found']);
        }
    }

    private function handlePut($segments)
    {
        $action = $segments[4] ?? '';
        $id = $segments[5] ?? null;
        $data = json_decode(file_get_contents('php://input'), true);

        switch ($action) {
            case 'quote':
                if ($id) {
                    $this->updateQuoteStatus($id, $data);
                } else {
                    $this->sendResponse(400, ['error' => 'Quote ID required']);
                }
                break;
            default:
                $this->sendResponse(404, ['error' => 'Endpoint not found']);
        }
    }

    // Teklif talebi oluştur
    public function createQuoteRequest($data)
    {
        try {
            // Validate required fields
            if (!isset($data['user_id'], $data['vehicle_id'], $data['title'])) {
                $this->sendResponse(400, ['error' => 'Gerekli alanlar eksik']);
                return;
            }

            // Handle service_type_id or service_type
            $serviceTypeId = null;
            if (isset($data['service_type_id'])) {
                $serviceTypeId = $data['service_type_id'];
            } elseif (isset($data['service_type'])) {
                // Convert service type name to ID
                $serviceTypeMap = [
                    'Servis' => 1,
                    'Sigorta' => 2,
                    'Kasko' => 3,
                    'Lastik' => 4,
                    'Yağ Değişimi' => 5,
                    'service' => 1,
                    'insurance' => 2,
                    'kasko' => 3,
                    'tire' => 4,
                    'oil_change' => 5
                ];

                $serviceTypeName = $data['service_type'];
                if (isset($serviceTypeMap[$serviceTypeName])) {
                    $serviceTypeId = $serviceTypeMap[$serviceTypeName];
                } else {
                    // Try to find in database
                    $stmt = $this->conn->prepare("SELECT id FROM service_types WHERE name = ? OR LOWER(name) = LOWER(?)");
                    $stmt->execute([$serviceTypeName, $serviceTypeName]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result) {
                        $serviceTypeId = $result['id'];
                    }
                }
            }

            if (!$serviceTypeId) {
                $this->sendResponse(400, ['error' => 'Geçerli bir servis türü belirtiniz']);
                return;
            }

            // Check if service type is eligible for quotes
            $eligibleTypes = ['Servis', 'Sigorta', 'Kasko', 'Lastik', 'Yağ Değişimi'];
            $stmt = $this->conn->prepare("SELECT name FROM service_types WHERE id = ?");
            $stmt->execute([$serviceTypeId]);
            $serviceType = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$serviceType || !in_array($serviceType['name'], $eligibleTypes)) {
                $this->sendResponse(400, ['error' => 'Bu hizmet türü için teklif alınamaz']);
                return;
            }

            // Create quote request
            $stmt = $this->conn->prepare("
                INSERT INTO quote_requests 
                (user_id, vehicle_id, service_type_id, title, description, user_notes, share_phone, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");

            $stmt->execute([
                $data['user_id'],
                $data['vehicle_id'],
                $serviceTypeId,
                $data['title'],
                $data['description'] ?? '',
                $data['user_notes'] ?? '',
                $data['share_phone'] ?? false
            ]);

            $requestId = $this->conn->lastInsertId();

            // Send notifications to eligible providers
            $this->notifyProviders($requestId, $serviceTypeId);

            $this->sendResponse(201, [
                'success' => true,
                'message' => 'Teklif talebi başarıyla oluşturuldu',
                'request_id' => $requestId
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, ['error' => $e->getMessage()]);
        }
    }

    // Servis sağlayıcılara bildirim gönder
    private function notifyProviders($requestId, $serviceTypeId)
    {
        // Get active providers for this service type
        $stmt = $this->conn->prepare("
            SELECT DISTINCT sp.id, sp.user_id, sp.company_name, u.email
            FROM service_providers sp
            JOIN provider_services ps ON sp.id = ps.provider_id
            JOIN subscriptions sub ON sp.id = sub.provider_id
            JOIN users u ON sp.user_id = u.id
            WHERE ps.service_type_id = ? 
            AND sp.is_active = 1 
            AND ps.is_active = 1
            AND sub.is_active = 1 
            AND sub.end_date > NOW()
        ");

        $stmt->execute([$serviceTypeId]);
        $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Send email notifications (simplified)
        foreach ($providers as $provider) {
            $this->sendEmailNotification($provider, $requestId);

            // Create in-app notification
            $notifStmt = $this->conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, action_url)
                VALUES (?, ?, ?, 'info', ?)
            ");

            $notifStmt->execute([
                $provider['user_id'],
                'Yeni Teklif Talebi',
                'Size uygun yeni bir teklif talebi var. Hemen inceleyin!',
                '/provider/quotes/' . $requestId
            ]);
        }
    }

    private function sendEmailNotification($provider, $requestId)
    {
        // Email sending logic would go here
        // For now, we'll just log it
        error_log("Email sent to {$provider['email']} for quote request {$requestId}");
    }

    // Teklif ver
    public function createQuote($data)
    {
        try {
            if (!isset($data['request_id'], $data['provider_id'], $data['title'], $data['price'])) {
                $this->sendResponse(400, ['error' => 'Gerekli alanlar eksik']);
                return;
            }

            // Check if provider is eligible
            $stmt = $this->conn->prepare("
                SELECT qr.*, sp.id as provider_id
                FROM quote_requests qr
                JOIN service_providers sp ON sp.id = ?
                JOIN provider_services ps ON sp.id = ps.provider_id
                JOIN subscriptions sub ON sp.id = sub.provider_id
                WHERE qr.id = ? 
                AND ps.service_type_id = qr.service_type_id
                AND sp.is_active = 1
                AND sub.is_active = 1
                AND sub.end_date > NOW()
            ");

            $stmt->execute([$data['provider_id'], $data['request_id']]);

            if ($stmt->rowCount() === 0) {
                $this->sendResponse(403, ['error' => 'Bu teklif talebine yanıt verme yetkiniz yok']);
                return;
            }

            // Create quote
            $validUntil = date('Y-m-d H:i:s', strtotime('+7 days'));

            $stmt = $this->conn->prepare("
                INSERT INTO quotes 
                (request_id, provider_id, title, description, price, estimated_duration, valid_until, provider_notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['request_id'],
                $data['provider_id'],
                $data['title'],
                $data['description'] ?? '',
                $data['price'],
                $data['estimated_duration'] ?? '',
                $validUntil,
                $data['provider_notes'] ?? ''
            ]);

            $quoteId = $this->conn->lastInsertId();

            // Notify user about new quote
            $this->notifyUserAboutQuote($data['request_id'], $quoteId);

            $this->sendResponse(201, [
                'success' => true,
                'message' => 'Teklif başarıyla gönderildi',
                'quote_id' => $quoteId
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, ['error' => $e->getMessage()]);
        }
    }

    private function notifyUserAboutQuote($requestId, $quoteId)
    {
        // Get user from request
        $stmt = $this->conn->prepare("SELECT user_id FROM quote_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($request) {
            $stmt = $this->conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, action_url)
                VALUES (?, ?, ?, 'success', ?)
            ");

            $stmt->execute([
                $request['user_id'],
                'Yeni Teklif Aldınız!',
                'Talebiniz için yeni bir teklif geldi. İnceleyin ve karşılaştırın.',
                '/quotes/request/' . $requestId
            ]);
        }
    }

    // Kullanıcının teklif taleplerini getir
    public function getUserQuoteRequests($userId)
    {
        $stmt = $this->conn->prepare("
            SELECT qr.*, 
                   v.brand, v.model, v.year, v.plate,
                   st.name as service_name,
                   COUNT(q.id) as quote_count
            FROM quote_requests qr
            LEFT JOIN vehicles v ON qr.vehicle_id = v.id
            LEFT JOIN service_types st ON qr.service_type_id = st.id
            LEFT JOIN quotes q ON qr.id = q.request_id
            WHERE qr.user_id = ?
            GROUP BY qr.id
            ORDER BY qr.created_at DESC
        ");

        $stmt->execute([$userId]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->sendResponse(200, ['success' => true, 'data' => $requests]);
    }

    // Servis sağlayıcının tekliflerini getir
    public function getProviderQuotes($providerId)
    {
        $stmt = $this->conn->prepare("
            SELECT q.*, qr.title as request_title, qr.description as request_description,
                   qr.user_notes, qr.share_phone,
                   u.full_name as user_name, u.phone as user_phone,
                   v.brand, v.model, v.year, v.plate,
                   st.name as service_name
            FROM quotes q
            JOIN quote_requests qr ON q.request_id = qr.id
            JOIN users u ON qr.user_id = u.id
            JOIN vehicles v ON qr.vehicle_id = v.id
            JOIN service_types st ON qr.service_type_id = st.id
            WHERE q.provider_id = ?
            ORDER BY q.created_at DESC
        ");

        $stmt->execute([$providerId]);
        $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Hide phone if not shared
        foreach ($quotes as &$quote) {
            if (!$quote['share_phone']) {
                $quote['user_phone'] = null;
            }
        }

        $this->sendResponse(200, ['success' => true, 'data' => $quotes]);
    }

    private function sendResponse($code, $data)
    {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

$api = new QuoteAPI();
$api->handleRequest();
