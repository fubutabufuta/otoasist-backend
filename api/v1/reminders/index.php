<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once __DIR__ . '/../../../config/database.php';

// GET - Hatırlatıcıları getir
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Veritabanı bağlantısı
        $database = new Database();
        $db = $database->getConnection();

        if ($db === null) {
            http_response_code(500);
            echo json_encode(array("message" => "Veritabanı bağlantı hatası"));
            exit();
        }

        // User ID al
        $user_id = 1; // Demo için sabit
        $headers = getallheaders();
        if ($headers && isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
            $decoded = json_decode(base64_decode($token), true);
            if ($decoded && isset($decoded['user_id'])) {
                $user_id = $decoded['user_id'];
            }
        }

        // URL path'ini kontrol et
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path_parts = explode('/', $path);
        $action = end($path_parts);

        if ($action === 'upcoming' || strpos($path, 'upcoming') !== false) {
            // Yaklaşan yenilemeler için özel query - 1 yıl ekleyerek hesapla
            $query = "
                SELECT 
                    'navigation' as type,
                    v.id as vehicle_id,
                    v.plate,
                    v.brand,
                    v.model,
                    v.year,
                    v.image as vehicle_image,
                    DATE_ADD(v.registration_expiry_date, INTERVAL 1 YEAR) as due_date,
                    'Seyrüsefer Yenileme' as title,
                    DATEDIFF(DATE_ADD(v.registration_expiry_date, INTERVAL 1 YEAR), CURDATE()) as days_remaining,
                    v.registration_expiry_date as original_date
                FROM vehicles v 
                WHERE v.user_id = :user_id 
                    AND v.registration_expiry_date IS NOT NULL 
                    AND DATE_ADD(v.registration_expiry_date, INTERVAL 1 YEAR) >= CURDATE()
                    AND DATEDIFF(DATE_ADD(v.registration_expiry_date, INTERVAL 1 YEAR), CURDATE()) <= 90
                    AND v.is_active = 1
                
                UNION ALL
                
                SELECT 
                    'inspection' as type,
                    v.id as vehicle_id,
                    v.plate,
                    v.brand,
                    v.model,
                    v.year,
                    v.image as vehicle_image,
                    DATE_ADD(v.last_inspection_date, INTERVAL 1 YEAR) as due_date,
                    'Muayene Yenileme' as title,
                    DATEDIFF(DATE_ADD(v.last_inspection_date, INTERVAL 1 YEAR), CURDATE()) as days_remaining,
                    v.last_inspection_date as original_date
                FROM vehicles v 
                WHERE v.user_id = :user_id 
                    AND v.last_inspection_date IS NOT NULL 
                    AND DATE_ADD(v.last_inspection_date, INTERVAL 1 YEAR) >= CURDATE()
                    AND DATEDIFF(DATE_ADD(v.last_inspection_date, INTERVAL 1 YEAR), CURDATE()) <= 90
                    AND v.is_active = 1
                
                UNION ALL
                
                SELECT 
                    'insurance' as type,
                    v.id as vehicle_id,
                    v.plate,
                    v.brand,
                    v.model,
                    v.year,
                    v.image as vehicle_image,
                    DATE_ADD(v.insurance_expiry_date, INTERVAL 1 YEAR) as due_date,
                    'Sigorta Yenileme' as title,
                    DATEDIFF(DATE_ADD(v.insurance_expiry_date, INTERVAL 1 YEAR), CURDATE()) as days_remaining,
                    v.insurance_expiry_date as original_date
                FROM vehicles v 
                WHERE v.user_id = :user_id 
                    AND v.insurance_expiry_date IS NOT NULL 
                    AND DATE_ADD(v.insurance_expiry_date, INTERVAL 1 YEAR) >= CURDATE()
                    AND DATEDIFF(DATE_ADD(v.insurance_expiry_date, INTERVAL 1 YEAR), CURDATE()) <= 90
                    AND v.is_active = 1
                
                UNION ALL
                
                SELECT 
                    'kasko' as type,
                    v.id as vehicle_id,
                    v.plate,
                    v.brand,
                    v.model,
                    v.year,
                    v.image as vehicle_image,
                    DATE_ADD(v.kasko_expiry_date, INTERVAL 1 YEAR) as due_date,
                    'Kasko Yenileme' as title,
                    DATEDIFF(DATE_ADD(v.kasko_expiry_date, INTERVAL 1 YEAR), CURDATE()) as days_remaining,
                    v.kasko_expiry_date as original_date
                FROM vehicles v 
                WHERE v.user_id = :user_id 
                    AND v.kasko_expiry_date IS NOT NULL 
                    AND DATE_ADD(v.kasko_expiry_date, INTERVAL 1 YEAR) >= CURDATE()
                    AND DATEDIFF(DATE_ADD(v.kasko_expiry_date, INTERVAL 1 YEAR), CURDATE()) <= 90
                    AND v.is_active = 1
                
                UNION ALL
                
                SELECT 
                    'service' as type,
                    v.id as vehicle_id,
                    v.plate,
                    v.brand,
                    v.model,
                    v.year,
                    v.image as vehicle_image,
                    DATE_ADD(v.last_service_date, INTERVAL 1 YEAR) as due_date,
                    'Servis Yenileme' as title,
                    DATEDIFF(DATE_ADD(v.last_service_date, INTERVAL 1 YEAR), CURDATE()) as days_remaining,
                    v.last_service_date as original_date
                FROM vehicles v 
                WHERE v.user_id = :user_id 
                    AND v.last_service_date IS NOT NULL 
                    AND DATE_ADD(v.last_service_date, INTERVAL 1 YEAR) >= CURDATE()
                    AND DATEDIFF(DATE_ADD(v.last_service_date, INTERVAL 1 YEAR), CURDATE()) <= 90
                    AND v.is_active = 1
                
                UNION ALL
                
                SELECT 
                    'tire' as type,
                    v.id as vehicle_id,
                    v.plate,
                    v.brand,
                    v.model,
                    v.year,
                    v.image as vehicle_image,
                    DATE_ADD(v.tire_change_date, INTERVAL 1 YEAR) as due_date,
                    'Lastik Değişim Yenileme' as title,
                    DATEDIFF(DATE_ADD(v.tire_change_date, INTERVAL 1 YEAR), CURDATE()) as days_remaining,
                    v.tire_change_date as original_date
                FROM vehicles v 
                WHERE v.user_id = :user_id 
                    AND v.tire_change_date IS NOT NULL 
                    AND DATE_ADD(v.tire_change_date, INTERVAL 1 YEAR) >= CURDATE()
                    AND DATEDIFF(DATE_ADD(v.tire_change_date, INTERVAL 1 YEAR), CURDATE()) <= 90
                    AND v.is_active = 1
                
                UNION ALL
                
                SELECT 
                    'reminder' as type,
                    r.vehicle_id,
                    v.plate,
                    v.brand,
                    v.model,
                    v.year,
                    v.image as vehicle_image,
                    r.date as due_date,
                    r.title,
                    DATEDIFF(r.date, CURDATE()) as days_remaining,
                    r.date as original_date
                FROM reminders r
                JOIN vehicles v ON r.vehicle_id = v.id
                WHERE v.user_id = :user_id 
                    AND r.date >= CURDATE()
                    AND DATEDIFF(r.date, CURDATE()) <= 90
                    AND r.is_completed = 0
                    AND v.is_active = 1
                
                ORDER BY days_remaining ASC, due_date ASC
            ";

            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();

            $renewals = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $status = 'normal';
                if ($row['days_remaining'] < 0) {
                    $status = 'overdue';
                } elseif ($row['days_remaining'] <= 7) {
                    $status = 'urgent';
                } elseif ($row['days_remaining'] <= 30) {
                    $status = 'warning';
                }

                $renewals[] = array(
                    "id" => $row['vehicle_id'] . '_' . $row['type'],
                    "type" => $row['type'],
                    "vehicle_id" => $row['vehicle_id'],
                    "vehicle_plate" => $row['plate'],
                    "vehicle_brand" => $row['brand'],
                    "vehicle_model" => $row['model'],
                    "vehicle_year" => $row['year'],
                    "vehicle_image" => $row['vehicle_image'],
                    "title" => $row['title'],
                    "due_date" => $row['due_date'],
                    "original_date" => $row['original_date'],
                    "days_remaining" => $row['days_remaining'],
                    "status" => $status
                );
            }

            http_response_code(200);
            echo json_encode($renewals);
        } else {
            // vehicle_id parametresini kontrol et
            $vehicle_id_filter = $_GET['vehicle_id'] ?? null;

            if ($vehicle_id_filter) {
                // Belirli araç için hatırlatıcıları getir
                $query = "
                    SELECT 
                        r.*,
                        v.plate as vehicle_plate,
                        v.brand as vehicle_brand,
                        v.model as vehicle_model,
                        v.year as vehicle_year,
                        v.image as vehicle_image
                    FROM reminders r
                    JOIN vehicles v ON r.vehicle_id = v.id
                    WHERE v.user_id = :user_id AND r.vehicle_id = :vehicle_id
                    ORDER BY r.date ASC
                ";

                $stmt = $db->prepare($query);
                $stmt->bindParam(":user_id", $user_id);
                $stmt->bindParam(":vehicle_id", $vehicle_id_filter);
                $stmt->execute();
            } else {
                // Tüm hatırlatıcıları getir
                $query = "
                    SELECT 
                        r.*,
                        v.plate as vehicle_plate,
                        v.brand as vehicle_brand,
                        v.model as vehicle_model,
                        v.year as vehicle_year,
                        v.image as vehicle_image
                    FROM reminders r
                    JOIN vehicles v ON r.vehicle_id = v.id
                    WHERE v.user_id = :user_id
                    ORDER BY r.date ASC
                ";

                $stmt = $db->prepare($query);
                $stmt->bindParam(":user_id", $user_id);
                $stmt->execute();
            }

            $reminders = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $reminders[] = array(
                    "id" => $row['id'],
                    "vehicle_id" => $row['vehicle_id'],
                    "title" => $row['title'],
                    "description" => $row['description'],
                    "date" => $row['date'],
                    "reminder_time" => $row['reminder_time'] ?? '09:00:00',
                    "type" => $row['type'] ?? 'general',
                    "reminder_days" => $row['reminder_days'],
                    "isCompleted" => (bool)$row['is_completed'],
                    "created_at" => $row['created_at'],
                    "updated_at" => $row['updated_at'],
                    "vehicle_plate" => $row['vehicle_plate'],
                    "vehicle_brand" => $row['vehicle_brand'],
                    "vehicle_model" => $row['vehicle_model'],
                    "vehicle_year" => $row['vehicle_year'],
                    "vehicle_image" => $row['vehicle_image']
                );
            }

            http_response_code(200);
            echo json_encode($reminders);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Sunucu hatası: " . $e->getMessage()));
    }
}

// POST - Yeni hatırlatıcı ekle (çoklu destek)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents("php://input");
    $data = json_decode($input);

    // Debug: Gelen veriyi logla
    error_log("Reminder POST data: " . $input);
    error_log("Decoded data: " . print_r($data, true));

    // Hem due_date hem de date kontrolü yap
    $due_date = $data->due_date ?? $data->date ?? null;

    if (empty($data->title) || empty($data->vehicle_id) || empty($due_date)) {
        http_response_code(400);
        echo json_encode(array(
            "message" => "Başlık, araç ID ve yenileme tarihi gereklidir",
            "received" => array(
                "title" => $data->title ?? "MISSING",
                "vehicle_id" => $data->vehicle_id ?? "MISSING",
                "due_date" => $data->due_date ?? "MISSING",
                "date" => $data->date ?? "MISSING"
            )
        ));
        exit();
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        if ($db === null) {
            http_response_code(500);
            echo json_encode(array("message" => "Veritabanı bağlantı hatası"));
            exit();
        }

        $responses = [];

        // Çoklu hatırlatıcı günleri ve saatleri
        $reminder_days = $data->reminder_days ?? [1]; // Varsayılan 1 gün
        $reminder_times = $data->reminder_times ?? ['09:00:00']; // Varsayılan 09:00

        foreach ($reminder_days as $days) {
            foreach ($reminder_times as $reminder_time) {
                // Hatırlatma tarihini hesapla (yenileme tarihinden X gün önce)
                $due_date_obj = new DateTime($due_date);
                $reminder_date = clone $due_date_obj;
                $reminder_date->sub(new DateInterval("P{$days}D"));

                // Değişkenleri hazırla
                $date = $reminder_date->format('Y-m-d');

                // Aynı araç, aynı tarih ve aynı saat için kontrol et
                $check_query = "SELECT COUNT(*) as count FROM reminders 
                               WHERE vehicle_id = :vehicle_id 
                               AND date = :date 
                               AND reminder_time = :reminder_time";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(":vehicle_id", $data->vehicle_id);
                $check_stmt->bindParam(":date", $date);
                $check_stmt->bindParam(":reminder_time", $reminder_time);
                $check_stmt->execute();
                $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

                // Eğer aynı tarih ve saatte hatırlatıcı varsa, atla
                if ($existing['count'] > 0) {
                    error_log("Duplicate reminder skipped: vehicle_id=$data->vehicle_id, date=$date, time=$reminder_time");
                    continue;
                }

                $id = uniqid('reminder_');
                $vehicle_id = $data->vehicle_id;
                $title = $data->title . " ({$days} gün önce)";
                $description = $data->description ?? '';
                $type = $data->type ?? 'general';
                $is_completed = false;

                // Hatırlatıcı ekle
                $query = "INSERT INTO reminders (
                            id, vehicle_id, title, description, date, reminder_time, type, reminder_days, is_completed,
                            created_at, updated_at
                          ) VALUES (
                            :id, :vehicle_id, :title, :description, :date, :reminder_time, :type, :reminder_days, :is_completed,
                            NOW(), NOW()
                          )";

                $stmt = $db->prepare($query);
                $stmt->bindParam(":id", $id);
                $stmt->bindParam(":vehicle_id", $vehicle_id);
                $stmt->bindParam(":title", $title);
                $stmt->bindParam(":description", $description);
                $stmt->bindParam(":date", $date);
                $stmt->bindParam(":reminder_time", $reminder_time);
                $stmt->bindParam(":type", $type);
                $stmt->bindParam(":reminder_days", $days);
                $stmt->bindParam(":is_completed", $is_completed, PDO::PARAM_BOOL);

                if ($stmt->execute()) {
                    // Eklenen hatırlatıcıyı getir
                    $get_query = "SELECT r.*, 
                                         v.plate as vehicle_plate,
                                         v.brand as vehicle_brand,
                                         v.model as vehicle_model,
                                         v.year as vehicle_year,
                                         v.image as vehicle_image
                                  FROM reminders r
                                  LEFT JOIN vehicles v ON r.vehicle_id = v.id
                                  WHERE r.id = :id";
                    $get_stmt = $db->prepare($get_query);
                    $get_stmt->bindParam(":id", $id);
                    $get_stmt->execute();
                    $reminder = $get_stmt->fetch(PDO::FETCH_ASSOC);

                    if ($reminder) {
                        $responses[] = array(
                            "id" => $reminder['id'],
                            "vehicle_id" => $reminder['vehicle_id'],
                            "title" => $reminder['title'],
                            "description" => $reminder['description'],
                            "date" => $reminder['date'],
                            "reminder_time" => $reminder['reminder_time'],
                            "type" => $reminder['type'] ?? 'general',
                            "reminder_days" => $reminder['reminder_days'],
                            "isCompleted" => (bool)$reminder['is_completed'],
                            "created_at" => $reminder['created_at'],
                            "updated_at" => $reminder['updated_at'],
                            "vehicle_plate" => $reminder['vehicle_plate'],
                            "vehicle_brand" => $reminder['vehicle_brand'],
                            "vehicle_model" => $reminder['vehicle_model'],
                            "vehicle_year" => $reminder['vehicle_year'],
                            "vehicle_image" => $reminder['vehicle_image']
                        );
                    }
                }
            }
        }

        if (!empty($responses)) {
            http_response_code(201);
            echo json_encode($responses);
        } else {
            // Hiçbir yeni hatırlatıcı eklenmedi (duplicate skip edildi)
            http_response_code(200);
            echo json_encode(array(
                "message" => "Hatırlatıcılar zaten mevcut",
                "added_count" => 0,
                "skipped_count" => count($reminder_days) * count($reminder_times)
            ));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Sunucu hatası: " . $e->getMessage()));
    }
}

// PUT - Hatırlatıcı güncelle
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = file_get_contents("php://input");
    $data = json_decode($input);

    // URL'den hatırlatıcı ID'sini al
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', $path);
    $reminder_id = end($path_parts);

    if (empty($data->title) || empty($data->vehicle_id) || empty($data->date)) {
        http_response_code(400);
        echo json_encode(array("message" => "Başlık, araç ID ve tarih gereklidir"));
        exit();
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        if ($db === null) {
            http_response_code(500);
            echo json_encode(array("message" => "Veritabanı bağlantı hatası"));
            exit();
        }

        // Değişkenleri hazırla
        $vehicle_id = $data->vehicle_id;
        $title = $data->title;
        $description = $data->description ?? '';
        $date = $data->date;
        $type = $data->type ?? 'general';
        $reminder_days = $data->reminder_days ?? 1;
        $is_completed = $data->isCompleted ?? false;

        // Hatırlatıcı güncelle
        $query = "UPDATE reminders SET 
                    vehicle_id = :vehicle_id,
                    title = :title, 
                    description = :description, 
                    date = :date,
                    type = :type,
                    reminder_days = :reminder_days,
                    is_completed = :is_completed,
                    updated_at = NOW() 
                  WHERE id = :id";

        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $reminder_id);
        $stmt->bindParam(":vehicle_id", $vehicle_id);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":date", $date);
        $stmt->bindParam(":type", $type);
        $stmt->bindParam(":reminder_days", $reminder_days);
        $stmt->bindParam(":is_completed", $is_completed, PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                // Güncellenen hatırlatıcıyı getir
                $get_query = "SELECT r.*,
                                     v.plate as vehicle_plate,
                                     v.brand as vehicle_brand,
                                     v.model as vehicle_model,
                                     v.year as vehicle_year,
                                     v.image as vehicle_image
                              FROM reminders r
                              LEFT JOIN vehicles v ON r.vehicle_id = v.id
                              WHERE r.id = :id";
                $get_stmt = $db->prepare($get_query);
                $get_stmt->bindParam(":id", $reminder_id);
                $get_stmt->execute();
                $reminder = $get_stmt->fetch(PDO::FETCH_ASSOC);

                if ($reminder) {
                    $response = array(
                        "id" => $reminder['id'],
                        "vehicle_id" => $reminder['vehicle_id'],
                        "title" => $reminder['title'],
                        "description" => $reminder['description'],
                        "date" => $reminder['date'],
                        "type" => $reminder['type'] ?? 'general',
                        "reminder_days" => $reminder['reminder_days'],
                        "isCompleted" => (bool)$reminder['is_completed'],
                        "created_at" => $reminder['created_at'],
                        "updated_at" => $reminder['updated_at'],
                        "vehicle_plate" => $reminder['vehicle_plate'],
                        "vehicle_brand" => $reminder['vehicle_brand'],
                        "vehicle_model" => $reminder['vehicle_model'],
                        "vehicle_year" => $reminder['vehicle_year'],
                        "vehicle_image" => $reminder['vehicle_image']
                    );

                    http_response_code(200);
                    echo json_encode($response);
                } else {
                    http_response_code(404);
                    echo json_encode(array("message" => "Hatırlatıcı bulunamadı"));
                }
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Hatırlatıcı bulunamadı veya güncelleme gerekli değil"));
            }
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Hatırlatıcı güncellenemedi"));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Sunucu hatası: " . $e->getMessage()));
    }
}

// DELETE - Hatırlatıcı sil
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // URL'den hatırlatıcı ID'sini veya araç ID'sini al
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', $path);

    // /api/v1/reminders/vehicle/{vehicle_id} formatını kontrol et
    if (count($path_parts) >= 3 && $path_parts[count($path_parts) - 2] === 'vehicle') {
        $vehicle_id = end($path_parts);

        try {
            $database = new Database();
            $db = $database->getConnection();

            if ($db === null) {
                http_response_code(500);
                echo json_encode(array("message" => "Veritabanı bağlantı hatası"));
                exit();
            }

            // Tüm hatırlatıcıları sil
            $query = "DELETE FROM reminders WHERE vehicle_id = :vehicle_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":vehicle_id", $vehicle_id);

            if ($stmt->execute()) {
                $deleted_count = $stmt->rowCount();
                http_response_code(200);
                echo json_encode(array(
                    "message" => "Tüm hatırlatıcılar silindi",
                    "deleted_count" => $deleted_count
                ));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Hatırlatıcılar silinemedi"));
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Sunucu hatası: " . $e->getMessage()));
        }
    } else {
        // Tek hatırlatıcı silme
        $reminder_id = end($path_parts);

        try {
            $database = new Database();
            $db = $database->getConnection();

            if ($db === null) {
                http_response_code(500);
                echo json_encode(array("message" => "Veritabanı bağlantı hatası"));
                exit();
            }

            // Hatırlatıcıyı sil
            $query = "DELETE FROM reminders WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $reminder_id);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Hatırlatıcı silindi"));
                } else {
                    http_response_code(404);
                    echo json_encode(array("message" => "Hatırlatıcı bulunamadı"));
                }
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Hatırlatıcı silinemedi"));
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Sunucu hatası: " . $e->getMessage()));
        }
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Method not allowed"));
}
