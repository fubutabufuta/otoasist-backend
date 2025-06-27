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

class QuoteRequestAPI
{
    private $conn;

    public function __construct()
    {
        try {
            $database = new Database();
            $this->conn = $database->getConnection();
            error_log("Database connection successful");
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            $this->conn = null; // Continue without database for demo purposes
        }
    }

    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        try {
            switch ($method) {
                case 'GET':
                    $this->getUserQuoteRequests();
                    break;
                case 'POST':
                    $this->createQuoteRequest();
                    break;
                default:
                    $this->sendResponse(405, ['error' => 'Method not allowed']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, ['error' => $e->getMessage()]);
        }
    }

    // Teklif talebi oluştur
    public function createQuoteRequest()
    {
        $rawInput = file_get_contents('php://input');
        error_log("=== Quote Request Debug ===");
        error_log("Raw input: " . $rawInput);

        // Check if input is empty
        if (empty($rawInput)) {
            $this->sendResponse(400, [
                'error' => 'Request body is empty',
                'debug' => 'No JSON data received'
            ]);
            return;
        }

        $data = json_decode($rawInput, true);

        // Check JSON parsing error
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendResponse(400, [
                'error' => 'Invalid JSON: ' . json_last_error_msg(),
                'debug' => 'Raw input: ' . substr($rawInput, 0, 200),
                'json_error_code' => json_last_error()
            ]);
            return;
        }

        // Check if data is null or not an array
        if ($data === null || !is_array($data)) {
            $this->sendResponse(400, [
                'error' => 'Invalid data format',
                'debug' => 'Parsed data is null or not an array',
                'data_type' => gettype($data),
                'raw_input' => substr($rawInput, 0, 200)
            ]);
            return;
        }

        error_log("Parsed data: " . print_r($data, true));

        try {
            // Validate required fields
            $required = ['user_id', 'vehicle_id', 'service_type', 'title'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->sendResponse(400, ['error' => "Zorunlu alan eksik: $field"]);
                    return;
                }
            }

            error_log("All required fields present");

            // Validate service_type enum
            $validServiceTypes = ['maintenance', 'repair', 'insurance', 'parts', 'towing', 'other'];
            $serviceType = $data['service_type'];

            // Map common service types to valid enum values
            $serviceTypeMapping = [
                'service' => 'maintenance',
                'servis' => 'maintenance',
                'bakim' => 'maintenance',
                'sigorta' => 'insurance',
                'kasko' => 'insurance',
                'lastik' => 'parts',
                'parca' => 'parts',
                'yag' => 'maintenance'
            ];

            if (isset($serviceTypeMapping[strtolower($serviceType)])) {
                $serviceType = $serviceTypeMapping[strtolower($serviceType)];
            }

            if (!in_array($serviceType, $validServiceTypes)) {
                $serviceType = 'maintenance'; // Default fallback
            }

            error_log("Service type mapped to: " . $serviceType);

            // Check for existing quote request (prevent duplicates)
            if ($this->conn !== null) {
                $stmt = $this->conn->prepare("
                    SELECT id, status, created_at 
                    FROM quote_requests 
                    WHERE user_id = ? AND vehicle_id = ? AND service_type = ? 
                    AND status IN ('pending', 'quoted') 
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$data['user_id'], $data['vehicle_id'], $serviceType]);
                $existing = $stmt->fetch();

                if ($existing) {
                    error_log("Duplicate quote request found: " . print_r($existing, true));
                    $this->sendResponse(409, [
                        'error' => 'Bu araç ve servis türü için zaten bekleyen bir teklif talebiniz var',
                        'existing_request' => $existing
                    ]);
                    return;
                }
            }

            // Create quote request
            if ($this->conn !== null) {
                try {
                    // Detect database type and use appropriate syntax
                    $driver = $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME);
                    $timestampFunction = ($driver === 'sqlite') ? "datetime('now')" : "NOW()";

                    // Add missing columns if they don't exist
                    try {
                        $this->conn->exec("ALTER TABLE quote_requests ADD COLUMN user_notes TEXT");
                    } catch (PDOException $e) {
                        // Column might already exist, ignore error
                    }

                    try {
                        $this->conn->exec("ALTER TABLE quote_requests ADD COLUMN share_phone TINYINT(1) DEFAULT 0");
                    } catch (PDOException $e) {
                        // Column might already exist, ignore error
                    }

                    $stmt = $this->conn->prepare("
                        INSERT INTO quote_requests 
                        (user_id, vehicle_id, service_type, title, description, user_notes, share_phone, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', $timestampFunction)
                    ");

                    $result = $stmt->execute([
                        $data['user_id'],
                        $data['vehicle_id'],
                        $serviceType,
                        $data['title'],
                        $data['description'] ?? '',
                        $data['user_notes'] ?? '',
                        $data['share_phone'] ? 1 : 0
                    ]);

                    if (!$result) {
                        error_log("Database insert failed: " . print_r($stmt->errorInfo(), true));
                        $this->sendResponse(500, ['error' => 'Teklif talebi kaydedilemedi: ' . $stmt->errorInfo()[2]]);
                        return;
                    }

                    $requestId = $this->conn->lastInsertId();
                    error_log("Quote request created with ID: " . $requestId);

                    $this->sendResponse(201, [
                        'success' => true,
                        'message' => 'Teklif talebi başarıyla oluşturuldu',
                        'request_id' => $requestId,
                        'service_type' => $serviceType
                    ]);
                } catch (Exception $dbError) {
                    error_log("Database operation failed: " . $dbError->getMessage());
                    $this->sendResponse(500, ['error' => 'Veritabanı hatası: ' . $dbError->getMessage()]);
                }
            } else {
                // No database connection - return demo response
                error_log("No database connection, returning demo response");
                $this->sendResponse(201, [
                    'success' => true,
                    'message' => 'Teklif talebi başarıyla oluşturuldu (demo mode)',
                    'request_id' => rand(1000, 9999),
                    'debug' => 'Database not available, using demo mode'
                ]);
            }
        } catch (Exception $e) {
            error_log("Quote request error: " . $e->getMessage());
            $this->sendResponse(500, ['error' => 'Sunucu hatası: ' . $e->getMessage()]);
        }
    }

    // Kullanıcının teklif taleplerini getir
    public function getUserQuoteRequests()
    {
        $userId = $_GET['user_id'] ?? null;
        if (!$userId) {
            $this->sendResponse(400, ['error' => 'User ID gerekli']);
            return;
        }

        error_log("Getting quote requests for user ID: " . $userId);

        try {
            if ($this->conn === null) {
                error_log("No database connection, returning empty data");
                $this->sendResponse(200, ['success' => true, 'data' => []]);
                return;
            }

            $stmt = $this->conn->prepare("
                SELECT qr.*, 
                       v.brand, v.model, v.year, v.plate,
                       qr.service_type as service_name,
                       0 as quote_count
                FROM quote_requests qr
                LEFT JOIN vehicles v ON qr.vehicle_id = v.id
                WHERE qr.user_id = ?
                ORDER BY qr.created_at DESC
            ");

            $stmt->execute([$userId]);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("Found " . count($requests) . " quote requests for user " . $userId);

            $this->sendResponse(200, ['success' => true, 'data' => $requests]);
        } catch (Exception $e) {
            error_log("Get user quotes error: " . $e->getMessage());
            // Fallback to empty data
            $this->sendResponse(200, ['success' => true, 'data' => []]);
        }
    }

    // Servis sağlayıcılara bildirim gönder
    private function notifyProviders($requestId, $serviceTypeId)
    {
        try {
            // Get active providers for this service type - handle if tables don't exist
            $stmt = $this->conn->prepare("
                SELECT DISTINCT u.id as user_id, u.email, 'Genel Servis' as company_name
                FROM users u
                WHERE u.user_type = 'provider' 
                AND u.is_active = 1
                LIMIT 5
            ");

            $stmt->execute();
            $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Send notifications to providers
            foreach ($providers as $provider) {
                $this->sendEmailNotification($provider, $requestId);

                // Try to create in-app notification if notifications table exists
                try {
                    $notifStmt = $this->conn->prepare("
                        INSERT INTO notifications (user_id, title, message, type, action_url, created_at)
                        VALUES (?, ?, ?, 'info', ?, NOW())
                    ");

                    $notifStmt->execute([
                        $provider['user_id'],
                        'Yeni Teklif Talebi',
                        'Size uygun yeni bir teklif talebi var. Hemen inceleyin!',
                        '/provider/quotes/' . $requestId
                    ]);
                } catch (Exception $e) {
                    // Ignore notification errors
                    error_log("Notification error: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            // Log error but don't fail the main request
            error_log("Error notifying providers: " . $e->getMessage());
        }
    }

    private function sendEmailNotification($provider, $requestId)
    {
        // Email sending logic would go here
        // For now, we'll just log it
        error_log("Email sent to {$provider['email']} for quote request {$requestId}");
    }

    private function sendResponse($code, $data)
    {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }
}

$api = new QuoteRequestAPI();
$api->handleRequest();
