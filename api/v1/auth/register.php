<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once __DIR__ . '/../../../config/database.php';

// Sadece POST isteklerine izin ver
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array("message" => "Method not allowed"));
    exit();
}

// JSON verisini al
$data = json_decode(file_get_contents("php://input"));

// Gerekli alanları kontrol et
if (empty($data->phone) || empty($data->password) || empty($data->full_name)) {
    http_response_code(400);
    echo json_encode(array("message" => "Telefon numarası, şifre ve ad soyad gereklidir"));
    exit();
}

try {
    // Veritabanı bağlantısı
    $database = new Database();
    $db = $database->getConnection();

    if ($db === null) {
        http_response_code(500);
        echo json_encode(array("message" => "Veritabanı bağlantı hatası"));
        exit();
    }

    // Telefon numarası zaten kayıtlı mı kontrol et
    $check_query = "SELECT id FROM users WHERE phone = :phone";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":phone", $data->phone);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(array("message" => "Bu telefon numarası zaten kayıtlı"));
        exit();
    }

    // Şifreyi hashle
    $hashed_password = password_hash($data->password, PASSWORD_DEFAULT);

    // Kullanıcıyı kaydet
    $query = "INSERT INTO users (phone, password, full_name, email, is_verified, created_at, updated_at) 
              VALUES (:phone, :password, :full_name, :email, 0, NOW(), NOW())";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":phone", $data->phone);
    $stmt->bindParam(":password", $hashed_password);
    $stmt->bindParam(":full_name", $data->full_name);
    $stmt->bindParam(":email", $data->email);

    if ($stmt->execute()) {
        $user_id = $db->lastInsertId();

        // SMS doğrulama kodu oluştur (demo için sabit)
        $verification_code = "123456";

        // Doğrulama kodunu kaydet
        $code_query = "INSERT INTO verification_codes (user_id, code, type, expires_at, created_at) 
                       VALUES (:user_id, :code, 'register', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())";
        $code_stmt = $db->prepare($code_query);
        $code_stmt->bindParam(":user_id", $user_id);
        $code_stmt->bindParam(":code", $verification_code);
        $code_stmt->execute();

        http_response_code(201);
        echo json_encode(array(
            "message" => "Kullanıcı başarıyla kaydedildi",
            "user_id" => $user_id,
            "verification_required" => true
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Kullanıcı kaydedilemedi"));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Sunucu hatası: " . $e->getMessage()));
}
