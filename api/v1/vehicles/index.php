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

// GET - Araçları listele
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

        // Authorization header'dan user_id al (basit token decode)
        $user_id = 1; // Demo için sabit user_id

        $headers = getallheaders();
        if ($headers && isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
            $decoded = json_decode(base64_decode($token), true);
            if ($decoded && isset($decoded['user_id'])) {
                $user_id = $decoded['user_id'];
            }
        }

        // Kullanıcının araçlarını getir
        $query = "SELECT * FROM vehicles WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        $vehicles = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $vehicles[] = array(
                "id" => $row['id'],
                "brand" => $row['brand'],
                "model" => $row['model'],
                "year" => $row['year'],
                "plate" => $row['plate'],
                "color" => $row['color'],
                "image" => $row['image'],
                "last_service_date" => $row['last_service_date'],
                "last_inspection_date" => $row['last_inspection_date'],
                "insurance_expiry_date" => $row['insurance_expiry_date'],
                "kasko_expiry_date" => $row['kasko_expiry_date'],
                "registration_expiry_date" => $row['registration_expiry_date'],
                "oil_change_date" => $row['oil_change_date'],
                "tire_change_date" => $row['tire_change_date'],
                "created_at" => $row['created_at'],
                "updated_at" => $row['updated_at']
            );
        }

        http_response_code(200);
        echo json_encode($vehicles);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Sunucu hatası: " . $e->getMessage()));
    }
}

// POST - Yeni araç ekle
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->brand) || empty($data->model) || empty($data->plate)) {
        http_response_code(400);
        echo json_encode(array("message" => "Marka, model ve plaka gereklidir"));
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

        // Değişkenleri hazırla (bindParam için referans gerekli)
        $brand = $data->brand;
        $model = $data->model;
        $year = $data->year ?? null;
        $plate = $data->plate;
        $color = $data->color ?? null;
        $image = $data->image ?? null;

        // Tarih alanları - ISO 8601 formatını kontrol et
        $lastServiceDate = null;
        if (isset($data->last_service_date) && !empty($data->last_service_date)) {
            $lastServiceDate = $data->last_service_date;
            // ISO 8601 formatını MySQL DATE formatına çevir
            if (strpos($lastServiceDate, 'T') !== false) {
                $lastServiceDate = date('Y-m-d', strtotime($lastServiceDate));
            }
        }

        $lastInspectionDate = null;
        if (isset($data->last_inspection_date) && !empty($data->last_inspection_date)) {
            $lastInspectionDate = $data->last_inspection_date;
            if (strpos($lastInspectionDate, 'T') !== false) {
                $lastInspectionDate = date('Y-m-d', strtotime($lastInspectionDate));
            }
        }

        $insuranceExpiryDate = null;
        if (isset($data->insurance_expiry_date) && !empty($data->insurance_expiry_date)) {
            $insuranceExpiryDate = $data->insurance_expiry_date;
            if (strpos($insuranceExpiryDate, 'T') !== false) {
                $insuranceExpiryDate = date('Y-m-d', strtotime($insuranceExpiryDate));
            }
        }

        $kaskoExpiryDate = null;
        if (isset($data->kasko_expiry_date) && !empty($data->kasko_expiry_date)) {
            $kaskoExpiryDate = $data->kasko_expiry_date;
            if (strpos($kaskoExpiryDate, 'T') !== false) {
                $kaskoExpiryDate = date('Y-m-d', strtotime($kaskoExpiryDate));
            }
        }

        $registrationExpiryDate = null;
        if (isset($data->registration_expiry_date) && !empty($data->registration_expiry_date)) {
            $registrationExpiryDate = $data->registration_expiry_date;
            if (strpos($registrationExpiryDate, 'T') !== false) {
                $registrationExpiryDate = date('Y-m-d', strtotime($registrationExpiryDate));
            }
        }

        $oilChangeDate = null;
        if (isset($data->oil_change_date) && !empty($data->oil_change_date)) {
            $oilChangeDate = $data->oil_change_date;
            if (strpos($oilChangeDate, 'T') !== false) {
                $oilChangeDate = date('Y-m-d', strtotime($oilChangeDate));
            }
        }

        $tireChangeDate = null;
        if (isset($data->tire_change_date) && !empty($data->tire_change_date)) {
            $tireChangeDate = $data->tire_change_date;
            if (strpos($tireChangeDate, 'T') !== false) {
                $tireChangeDate = date('Y-m-d', strtotime($tireChangeDate));
            }
        }

        // Debug: Gelen verileri logla
        error_log("=== Vehicle POST Debug ===");
        error_log("Brand: " . $brand);
        error_log("Model: " . $model);
        error_log("Plate: " . $plate);
        error_log("Last Service Date: " . ($lastServiceDate ?? 'NULL'));
        error_log("Last Inspection Date: " . ($lastInspectionDate ?? 'NULL'));
        error_log("Insurance Expiry Date: " . ($insuranceExpiryDate ?? 'NULL'));
        error_log("Kasko Expiry Date: " . ($kaskoExpiryDate ?? 'NULL'));
        error_log("Registration Expiry Date: " . ($registrationExpiryDate ?? 'NULL'));
        error_log("Oil Change Date: " . ($oilChangeDate ?? 'NULL'));
        error_log("Tire Change Date: " . ($tireChangeDate ?? 'NULL'));

        // Araç ekle
        $query = "INSERT INTO vehicles (user_id, brand, model, year, plate, color, image, 
                                       last_service_date, last_inspection_date, insurance_expiry_date, 
                                       kasko_expiry_date, registration_expiry_date, oil_change_date, tire_change_date,
                                       created_at, updated_at) 
                  VALUES (:user_id, :brand, :model, :year, :plate, :color, :image, 
                         :last_service_date, :last_inspection_date, :insurance_expiry_date,
                         :kasko_expiry_date, :registration_expiry_date, :oil_change_date, :tire_change_date,
                         NOW(), NOW())";

        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":brand", $brand);
        $stmt->bindParam(":model", $model);
        $stmt->bindParam(":year", $year);
        $stmt->bindParam(":plate", $plate);
        $stmt->bindParam(":color", $color);
        $stmt->bindParam(":image", $image);
        $stmt->bindParam(":last_service_date", $lastServiceDate);
        $stmt->bindParam(":last_inspection_date", $lastInspectionDate);
        $stmt->bindParam(":insurance_expiry_date", $insuranceExpiryDate);
        $stmt->bindParam(":kasko_expiry_date", $kaskoExpiryDate);
        $stmt->bindParam(":registration_expiry_date", $registrationExpiryDate);
        $stmt->bindParam(":oil_change_date", $oilChangeDate);
        $stmt->bindParam(":tire_change_date", $tireChangeDate);

        if ($stmt->execute()) {
            $vehicle_id = $db->lastInsertId();

            // Eklenen aracı getir
            $get_query = "SELECT * FROM vehicles WHERE id = :id";
            $get_stmt = $db->prepare($get_query);
            $get_stmt->bindParam(":id", $vehicle_id);
            $get_stmt->execute();
            $vehicle = $get_stmt->fetch(PDO::FETCH_ASSOC);

            http_response_code(201);
            echo json_encode($vehicle);
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Araç eklenemedi"));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Sunucu hatası: " . $e->getMessage()));
    }
}

// PUT - Araç güncelle
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = file_get_contents("php://input");
    $data = json_decode($input);

    // URL'den araç ID'sini al
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', $path);
    $vehicle_id = end($path_parts);

    if (empty($data->brand) || empty($data->model) || empty($data->plate)) {
        http_response_code(400);
        echo json_encode(array("message" => "Marka, model ve plaka gereklidir"));
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

        // Değişkenleri hazırla (bindParam için referans gerekli)
        $brand = $data->brand;
        $model = $data->model;
        $year = $data->year ?? null;
        $plate = $data->plate;
        $color = $data->color ?? null;
        $image = $data->image ?? null;

        // Tarih alanları - ISO 8601 formatını kontrol et
        $lastServiceDate = null;
        if (isset($data->last_service_date) && !empty($data->last_service_date)) {
            $lastServiceDate = $data->last_service_date;
            // ISO 8601 formatını MySQL DATE formatına çevir
            if (strpos($lastServiceDate, 'T') !== false) {
                $lastServiceDate = date('Y-m-d', strtotime($lastServiceDate));
            }
        }

        $lastInspectionDate = null;
        if (isset($data->last_inspection_date) && !empty($data->last_inspection_date)) {
            $lastInspectionDate = $data->last_inspection_date;
            if (strpos($lastInspectionDate, 'T') !== false) {
                $lastInspectionDate = date('Y-m-d', strtotime($lastInspectionDate));
            }
        }

        $insuranceExpiryDate = null;
        if (isset($data->insurance_expiry_date) && !empty($data->insurance_expiry_date)) {
            $insuranceExpiryDate = $data->insurance_expiry_date;
            if (strpos($insuranceExpiryDate, 'T') !== false) {
                $insuranceExpiryDate = date('Y-m-d', strtotime($insuranceExpiryDate));
            }
        }

        $kaskoExpiryDate = null;
        if (isset($data->kasko_expiry_date) && !empty($data->kasko_expiry_date)) {
            $kaskoExpiryDate = $data->kasko_expiry_date;
            if (strpos($kaskoExpiryDate, 'T') !== false) {
                $kaskoExpiryDate = date('Y-m-d', strtotime($kaskoExpiryDate));
            }
        }

        $registrationExpiryDate = null;
        if (isset($data->registration_expiry_date) && !empty($data->registration_expiry_date)) {
            $registrationExpiryDate = $data->registration_expiry_date;
            if (strpos($registrationExpiryDate, 'T') !== false) {
                $registrationExpiryDate = date('Y-m-d', strtotime($registrationExpiryDate));
            }
        }

        $oilChangeDate = null;
        if (isset($data->oil_change_date) && !empty($data->oil_change_date)) {
            $oilChangeDate = $data->oil_change_date;
            if (strpos($oilChangeDate, 'T') !== false) {
                $oilChangeDate = date('Y-m-d', strtotime($oilChangeDate));
            }
        }

        $tireChangeDate = null;
        if (isset($data->tire_change_date) && !empty($data->tire_change_date)) {
            $tireChangeDate = $data->tire_change_date;
            if (strpos($tireChangeDate, 'T') !== false) {
                $tireChangeDate = date('Y-m-d', strtotime($tireChangeDate));
            }
        }

        // Araç güncelle
        $query = "UPDATE vehicles SET 
                    brand = :brand, 
                    model = :model, 
                    year = :year, 
                    plate = :plate, 
                    color = :color, 
                    image = :image,
                    last_service_date = :last_service_date,
                    last_inspection_date = :last_inspection_date,
                    insurance_expiry_date = :insurance_expiry_date,
                    kasko_expiry_date = :kasko_expiry_date,
                    registration_expiry_date = :registration_expiry_date,
                    oil_change_date = :oil_change_date,
                    tire_change_date = :tire_change_date,
                    updated_at = NOW() 
                  WHERE id = :id AND user_id = :user_id";

        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $vehicle_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":brand", $brand);
        $stmt->bindParam(":model", $model);
        $stmt->bindParam(":year", $year);
        $stmt->bindParam(":plate", $plate);
        $stmt->bindParam(":color", $color);
        $stmt->bindParam(":image", $image);
        $stmt->bindParam(":last_service_date", $lastServiceDate);
        $stmt->bindParam(":last_inspection_date", $lastInspectionDate);
        $stmt->bindParam(":insurance_expiry_date", $insuranceExpiryDate);
        $stmt->bindParam(":kasko_expiry_date", $kaskoExpiryDate);
        $stmt->bindParam(":registration_expiry_date", $registrationExpiryDate);
        $stmt->bindParam(":oil_change_date", $oilChangeDate);
        $stmt->bindParam(":tire_change_date", $tireChangeDate);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                // Güncellenen aracı getir
                $get_query = "SELECT * FROM vehicles WHERE id = :id AND user_id = :user_id";
                $get_stmt = $db->prepare($get_query);
                $get_stmt->bindParam(":id", $vehicle_id);
                $get_stmt->bindParam(":user_id", $user_id);
                $get_stmt->execute();
                $vehicle = $get_stmt->fetch(PDO::FETCH_ASSOC);

                if ($vehicle) {
                    http_response_code(200);
                    echo json_encode($vehicle);
                } else {
                    http_response_code(404);
                    echo json_encode(array("message" => "Araç bulunamadı"));
                }
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Araç bulunamadı veya güncelleme gerekli değil"));
            }
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Araç güncellenemedi"));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Sunucu hatası: " . $e->getMessage()));
    }
}

// DELETE - Araç sil
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        // URL'den araç ID'sini al
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path_parts = explode('/', $path);
        $vehicle_id = end($path_parts);

        if (empty($vehicle_id) || !is_numeric($vehicle_id)) {
            http_response_code(400);
            echo json_encode(array("message" => "Geçerli araç ID'si gereklidir"));
            exit();
        }

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

        // Debug: DELETE işlem bilgilerini logla
        error_log("=== Vehicle DELETE Debug ===");
        error_log("Vehicle ID: " . $vehicle_id);
        error_log("User ID: " . $user_id);

        // Önce araç var mı kontrol et
        $check_query = "SELECT id FROM vehicles WHERE id = :id AND user_id = :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":id", $vehicle_id);
        $check_stmt->bindParam(":user_id", $user_id);
        $check_stmt->execute();

        $vehicle_exists = $check_stmt->rowCount();
        error_log("Vehicle exists check: " . $vehicle_exists);

        if ($vehicle_exists == 0) {
            http_response_code(404);
            echo json_encode(array("message" => "Araç bulunamadı"));
            exit();
        }

        // Önce araça ait hatırlatıcıları sil
        $delete_reminders_query = "DELETE FROM reminders WHERE vehicle_id = :vehicle_id";
        $delete_reminders_stmt = $db->prepare($delete_reminders_query);
        $delete_reminders_stmt->bindParam(":vehicle_id", $vehicle_id);
        $delete_reminders_stmt->execute();
        $deleted_reminders = $delete_reminders_stmt->rowCount();
        error_log("Deleted reminders: " . $deleted_reminders);

        // Sonra aracı sil
        $delete_query = "DELETE FROM vehicles WHERE id = :id AND user_id = :user_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(":id", $vehicle_id);
        $delete_stmt->bindParam(":user_id", $user_id);
        error_log("Executing DELETE query: " . $delete_query);

        if ($delete_stmt->execute()) {
            $deleted_rows = $delete_stmt->rowCount();
            error_log("DELETE executed. Affected rows: " . $deleted_rows);

            if ($deleted_rows > 0) {
                error_log("Vehicle successfully deleted");
                http_response_code(200);
                echo json_encode(array(
                    "message" => "Araç başarıyla silindi",
                    "deleted_vehicle_id" => $vehicle_id,
                    "deleted_vehicle_rows" => $deleted_rows,
                    "deleted_reminders_count" => $deleted_reminders
                ));
            } else {
                error_log("No rows affected - vehicle not found or already deleted");
                http_response_code(404);
                echo json_encode(array("message" => "Araç bulunamadı veya zaten silinmiş"));
            }
        } else {
            error_log("DELETE execution failed");
            http_response_code(500);
            echo json_encode(array("message" => "Araç silinemedi"));
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Sunucu hatası: " . $e->getMessage()));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Method not allowed"));
}
